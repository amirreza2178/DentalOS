<?php

namespace Tests\Unit\Reception;

use App\Support\ClinicDay;
use Tests\TestCase;

class ClinicDayTest extends TestCase
{
    public function test_week_bounds_start_on_saturday_and_day_is_half_open(): void
    {
        $this->assertSame('UTC', config('app.timezone'));
        $day = new ClinicDay;

        [$start, $end] = $day->weekBounds('2026-11-07');
        $this->assertSame('2026-11-07', $start);
        $this->assertSame('2026-11-13', $end);

        $this->assertSame('2026-11-07 00:00:00', $day->startOfDay('2026-11-07')->format('Y-m-d H:i:s'));
        $this->assertSame('2026-11-08 00:00:00', $day->endExclusive('2026-11-07')->format('Y-m-d H:i:s'));
    }
}
