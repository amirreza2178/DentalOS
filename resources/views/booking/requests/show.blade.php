<x-management-layout title="جزئیات درخواست آنلاین">
    <x-page-header title="جزئیات درخواست آنلاین" description="تأیید منابع را دوباره تخصیص نمی‌کند؛ رد، منابع را آزاد می‌کند.">
        <a href="{{ route('booking.requests.index') }}" class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-teal-700">بازگشت به فهرست</a>
    </x-page-header>

    <div class="grid gap-4 lg:grid-cols-[1.4fr_1fr]">
        <section class="rounded-2xl border border-slate-200 bg-white p-5">
            <h2 class="text-lg font-semibold">خلاصه نوبت</h2>
            <dl class="mt-4 grid gap-3 text-sm sm:grid-cols-2">
                <div>
                    <dt class="text-slate-500">وضعیت</dt>
                    <dd class="font-semibold">{{ $appointment->status->label() }}</dd>
                </div>
                <div>
                    <dt class="text-slate-500">خدمت</dt>
                    <dd class="font-semibold">{{ $appointment->treatmentService?->name }}</dd>
                </div>
                <div>
                    <dt class="text-slate-500">تاریخ و ساعت</dt>
                    <dd class="font-semibold">{{ $calendar->formatDate($appointment->starts_at) }} — {{ $appointment->starts_at->timezone(config('app.timezone'))->format('H:i') }} تا {{ $appointment->ends_at->timezone(config('app.timezone'))->format('H:i') }}</dd>
                </div>
                <div>
                    <dt class="text-slate-500">مدت خدمت</dt>
                    <dd class="font-semibold">{{ $appointment->treatmentService?->default_duration_minutes }} دقیقه</dd>
                </div>
                <div>
                    <dt class="text-slate-500">کد پیگیری عمومی</dt>
                    <dd class="break-all font-mono text-xs">{{ $appointment->public_reference }}</dd>
                </div>
                @if ($appointment->patient_notes)
                    <div class="sm:col-span-2">
                        <dt class="text-slate-500">یادداشت بیمار</dt>
                        <dd class="font-medium">{{ $appointment->patient_notes }}</dd>
                    </div>
                @endif
                @if ($appointment->decision_reason)
                    <div class="sm:col-span-2">
                        <dt class="text-slate-500">دلیل رد</dt>
                        <dd class="font-medium">{{ $appointment->decision_reason }}</dd>
                    </div>
                @endif
                @if ($appointment->approvedBy)
                    <div>
                        <dt class="text-slate-500">تعیین‌تکلیف‌کننده</dt>
                        <dd class="font-medium">{{ $appointment->approvedBy->name }}</dd>
                    </div>
                @endif
            </dl>
        </section>

        <section class="rounded-2xl border border-slate-200 bg-white p-5">
            <h2 class="text-lg font-semibold">اطلاعات تماس</h2>
            <dl class="mt-4 space-y-3 text-sm">
                <div>
                    <dt class="text-slate-500">نام</dt>
                    <dd class="font-semibold">{{ $appointment->patient?->fullName() }}</dd>
                </div>
                <div>
                    <dt class="text-slate-500">موبایل</dt>
                    <dd class="font-semibold dir-ltr text-left">{{ $appointment->patient?->mobile }}</dd>
                </div>
                <div>
                    <dt class="text-slate-500">وضعیت هویت</dt>
                    <dd class="font-semibold">{{ $appointment->patient?->identity_status?->label() }}</dd>
                </div>
                <div>
                    <dt class="text-slate-500">کد پرونده موقت</dt>
                    <dd class="font-semibold">{{ $appointment->patient?->code }}</dd>
                </div>
            </dl>

            @if ($duplicateCount > 0)
                <div class="mt-4 rounded-xl border border-amber-300 bg-amber-50 px-3 py-3 text-sm text-amber-950" role="status">
                    {{ $duplicateCount }} پرونده دیگر با همین موبایل وجود دارد. درخواست آنلاین به‌طور خودکار به آن‌ها وصل نشده است.
                </div>
            @endif

            @if ($appointment->patient?->mobile)
                <div class="mt-4 flex flex-wrap gap-2">
                    <a href="{{ route('patients.index', ['q' => $appointment->patient->mobile]) }}" class="inline-flex min-h-10 items-center rounded-lg border border-slate-300 px-3 text-xs font-medium hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-teal-700">جستجوی پرونده‌ها با این موبایل</a>
                    <a href="{{ route('reception.search', ['q' => $appointment->patient->mobile]) }}" class="inline-flex min-h-10 items-center rounded-lg border border-slate-300 px-3 text-xs font-medium hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-teal-700">جستجو روی میز امروز</a>
                </div>
            @endif
        </section>
    </div>

    @if ($appointment->status === \App\Enums\AppointmentStatus::PendingApproval)
        <div class="mt-4 flex flex-col gap-4 rounded-2xl border border-slate-200 bg-white p-5 sm:flex-row sm:items-start">
            <form method="POST" action="{{ route('booking.requests.approve', $appointment) }}" data-booking-submit>
                @csrf
                <button type="submit" class="min-h-11 rounded-lg bg-teal-700 px-4 py-2 text-sm font-semibold text-white hover:bg-teal-800 focus:outline-none focus:ring-2 focus:ring-teal-700">تأیید درخواست</button>
            </form>

            <form id="reject" method="POST" action="{{ route('booking.requests.reject', $appointment) }}" data-booking-submit class="grow space-y-2">
                @csrf
                <label class="block space-y-1" for="decision_reason">
                    <span class="text-sm font-medium">دلیل رد <span class="text-rose-700">*</span></span>
                    <textarea id="decision_reason" name="decision_reason" rows="2" required minlength="3" maxlength="500" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-teal-700 focus:outline-none focus:ring-2 focus:ring-teal-700/30">{{ old('decision_reason') }}</textarea>
                    @error('decision_reason')
                        <span class="block text-xs text-rose-700">{{ $message }}</span>
                    @enderror
                </label>
                <button type="submit" class="min-h-11 rounded-lg border border-rose-300 px-4 py-2 text-sm font-semibold text-rose-800 hover:bg-rose-50 focus:outline-none focus:ring-2 focus:ring-rose-600">رد درخواست</button>
            </form>
        </div>
    @endif
</x-management-layout>
