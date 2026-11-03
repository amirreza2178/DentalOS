<x-management-layout title="تنظیمات کلینیک">
    <x-page-header title="تنظیمات کلینیک" description="فیلدهای خالی به‌معنای تعیین‌نشده است. منطقه زمانی و واحد پول حدس زده نمی‌شوند." />
    <form method="POST" action="{{ route('settings.update') }}" class="space-y-4 rounded-2xl border border-slate-200 bg-white p-4">
        @csrf
        @method('PUT')
        <div class="grid gap-4 md:grid-cols-2">
            <x-text-field name="clinic_name" label="نام کلینیک" :value="$values['clinic_name']" />
            <x-text-field name="contact_phone" label="تلفن تماس" :value="$values['contact_phone']" />
            <x-text-field name="timezone" label="منطقه زمانی کلینیک" :value="$values['timezone']" placeholder="فقط پس از تأیید، مانند Asia/Tehran" />
            <x-text-field name="currency" label="واحد پول" :value="$values['currency']" placeholder="تعیین نشده" />
            <x-select-field
                name="booking_confirmation_mode"
                label="سیاست تأیید نوبت آنلاین"
                :options="collect($modes)->mapWithKeys(fn ($mode) => [$mode->value => $mode->label()])->all()"
                :value="$values['booking_confirmation_mode']"
                placeholder="هنوز انتخاب نشده"
            />
        </div>
        <p class="text-xs leading-5 text-slate-500">منطقه زمانی فعلی پیکربندی برنامه {{ $applicationTimezone }} است و تا تأیید کلینیک به‌عنوان واقعیت کسب‌وکار ثبت نمی‌شود.</p>
        <button type="submit" class="rounded-lg bg-teal-700 px-4 py-2 text-sm font-semibold text-white focus:outline-none focus:ring-2 focus:ring-teal-700">ذخیره تنظیمات</button>
    </form>
</x-management-layout>
