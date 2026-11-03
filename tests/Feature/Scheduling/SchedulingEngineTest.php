<?php

namespace Tests\Feature\Scheduling;

use App\Actions\Scheduling\AppointmentLifecycle;
use App\Actions\Scheduling\AvailabilityService;
use App\Actions\Scheduling\CancelAppointment;
use App\Actions\Scheduling\ReservationData;
use App\Actions\Scheduling\RescheduleAppointment;
use App\Actions\Scheduling\ReserveAppointment;
use App\Enums\AppointmentSource;
use App\Enums\AppointmentStatus;
use App\Enums\BookingConfirmationMode;
use App\Enums\FollowUpAuthorization;
use App\Enums\FollowUpStatus;
use App\Enums\UserRole;
use App\Exceptions\Scheduling\FollowUpConfirmationRequiredException;
use App\Exceptions\Scheduling\SlotUnavailableException;
use App\Models\Appointment;
use App\Models\AppointmentResourceAllocation;
use App\Models\Chair;
use App\Models\ClinicSetting;
use App\Models\FollowUp;
use App\Models\Holiday;
use App\Models\Patient;
use App\Models\TreatmentService;
use App\Models\User;
use App\Models\WorkShift;
use App\Support\JalaliCalendar;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Tests\Support\BuildsClinicSchedule;
use Tests\TestCase;

class SchedulingEngineTest extends TestCase
{
    use BuildsClinicSchedule;
    use RefreshDatabase;

    public function test_migrations_do_not_seed_chairs_shifts_or_patients(): void
    {
        $this->assertSame('Asia/Tehran', config('app.timezone'));
        $this->assertSame(0, Chair::query()->count());
        $this->assertSame(0, Patient::query()->count());
        $this->assertSame(0, WorkShift::query()->count());
        $this->assertSame(0, Appointment::query()->count());
    }

    public function test_duration_comes_from_the_service_and_buffer_holds_the_resource(): void
    {
        $clinic = $this->makeClinic();
        $appointment = $this->reserveAt($clinic, '08:00');

        $this->assertSame('2026-11-07 08:40:00', $appointment->ends_at->timezone(config('app.timezone'))->format('Y-m-d H:i:s'));
        $this->assertNotSame('2026-11-07 09:00:00', $appointment->ends_at->timezone(config('app.timezone'))->format('Y-m-d H:i:s'));
        $this->assertSame(
            '2026-11-07 08:45:00',
            $appointment->allocations->first()->ends_at->timezone(config('app.timezone'))->format('Y-m-d H:i:s'),
        );

        $this->assertSame(2, Chair::query()->count());
        $this->assertNotSame(6, Chair::query()->count());
    }

    public function test_suggested_slots_step_by_service_duration_and_skip_breaks(): void
    {
        $clinic = $this->makeClinic();
        $slots = app(AvailabilityService::class)->slots($clinic['service'], '2026-11-07', $clinic['patient']->id);
        $times = array_map(fn ($slot) => $slot->startsAt->format('H:i'), $slots);

        $this->assertSame(['08:00', '08:40', '10:40'], $times);
        $this->assertNotContains('09:00', $times);
        $this->assertNotContains('10:00', $times);
    }

    public function test_two_competing_reservations_only_one_succeeds_and_the_mutex_is_written(): void
    {
        $clinic = $this->makeClinic();
        DB::enableQueryLog();

        $first = $this->reserveAt($clinic, '08:00');
        $queries = collect(DB::getQueryLog())->pluck('query')->implode("\n");
        $this->assertStringContainsString('reservation_locks', $queries);

        $failures = 0;

        try {
            $this->reserveAt($clinic, '08:00', [
                'patient' => $clinic['otherPatient'],
            ]);
        } catch (SlotUnavailableException $exception) {
            $failures++;
            $this->assertNotSame([], $exception->reasons);
        }

        $this->assertSame(1, $failures);
        $this->assertSame(1, Appointment::query()->count());
        $this->assertTrue($first->fresh()->status->occupiesResources());
        $this->assertSame(3, AppointmentResourceAllocation::query()->count());
    }

