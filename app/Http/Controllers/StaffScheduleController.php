<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Http\Requests\StaffScheduleRequest;
use App\Models\StaffSchedule;
use App\Models\User;
use App\Models\WorkShift;
use App\Support\JalaliCalendar;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class StaffScheduleController extends Controller
{
    public function index(JalaliCalendar $calendar): View
    {
        $this->authorize('viewAny', StaffSchedule::class);

        $schedules = StaffSchedule::query()
            ->with(['user', 'workShift'])
            ->orderByDesc('schedule_date')
            ->orderBy('user_id')
            ->get();

        return view('schedules.index', [
            'schedules' => $schedules,
            'calendar' => $calendar,
            'staff' => $this->staffOptions(),
            'shifts' => WorkShift::query()->orderBy('weekday')->orderBy('starts_at')->get(),
        ]);
    }

    public function store(StaffScheduleRequest $request): RedirectResponse
    {
        $kind = $request->validated('kind');

        StaffSchedule::query()->create([
            'user_id' => $request->validated('user_id'),
            'work_shift_id' => $kind === 'day_off' ? null : $request->validated('work_shift_id'),
            'schedule_date' => $kind === 'recurring' ? null : $request->validated('schedule_date_gregorian'),
            'starts_at' => $kind === 'date' ? $request->validated('starts_at') : null,
            'ends_at' => $kind === 'date' ? $request->validated('ends_at') : null,
            'is_unavailable' => $kind === 'day_off',
            'is_active' => $request->validated('is_active'),
        ]);

        return redirect()->route('schedules.index')->with('status', 'برنامه کارکنان ثبت شد.');
    }

    public function destroy(StaffSchedule $staffSchedule): RedirectResponse
    {
        $this->authorize('delete', $staffSchedule);
        $staffSchedule->delete();

        return redirect()->route('schedules.index')->with('status', 'برنامه کارکنان حذف شد.');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Collection<int, User>
     */
    private function staffOptions()
    {
        return User::query()
            ->whereIn('role', [UserRole::Specialist->value, UserRole::ClinicalStaff->value])
            ->where('is_active', true)
            ->whereHas('staffProfile', fn ($query) => $query->where('is_active', true))
            ->orderBy('name')
            ->get();
    }
}
