<?php

namespace Tests\Feature\Reception;

use App\Enums\AppointmentSource;
use App\Enums\AppointmentStatus;
use App\Enums\FollowUpStatus;
use App\Enums\UserRole;
use App\Models\Appointment;
use App\Models\FollowUp;
use App\Models\Patient;
use App\Support\ClinicIdentity;
use App\Support\JalaliCalendar;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Tests\Support\BuildsClinicSchedule;
use Tests\TestCase;

class ReceptionCommandCenterTest extends TestCase
{
    use BuildsClinicSchedule;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow(Carbon::parse('2026-11-07 07:30:00', 'Asia/Tehran'));
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_receptionist_command_center_shows_real_pending_online_and_follow_ups(): void
    {
        $clinic = $this->makeClinic();
        $this->signIn(UserRole::Receptionist);
        $date = app(JalaliCalendar::class)->formatDate('2026-11-07');

        $onlinePatient = Patient::query()->create([
            'code' => 'P-ON1',
            'first_name' => 'آنلاین',
            'last_name' => 'درخواست',
            'mobile' => '09121112233',
            'mobile_normalized' => '09121112233',
            'status' => 'active',
        ]);

        Appointment::query()->create([
            'public_reference' => (string) Str::uuid(),
            'patient_id' => $onlinePatient->id,
            'treatment_service_id' => $clinic['service']->id,
            'starts_at' => '2026-11-07 09:00:00',
            'ends_at' => '2026-11-07 09:40:00',
            'status' => AppointmentStatus::PendingApproval->value,
            'source' => AppointmentSource::Online->value,
        ]);

        FollowUp::query()->create([
            'patient_id' => $clinic['patient']->id,
            'treatment_service_id' => $clinic['service']->id,
            'proposed_window_start' => '2026-11-01',
            'proposed_window_end' => '2026-11-10',
            'status' => FollowUpStatus::Proposed->value,
            'created_by' => $clinic['specialist']->id,
        ]);

        $this->reserveAt($clinic, '08:00');

        $this->get(route('reception.day', ['date' => $date]))
            ->assertOk()
            ->assertSee('مرکز فرمان پذیرش', false)
            ->assertSee(ClinicIdentity::DEFAULT_DOCTOR_DISPLAY_NAME, false)
            ->assertSee('آنلاین درخواست', false)
            ->assertSee('درخواست‌های آنلاین', false)
            ->assertSee('پیگیری‌های نیازمند اقدام', false)
            ->assertSee($clinic['patient']->fullName(), false);
    }

    public function test_specialist_cannot_open_reception_search_but_can_open_scoped_day_board(): void
    {
        $clinic = $this->makeClinic();
        $this->signIn(UserRole::Specialist);
        $date = app(JalaliCalendar::class)->formatDate('2026-11-07');

        $this->get(route('reception.search'))->assertForbidden();
        $this->get(route('reception.day', ['date' => $date]))
            ->assertOk()
            ->assertSee('برنامه امروز من', false)
            ->assertDontSee('درخواست‌های آنلاین', false);
    }

    public function test_pending_online_rows_offer_inline_approval_on_command_center(): void
    {
        $clinic = $this->makeClinic();
        $this->signIn(UserRole::Receptionist);
        $date = app(JalaliCalendar::class)->formatDate('2026-11-07');

        $onlinePatient = Patient::query()->create([
            'code' => 'P-ON2',
            'first_name' => 'نیما',
            'last_name' => 'آنلاین',
            'mobile' => '09123334455',
            'mobile_normalized' => '09123334455',
            'status' => 'active',
        ]);

        $pending = Appointment::query()->create([
            'public_reference' => (string) Str::uuid(),
            'patient_id' => $onlinePatient->id,
            'treatment_service_id' => $clinic['service']->id,
            'starts_at' => '2026-11-07 09:20:00',
            'ends_at' => '2026-11-07 10:00:00',
            'status' => AppointmentStatus::PendingApproval->value,
            'source' => AppointmentSource::Online->value,
        ]);

        $this->get(route('reception.day', ['date' => $date]))
            ->assertOk()
            ->assertSee('تأیید آنلاین', false)
            ->assertSee('تا تأیید پذیرش، چک‌این ممکن نیست.', false);

        $this->post(route('booking.requests.approve', $pending), [
            'return_to' => 'reception.day',
            'return_date' => $date,
        ])->assertRedirect();

        $this->assertSame(AppointmentStatus::Confirmed, $pending->fresh()->status);
    }