    public function test_overlap_is_per_resource_and_back_to_back_after_buffer_is_allowed(): void
    {
        $clinic = $this->makeClinic();
        $this->reserveAt($clinic, '08:00');

        $this->reserveAt($clinic, '08:45', [
            'patient' => $clinic['otherPatient'],
        ]);
        $this->assertSame(2, Appointment::query()->count());

        try {
            $this->reserveAt($clinic, '08:00', [
                'patient' => $clinic['otherPatient'],
                'chair' => $clinic['orthoChair'],
                'specialist' => $clinic['specialist'],
                'staff' => [$clinic['assistantTwo']->id],
            ]);
            $this->fail('The same specialist was reserved twice.');
        } catch (SlotUnavailableException $exception) {
            $this->assertTrue(collect($exception->reasons)->contains(fn (string $reason) => str_contains($reason, 'متخصص')));
        }

        $this->reserveAt($clinic, '08:00', [
            'patient' => $clinic['otherPatient'],
            'chair' => $clinic['orthoChair'],
            'specialist' => $clinic['specialistTwo'],
            'staff' => [$clinic['assistantTwo']->id],
        ]);

        $this->assertSame(3, Appointment::query()->count());
    }

    public function test_clinical_staff_overlap_and_exact_staff_count_are_enforced(): void
    {
        $clinic = $this->makeClinic();
        $clinic['service']->update(['required_clinical_staff_count' => 2]);

        try {
            $this->reserveAt($clinic, '08:00', ['staff' => [$clinic['assistant']->id]]);
            $this->fail('A short staff list was accepted.');
        } catch (SlotUnavailableException $exception) {
            $this->assertTrue(collect($exception->reasons)->contains(fn (string $reason) => str_contains($reason, '2')));
        }

        $this->reserveAt($clinic, '08:00', [
            'staff' => [$clinic['assistant']->id, $clinic['assistantTwo']->id],
        ]);

        try {
            $this->reserveAt($clinic, '08:00', [
                'patient' => $clinic['otherPatient'],
                'specialist' => $clinic['specialistTwo'],
                'chair' => $clinic['orthoChair'],
                'staff' => [$clinic['assistant']->id, $clinic['assistantTwo']->id],
            ]);
            $this->fail('Busy clinical staff were reserved again.');
        } catch (SlotUnavailableException) {
            $this->assertSame(1, Appointment::query()->count());
        }
    }

    public function test_inactive_resources_and_closed_days_cannot_be_newly_allocated(): void
    {
        $clinic = $this->makeClinic();
        $calendar = app(JalaliCalendar::class);
        $jalali = $calendar->formatDate('2026-11-07');

        Holiday::query()->create([
            'holiday_date' => '2026-11-07',
            'title' => 'تعطیل آزمایش',
            'is_closed' => true,
        ]);

        $reasons = app(AvailabilityService::class)->reasons($this->intent($clinic, '08:00'));
        $this->assertTrue(collect($reasons)->contains(fn (string $reason) => str_contains($reason, 'تعطیل')));

        Holiday::query()->delete();
        $clinic['chair']->update(['is_active' => false]);

        try {
            $this->reserveAt($clinic, '08:00');
            $this->fail('An inactive chair was allocated.');
        } catch (SlotUnavailableException $exception) {
            $this->assertTrue(collect($exception->reasons)->contains(fn (string $reason) => str_contains($reason, 'یونیت')));
        }

        $clinic['chair']->update(['is_active' => true]);
        $clinic['specialist']->update(['is_active' => false]);

        try {
            $this->reserveAt($clinic, '08:00');
            $this->fail('An inactive specialist was allocated.');
        } catch (SlotUnavailableException $exception) {
            $this->assertTrue(collect($exception->reasons)->contains(fn (string $reason) => str_contains($reason, 'متخصص')));
        }

        $clinic['service']->update(['is_active' => false]);
        $this->assertSame(
            ['خدمت انتخاب‌شده فعال نیست.'],
            app(AvailabilityService::class)->reasons($this->intent($clinic, '08:45')),
        );
        $this->assertSame([], app(AvailabilityService::class)->slots($clinic['service'], '2026-11-07'));
        $this->assertNotNull($jalali);
    }

