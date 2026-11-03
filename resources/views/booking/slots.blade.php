<x-booking-layout title="انتخاب ساعت" :step="$step">
    <header class="mb-6">
        <p class="text-sm text-slate-500">{{ $service->name }}</p>
        <h1 class="mt-1 text-2xl font-bold text-[color:var(--dos-navy)]">زمان‌های آزاد</h1>
        <p class="mt-1 text-sm leading-6 text-slate-600">
            @if ($gregorian)
                تاریخ {{ $calendar->formatDate($gregorian) }}
            @else
                تاریخ را اصلاح کنید.
            @endif
        </p>
    </header>

    @if ($dateError)
        <div class="mb-4 rounded-xl border border-amber-300 bg-amber-50 px-4 py-3 text-sm text-amber-950" role="alert">{{ $dateError }}</div>
    @endif

    <form method="GET" action="{{ route('booking.slots', $service) }}" class="mb-5 flex flex-wrap items-end gap-3 rounded-2xl border border-slate-200 bg-white p-4">
        <label class="block grow space-y-1" for="date">
            <span class="text-sm font-medium">تاریخ جلالی</span>
            <input id="date" name="date" value="{{ $date }}" required inputmode="numeric" class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm focus:border-[color:var(--dos-navy)] focus:outline-none focus:ring-2 focus:ring-[color:var(--dos-accent)]/40">
        </label>
        <button type="submit" class="min-h-11 rounded-xl border border-slate-300 px-4 py-2.5 text-sm font-medium hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-[color:var(--dos-navy)]">به‌روزرسانی</button>
    </form>

    @if ($gregorian && $slots === [])
        <div class="rounded-2xl border border-dashed border-slate-300 bg-white/80 px-5 py-10 text-center">
            <h2 class="text-lg font-semibold text-[color:var(--dos-navy)]">زمان آزادی نیست</h2>
            <p class="mt-2 text-sm text-slate-600">برای این تاریخ زمان آزادی پیدا نشد. تاریخ دیگری را امتحان کنید.</p>
        </div>
    @elseif ($slots !== [])
        <ul class="grid grid-cols-2 gap-3 sm:grid-cols-3" role="list">
            @foreach ($slots as $slot)
                <li>
                    <a href="{{ route('booking.form', ['service' => $service, 'date' => $date, 'time' => $slot->startsAt->format('H:i:s')]) }}" class="flex min-h-12 items-center justify-center rounded-xl border border-slate-200 bg-white text-sm font-semibold text-[color:var(--dos-navy)] shadow-sm hover:border-[color:var(--dos-accent)] focus:outline-none focus:ring-2 focus:ring-[color:var(--dos-navy)]">
                        {{ $slot->startsAt->format('H:i') }}
                        <span class="sr-only">تا {{ $slot->endsAt->format('H:i') }}</span>
                    </a>
                </li>
            @endforeach
        </ul>
    @endif

    <p class="mt-6">
        <a href="{{ route('booking.service', $service) }}" class="text-sm font-medium text-[color:var(--dos-navy)] underline-offset-2 hover:underline focus:outline-none focus:ring-2 focus:ring-[color:var(--dos-accent)]">بازگشت به تاریخ</a>
    </p>
</x-booking-layout>
