<?php

namespace App\Http\Controllers;

use App\Enums\Weekday;
use App\Http\Requests\ShiftBreakRequest;
use App\Http\Requests\WorkShiftRequest;
use App\Models\ShiftBreak;
use App\Models\WorkShift;
use App\Support\ClockInput;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class WorkShiftController extends Controller
{
    public function index(): View
    {
        $this->authorize('viewAny', WorkShift::class);

        $shifts = WorkShift::query()
            ->withCount('breaks')
            ->orderBy('weekday')
            ->orderBy('starts_at')
            ->get();

        return view('shifts.index', ['shifts' => $shifts]);
    }

    public function create(): View
    {
        $this->authorize('create', WorkShift::class);

        return view('shifts.create', ['weekdays' => Weekday::options()]);
    }

    public function store(WorkShiftRequest $request): RedirectResponse
    {
        WorkShift::query()->create($request->safe()->only(['name', 'weekday', 'starts_at', 'ends_at', 'is_active']));

        return redirect()->route('shifts.index')->with('status', 'شیفت ثبت شد.');
    }

    public function edit(WorkShift $shift): View
    {
        $this->authorize('update', $shift);
        $shift->load('breaks');

        return view('shifts.edit', [
            'shift' => $shift,
            'weekdays' => Weekday::options(),
            'starts' => ClockInput::short((string) $shift->starts_at),
            'ends' => ClockInput::short((string) $shift->ends_at),
        ]);
    }

    public function update(WorkShiftRequest $request, WorkShift $shift): RedirectResponse
    {
        $shift->update($request->safe()->only(['name', 'weekday', 'starts_at', 'ends_at', 'is_active']));

        return redirect()->route('shifts.edit', $shift)->with('status', 'شیفت به‌روزرسانی شد.');
    }

    public function storeBreak(ShiftBreakRequest $request, WorkShift $shift): RedirectResponse
    {
        $shift->breaks()->create($request->safe()->only(['name', 'starts_at', 'ends_at']));

        return redirect()->route('shifts.edit', $shift)->with('status', 'زمان استراحت ثبت شد.');
    }

    public function destroyBreak(WorkShift $shift, ShiftBreak $shiftBreak): RedirectResponse
    {
        $this->authorize('update', $shift);
        abort_unless($shiftBreak->work_shift_id === $shift->id, 404);
        $shiftBreak->delete();

        return redirect()->route('shifts.edit', $shift)->with('status', 'زمان استراحت حذف شد.');
    }
}
