<?php

namespace Tests\Feature\Booking;

use App\Enums\AppointmentSource;
use App\Enums\AppointmentStatus;
use App\Enums\PatientIdentityStatus;
use App\Enums\UserRole;
use App\Models\Appointment;
use App\Models\Patient;
use App\Models\TreatmentService;
use App\Support\JalaliCalendar;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Tests\Support\BuildsClinicSchedule;
use Tests\TestCase;

class PublicOnlineBookingTest extends TestCase
{
    use BuildsClinicSchedule;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow(Carbon::parse('2026-11-03 09:00:00', 'Asia/Tehran'));
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_only_active_bookable_services_are_listed(): void
    {
        $clinic = $this->makeClinic();
        TreatmentService::query()->create([
            'code' => 'OFF',
            'name' => 'غیرفعال',
            'default_duration_minutes' => 30,
            'buffer_minutes' => 0,
            'requires_specialist' => false,
            'required_clinical_staff_count' => 0,
            'requires_chair' => false,
            'is_active' => false,
            'is_bookable_online' => true,
        ]);
        TreatmentService::query()->create([
            'code' => 'INT',
            'name' => 'فقط داخلی',
            'default_duration_minutes' => 30,
            'buffer_minutes' => 0,
            'requires_specialist' => false,
            'required_clinical_staff_count' => 0,
            'requires_chair' => false,
            'is_active' => true,
            'is_bookable_online' => false,
        ]);

        $this->get(route('booking.services'))
            ->assertOk()
            ->assertSee($clinic['service']->name)
            ->assertDontSee('غیرفعال')
            ->assertDontSee('فقط داخلی');
    }

    public function test_guest_can_see_real_slots_and_submit_pending_request(): void
    {
        $clinic = $this->makeClinic();
        $jalali = app(JalaliCalendar::class)->formatDate('2026-11-07');

        $this->get(route('booking.slots', ['service' => $clinic['service'], 'date' => $jalali]))
            ->assertOk()
            ->assertSee('08:00')
            ->assertSee('08:40');

        $key = (string) Str::uuid();
        $response = $this->post(route('booking.store'), $this->payload($clinic, $jalali, '08:00:00', $key));

        $appointment = Appointment::query()->first();
        $this->assertNotNull($appointment);
        $response->assertRedirect(route('booking.confirmation', $appointment->public_reference));

        $this->assertSame(AppointmentStatus::PendingApproval, $appointment->status);
        $this->assertSame(AppointmentSource::Online, $appointment->source);
        $this->assertSame(PatientIdentityStatus::UnverifiedOnline, $appointment->patient->identity_status);
        $this->assertNull($appointment->booked_by_user_id);
    }

    public function test_online_booking_does_not_attach_to_existing_patient_by_mobile(): void
    {
        $clinic = $this->makeClinic();
        $existing = $clinic['patient'];
        $existing->mobile = '09121234567';
        $existing->mobile_normalized = '09121234567';
        $existing->identity_status = PatientIdentityStatus::Verified;
        $existing->save();

        $jalali = app(JalaliCalendar::class)->formatDate('2026-11-07');
        $this->post(route('booking.store'), $this->payload($clinic, $jalali, '08:00:00', (string) Str::uuid(), [
            'mobile' => '09121234567',
        ]))->assertRedirect();

        $this->assertSame(2, Patient::query()->where('mobile_normalized', '09121234567')->count());
        $created = Appointment::query()->first()->patient;
        $this->assertNotSame($existing->id, $created->id);
        $this->assertSame(PatientIdentityStatus::UnverifiedOnline, $created->identity_status);
    }

    public function test_inactive_service_and_past_slot_are_rejected(): void
    {
        $clinic = $this->makeClinic();
        $clinic['service']->update(['is_active' => false]);
        $jalali = app(JalaliCalendar::class)->formatDate('2026-11-07');

        $this->post(route('booking.store'), $this->payload($clinic, $jalali, '08:00:00', (string) Str::uuid()))
            ->assertSessionHasErrors('appointment_time');

        $clinic['service']->update(['is_active' => true, 'is_bookable_online' => false]);
        $this->post(route('booking.store'), $this->payload($clinic, $jalali, '08:00:00', (string) Str::uuid()))
            ->assertSessionHasErrors('appointment_time');

        Carbon::setTestNow(Carbon::parse('2026-11-07 09:00:00', 'Asia/Tehran'));
        $clinic['service']->update(['is_bookable_online' => true]);
        $this->post(route('booking.store'), $this->payload($clinic, $jalali, '08:00:00', (string) Str::uuid()))
            ->assertSessionHasErrors('appointment_time');
    }

    public function test_double_booking_and_duplicate_idempotency_are_controlled(): void
    {
        $clinic = $this->makeClinic();
        $jalali = app(JalaliCalendar::class)->formatDate('2026-11-07');
        $key = (string) Str::uuid();

        $first = $this->post(route('booking.store'), $this->payload($clinic, $jalali, '08:00:00', $key));
        $appointment = Appointment::query()->first();
        $first->assertRedirect(route('booking.confirmation', $appointment->public_reference));

        $second = $this->post(route('booking.store'), $this->payload($clinic, $jalali, '08:00:00', $key, [
            'first_name' => 'تکرار',
        ]));
        $second->assertRedirect(route('booking.confirmation', $appointment->public_reference));
        $this->assertSame(1, Appointment::query()->count());

        $clinic['specialistTwo']->update(['is_active' => false]);
        $clinic['assistantTwo']->update(['is_active' => false]);
        $clinic['orthoChair']->update(['is_active' => false]);

        $this->post(route('booking.store'), $this->payload($clinic, $jalali, '08:00:00', (string) Str::uuid()))
            ->assertSessionHasErrors('appointment_time');
        $this->assertSame(1, Appointment::query()->count());
    }

    public function test_guest_cannot_open_private_management_routes(): void
    {
        $this->get(route('booking.requests.index'))->assertRedirect(route('login'));
        $this->get(route('dashboard'))->assertRedirect(route('login'));
        $this->get(route('patients.index'))->assertRedirect(route('login'));
    }

    public function test_malicious_text_is_stripped_from_public_notes(): void
    {
        $clinic = $this->makeClinic();
        $jalali = app(JalaliCalendar::class)->formatDate('2026-11-07');

        $this->post(route('booking.store'), $this->payload($clinic, $jalali, '08:00:00', (string) Str::uuid(), [
            'patient_notes' => '<script>alert(1)</script>نوبت صبح',
            'first_name' => '<b>علی</b>',
        ]))->assertRedirect();

        $appointment = Appointment::query()->first();
        $this->assertSame('نوبت صبح', $appointment->patient_notes);
        $this->assertSame('علی', $appointment->patient->first_name);
        $this->assertStringNotContainsString('<script>', (string) $appointment->patient_notes);
    }

    /**
     * @param  array<string, mixed>  $clinic
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function payload(array $clinic, string $jalali, string $time, string $key, array $overrides = []): array
    {
        return array_merge([
            'treatment_service_id' => $clinic['service']->id,
            'appointment_date' => $jalali,
            'appointment_time' => $time,
            'first_name' => 'مینا',
            'last_name' => 'کاظمی',
            'mobile' => '09120001122',
            'patient_notes' => null,
            'idempotency_key' => $key,
        ], $overrides);
    }
}
