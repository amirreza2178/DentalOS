<?php

namespace Tests\Feature\Ops;

use App\Enums\FollowUpStatus;
use App\Enums\UserRole;
use App\Models\FollowUp;
use App\Models\User;
use App\Support\JalaliCalendar;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\Support\BuildsClinicSchedule;
use Tests\TestCase;

class Day8OperationalExcellenceTest extends TestCase
{
    use BuildsClinicSchedule;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow(Carbon::parse('2026-11-07 07:30:00', 'UTC'));
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_receptionist_lands_on_command_center_after_login(): void
    {
        User::factory()->create([
            'email' => 'desk@example.com',
            'password' => 'password',
            'role' => UserRole::Receptionist,
            'is_active' => true,
        ]);

        $this->post(route('login.store'), [
            'email' => 'desk@example.com',
            'password' => 'password',
        ])->assertRedirect(route('reception.day'));
    }

    public function test_specialist_lands_on_schedule_and_clinical_staff_on_tasks(): void
    {
        User::factory()->create([
            'email' => 'ortho@example.com',
            'password' => 'password',
            'role' => UserRole::Specialist,
            'is_active' => true,
        ]);
        User::factory()->create([
            'email' => 'assist@example.com',
            'password' => 'password',
            'role' => UserRole::ClinicalStaff,
            'is_active' => true,
        ]);

        $this->post(route('login.store'), [
            'email' => 'ortho@example.com',
            'password' => 'password',
        ])->assertRedirect(route('reception.day'));

        $this->post(route('logout'));

        $this->post(route('login.store'), [
            'email' => 'assist@example.com',
            'password' => 'password',
        ])->assertRedirect(route('clinical.tasks.index'));
    }

    public function test_public_booking_date_page_offers_quick_jalali_presets(): void
    {
        $clinic = $this->makeClinic();
        $calendar = app(JalaliCalendar::class);
        $today = $calendar->formatDate(now(config('app.timezone')));

        $this->get(route('booking.service', $clinic['service']))
            ->assertOk()
            ->assertSee('امروز', false)
            ->assertSee('فردا', false)
            ->assertSee($today, false);
    }

    public function test_follow_up_offer_is_idempotent_under_repeat_action(): void
    {
        $clinic = $this->makeClinic();
        $actor = $this->signIn(UserRole::Receptionist);

        $followUp = FollowUp::query()->create([
            'patient_id' => $clinic['patient']->id,
            'treatment_service_id' => $clinic['service']->id,
            'proposed_window_start' => '2026-11-10',
            'proposed_window_end' => '2026-11-20',
            'status' => FollowUpStatus::Proposed->value,
            'created_by' => $clinic['specialist']->id,
        ]);

        $this->post(route('clinical.follow-ups.offer', $followUp))
            ->assertRedirect();
        $this->assertSame(FollowUpStatus::OfferedToPatient, $followUp->fresh()->status);

        $this->post(route('clinical.follow-ups.offer', $followUp))
            ->assertRedirect();
        $this->assertSame(FollowUpStatus::OfferedToPatient, $followUp->fresh()->status);
        $this->assertSame($actor->id, auth()->id());
    }

    public function test_confirmation_page_exposes_copy_affordance_without_patient_pii(): void
    {
        $clinic = $this->makeClinic();
        $jalali = app(JalaliCalendar::class)->formatDate('2026-11-07');
        $key = (string) \Illuminate\Support\Str::uuid();

        $this->post(route('booking.store'), [
            'treatment_service_id' => $clinic['service']->id,
            'appointment_date' => $jalali,
            'appointment_time' => '08:00:00',
            'first_name' => 'سارا',
            'last_name' => 'رضایی',
            'mobile' => '09120001122',
            'patient_notes' => null,
            'idempotency_key' => $key,
        ])->assertRedirect();

        $appointment = \App\Models\Appointment::query()->firstOrFail();

        $this->get(route('booking.confirmation', $appointment->public_reference))
            ->assertOk()
            ->assertSee('کپی کد', false)
            ->assertSee($appointment->public_reference, false)
            ->assertDontSee('09120001122', false)
            ->assertDontSee('سارا', false);
    }
}
