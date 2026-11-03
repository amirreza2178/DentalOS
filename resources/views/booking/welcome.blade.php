<x-booking-layout title="رزرو نوبت">
    <section class="overflow-hidden rounded-3xl border border-slate-200/80 bg-[color:var(--dos-navy)] text-white shadow-sm">
        <div class="relative px-6 py-14 sm:px-10 sm:py-16">
            <div class="dos-hero-glow pointer-events-none absolute inset-0 opacity-40" aria-hidden="true"></div>
            <div class="relative max-w-xl">
                <p class="text-sm font-semibold tracking-wide text-[color:var(--dos-accent)]">{{ $clinicIdentity->doctorTitle() }}</p>
                <h1 class="mt-3 text-3xl font-bold leading-tight sm:text-4xl">{{ $clinicIdentity->clinicDisplayName() }}</h1>
                <p class="mt-2 text-lg font-medium text-white/90">{{ $clinicIdentity->doctorDisplayName() }}</p>
                <p class="mt-4 text-sm leading-7 text-slate-300 sm:text-base">{{ $clinicIdentity->introduction() }}</p>
                <p class="mt-4 rounded-xl border border-white/15 bg-white/5 px-4 py-3 text-sm leading-7 text-slate-200">{{ $clinicIdentity->bookingClarification() }}</p>
                <div class="mt-8 flex flex-wrap gap-3">
                    <a href="{{ route('booking.services') }}" class="inline-flex min-h-11 items-center justify-center rounded-xl bg-[color:var(--dos-accent)] px-5 py-3 text-sm font-bold text-[color:var(--dos-navy)] hover:brightness-95 focus:outline-none focus:ring-2 focus:ring-white">شروع درخواست نوبت</a>
                    <a href="{{ route('login') }}" class="inline-flex min-h-11 items-center justify-center rounded-xl border border-white/25 px-5 py-3 text-sm font-medium text-white hover:bg-white/10 focus:outline-none focus:ring-2 focus:ring-[color:var(--dos-accent)]">ورود کارکنان</a>
                </div>
            </div>
        </div>
    </section>

    <section class="mt-6 grid gap-4 sm:grid-cols-3">
        <div class="rounded-2xl border border-slate-200 bg-white/80 p-4">
            <p class="text-sm font-semibold text-[color:var(--dos-navy)]">۱. انتخاب خدمت</p>
            <p class="mt-2 text-sm leading-6 text-slate-600">خدمت قابل رزرو آنلاین را انتخاب کنید.</p>
        </div>
        <div class="rounded-2xl border border-slate-200 bg-white/80 p-4">
            <p class="text-sm font-semibold text-[color:var(--dos-navy)]">۲. زمان واقعی</p>
            <p class="mt-2 text-sm leading-6 text-slate-600">فقط بازه‌های آزاد همان لحظه نمایش داده می‌شود.</p>
        </div>
        <div class="rounded-2xl border border-slate-200 bg-white/80 p-4">
            <p class="text-sm font-semibold text-[color:var(--dos-navy)]">۳. تأیید پذیرش</p>
            <p class="mt-2 text-sm leading-6 text-slate-600">پس از ارسال، پذیرش درخواست را بررسی می‌کند؛ قطعی‌شدن خودکار نیست.</p>
        </div>
    </section>

    @if ($clinicIdentity->contactPhone() || $clinicIdentity->address())
        <aside class="mt-6 rounded-2xl border border-slate-200 bg-white/80 p-4 text-sm text-slate-700">
            @if ($clinicIdentity->contactPhone())
                <p>تلفن: <span class="font-semibold text-[color:var(--dos-navy)]" dir="ltr">{{ $clinicIdentity->contactPhone() }}</span></p>
            @endif
            @if ($clinicIdentity->address())
                <p class="mt-1 leading-6">آدرس: {{ $clinicIdentity->address() }}</p>
            @endif
        </aside>
    @endif
</x-booking-layout>