    public function test_chair_capability_is_matched_and_not_assumed_equal(): void
    {
        $clinic = $this->makeClinic();
        $clinic['service']->update(['required_chair_capabilities' => ['ortho']]);

        try {
            $this->reserveAt($clinic, '08:00', ['chair' => $clinic['chair']]);
            $this->fail('A chair without the required capability was accepted.');
        } catch (SlotUnavailableException $exception) {
            $this->assertTrue(collect($exception->reasons)->contains(fn (string $reason) => str_contains($reason, 'قابلیت')));
        }

        $appointment = $this->reserveAt($clinic, '08:00', ['chair' => $clinic['orthoChair']]);
        $this->assertTrue($appointment->allocations->contains(fn ($allocation) => $allocation->resource_id === $clinic['orthoChair']->id));
    }

    public function test_break_and_outside_shift_explain_why_a_time_is_unavailable(): void
    {
        $clinic = $this->makeClinic();
        $break = app(AvailabilityService::class)->reasons($this->intent($clinic, '10:00'));
        $outside = app(AvailabilityService::class)->reasons($this->intent($clinic, '12:10'));

        $this->assertTrue(collect($break)->contains(fn (string $reason) => str_contains($reason, 'استراحت')));
        $this->assertTrue(collect($outside)->contains(fn (string $reason) => str_contains($reason, 'خارج از شیفت')));
    }

    public function test_cancel_and_non_occupying_statuses_release_resources_without_deleting_history(): void
    {
        $clinic = $this->makeClinic();
        $appointment = $this->reserveAt($clinic, '08:00');
        app(CancelAppointment::class)->cancel($appointment);

        $this->assertSame(AppointmentStatus::Cancelled, $appointment->fresh()->status);
        $this->assertSame(3, $appointment->allocations()->count());

        $replacement = $this->reserveAt($clinic, '08:00', ['patient' => $clinic['otherPatient']]);
        $this->assertSame(AppointmentStatus::Confirmed, $replacement->status);

        $draft = Appointment::query()->create([
            'patient_id' => $clinic['patient']->id,
            'treatment_service_id' => $clinic['service']->id,
            'starts_at' => '2026-11-07 10:40:00',
            'ends_at' => '2026-11-07 11:20:00',
            'status' => AppointmentStatus::Draft,
            'source' => AppointmentSource::Receptionist,
        ]);
        $draft->allocations()->create([
            'resource_type' => 'chair',
            'resource_id' => $clinic['chair']->id,
            'starts_at' => '2026-11-07 10:40:00',
            'ends_at' => '2026-11-07 11:25:00',
            'is_required' => true,
        ]);

        $this->reserveAt($clinic, '10:40', [
            'patient' => $clinic['patient'],
            'specialist' => $clinic['specialistTwo'],
            'staff' => [$clinic['assistantTwo']->id],
        ]);
        $this->assertSame(2, Appointment::query()->whereIn('status', AppointmentStatus::occupyingValues())->count());
    }

