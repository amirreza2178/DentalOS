@props([
    'name',
    'label' => 'تاریخ جلالی',
    'value' => null,
    'required' => false,
    'id' => null,
    'display' => 'popover', // popover|inline
    'hint' => null,
    'autoSubmit' => false,
    'bookingOnly' => false,
    'disableUnavailable' => false,
    'disablePast' => false,
    'min' => null,
    'max' => null,
    'dayStatuses' => null,
    'timezone' => null,
])

@php
    $fieldId = $id ?? $name;
    $resolvedValue = old($name, $value);
    $tz = $timezone ?? config('app.timezone');
    $todayJalali = app(\App\Support\JalaliCalendar::class)->formatDate(now($tz)->toDateString());
    $minValue = $min;
    if ($disablePast && $minValue === null) {
        $minValue = $todayJalali;
    }
    $hintText = $hint ?? 'تاریخ با تقویم شمسی انتخاب می‌شود و در پایگاه به‌صورت میلادی ذخیره می‌گردد.';
@endphp

<div
    class="dos-cal {{ $display === 'inline' ? 'dos-cal--inline' : 'dos-cal--popover' }}"
    data-jalali-date-picker
    data-display="{{ $display }}"
    data-open="{{ $display === 'inline' ? '1' : '0' }}"
    data-timezone="{{ $tz }}"
    data-auto-submit="{{ $autoSubmit ? '1' : '0' }}"
    data-booking-only="{{ $bookingOnly ? '1' : '0' }}"
    data-disable-unavailable="{{ $disableUnavailable ? '1' : '0' }}"
    data-min="{{ $minValue }}"
    data-max="{{ $max }}"
    data-day-statuses='@json($dayStatuses ?? new \stdClass())'
    {{ $attributes }}
>
    <label class="block space-y-1" for="{{ $fieldId }}">
        <span class="text-sm font-medium text-slate-700">{{ $label }} @if($required)<span class="text-rose-700">*</span>@endif</span>
        <div class="flex gap-2">
            <input
                id="{{ $fieldId }}"
                name="{{ $name }}"
                type="text"
                value="{{ $resolvedValue }}"
                data-cal-input
                inputmode="numeric"
                autocomplete="off"
                placeholder="{{ $todayJalali }}"
                @required($required)
                class="dos-cal-input min-h-11 w-full rounded-xl border border-slate-300 bg-white px-3 text-sm focus:border-teal-700 focus:outline-none focus:ring-2 focus:ring-teal-700/30"
                aria-describedby="{{ $fieldId }}-hint {{ $fieldId }}-live"
            >
            @if ($display === 'popover')
                <button
                    type="button"
                    data-cal-toggle
                    class="inline-flex min-h-11 min-w-11 shrink-0 items-center justify-center rounded-xl border border-slate-300 bg-white text-slate-700 hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-teal-700"
                    aria-expanded="false"
                    aria-controls="{{ $fieldId }}-panel"
                    aria-label="باز کردن تقویم"
                >
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                        <rect x="3" y="5" width="18" height="16" rx="2"></rect>
                        <path d="M3 10h18M8 3v4M16 3v4"></path>
                    </svg>
                </button>
            @endif
        </div>
    </label>

    <div
        id="{{ $fieldId }}-panel"
        class="dos-cal-panel mt-2 rounded-2xl border border-slate-200 bg-white p-3 shadow-sm"
        role="region"
        aria-label="تقویم شمسی"
        @if ($display !== 'inline') hidden @endif
        data-cal-panel
    >
        <div class="mb-3 flex items-center justify-between gap-2">
            <button type="button" data-cal-next class="dos-cal-nav" aria-label="ماه بعد">‹</button>
            <div class="min-w-0 text-center">
                <p id="{{ $fieldId }}-cal-title" data-cal-title class="text-sm font-semibold text-[color:var(--dos-navy)]"></p>
                <button type="button" data-cal-today class="mt-0.5 text-xs font-medium text-teal-800 hover:underline focus:outline-none focus:ring-2 focus:ring-teal-700">برو به امروز</button>
            </div>
            <button type="button" data-cal-prev class="dos-cal-nav" aria-label="ماه قبل">›</button>
        </div>

        <div class="dos-cal-weekdays mb-1 grid grid-cols-7 gap-1 text-center text-[0.7rem] font-medium text-slate-500" aria-hidden="true">
            @foreach (\App\Support\JalaliCalendar::WEEKDAY_LABELS as $weekday)
                <span>{{ $weekday }}</span>
            @endforeach
        </div>

        <div data-cal-grid class="dos-cal-grid grid grid-cols-7 gap-1" role="grid" aria-labelledby="{{ $fieldId }}-cal-title"></div>

        @if ($bookingOnly || $dayStatuses)
            <ul class="mt-3 flex flex-wrap gap-3 text-[0.7rem] text-slate-600" aria-label="راهنمای وضعیت روزها">
                <li class="inline-flex items-center gap-1"><span class="dos-cal-legend dos-cal-legend--open"></span> قابل رزرو</li>
                <li class="inline-flex items-center gap-1"><span class="dos-cal-legend dos-cal-legend--full"></span> پر</li>
                <li class="inline-flex items-center gap-1"><span class="dos-cal-legend dos-cal-legend--closed"></span> تعطیل/بدون شیفت</li>
            </ul>
        @endif
    </div>

    <p id="{{ $fieldId }}-hint" class="mt-1 text-xs leading-5 text-slate-500">{{ $hintText }}</p>
    <p id="{{ $fieldId }}-live" data-cal-live class="sr-only" aria-live="polite"></p>

    @error($name)
        <span class="mt-1 block text-sm text-rose-700">{{ $message }}</span>
    @enderror
</div>
