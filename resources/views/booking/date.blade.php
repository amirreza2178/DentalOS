<x-booking-layout title="انتخاب تاریخ" :step="$step">
    <header class="mb-6">
        <p class="text-sm text-slate-500">{{ $service->name }} · {{ $service->default_duration_minutes }} دقیقه</p>
        <h1 class="mt-1 text-2xl font-bold text-[color:var(--dos-navy)]">انتخاب تاریخ</h1>
        <p class="mt-1 text-sm leading-6 text-slate-600">تاریخ مراجعه را انتخاب کنید. انتخاب زمان هنوز نوبت قطعی نیست و پس از بررسی پذیرش نهایی می‌شود.</p>
    </header>

    <div class="mb-4 flex flex-wrap gap-2" role="group" aria-label="تاریخ‌های سریع">
        @foreach ($quickDates as $quick)
            <a href="{{ route('booking.slots', ['service' => $service, 'date' => $quick['value']]) }}" class="inline-flex min-h-11 items-center rounded-xl border border-slate-300 bg-white px-4 text-sm font-medium text-[color:var(--dos-navy)] hover:border-[color:var(--dos-accent)] focus:outline-none focus:ring-2 focus:ring-[color:var(--dos-accent)]">
                {{ $quick['label'] }}
                <span class="mr-2 text-xs text-slate-500">{{ $quick['value'] }}</span>
            </a>
        @endforeach
    </div>

    <form method="GET" action="{{ route('booking.slots', $service) }}" class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
        <label class="block space-y-2" for="date">
            <span class="text-sm font-medium">تاریخ جلالی دیگر</span>
            <input id="date" name="date" value="{{ old('date') }}" placeholder="{{ $todayJalali }}" inputmode="numeric" autocomplete="off" class="w-full rounded-xl border border-slate-300 px-3 py-3 text-sm focus:border-[color:var(--dos-navy)] focus:outline-none focus:ring-2 focus:ring-[color:var(--dos-accent)]/40" aria-describedby="date-hint">
            <span id="date-hint" class="block text-xs leading-5 text-slate-500">قالب: ۱۴۰۴/۰۸/۱۲ — می‌توانید از دکمه‌های بالا هم استفاده کنید.</span>
        </label>
        <button type="submit" class="mt-4 inline-flex min-h-11 w-full items-center justify-center rounded-xl bg-[color:var(--dos-navy)] px-4 py-3 text-sm font-semibold text-white hover:opacity-95 focus:outline-none focus:ring-2 focus:ring-[color:var(--dos-accent)] sm:w-auto">مشاهده زمان‌های آزاد</button>
    </form>

    <p class="mt-4">
        <a href="{{ route('booking.services') }}" class="text-sm font-medium text-[color:var(--dos-navy)] underline-offset-2 hover:underline focus:outline-none focus:ring-2 focus:ring-[color:var(--dos-accent)]">بازگشت به خدمات</a>
    </p>
</x-booking-layout>