    public function test_reschedule_links_a_new_appointment_and_a_failed_one_keeps_the_old_resources(): void
    {
        $clinic = $this->makeClinic();
        $actor = User::factory()->create(['role' => UserRole::Receptionist, 'is_active' => true]);
        $original = $this->reserveAt($clinic, '08:00', ['actor' => $actor]);
        $blocker = $this->reserveAt($clinic, '08:45', [
            'patient' => $clinic['otherPatient'],
            'actor' => $actor,
        ]);

        try {
            app(RescheduleAppointment::class)->reschedule($original, $this->intent($clinic, '08:45', [
                'actor' => $actor,
                'patient' => $clinic['patient'],
            ]));
            $this->fail('Reschedule occupied a taken slot.');
        } catch (SlotUnavailableException) {
            $this->assertSame(AppointmentStatus::Confirmed, $original->fresh()->status);
            $this->assertSame(AppointmentStatus::Confirmed, $blocker->fresh()->status);
        }

        $moved = app(RescheduleAppointment::class)->reschedule($original->fresh(), $this->intent($clinic, '10:40', [
            'actor' => $actor,
        ]));

        $this->assertSame($original->id, $moved->rescheduled_from_id);
        $this->assertSame(AppointmentStatus::Rescheduled, $original->fresh()->status);
        $this->assertFalse($original->fresh()->status->occupiesResources());
        $this->assertTrue($moved->status->occupiesResources());

        $this->reserveAt($clinic, '08:00', [
            'patient' => $clinic['otherPatient'],
            'actor' => $actor,
            'specialist' => $clinic['specialistTwo'],
            'staff' => [$clinic['assistantTwo']->id],
            'chair' => $clinic['orthoChair'],
        ]);
    }

    public function test_a_proposed_follow_up_cannot_become_confirmed_without_an_explicit_confirmation(): void
    {
        $clinic = $this->makeClinic();
        $clinic['service']->update([
            'booking_confirmation_mode' => BookingConfirmationMode::Immediate,
        ]);
        $followUp = $this->followUp($clinic);
        $actor = User::factory()->create(['role' => UserRole::Receptionist, 'is_active' => true]);

        try {
            $this->reserveAt($clinic, '08:00', [
                'source' => AppointmentSource::SystemSuggested,
                'followUp' => $followUp,
                'actor' => $actor,
            ]);
            $this->fail('A proposed follow-up was confirmed silently.');
        } catch (FollowUpConfirmationRequiredException) {
            $this->assertSame(0, Appointment::query()->count());
            $this->assertSame(FollowUpStatus::Proposed, $followUp->fresh()->status);
        }

        $confirmed = $this->reserveAt($clinic, '08:00', [
            'source' => AppointmentSource::SystemSuggested,
            'followUp' => $followUp->fresh(),
            'authorization' => FollowUpAuthorization::Receptionist,
            'actor' => $actor,
        ]);

        $this->assertSame(AppointmentStatus::Confirmed, $confirmed->status);
        $this->assertSame($followUp->id, $confirmed->follow_up_id);
        $this->assertSame(FollowUpStatus::Scheduled, $followUp->fresh()->status);
        $this->assertSame($actor->id, $followUp->fresh()->authorized_by_user_id);
    }

    public function test_patient_confirmation_respects_the_clinic_confirmation_mode(): void
    {
        $clinic = $this->makeClinic();
        $followUp = $this->followUp($clinic);

        $pending = $this->reserveAt($clinic, '08:00', [
            'source' => AppointmentSource::Online,
            'followUp' => $followUp,
            'authorization' => FollowUpAuthorization::Patient,
        ]);

        $this->assertNull(ClinicSetting::getValue(ClinicSetting::BOOKING_CONFIRMATION_MODE));
        $this->assertSame(AppointmentStatus::PendingApproval, $pending->status);
        $this->assertNotSame(AppointmentStatus::Confirmed, $pending->status);
        $this->assertNotNull($followUp->fresh()->patient_confirmed_at);

        $second = $this->followUp($clinic, 'P-100003', 'رضا', 'آزمایش');
        $clinic['service']->update(['booking_confirmation_mode' => BookingConfirmationMode::Immediate]);

        $confirmed = $this->reserveAt($clinic, '08:45', [
            'patient' => $second->patient,
            'source' => AppointmentSource::Online,
            'followUp' => $second,
            'authorization' => FollowUpAuthorization::Patient,
            'specialist' => $clinic['specialistTwo'],
            'staff' => [$clinic['assistantTwo']->id],
        ]);

        $this->assertSame(AppointmentStatus::Confirmed, $confirmed->status);
    }