    public function test_command_center_defaults_online_pending_to_selected_day_with_all_days_toggle(): void
    {
        $clinic = $this->makeClinic();
        $this->signIn(UserRole::Receptionist);
        $date = app(JalaliCalendar::class)->formatDate('2026-11-07');

        $sameDay = Patient::query()->create([
            'code' => 'P-ON-D',
            'first_name' => 'امروز',
            'last_name' => 'آنلاین',
            'mobile' => '09120001111',
            'mobile_normalized' => '09120001111',
            'status' => 'active',
        ]);
        $otherDay = Patient::query()->create([
            'code' => 'P-ON-O',
            'first_name' => 'فردا',
            'last_name' => 'آنلاین',
            'mobile' => '09120002222',
            'mobile_normalized' => '09120002222',
            'status' => 'active',
        ]);

        Appointment::query()->create([
            'public_reference' => (string) Str::uuid(),
            'patient_id' => $sameDay->id,
            'treatment_service_id' => $clinic['service']->id,
            'starts_at' => '2026-11-07 09:00:00',
            'ends_at' => '2026-11-07 09:40:00',
            'status' => AppointmentStatus::PendingApproval->value,
            'source' => AppointmentSource::Online->value,
        ]);
        Appointment::query()->create([
            'public_reference' => (string) Str::uuid(),
            'patient_id' => $otherDay->id,
            'treatment_service_id' => $clinic['service']->id,
            'starts_at' => '2026-11-08 09:00:00',
            'ends_at' => '2026-11-08 09:40:00',
            'status' => AppointmentStatus::PendingApproval->value,
            'source' => AppointmentSource::Online->value,
        ]);

        $this->get(route('reception.day', ['date' => $date]))
            ->assertOk()
            ->assertSee('امروز آنلاین', false)
            ->assertDontSee('فردا آنلاین', false)
            ->assertSee('همه روزها', false)
            ->assertSee('ثبت بیمار جدید', false)
            ->assertSee(route('patients.create', absolute: false), false);

        $this->get(route('reception.day', ['date' => $date, 'online' => 'all']))
            ->assertOk()
            ->assertSee('امروز آنلاین', false)
            ->assertSee('فردا آنلاین', false)
            ->assertSee('همین روز', false);
    }

    public function test_command_center_keeps_independent_follow_ups_and_focuses_day_plus_overdue(): void
    {
        $clinic = $this->makeClinic();
        $this->signIn(UserRole::Receptionist);
        $date = app(JalaliCalendar::class)->formatDate('2026-11-07');

        $overdue = FollowUp::query()->create([
            'patient_id' => $clinic['patient']->id,
            'treatment_service_id' => $clinic['service']->id,
            'proposed_window_start' => '2026-10-01',
            'proposed_window_end' => '2026-10-15',
            'status' => FollowUpStatus::Proposed->value,
            'created_by' => $clinic['specialist']->id,
        ]);
        $futureIndependent = FollowUp::query()->create([
            'patient_id' => $clinic['patient']->id,
            'treatment_service_id' => $clinic['service']->id,
            'proposed_window_start' => '2026-11-20',
            'proposed_window_end' => '2026-11-30',
            'status' => FollowUpStatus::Proposed->value,
            'created_by' => $clinic['specialist']->id,
        ]);
        $sameDayWindow = FollowUp::query()->create([
            'patient_id' => $clinic['otherPatient']->id,
            'treatment_service_id' => $clinic['service']->id,
            'proposed_window_start' => '2026-11-01',
            'proposed_window_end' => '2026-11-10',
            'status' => FollowUpStatus::OfferedToPatient->value,
            'created_by' => $clinic['specialist']->id,
        ]);

        $focused = $this->get(route('reception.day', ['date' => $date]))
            ->assertOk()
            ->assertSee('سررسید گذشته', false)
            ->assertSee('همه پیگیری‌ها', false)
            ->getContent();

        $this->assertStringContainsString(route('clinical.follow-ups.show', $overdue, absolute: false), $focused);
        $this->assertStringContainsString(route('clinical.follow-ups.show', $sameDayWindow, absolute: false), $focused);
        $this->assertStringNotContainsString(route('clinical.follow-ups.show', $futureIndependent, absolute: false), $focused);

        $all = $this->get(route('reception.day', ['date' => $date, 'followups' => 'all']))
            ->assertOk()
            ->assertSee('تمرکز امروز', false)
            ->getContent();

        $this->assertStringContainsString(route('clinical.follow-ups.show', $futureIndependent, absolute: false), $all);
        $this->assertStringContainsString(route('clinical.follow-ups.show', $overdue, absolute: false), $all);
    }

