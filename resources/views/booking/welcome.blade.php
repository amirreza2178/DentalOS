<x-booking-layout title="رزرو نوبت">
    <section class="overflow-hidden rounded-3xl border border-slate-200/80 bg-[color:var(--dos-navy)] text-white">
        <div class="relative px-6 py-14 sm:px-10 sm:py-16">
            <div class="dos-hero-glow pointer-events-none absolute inset-0 opacity-40" aria-hidden="true"></div>
            <div class="relative max-w-xl">
                <p class="text-sm font-semibold tracking-wide text-[color:var(--dos-accent)]">{{ $clinicIdentity->doctorTitle() }}</p>
                <h1 class="dos-title-xl mt-3 text-white">{{ $clinicIdentity->doctorDisplayName() }}</h1>
                <p class="mt-3 text-base font-medium text-white/90 sm:text-lg">{{ $clinicIdentity->publicNavBrand() }}</p>
                <p class="dos-body mt-4 text-slate-300">{{ $clinicIdentity->introduction() }}</p>
                <p class="dos-body mt-5 text-slate-200/95">{{ $clinicIdentity->bookingClarification() }}</p>
                <div class="mt-8">
                    <a href="{{ route('booking.services') }}" class="inline-flex min-h-11 items-center justify-center rounded-xl bg-[color:var(--dos-accent)] px-5 py-3 text-sm font-bold text-[color:var(--dos-navy)] hover:brightness-95 focus:outline-none focus:ring-2 focus:ring-white">شروع درخواست نوبت</a>
                </div>
            </div>
        </div>
    </section>

    <section class="mt-8" aria-labelledby="booking-path-heading">
        <h2 id="booking-path-heading" class="text-base font-semibold text-[color:var(--dos-navy)]">مسیر درخواست نوبت</h2>
        <p class="mt-1 text-sm leading-6 text-slate-600">سه گام شفاف تا بررسی پذیرش؛ بدون ساخت حساب و بدون قطعی‌شدن خودکار.</p>
        <ol class="mt-5 divide-y divide-slate-200 overflow-hidden rounded-2xl border border-slate-200 bg-white">
            <li class="flex gap-4 px-4 py-4 sm:px-5">
                <span class="mt-0.5 text-sm font-bold tabular-nums text-[color:var(--dos-navy)]">۱</span>
                <div>
                    <p class="text-sm font-semibold text-[color:var(--dos-navy)]">انتخاب خدمت</p>
                    <p class="mt-1 text-sm leading-6 text-slate-600">خدمت قابل رزرو آنلاین را انتخاب کنید.</p>
                </div>
            </li>
            <li class="flex gap-4 px-4 py-4 sm:px-5">
                <span class="mt-0.5 text-sm font-bold tabular-nums text-[color:var(--dos-navy)]">۲</span>
                <div>
                    <p class="text-sm font-semibold text-[color:var(--dos-navy)]">زمان واقعی</p>
                    <p class="mt-1 text-sm leading-6 text-slate-600">فقط بازه‌های آزاد همان لحظه نمایش داده می‌شود.</p>
                </div>
            </li>
            <li class="flex gap-4 px-4 py-4 sm:px-5">
                <span class="mt-0.5 text-sm font-bold tabular-nums text-[color:var(--dos-navy)]">۳</span>
                <div>
                    <p class="text-sm font-semibold text-[color:var(--dos-navy)]">بررسی پذیرش</p>
                    <p class="mt-1 text-sm leading-6 text-slate-600">پس از ارسال، پذیرش درخواست را بررسی می‌کند؛ تا تأیید، نوبت قطعی نیست.</p>
                </div>
            </li>
        </ol>
    </section>

    @if ($clinicIdentity->contactPhone() || $clinicIdentity->address())
        <aside class="mt-8 border-t border-slate-200 pt-6 text-sm text-slate-700" aria-label="اطلاعات تماس">
            @if ($clinicIdentity->contactPhone())
                <p>تلفن: <span class="font-semibold text-[color:var(--dos-navy)]" dir="ltr">{{ $clinicIdentity->contactPhone() }}</span></p>
            @endif
            @if ($clinicIdentity->address())
                <p class="mt-1 leading-6">آدرس: {{ $clinicIdentity->address() }}</p>
            @endif
        </aside>
    @endif
</x-booking-layout>
