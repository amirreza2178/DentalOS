<?php

namespace Tests\Feature\Booking;

use App\Enums\AppointmentSource;
use App\Enums\AppointmentStatus;
use App\Enums\UserRole;
use App\Models\Appointment;
use App\Models\Patient;
use App\Support\JalaliCalendar;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Tests\Support\BuildsClinicSchedule;
use Tests\TestCase;

class PublicConfirmationPrivacyTest extends TestCase
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

    public function test_confirmation_shows_minimal_fields_without_patient_name_or_mobile(): void
    {
        $clinic = $this->makeClinic();
        $jalali = app(JalaliCalendar::class)->formatDate('2026-11-07');
        $key = (string) Str::uuid();

        $this->post(route('booking.store'), [
            'first_name' => 'آناهیتا',
            'last_name' => 'محرمانه',
            'mobile' => '09123334455',
            'treatment_service_id' => $clinic['service']->id,
            'appointment_date' => $jalali,
            'appointment_time' => '08:00:00',
            'idempotency_key' => $key,
            'patient_notes' => null,
        ])->assertRedirect();

        $appointment = Appointment::query()->firstOrFail();

        $this->get(route('booking.confirmation', $appointment->public_reference))
            ->assertOk()
            ->assertSee($appointment->public_reference, false)
            ->assertSee($clinic['service']->name, false)
            ->assertSee(AppointmentStatus::PendingApproval->label(), false)
            ->assertDontSee('آناهیتا', false)
            ->assertDontSee('محرمانه', false)
            ->assertDontSee('09123334455', false)
            ->assertDontSee('patient_id', false)
            ->assertDontSee('/appointments/'.$appointment->id, false);
    }

    public function test_unknown_confirmation_reference_returns_generic_not_found_without_leak(): void
    {
        $fake = (string) Str::uuid();

        $this->get(route('booking.confirmation', $fake))
            ->assertNotFound()
            ->assertSee('کد پیگیری معتبر نیست', false)
            ->assertDontSee('آناهیتا', false);
    }

    public function test_confirmation_route_rejects_non_uuid_identifiers(): void
    {
        $this->get('/book/confirmation/123')->assertNotFound();
        $this->get('/book/confirmation/not-a-uuid')->assertNotFound();
    }

    public function test_confirmation_endpoint_is_read_only_for_guests(): void
    {
        $clinic = $this->makeClinic();
        $patient = Patient::query()->create([
            'code' => 'P-CONF1',
            'first_name' => 'مهمان',
            'last_name' => 'تست',
            'status' => 'active',
        ]);
        $appointment = Appointment::query()->create([
            'public_reference' => (string) Str::uuid(),
            'patient_id' => $patient->id,
            'treatment_service_id' => $clinic['service']->id,
            'starts_at' => '2026-11-07 08:00:00',
            'ends_at' => '2026-11-07 08:40:00',
            'status' => AppointmentStatus::PendingApproval->value,
            'source' => AppointmentSource::Online->value,
        ]);

        $this->get(route('booking.confirmation', $appointment->public_reference))->assertOk();
        $this->assertSame(AppointmentStatus::PendingApproval, $appointment->fresh()->status);

        $this->post(route('booking.requests.approve', $appointment))->assertRedirect(route('login'));
        $this->assertSame(AppointmentStatus::PendingApproval, $appointment->fresh()->status);
    }
}
