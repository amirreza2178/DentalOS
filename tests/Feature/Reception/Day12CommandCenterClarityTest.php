<?php

namespace Tests\Feature\Reception;

use App\Enums\AppointmentSource;
use App\Enums\AppointmentStatus;
use App\Enums\FollowUpStatus;
use App\Enums\UserRole;
use App\Models\Appointment;
use App\Models\FollowUp;
use App\Models\Patient;
use App\Support\JalaliCalendar;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\Support\BuildsClinicSchedule;
use Tests\TestCase;

class Day12CommandCenterClarityTest extends TestCase
{
    use BuildsClinicSchedule;
    use RefreshDatabase;

    public function test_capped_online_panel_reports_remaining_scoped_total(): void
    {
        $clinic = $this->makeClinic();
        $this->signIn(UserRole::Receptionist);
        $date = app(JalaliCalendar::class)->formatDate('2026-11-07');

        for ($i = 1; $i <= 10; $i++) {
            $patient = Patient::query()->create([
                'code' => 'P-CAP-'.$i,
                'first_name' => 'آنلاین',
                'last_name' => 'شماره'.$i,
                'status' => 'active',
            ]);

            Appointment::query()->create([
                'public_reference' => (string) Str::uuid(),
                'patient_id' => $patient->id,
                'treatment_service_id' => $clinic['service']->id,
                'starts_at' => sprintf('2026-11-07 %02d:00:00', 8 + ($i % 4)),
                'ends_at' => sprintf('2026-11-07 %02d:40:00', 8 + ($i % 4)),
                'status' => AppointmentStatus::PendingApproval->value,
                'source' => AppointmentSource::Online->value,
            ]);
        }

        $this->get(route('reception.day', ['date' => $date]))
            ->assertOk()
            ->assertSee('10 درخواست آنلاین در انتظار تأیید', false)
            ->assertSee('نمایش 8 از 10', false)
            ->assertSee('فهرست کامل', false)
            ->assertSee('(10)', false);
    }

    public function test_capped_follow_up_panel_reports_remaining_scoped_total(): void
    {
        $clinic = $this->makeClinic();
        $this->signIn(UserRole::Receptionist);
        $date = app(JalaliCalendar::class)->formatDate('2026-11-07');

        for ($i = 1; $i <= 9; $i++) {
            $patient = Patient::query()->create([
                'code' => 'P-FU-'.$i,
                'first_name' => 'پیگیری',
                'last_name' => 'شماره'.$i,
                'status' => 'active',
            ]);

            FollowUp::query()->create([
                'patient_id' => $patient->id,
                'treatment_service_id' => $clinic['service']->id,
                'proposed_window_start' => '2026-11-01',
                'proposed_window_end' => '2026-11-10',
                'status' => FollowUpStatus::Proposed->value,
                'created_by' => $clinic['specialist']->id,
            ]);
        }

        $this->get(route('reception.day', ['date' => $date]))
            ->assertOk()
            ->assertSee('9 پیگیری نیازمند اقدام پذیرش', false)
            ->assertSee('نمایش 8 از 9', false)
            ->assertSee('فهرست کامل', false);
    }
}
