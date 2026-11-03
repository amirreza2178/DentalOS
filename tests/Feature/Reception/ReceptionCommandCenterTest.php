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
        Carbon::setTestNow(Carbon::parse('2026-11-07 07:30:00', 'UTC'));
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
            'proposed_window_start' => '2026-11-10',
            'proposed_window_end' => '2026-11-20',
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
        $this->get(route('reception.day', ['date' => $date]))->assertOk();
    }
}
