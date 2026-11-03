<x-booking-layout title="انتخاب خدمت" :step="$step">
    <header class="mb-6">
        <p class="text-sm font-semibold text-teal-800">{{ $clinicIdentity->doctorTitle() }}</p>
        <h1 class="dos-title-lg mt-2">خدمت مورد نظر را انتخاب کنید</h1>
        <p class="dos-body mt-2 text-slate-600">{{ $clinicIdentity->bookingClarification() }}</p>
    </header>

    @if ($services->isEmpty())
        <x-empty-state title="هنوز خدمت قابل رزرو آنلاین باز نیست" description="در حال حاضر خدمت فعالی برای درخواست نوبت آنلاین تعریف نشده است. می‌توانید با کلینیک تماس بگیرید تا زمان مناسب برای شما هماهنگ شود.">
            <div class="space-y-2 text-sm text-slate-700">
                @if ($clinicIdentity->contactPhone())
                    <p>تلفن کلینیک: <span class="font-semibold text-[color:var(--dos-navy)]" dir="ltr">{{ $clinicIdentity->contactPhone() }}</span></p>
                @endif
                <a href="{{ route('home') }}" class="inline-flex min-h-11 items-center justify-center rounded-xl border border-slate-300 bg-white px-4 text-sm font-medium text-[color:var(--dos-navy)] hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-[color:var(--dos-accent)]">بازگشت به صفحه اصلی</a>
            </div>
        </x-empty-state>
    @else
        <ul class="space-y-3">
            @foreach ($services as $service)
                <li>
                    <a href="{{ route('booking.service', $service) }}" class="block rounded-2xl border border-slate-200 bg-white p-5 transition hover:border-[color:var(--dos-navy)] focus:outline-none focus:ring-2 focus:ring-[color:var(--dos-accent)]">
                        <div class="flex flex-wrap items-start justify-between gap-3">
                            <div class="min-w-0">
                                <h2 class="dos-title-md text-[color:var(--dos-navy)]">{{ $service->name }}</h2>
                                @if ($service->description)
                                    <p class="dos-body mt-2 text-slate-600">{{ $service->description }}</p>
                                @endif
                            </div>
                            <p class="dos-meta shrink-0">{{ $service->default_duration_minutes }} دقیقه</p>
                        </div>
                        <p class="mt-3 text-sm font-semibold text-teal-800">ادامه ← انتخاب تاریخ</p>
                    </a>
                </li>
            @endforeach
        </ul>
    @endif
</x-booking-layout>
