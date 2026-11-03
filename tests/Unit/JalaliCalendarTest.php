<?php

namespace Tests\Unit;

use App\Support\JalaliCalendar;
use PHPUnit\Framework\TestCase;

class JalaliCalendarTest extends TestCase
{
    public function test_date_only_conversion_round_trips_without_storing_jalali_text(): void
    {
        $calendar = new JalaliCalendar;
        $gregorian = $calendar->parseToGregorianDate('۱۴۰۳/۰۱/۰۱');

        $this->assertSame('2024-03-20', $gregorian);
        $this->assertSame('1403/01/01', $calendar->formatDate($gregorian));
        $this->assertSame('2024-03-21', $calendar->parseToGregorianDate($calendar->formatDate('2024-03-21')));
    }

    public function test_invalid_dates_are_rejected(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        (new JalaliCalendar)->parseToGregorianDate('1403/13/01');
    }
}
