<x-booking-layout title="رزرو نوبت">
    <section class="overflow-hidden rounded-3xl border border-slate-200/80 bg-[color:var(--dos-navy)] text-white shadow-sm">
        <div class="relative px-6 py-14 sm:px-10 sm:py-16">
            <div class="pointer-events-none absolute inset-0 opacity-40" style="background: radial-gradient(circle at 80% 20%, rgba(85,230,208,0.55), transparent 45%);" aria-hidden="true"></div>
            <div class="relative max-w-xl">
                <p class="text-sm font-semibold tracking-wide text-[color:var(--dos-accent)]">DentalOS</p>
                <h1 class="mt-3 text-3xl font-bold leading-tight sm:text-4xl">نوبت خود را ساده و امن درخواست کنید</h1>
                <p class="mt-4 text-sm leading-7 text-slate-300 sm:text-base">خدمت را انتخاب کنید، زمان آزاد واقعی را ببینید و درخواست را برای تأیید پذیرش ثبت کنید.</p>
                <div class="mt-8 flex flex-wrap gap-3">
                    <a href="{{ route('booking.services') }}" class="inline-flex min-h-11 items-center justify-center rounded-xl bg-[color:var(--dos-accent)] px-5 py-3 text-sm font-bold text-[color:var(--dos-navy)] hover:brightness-95 focus:outline-none focus:ring-2 focus:ring-white">شروع درخواست نوبت</a>
                    <a href="{{ route('login') }}" class="inline-flex min-h-11 items-center justify-center rounded-xl border border-white/25 px-5 py-3 text-sm font-medium text-white hover:bg-white/10 focus:outline-none focus:ring-2 focus:ring-[color:var(--dos-accent)]">ورود کارکنان</a>
                </div>
            </div>
        </div>
    </section>
</x-booking-layout>
