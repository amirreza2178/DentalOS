<x-booking-layout title="نتیجه درخواست" :step="$step">
    <section class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm sm:p-8" role="status">
        <p class="text-sm font-semibold text-[color:var(--dos-accent)]" style="color:#0B8F7E">درخواست ثبت شد</p>
        <h1 class="mt-2 text-2xl font-bold text-[color:var(--dos-navy)]">درخواست نوبت شما دریافت شد</h1>
        <p class="mt-3 text-sm leading-7 text-slate-600">پذیرش کلینیک درخواست را بررسی می‌کند و در صورت تأیید، نوبت قطعی می‌شود. کد پیگیری را نگه دارید.</p>

        <dl class="mt-6 grid gap-3 rounded-2xl bg-slate-50 p-4 text-sm sm:grid-cols-2">
            <div>
                <dt class="text-slate-500">کد پیگیری</dt>
                <dd class="mt-1 break-all font-mono text-xs font-semibold text-[color:var(--dos-navy)] sm:text-sm">{{ $appointment->public_reference }}</dd>
            </div>
            <div>
                <dt class="text-slate-500">وضعیت</dt>
                <dd class="mt-1 font-semibold text-[color:var(--dos-navy)]">{{ $appointment->status->label() }}</dd>
            </div>
            <div>
                <dt class="text-slate-500">خدمت</dt>
                <dd class="mt-1 font-semibold text-[color:var(--dos-navy)]">{{ $appointment->treatmentService?->name }}</dd>
            </div>
            <div>
                <dt class="text-slate-500">زمان</dt>
                <dd class="mt-1 font-semibold text-[color:var(--dos-navy)]">{{ $calendar->formatDate($appointment->starts_at) }} — {{ $appointment->starts_at->timezone(config('app.timezone'))->format('H:i') }}</dd>
            </div>
            <div>
                <dt class="text-slate-500">نام</dt>
                <dd class="mt-1 font-semibold text-[color:var(--dos-navy)]">{{ $appointment->patient?->fullName() }}</dd>
            </div>
        </dl>

        <div class="mt-6 flex flex-wrap gap-3">
            <a href="{{ route('booking.services') }}" class="inline-flex min-h-11 items-center justify-center rounded-xl bg-[color:var(--dos-navy)] px-4 py-3 text-sm font-semibold text-white focus:outline-none focus:ring-2 focus:ring-[color:var(--dos-accent)]">درخواست جدید</a>
            <a href="{{ route('home') }}" class="inline-flex min-h-11 items-center justify-center rounded-xl border border-slate-300 px-4 py-3 text-sm font-medium focus:outline-none focus:ring-2 focus:ring-[color:var(--dos-navy)]">بازگشت به صفحه اصلی</a>
        </div>
    </section>
</x-booking-layout>
