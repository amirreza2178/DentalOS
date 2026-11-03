<x-booking-layout title="انتخاب خدمت" :step="$step">
    <header class="mb-6">
        <p class="text-sm font-semibold text-teal-800">{{ $clinicIdentity->doctorDisplayName() }} · {{ $clinicIdentity->doctorTitle() }}</p>
        <h1 class="mt-2 text-2xl font-bold text-[color:var(--dos-navy)]">خدمت مورد نظر را انتخاب کنید</h1>
        <p class="mt-2 text-sm leading-7 text-slate-600">{{ $clinicIdentity->bookingClarification() }}</p>
    </header>

    @if ($services->isEmpty())
        <x-empty-state title="خدمت آنلاین فعالی نیست" description="در حال حاضر خدمت قابل رزرو آنلاین تعریف نشده است." />
    @else
        <ul class="space-y-3">
            @foreach ($services as $service)
                <li>
                    <a href="{{ route('booking.service', $service) }}" class="block rounded-2xl border border-slate-200 bg-white p-5 transition hover:border-[color:var(--dos-navy)] focus:outline-none focus:ring-2 focus:ring-[color:var(--dos-accent)]">
                        <div class="flex flex-wrap items-start justify-between gap-3">
                            <div class="min-w-0">
                                <h2 class="text-lg font-semibold text-[color:var(--dos-navy)]">{{ $service->name }}</h2>
                                @if ($service->description)
                                    <p class="mt-2 text-sm leading-6 text-slate-600">{{ $service->description }}</p>
                                @endif
                            </div>
                            <p class="shrink-0 text-sm font-medium text-slate-500">{{ $service->default_duration_minutes }} دقیقه</p>
                        </div>
                    </a>
                </li>
            @endforeach
        </ul>
    @endif
</x-booking-layout>
