<?php

namespace Tests\Feature\Scheduling;

use App\Actions\Scheduling\AvailabilityService;
use App\Models\Holiday;
use App\Support\JalaliCalendar;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Tests\Support\BuildsClinicSchedule;
use Tests\TestCase;

class AvailabilityDayStatusTest extends TestCase
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

    public function test_day_status_distinguishes_open_full_and_closed(): void
    {
        $clinic = $this->makeClinic();
        $availability = app(AvailabilityService::class);
        $calendar = app(JalaliCalendar::class);

        $openDay = '2026-11-07';
        $this->assertSame('open', $availability->dayStatus($clinic['service'], $openDay));

        Holiday::query()->create([
            'holiday_date' => '2026-11-08',
            'title' => 'تعطیل آزمایشی',
            'is_closed' => true,
        ]);
        $this->assertSame('closed', $availability->dayStatus($clinic['service'], '2026-11-08'));

        // Friday in this fixture has no weekday shift → closed.
        $this->assertSame('closed', $availability->dayStatus($clinic['service'], '2026-11-06'));

        $map = $availability->dayStatusesBetween($clinic['service'], '2026-11-06', '2026-11-08');
        $this->assertSame('closed', $map[$calendar->formatDate('2026-11-06')]);
        $this->assertSame('open', $map[$calendar->formatDate('2026-11-07')]);
        $this->assertSame('closed', $map[$calendar->formatDate('2026-11-08')]);
    }

    public function test_day_statuses_between_batches_lookups_and_matches_day_status(): void
    {
        $clinic = $this->makeClinic();
        Holiday::query()->create([
            'holiday_date' => '2026-11-14',
            'title' => 'تعطیل بازه',
            'is_closed' => true,
        ]);

        $availability = app(AvailabilityService::class);
        $from = '2026-11-03';
        $to = Carbon::parse($from, 'Asia/Tehran')->addDays(62)->toDateString();
        $expectedDays = 63;

        DB::flushQueryLog();
        DB::enableQueryLog();
        $map = $availability->dayStatusesBetween($clinic['service'], $from, $to);
        $queries = DB::getQueryLog();
        DB::disableQueryLog();

        $this->assertSame($expectedDays, count($map));

        $sql = collect($queries)->map(fn (array $item): string => strtolower($item['query']));
        $holidayCatalogQueries = $sql->filter(
            fn (string $query): bool => str_contains($query, 'from "holidays"')
                || str_contains($query, 'from `holidays`')
                || str_contains($query, 'from holidays'),
        )->count();
        // Catalog load filters is_active; eager-loaded shift-by-id lookups are separate.
        $shiftCatalogQueries = $sql->filter(
            fn (string $query): bool => str_contains($query, 'work_shifts')
                && str_contains($query, 'is_active')
                && ! str_contains($query, '."id" in (')
                && ! str_contains($query, '.`id` in ('),
        )->count();

        // Batched once for the range — not once per calendar day.
        $this->assertSame(1, $holidayCatalogQueries);
        $this->assertSame(1, $shiftCatalogQueries);
        // Remaining queries are opening checks (schedules/allocations) on shift days only.
        $this->assertLessThan(160, count($queries), 'dayStatusesBetween query count regresses: '.count($queries));
        $this->assertGreaterThanOrEqual(2, count($queries));

        $calendar = app(JalaliCalendar::class);
        $this->assertSame('open', $map[$calendar->formatDate('2026-11-07')]);
        $this->assertSame('closed', $map[$calendar->formatDate('2026-11-14')]);
        $this->assertSame(
            $availability->dayStatus($clinic['service'], '2026-11-07'),
            $map[$calendar->formatDate('2026-11-07')],
        );
    }
}