    public function test_online_and_reception_share_one_availability_result(): void
    {
        $clinic = $this->makeClinic();
        $this->reserveAt($clinic, '08:00');
        $reasons = app(AvailabilityService::class)->reasons($this->intent($clinic, '08:00', [
            'patient' => $clinic['otherPatient'],
            'source' => AppointmentSource::Online,
        ]));

        try {
            $this->reserveAt($clinic, '08:00', [
                'patient' => $clinic['otherPatient'],
                'source' => AppointmentSource::Online,
            ]);
            $this->fail('Online booking bypassed the shared availability rules.');
        } catch (SlotUnavailableException $exception) {
            $this->assertSame($reasons, $exception->reasons);
        }

        $pending = $this->reserveAt($clinic, '10:40', [
            'patient' => $clinic['otherPatient'],
            'source' => AppointmentSource::Online,
        ]);
        $this->assertSame(AppointmentStatus::PendingApproval, $pending->status);

        try {
            $this->reserveAt($clinic, '10:40', [
                'patient' => $clinic['patient'],
                'source' => AppointmentSource::Receptionist,
                'actor' => User::factory()->create(['role' => UserRole::Receptionist, 'is_active' => true]),
            ]);
            $this->fail('A pending online appointment did not occupy its resources.');
        } catch (SlotUnavailableException) {
            $this->assertTrue($pending->fresh()->status->occupiesResources());
        }
    }

    public function test_only_authorized_roles_can_move_pending_and_completed_appointments(): void
    {
        $clinic = $this->makeClinic();
        $clinical = $clinic['assistant'];
        $appointment = $this->reserveAt($clinic, '08:00', [
            'source' => AppointmentSource::Online,
        ]);

        try {
            app(AppointmentLifecycle::class)->transition($appointment, AppointmentStatus::Confirmed, $clinical);
            $this->fail('Clinical staff confirmed an appointment.');
        } catch (AuthorizationException) {
            $this->assertSame(AppointmentStatus::PendingApproval, $appointment->fresh()->status);
        }
    }

    public function test_receptionist_can_confirm_pending_and_cannot_complete_before_in_progress(): void
    {
        $clinic = $this->makeClinic();
        $receptionist = User::factory()->create(['role' => UserRole::Receptionist, 'is_active' => true]);
        $specialist = User::factory()->create(['role' => UserRole::Specialist, 'is_active' => true]);
        $appointment = $this->reserveAt($clinic, '08:00', ['source' => AppointmentSource::Online]);

        try {
            app(AppointmentLifecycle::class)->transition($appointment, AppointmentStatus::Completed, $receptionist);
            $this->fail('Completed was reached from pending approval.');
        } catch (\App\Exceptions\Scheduling\InvalidAppointmentTransitionException) {
            $this->assertSame(AppointmentStatus::PendingApproval, $appointment->fresh()->status);
        }

        $confirmed = app(AppointmentLifecycle::class)->transition($appointment->fresh(), AppointmentStatus::Confirmed, $receptionist);
        $this->assertSame($receptionist->id, $confirmed->approved_by_user_id);

        $this->expectException(AuthorizationException::class);
        app(AppointmentLifecycle::class)->transition($confirmed->fresh(), AppointmentStatus::CheckedIn, $specialist);
    }

