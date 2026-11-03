<?php

namespace App\Support;

use Carbon\Carbon;
use Carbon\CarbonInterface;

/**
 * Civil-day helpers in the approved clinic application timezone (Asia/Tehran).
 * Wall-clock appointment and shift times are stored and interpreted in this timezone.
 */
final class ClinicDay
{
    public function timezone(): string
    {
        return (string) config('app.timezone');
    }

    public function startOfDay(string $gregorianDate): Carbon
    {
        return Carbon::parse($gregorianDate.' 00:00:00', $this->timezone());
    }

    public function endExclusive(string $gregorianDate): Carbon
    {
        return $this->startOfDay($gregorianDate)->addDay();
    }

    /**
     * Saturday-start week containing the given civil date (Jalali week convention).
     * Provisional until the clinic confirms its week boundary.
     *
     * @return array{0: string, 1: string} [startDate, endDate] inclusive Y-m-d
     */
    public function weekBounds(string $gregorianDate): array
    {
        $day = $this->startOfDay($gregorianDate);
        $daysFromSaturday = ($day->dayOfWeek + 1) % 7;
        $start = $day->copy()->subDays($daysFromSaturday);
        $end = $start->copy()->addDays(6);

        return [$start->toDateString(), $end->toDateString()];
    }

    public function formatClock(CarbonInterface $moment): string
    {
        return $moment->timezone($this->timezone())->format('H:i');
    }
}
