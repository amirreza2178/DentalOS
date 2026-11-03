<x-booking-layout title="انتخاب خدمت" :step="$step">
    <header class="mb-6">
        <h1 class="text-2xl font-bold text-[color:var(--dos-navy)]">انتخاب خدمت</h1>
        <p class="mt-1 text-sm leading-6 text-slate-600">فقط خدمات فعال و قابل رزرو آنلاین نمایش داده می‌شوند.</p>
    </header>

    @if ($services->isEmpty())
        <div class="rounded-2xl border border-dashed border-slate-300 bg-white/80 px-5 py-10 text-center">
            <h2 class="text-lg font-semibold text-[color:var(--dos-navy)]">خدمت قابل رزروی نیست</h2>
            <p class="mt-2 text-sm text-slate-600">فعلاً خدمت آنلاینی برای درخواست نوبت تعریف نشده است.</p>
        </div>
    @else
        <ul class="space-y-3">
            @foreach ($services as $service)
                <li>
                    <a href="{{ route('booking.service', $service) }}" class="block rounded-2xl border border-slate-200 bg-white px-5 py-4 shadow-sm transition hover:border-[color:var(--dos-accent)] focus:outline-none focus:ring-2 focus:ring-[color:var(--dos-navy)]">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <h2 class="text-base font-semibold text-[color:var(--dos-navy)]">{{ $service->name }}</h2>
                                @if ($service->description)
                                    <p class="mt-1 text-sm leading-6 text-slate-600">{{ $service->description }}</p>
                                @endif
                            </div>
                            <span class="shrink-0 rounded-lg bg-slate-100 px-2.5 py-1 text-xs font-medium text-slate-700">{{ $service->default_duration_minutes }} دقیقه</span>
                        </div>
                    </a>
                </li>
            @endforeach
        </ul>
    @endif
</x-booking-layout>