    public function test_receptionist_can_cancel_from_day_schedule_and_return_to_command_center(): void
    {
        $clinic = $this->makeClinic();
        $this->signIn(UserRole::Receptionist);
        $date = app(JalaliCalendar::class)->formatDate('2026-11-07');
        $appointment = $this->reserveAt($clinic, '08:00');

        $this->get(route('reception.day', ['date' => $date]))
            ->assertOk()
            ->assertSee('دلیل لغو', false)
            ->assertSee(route('appointments.cancel', $appointment, absolute: false), false);

        $this->post(route('appointments.cancel', $appointment), [
            'cancellation_reason' => \App\Enums\CancellationReason::PatientRequest->value,
            'cancellation_note' => 'تماس تلفنی',
            'return_to' => 'reception.day',
            'return_date' => $date,
        ])->assertRedirect(route('reception.day', ['date' => $date]).'#day-appointments-heading');

        $fresh = $appointment->fresh();
        $this->assertSame(AppointmentStatus::Cancelled, $fresh->status);
        $this->assertSame('cancel:patient_request|تماس تلفنی', $fresh->decision_reason);
        $this->assertNotNull($fresh->decided_at);
        $this->assertDatabaseHas('operational_events', [
            'event_type' => 'appointment_cancelled',
            'subject_id' => $appointment->id,
        ]);
    }

    public function test_repeated_cancellation_is_idempotent_and_keeps_first_reason(): void
    {
        $clinic = $this->makeClinic();
        $user = $this->signIn(UserRole::Receptionist);
        $appointment = $this->reserveAt($clinic, '08:00');

        app(\App\Actions\Scheduling\CancelAppointment::class)->cancel(
            $appointment,
            $user,
            \App\Enums\CancellationReason::ClinicUnavailable,
            'اول',
        );

        app(\App\Actions\Scheduling\CancelAppointment::class)->cancel(
            $appointment->fresh(),
            $user,
            \App\Enums\CancellationReason::Other,
            'دوم نباید جایگزین شود',
        );

        $fresh = $appointment->fresh();
        $this->assertSame(AppointmentStatus::Cancelled, $fresh->status);
        $this->assertSame('cancel:clinic_unavailable|اول', $fresh->decision_reason);
        $this->assertSame(1, \App\Models\OperationalEvent::query()->where('subject_id', $appointment->id)->where('event_type', 'appointment_cancelled')->count());
    }

    public function test_receptionist_primary_nav_keeps_daily_path_and_moves_catalog_to_management(): void
    {
        $this->signIn(UserRole::Receptionist);
        $date = app(JalaliCalendar::class)->formatDate('2026-11-07');

        $html = $this->get(route('reception.day', ['date' => $date]))
            ->assertOk()
            ->assertSee('مرکز فرمان', false)
            ->assertSee('بیماران', false)
            ->assertSee('نوبت‌ها', false)
            ->assertSee('درخواست‌های آنلاین', false)
            ->assertSee('پیگیری‌ها', false)
            ->assertSee('هفته', false)
            ->assertSee('مدیریت / تنظیمات', false)
            ->getContent();

        $navStart = strpos($html, 'aria-label="ناوبری اصلی"');
        $this->assertNotFalse($navStart);
        $nav = substr($html, $navStart, 4500);
        $managementPos = strpos($nav, 'مدیریت / تنظیمات');
        $this->assertNotFalse($managementPos);

        $primary = substr($nav, 0, $managementPos);
        $secondary = substr($nav, $managementPos);

        $this->assertStringContainsString('مرکز فرمان', $primary);
        $this->assertStringContainsString('هفته', $primary);
        $this->assertStringNotContainsString('متخصصان', $primary);
        $this->assertStringNotContainsString('یونیت‌ها', $primary);
        $this->assertStringNotContainsString('خدمات', $primary);
        $this->assertStringContainsString('متخصصان', $secondary);
        $this->assertStringContainsString('یونیت‌ها', $secondary);
        $this->assertStringContainsString('خدمات', $secondary);
    }

    public function test_specialist_cannot_cancel_from_day_board(): void
    {
        $clinic = $this->makeClinic();
        $appointment = $this->reserveAt($clinic, '08:00');
        $this->signIn(UserRole::Specialist);
        $date = app(JalaliCalendar::class)->formatDate('2026-11-07');

        $this->get(route('reception.day', ['date' => $date]))
            ->assertOk()
            ->assertDontSee(route('appointments.cancel', $appointment, absolute: false), false);

        $this->post(route('appointments.cancel', $appointment))->assertForbidden();
    }
}
