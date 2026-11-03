<?php

namespace Tests\Unit\Support;

use App\Actions\Scheduling\AvailabilityService;
use App\Actions\Scheduling\ReservationData;
use App\Enums\AppointmentSource;
use App\Support\ClinicDay;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\Support\BuildsClinicSchedule;
use Tests\TestCase;

class AsiaTehranTimezoneTest extends TestCase
{
    use BuildsClinicSchedule;
    use RefreshDatabase;

    public function test_application_timezone_is_asia_tehran(): void
    {
        $this->assertSame('Asia/Tehran', config('app.timezone'));
    }

    public function test_tehran_offset_is_stable_year_round_without_dst(): void
    {
        // Iran abolished daylight saving; Asia/Tehran stays UTC+03:30.
        $winter = Carbon::parse('2026-01-15 12:00:00', 'Asia/Tehran');
        $summer = Carbon::parse('2026-07-15 12:00:00', 'Asia/Tehran');

        $this->assertSame(210, $winter->utcOffset());
        $this->assertSame(210, $summer->utcOffset());
        $this->assertSame('+0330', $winter->format('O'));
        $this->assertSame('+0330', $summer->format('O'));
    }

    public function test_civil_day_boundary_is_tehran_midnight(): void
    {
        $day = new ClinicDay;
        $start = $day->startOfDay('2026-11-07');
        $end = $day->endExclusive('2026-11-07');

        $this->assertSame('Asia/Tehran', $day->timezone());
        $this->assertSame('2026-11-07 00:00:00', $start->format('Y-m-d H:i:s'));
        $this->assertSame('Asia/Tehran', $start->timezoneName);
        $this->assertSame('2026-11-08 00:00:00', $end->format('Y-m-d H:i:s'));

        // Instant just before Tehran midnight belongs to the previous civil day.
        $beforeMidnight = Carbon::parse('2026-11-06 23:59:59', 'Asia/Tehran');
        $this->assertTrue($beforeMidnight->lt($start));
        $this->assertSame('2026-11-06', $beforeMidnight->toDateString());
    }

    public function test_past_slot_gate_uses_tehran_wall_clock_not_utc(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-11-07 08:15:00', 'Asia/Tehran'));

        try {
            $clinic = $this->makeClinic();
            $availability = app(AvailabilityService::class);

            $pastReasons = $availability->reasons(new ReservationData(
                patientId: $clinic['patient']->id,
                treatmentServiceId: $clinic['service']->id,
                startsAt: Carbon::parse('2026-11-07 08:00:00', 'Asia/Tehran'),
                specialistId: $clinic['specialist']->id,
                clinicalStaffIds: [$clinic['assistant']->id],
                chairId: $clinic['chair']->id,
                source: AppointmentSource::Receptionist,
                actorUserId: $clinic['specialist']->id,
            ));
            $this->assertContains('این زمان گذشته است و قابل رزرو نیست.', $pastReasons);

            $later = $availability->opening(
                $clinic['service'],
                Carbon::parse('2026-11-07 08:40:00', 'Asia/Tehran'),
                $clinic['patient']->id,
            );
            $this->assertNotNull($later);
            $this->assertSame('08:40', $later->startsAt->timezone('Asia/Tehran')->format('H:i'));
        } finally {
            Carbon::setTestNow();
        }
    }
}
