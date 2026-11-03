<?php

namespace Tests\Feature\Ops;

use App\Enums\AppointmentStatus;
use App\Enums\UserRole;
use App\Models\Appointment;
use App\Models\TreatmentService;
use App\Models\User;
use App\Support\JalaliCalendar;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Str;
use Tests\Support\BuildsClinicSchedule;
use Tests\TestCase;

class Day9CoreExperienceTest extends TestCase
{
    use BuildsClinicSchedule;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow(Carbon::parse('2026-11-03 09:00:00', 'UTC'));
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_booking_empty_state_explains_next_step_for_patient(): void
    {
        $this->get(route('booking.services'))
            ->assertOk()
            ->assertSee('هنوز خدمت قابل رزرو آنلاین باز نیست', false)
            ->assertSee('تماس', false);
    }

    public function test_bookable_services_show_clear_continue_affordance(): void
    {
        $clinic = $this->makeClinic();

        $this->get(route('booking.services'))
            ->assertOk()
            ->assertSee($clinic['service']->name, false)
            ->assertSee('ادامه', false)
            ->assertDontSee('هنوز خدمت قابل رزرو آنلاین باز نیست', false);
    }

    public function test_guest_booking_stays_pending_and_does_not_confirm_appointment(): void
    {
        $clinic = $this->makeClinic();
        $jalali = app(JalaliCalendar::class)->formatDate('2026-11-07');
        $key = (string) Str::uuid();

        $this->post(route('booking.store'), [
            'treatment_service_id' => $clinic['service']->id,
            'appointment_date' => $jalali,
            'appointment_time' => '08:00:00',
            'first_name' => 'آزمایش',
            'last_name' => 'روزنه',
            'mobile' => '09123334455',
            'patient_notes' => 'یادداشت کوتاه',
            'idempotency_key' => $key,
        ])->assertRedirect();

        $appointment = Appointment::query()->first();
        $this->assertNotNull($appointment);
        $this->assertSame(AppointmentStatus::PendingApproval, $appointment->status);
        $this->assertNotSame(AppointmentStatus::Confirmed, $appointment->status);
    }

    public function test_specialist_command_center_prioritizes_next_clinical_action(): void
    {
        $clinic = $this->makeClinic();
        $specialist = $clinic['specialist'];

        $this->actingAs($specialist)
            ->get(route('reception.day', ['date' => app(JalaliCalendar::class)->formatDate('2026-11-07')]))
            ->assertOk()
            ->assertSee('برنامه امروز من', false)
            ->assertDontSee('درخواست‌های آنلاین', false);
    }

    public function test_bootstrap_local_ops_refuses_non_local_environment(): void
    {
        $this->app['env'] = 'testing';

        $exit = Artisan::call('dentalos:bootstrap-local-ops');

        $this->assertSame(1, $exit);
        $this->assertStringContainsString('local', Artisan::output());
        $this->assertSame(0, TreatmentService::query()->count());
    }

    public function test_receptionist_cannot_open_specialist_only_clinical_plan_create_for_unrelated_flow_without_authz(): void
    {
        $clinic = $this->makeClinic();
        $receptionist = User::factory()->create([
            'role' => UserRole::Receptionist,
            'is_active' => true,
        ]);

        $this->actingAs($receptionist)
            ->get(route('clinical.plans.create', $clinic['patient']))
            ->assertForbidden();
    }
}