    public function test_reception_http_flow_books_explains_cancels_and_reschedules_in_persian(): void
    {
        $clinic = $this->makeClinic();
        $receptionist = $this->signIn(UserRole::Receptionist);
        $jalali = app(JalaliCalendar::class)->formatDate('2026-11-07');

        $this->get(route('appointments.index'))
            ->assertOk()
            ->assertSee('نوبتی برای نمایش نیست');

        $this->get(route('appointments.create', [
            'patient_id' => $clinic['patient']->id,
            'service_id' => $clinic['service']->id,
            'date' => $jalali,
            'time' => '10:00',
        ]))->assertOk()->assertSee('این ساعت قابل رزرو نیست')->assertSee('استراحت');

        $this->post(route('appointments.store'), [
            'patient_id' => $clinic['patient']->id,
            'treatment_service_id' => $clinic['service']->id,
            'appointment_date' => $jalali,
            'appointment_time' => '08:00',
            'specialist_id' => $clinic['specialist']->id,
            'clinical_staff_ids' => [$clinic['assistant']->id],
            'chair_id' => $clinic['chair']->id,
            'default_duration_minutes' => 60,
            'patient_notes' => 'یادداشت پذیرش',
        ])->assertRedirect();

        $appointment = Appointment::query()->firstOrFail();
        $this->assertSame('2026-11-07 08:40:00', $appointment->ends_at->timezone(config('app.timezone'))->format('Y-m-d H:i:s'));
        $this->assertSame($receptionist->id, $appointment->booked_by_user_id);
        $this->get(route('appointments.show', $appointment))
            ->assertOk()
            ->assertSee('نوبت ثبت شد')
            ->assertSee('یادداشت پذیرش')
            ->assertDontSee('یادداشت بالینی');

        $this->post(route('appointments.store'), [
            'patient_id' => $clinic['otherPatient']->id,
            'treatment_service_id' => $clinic['service']->id,
            'appointment_date' => $jalali,
            'appointment_time' => '08:00',
            'specialist_id' => $clinic['specialist']->id,
            'clinical_staff_ids' => [$clinic['assistant']->id],
            'chair_id' => $clinic['chair']->id,
        ])->assertSessionHasErrors('appointment_time');

        $this->post(route('appointments.cancel', $appointment))->assertRedirect();
        $this->assertSame(AppointmentStatus::Cancelled, $appointment->fresh()->status);
        $this->get(route('appointments.show', $appointment))->assertSee('منابعش برای رزرو بعدی آزاد شد');

        $this->post(route('appointments.store'), [
            'patient_id' => $clinic['otherPatient']->id,
            'treatment_service_id' => $clinic['service']->id,
            'appointment_date' => $jalali,
            'appointment_time' => '08:00',
            'specialist_id' => $clinic['specialist']->id,
            'clinical_staff_ids' => [$clinic['assistant']->id],
            'chair_id' => $clinic['chair']->id,
        ])->assertRedirect();

        $booked = Appointment::query()->where('status', AppointmentStatus::Confirmed)->firstOrFail();
        $this->post(route('appointments.reschedule.store', $booked), [
            'patient_id' => $clinic['patient']->id,
            'treatment_service_id' => $clinic['service']->id,
            'appointment_date' => $jalali,
            'appointment_time' => '10:40',
            'specialist_id' => $clinic['specialist']->id,
            'clinical_staff_ids' => [$clinic['assistant']->id],
            'chair_id' => $clinic['chair']->id,
        ])->assertRedirect();

        $moved = Appointment::query()->where('status', AppointmentStatus::Confirmed)->firstOrFail();
        $this->assertSame($booked->id, $moved->rescheduled_from_id);
        $this->assertSame($booked->patient_id, $moved->patient_id);
        $this->get(route('appointments.show', $moved))->assertSee('دیگر منبعی را اشغال نمی‌کند');
    }

