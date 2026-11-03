<x-booking-layout title="اطلاعات تماس" :step="$step">
    <header class="mb-6">
        <h1 class="text-2xl font-bold text-[color:var(--dos-navy)]">اطلاعات تماس و تأیید</h1>
        <p class="mt-1 text-sm leading-6 text-slate-600">فقط اطلاعات لازم برای تماس پذیرش. اطلاعات پزشکی در این مرحله گرفته نمی‌شود.</p>
    </header>

    <div class="mb-5 rounded-2xl border border-slate-200 bg-white p-4 text-sm">
        <dl class="grid gap-2 sm:grid-cols-2">
            <div>
                <dt class="text-slate-500">خدمت</dt>
                <dd class="font-semibold text-[color:var(--dos-navy)]">{{ $service->name }}</dd>
            </div>
            <div>
                <dt class="text-slate-500">مدت</dt>
                <dd class="font-semibold text-[color:var(--dos-navy)]">{{ $service->default_duration_minutes }} دقیقه</dd>
            </div>
            <div>
                <dt class="text-slate-500">تاریخ</dt>
                <dd class="font-semibold text-[color:var(--dos-navy)]">{{ $calendar->formatDate($gregorian) }}</dd>
            </div>
            <div>
                <dt class="text-slate-500">ساعت</dt>
                <dd class="font-semibold text-[color:var(--dos-navy)]">{{ $time }} تا {{ $endsAt->format('H:i') }}</dd>
            </div>
        </dl>
    </div>

    <form method="POST" action="{{ route('booking.store') }}" data-booking-submit class="space-y-4 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
        @csrf
        <input type="hidden" name="treatment_service_id" value="{{ $service->id }}">
        <input type="hidden" name="appointment_date" value="{{ $date }}">
        <input type="hidden" name="appointment_time" value="{{ $timeValue }}">
        <input type="hidden" name="idempotency_key" value="{{ old('idempotency_key', $idempotencyKey) }}">

        <div class="grid gap-4 sm:grid-cols-2">
            <label class="block space-y-1" for="first_name">
                <span class="text-sm font-medium">نام</span>
                <input id="first_name" name="first_name" value="{{ old('first_name') }}" required maxlength="80" autocomplete="given-name" class="w-full rounded-xl border border-slate-300 px-3 py-3 text-sm focus:border-[color:var(--dos-navy)] focus:outline-none focus:ring-2 focus:ring-[color:var(--dos-accent)]/40">
            </label>
            <label class="block space-y-1" for="last_name">
                <span class="text-sm font-medium">نام خانوادگی</span>
                <input id="last_name" name="last_name" value="{{ old('last_name') }}" required maxlength="80" autocomplete="family-name" class="w-full rounded-xl border border-slate-300 px-3 py-3 text-sm focus:border-[color:var(--dos-navy)] focus:outline-none focus:ring-2 focus:ring-[color:var(--dos-accent)]/40">
            </label>
        </div>

        <label class="block space-y-1" for="mobile">
            <span class="text-sm font-medium">شماره موبایل</span>
            <input id="mobile" name="mobile" value="{{ old('mobile') }}" required maxlength="20" inputmode="tel" autocomplete="tel" placeholder="0912xxxxxxx" class="w-full rounded-xl border border-slate-300 px-3 py-3 text-sm focus:border-[color:var(--dos-navy)] focus:outline-none focus:ring-2 focus:ring-[color:var(--dos-accent)]/40">
        </label>

        <label class="block space-y-1" for="patient_notes">
            <span class="text-sm font-medium">یادداشت کوتاه برای پذیرش <span class="font-normal text-slate-500">(اختیاری)</span></span>
            <textarea id="patient_notes" name="patient_notes" rows="3" maxlength="300" class="w-full rounded-xl border border-slate-300 px-3 py-3 text-sm focus:border-[color:var(--dos-navy)] focus:outline-none focus:ring-2 focus:ring-[color:var(--dos-accent)]/40">{{ old('patient_notes') }}</textarea>
        </label>

        <p class="text-xs leading-5 text-slate-500">{{ $clinicIdentity->bookingClarification() }} این فرم حساب کاربری نمی‌سازد و به پرونده پزشکی موجود وصل نمی‌شود.</p>

        <button type="submit" class="inline-flex min-h-11 w-full items-center justify-center rounded-xl bg-[color:var(--dos-navy)] px-4 py-3 text-sm font-semibold text-white hover:opacity-95 focus:outline-none focus:ring-2 focus:ring-[color:var(--dos-accent)] disabled:cursor-not-allowed disabled:opacity-60 sm:w-auto">
            ثبت درخواست نوبت
        </button>
    </form>

    <p class="mt-4">
        <a href="{{ route('booking.slots', ['service' => $service, 'date' => $date]) }}" class="text-sm font-medium text-[color:var(--dos-navy)] underline-offset-2 hover:underline focus:outline-none focus:ring-2 focus:ring-[color:var(--dos-accent)]">بازگشت به زمان‌ها</a>
    </p>
</x-booking-layout>
