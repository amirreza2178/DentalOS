<?php

namespace App\Http\Controllers;

use App\Http\Requests\HolidayRequest;
use App\Models\Holiday;
use App\Support\JalaliCalendar;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class HolidayController extends Controller
{
    public function index(JalaliCalendar $calendar): View
    {
        $this->authorize('viewAny', Holiday::class);

        $holidays = Holiday::query()->orderBy('holiday_date')->get();

        return view('holidays.index', [
            'holidays' => $holidays,
            'calendar' => $calendar,
        ]);
    }

    public function store(HolidayRequest $request): RedirectResponse
    {
        Holiday::query()->create([
            'holiday_date' => $request->validated('holiday_date_gregorian'),
            'title' => $request->validated('title'),
            'is_closed' => $request->validated('is_closed'),
        ]);

        return redirect()->route('holidays.index')->with('status', 'تعطیلی ثبت شد.');
    }

    public function destroy(Holiday $holiday): RedirectResponse
    {
        $this->authorize('delete', $holiday);
        $holiday->delete();

        return redirect()->route('holidays.index')->with('status', 'تعطیلی حذف شد.');
    }
}
