<x-booking-layout title="انتخاب تاریخ" :step="$step">
    <header class="mb-6">
        <p class="text-sm text-slate-500">{{ $service->name }} · {{ $service->default_duration_minutes }} دقیقه</p>
        <h1 class="dos-title-lg mt-1">انتخاب تاریخ</h1>
        <p class="dos-body mt-1 text-slate-600">تاریخ مراجعه را از تقویم انتخاب کنید. فقط روزهای قابل رزرو فعال‌اند. انتخاب زمان هنوز نوبت قطعی نیست و پس از بررسی پذیرش نهایی می‌شود.</p>
    </header>

    <div class="mb-4 flex flex-wrap gap-2" role="group" aria-label="تاریخ‌های سریع">
        @foreach ($quickDates as $quick)
            @php $quickStatus = $dayStatuses[$quick['value']] ?? null; @endphp
            @if ($quickStatus === 'open')
                <a href="{{ route('booking.slots', ['service' => $service, 'date' => $quick['value']]) }}" class="inline-flex min-h-11 items-center rounded-xl border border-slate-300 bg-white px-4 text-sm font-medium text-[color:var(--dos-navy)] hover:border-[color:var(--dos-accent)] focus:outline-none focus:ring-2 focus:ring-[color:var(--dos-accent)]">
                    {{ $quick['label'] }}
                    <span class="mr-2 text-xs text-slate-500">{{ $quick['value'] }}</span>
                </a>
            @else
                <span class="inline-flex min-h-11 items-center rounded-xl border border-slate-200 bg-slate-50 px-4 text-sm text-slate-400" title="در این تاریخ زمان آزادی نیست">
                    {{ $quick['label'] }}
                    <span class="mr-2 text-xs">{{ $quickStatus === 'full' ? 'پر' : 'غیرفعال' }}</span>
                </span>
            @endif
        @endforeach
    </div>

    <form method="GET" action="{{ route('booking.slots', $service) }}" class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
        <x-jalali-date-field
            name="date"
            label="تاریخ مراجعه"
            :value="old('date')"
            display="inline"
            :booking-only="true"
            :disable-past="true"
            :day-statuses="$dayStatuses"
            hint="فقط روزهای سبز قابل انتخاب‌اند. پس از انتخاب، زمان‌های آزاد همان روز نمایش داده می‌شود."
            required
        />
        <button type="submit" class="mt-4 inline-flex min-h-11 w-full items-center justify-center rounded-xl bg-[color:var(--dos-navy)] px-4 py-3 text-sm font-semibold text-white hover:opacity-95 focus:outline-none focus:ring-2 focus:ring-[color:var(--dos-accent)] sm:w-auto">مشاهده زمان‌های آزاد</button>
    </form>

    <p class="mt-4">
        <a href="{{ route('booking.services') }}" class="text-sm font-medium text-[color:var(--dos-navy)] underline-offset-2 hover:underline focus:outline-none focus:ring-2 focus:ring-[color:var(--dos-accent)]">بازگشت به خدمات</a>
    </p>
</x-booking-layout>
