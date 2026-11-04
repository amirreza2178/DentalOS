<?php

namespace Tests\Feature\Scheduling;

use App\Enums\UserRole;
use App\Models\User;
use App\Support\JalaliCalendar;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\Support\BuildsClinicSchedule;
use Tests\TestCase;

class SharedJalaliDateFieldTest extends TestCase
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

    public function test_reception_and_internal_booking_use_shared_picker(): void
    {
        $clinic = $this->makeClinic();
        $reception = User::factory()->create([
            'role' => UserRole::Receptionist,
            'is_active' => true,
        ]);
        $jalali = app(JalaliCalendar::class)->formatDate('2026-11-07');

        $this->actingAs($reception)
            ->get(route('reception.day', ['date' => $jalali]))
            ->assertOk()
            ->assertSee('data-jalali-date-picker', false);

        $this->actingAs($reception)
            ->get(route('appointments.create', [
                'patient_id' => $clinic['patient']->id,
                'service_id' => $clinic['service']->id,
                'date' => $jalali,
            ]))
            ->assertOk()
            ->assertSee('data-jalali-date-picker', false);

        $this->actingAs($reception)
            ->get(route('appointments.index', ['date' => $jalali]))
            ->assertOk()
            ->assertSee('data-jalali-date-picker', false);
    }

    public function test_manager_holiday_and_schedule_forms_use_shared_picker(): void
    {
        $this->makeClinic();
        $admin = User::factory()->create([
            'role' => UserRole::Admin,
            'is_active' => true,
        ]);

        $this->actingAs($admin)
            ->get(route('holidays.index'))
            ->assertOk()
            ->assertSee('data-jalali-date-picker', false)
            ->assertSee('name="holiday_date"', false);

        $this->actingAs($admin)
            ->get(route('schedules.index'))
            ->assertOk()
            ->assertSee('data-jalali-date-picker', false)
            ->assertSee('name="schedule_date"', false);
    }
}