    public function test_authorization_boundaries_for_schedule_and_appointments(): void
    {
        $clinic = $this->makeClinic();
        $appointment = $this->reserveAt($clinic, '08:00');

        $this->signIn(UserRole::Patient);
        $this->get(route('appointments.index'))->assertForbidden();
        $this->get(route('appointments.show', $appointment))->assertForbidden();

        $this->signIn(UserRole::ClinicalStaff);
        $this->get(route('appointments.index'))->assertForbidden();
        $this->post(route('appointments.store'), [])->assertForbidden();
        $this->get(route('patients.clinical-notes', $clinic['patient']))->assertForbidden();

        $this->actingAs($clinic['specialist']);
        $this->get(route('appointments.show', $appointment))->assertOk();
        $this->post(route('appointments.cancel', $appointment))->assertForbidden();
        $this->get(route('shifts.create'))->assertForbidden();

        $this->actingAs($clinic['specialistTwo']);
        $this->get(route('appointments.show', $appointment))->assertForbidden();

        $this->signIn(UserRole::Receptionist);
        $this->get(route('shifts.index'))->assertForbidden();
        $this->post(route('holidays.store'), ['title' => 'غیرمجاز', 'holiday_date' => '1405/01/01'])->assertForbidden();
        $this->get(route('schedules.index'))->assertForbidden();
        $this->get(route('patients.clinical-notes', $clinic['patient']))->assertForbidden();

        $admin = $this->signIn(UserRole::Admin);
        $jalali = app(JalaliCalendar::class)->formatDate('2026-11-08');
        $this->get(route('shifts.index'))->assertOk()->assertSee('نوبت صبح')->assertDontSee('شیفتی ثبت نشده است');
        $this->get(route('holidays.index'))->assertOk()->assertSee('تعطیلی ثبت نشده است');
        $this->get(route('schedules.index'))->assertOk()->assertSee('دکتر آزمایش');

        $this->post(route('holidays.store'), [
            'holiday_date' => $jalali,
            'title' => 'تعطیل پذیرش',
            'is_closed' => '1',
        ])->assertRedirect();
        $this->assertSame(1, Holiday::query()->count());
        $this->assertNotSame($admin->role, UserRole::Receptionist);
    }

    /**
     * @param  array<string, mixed>  $clinic
     * @param  array<string, mixed>  $overrides
     */
    private function reserveAt(array $clinic, string $time, array $overrides = []): Appointment
    {
        return app(ReserveAppointment::class)->reserve($this->intent($clinic, $time, $overrides));
    }

    /**
     * @param  array<string, mixed>  $clinic
     * @param  array<string, mixed>  $overrides
     */
    private function intent(array $clinic, string $time, array $overrides = []): ReservationData
    {
        $patient = $overrides['patient'] ?? $clinic['patient'];
        $source = $overrides['source'] ?? AppointmentSource::Receptionist;
        $actor = $overrides['actor'] ?? null;
        $actorId = $actor?->id;

        if ($actorId === null && $source === AppointmentSource::Receptionist) {
            $actorId = User::factory()->create([
                'role' => UserRole::Receptionist,
                'is_active' => true,
            ])->id;
        }

        return new ReservationData(
            patientId: $patient->id,
            treatmentServiceId: $clinic['service']->id,
            startsAt: Carbon::parse('2026-11-07 '.$time.':00', 'Asia/Tehran'),
            specialistId: ($overrides['specialist'] ?? $clinic['specialist'])->id,
            clinicalStaffIds: $overrides['staff'] ?? [$clinic['assistant']->id],
            chairId: ($overrides['chair'] ?? $clinic['chair'])->id,
            source: $source,
            actorUserId: $actorId,
            followUpId: isset($overrides['followUp']) ? $overrides['followUp']->id : null,
            followUpAuthorization: $overrides['authorization'] ?? null,
        );
    }

    /**
     * @param  array<string, mixed>  $clinic
     */
    private function followUp(array $clinic, string $code = 'P-100001', string $first = 'سارا', string $last = 'آزمایش'): FollowUp
    {
        $patient = $code === $clinic['patient']->code
            ? $clinic['patient']
            : Patient::query()->firstOrCreate(
                ['code' => $code],
                ['first_name' => $first, 'last_name' => $last, 'status' => 'active'],
            );

        $followUp = FollowUp::query()->create([
            'patient_id' => $patient->id,
            'treatment_service_id' => $clinic['service']->id,
            'proposed_window_start' => '2026-11-01',
            'proposed_window_end' => '2026-11-30',
            'status' => FollowUpStatus::Proposed,
        ]);
        $followUp->setRelation('patient', $patient);

        return $followUp;
    }
}
