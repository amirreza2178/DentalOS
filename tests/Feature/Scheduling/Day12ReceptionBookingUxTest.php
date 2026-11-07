<?php

namespace Tests\Feature\Scheduling;

use App\Enums\AppointmentStatus;
use App\Enums\UserRole;
use App\Models\Appointment;
use App\Support\JalaliCalendar;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\Support\BuildsClinicSchedule;
use Tests\TestCase;

class Day12ReceptionBookingUxTest extends TestCase
{
    use BuildsClinicSchedule;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // Keep morning slots bookable (AvailabilityService rejects past wall-clock times).
        Carbon::setTestNow(Carbon::parse('2026-11-07 07:30:00', 'Asia/Tehran'));
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_known_patient_booking_keeps_sticky_patient_and_defaults_today(): void
    {
        $clinic = $this->makeClinic();
        $this->signIn(UserRole::Receptionist);
        $jalali = app(JalaliCalendar::class)->formatDate('2026-11-07');

        $this->get(route('appointments.create', [
            'patient_id' => $clinic['patient']->id,
            'service_id' => $clinic['service']->id,
        ]))
            ->assertOk()
            ->assertSee('بیمار انتخاب‌شده', false)
            ->assertSee($clinic['patient']->fullName(), false)
            ->assertSee('زمان‌های آزاد', false)
            ->assertSee($jalali, false)
            ->assertSee('ثبت این نوبت', false)
            ->assertSee('بیمار انتخاب‌شده', false);
    }

    public function test_successful_internal_booking_from_known_patient(): void
    {
        $clinic = $this->makeClinic();
        $this->signIn(UserRole::Receptionist);
        $jalali = app(JalaliCalendar::class)->formatDate('2026-11-07');

        $this->post(route('appointments.store'), [
            'patient_id' => $clinic['patient']->id,
            'treatment_service_id' => $clinic['service']->id,
            'appointment_date' => $jalali,
            'appointment_time' => '08:00',
            'specialist_id' => $clinic['specialist']->id,
            'clinical_staff_ids' => [$clinic['assistant']->id],
            'chair_id' => $clinic['chair']->id,
        ])->assertRedirect();

        $appointment = Appointment::query()->firstOrFail();
        $this->assertSame(AppointmentStatus::Confirmed, $appointment->status);
        $this->assertSame($clinic['patient']->id, $appointment->patient_id);

        $this->get(route('appointments.show', $appointment))
            ->assertOk()
            ->assertSee('نوبت ثبت شد', false)
            ->assertSee('میز پذیرش', false);
    }

    public function test_unavailable_slot_explains_reasons_without_booking(): void
    {
        $clinic = $this->makeClinic();
        $this->signIn(UserRole::Receptionist);
        $jalali = app(JalaliCalendar::class)->formatDate('2026-11-07');

        $this->get(route('appointments.create', [
            'patient_id' => $clinic['patient']->id,
            'service_id' => $clinic['service']->id,
            'date' => $jalali,
            'time' => '10:00',
        ]))
            ->assertOk()
            ->assertSee('این ساعت قابل رزرو نیست', false)
            ->assertSee('استراحت', false);

        $this->assertSame(0, Appointment::query()->count());
    }

    public function test_slot_conflict_preserves_service_and_date_for_recovery(): void
    {
        $clinic = $this->makeClinic();
        $this->signIn(UserRole::Receptionist);
        $jalali = app(JalaliCalendar::class)->formatDate('2026-11-07');
        $this->reserveAt($clinic, '08:00');

        $response = $this->from(route('appointments.create', [
            'patient_id' => $clinic['otherPatient']->id,
            'service_id' => $clinic['service']->id,
            'date' => $jalali,
        ]))->post(route('appointments.store'), [
            'patient_id' => $clinic['otherPatient']->id,
            'treatment_service_id' => $clinic['service']->id,
            'appointment_date' => $jalali,
            'appointment_time' => '08:00',
            'specialist_id' => $clinic['specialist']->id,
            'clinical_staff_ids' => [$clinic['assistant']->id],
            'chair_id' => $clinic['chair']->id,
        ]);

        $response->assertRedirect();
        $response->assertSessionHasErrors('appointment_time');
        $this->assertSame(1, Appointment::query()->count());

        // POST field aliases must restore sticky patient + service + date after conflict.
        $this->get(route('appointments.create', [
            'patient_id' => $clinic['otherPatient']->id,
            'treatment_service_id' => $clinic['service']->id,
            'appointment_date' => $jalali,
        ]))
            ->assertOk()
            ->assertSee('بیمار انتخاب‌شده', false)
            ->assertSee($clinic['otherPatient']->fullName(), false)
            ->assertSee('زمان‌های آزاد', false)
            ->assertSee($jalali, false);
    }

    public function test_reschedule_preloads_patient_service_and_date(): void
    {
        $clinic = $this->makeClinic();
        $this->signIn(UserRole::Receptionist);
        $appointment = $this->reserveAt($clinic, '08:00');
        $jalali = app(JalaliCalendar::class)->formatDate('2026-11-07');

        $this->get(route('appointments.reschedule', $appointment))
            ->assertOk()
            ->assertSee($clinic['patient']->fullName(), false)
            ->assertSee('تنظیم', false)
            ->assertSee($jalali, false)
            ->assertSee('زمان‌های آزاد', false);

        $this->post(route('appointments.reschedule.store', $appointment), [
            'patient_id' => $clinic['patient']->id,
            'treatment_service_id' => $clinic['service']->id,
            'appointment_date' => $jalali,
            'appointment_time' => '10:40',
            'specialist_id' => $clinic['specialist']->id,
            'clinical_staff_ids' => [$clinic['assistant']->id],
            'chair_id' => $clinic['chair']->id,
        ])->assertRedirect();

        $this->assertSame(AppointmentStatus::Rescheduled, $appointment->fresh()->status);
        $moved = Appointment::query()->where('status', AppointmentStatus::Confirmed)->firstOrFail();
        $this->assertSame($appointment->id, $moved->rescheduled_from_id);
        $this->assertSame($appointment->patient_id, $moved->patient_id);
    }

    public function test_specialist_cannot_create_internal_appointments(): void
    {
        $clinic = $this->makeClinic();
        $this->actingAs($clinic['specialist']);

        $this->get(route('appointments.create'))->assertForbidden();
        $this->post(route('appointments.store'), [])->assertForbidden();
    }

    public function test_patient_role_cannot_access_internal_booking(): void
    {
        $this->makeClinic();
        $this->signIn(UserRole::Patient);

        $this->get(route('appointments.create'))->assertForbidden();
    }
}
