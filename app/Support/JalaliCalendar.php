<?php

namespace App\Support;

use Carbon\CarbonInterface;
use InvalidArgumentException;
use Morilog\Jalali\CalendarUtils;

/**
 * Date-only Jalali conversion at the presentation boundary.
 * Canonical storage stays Gregorian Y-m-d. Conversion uses the library calendar
 * utilities so a timezone offset cannot shift the civil date.
 */
final class JalaliCalendar
{
    public function formatDate(CarbonInterface|string|null $date): ?string
    {
        if ($date === null || $date === '') {
            return null;
        }

        $gregorian = $date instanceof CarbonInterface ? $date->format('Y-m-d') : $date;
        [$year, $month, $day] = $this->parts($gregorian, '-');
        [$jalaliYear, $jalaliMonth, $jalaliDay] = CalendarUtils::toJalali($year, $month, $day);

        return sprintf('%04d/%02d/%02d', $jalaliYear, $jalaliMonth, $jalaliDay);
    }

    public function parseToGregorianDate(?string $input): ?string
    {
        $normalized = trim(str_replace('-', '/', Digits::toAscii($input)));

        if ($normalized === '') {
            return null;
        }

        [$year, $month, $day] = $this->parts($normalized, '/');

        if ($year < 1700) {
            if ($month < 1 || $month > 12 || $day < 1 || $day > 31) {
                throw new InvalidArgumentException('Date is not a valid calendar day.');
            }

            [$gregorianYear, $gregorianMonth, $gregorianDay] = CalendarUtils::toGregorian($year, $month, $day);
            [$backYear, $backMonth, $backDay] = CalendarUtils::toJalali($gregorianYear, $gregorianMonth, $gregorianDay);

            if ($backYear !== $year || $backMonth !== $month || $backDay !== $day) {
                throw new InvalidArgumentException('Date is not a valid calendar day.');
            }

            [$year, $month, $day] = [$gregorianYear, $gregorianMonth, $gregorianDay];
        }

        if (! checkdate($month, $day, $year)) {
            throw new InvalidArgumentException('Date is not a valid calendar day.');
        }

        return sprintf('%04d-%02d-%02d', $year, $month, $day);
    }

    /**
     * @return array{0: int, 1: int, 2: int}
     */
    private function parts(string $value, string $separator): array
    {
        $pieces = explode($separator, $value);

        if (count($pieces) !== 3 || in_array('', $pieces, true)) {
            throw new InvalidArgumentException('Date format is not recognized.');
        }

        return array_map(intval(...), $pieces);
    }
}
