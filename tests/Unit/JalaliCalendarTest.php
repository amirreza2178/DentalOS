<?php

namespace Tests\Unit;

use App\Support\JalaliCalendar;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

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

    public function test_impossible_jalali_day_is_rejected(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        // 1402 is not a leap year; Esfand 30 must fail.
        (new JalaliCalendar)->parseToGregorianDate('1402/12/30');
    }

    public function test_leap_esfand_30_is_accepted(): void
    {
        $calendar = new JalaliCalendar;

        $this->assertTrue($calendar->isJalaliLeapYear(1403));
        $this->assertSame(30, $calendar->daysInJalaliMonth(1403, 12));
        $this->assertSame('2025-03-20', $calendar->parseToGregorianDate('1403/12/30'));
    }

    public function test_year_transition_farvardin_first(): void
    {
        $calendar = new JalaliCalendar;

        $this->assertSame('2025-03-21', $calendar->parseToGregorianDate('1404/01/01'));
        $this->assertSame('1404/01/01', $calendar->formatDate('2025-03-21'));
    }

    public function test_month_boundary_shahrivar_to_mehr(): void
    {
        $calendar = new JalaliCalendar;

        $this->assertSame('2025-09-22', $calendar->parseToGregorianDate('1404/06/31'));
        $this->assertSame('2025-09-23', $calendar->parseToGregorianDate('1404/07/01'));
    }

    public function test_malformed_input_is_rejected(): void
    {
        $calendar = new JalaliCalendar;

        $this->expectException(\InvalidArgumentException::class);
        $calendar->parseToGregorianDate('1404-01');
    }

    public function test_asia_tehran_midnight_does_not_shift_civil_date(): void
    {
        config(['app.timezone' => 'Asia/Tehran']);
        $calendar = new JalaliCalendar;
        $midnight = Carbon::parse('2026-11-07 00:00:00', 'Asia/Tehran');
        $justBefore = Carbon::parse('2026-11-07 00:00:00', 'Asia/Tehran')->subSecond();

        $this->assertSame('1405/08/16', $calendar->formatDate($midnight->toDateString()));
        $this->assertSame('1405/08/15', $calendar->formatDate($justBefore->toDateString()));
        $this->assertSame(
            $midnight->toDateString(),
            $calendar->parseToGregorianDate($calendar->formatDate($midnight->toDateString())),
        );
    }

    public function test_saturday_based_weekday_index_for_known_friday(): void
    {
        config(['app.timezone' => 'Asia/Tehran']);
        $calendar = new JalaliCalendar;

        // 2026-11-06 is Friday → index 6 in Saturday-first week.
        $this->assertSame(6, $calendar->saturdayBasedWeekdayIndex('2026-11-06'));
        $this->assertSame(0, $calendar->saturdayBasedWeekdayIndex('2026-11-07'));
    }

    #[DataProvider('knownPairs')]
    public function test_known_conversion_pairs(string $jalali, string $gregorian): void
    {
        $calendar = new JalaliCalendar;

        $this->assertSame($gregorian, $calendar->parseToGregorianDate($jalali));
        $this->assertSame($jalali, $calendar->formatDate($gregorian));
    }

    /**
     * @return array<string, array{0: string, 1: string}>
     */
    public static function knownPairs(): array
    {
        return [
            'nowruz_1403' => ['1403/01/01', '2024-03-20'],
            'mid_year' => ['1403/07/01', '2024-09-22'],
            'esfand_non_leap_end' => ['1402/12/29', '2024-03-19'],
        ];
    }
}
