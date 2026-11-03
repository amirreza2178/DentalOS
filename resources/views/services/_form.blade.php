@php($service = $service ?? null)
<div class="grid gap-4 md:grid-cols-2">
    <x-text-field name="code" label="کد" :value="$service?->code" required />
    <x-text-field name="name" label="نام" :value="$service?->name" required />
    <x-text-field name="default_duration_minutes" type="number" label="مدت پیش‌فرض (دقیقه)" :value="$service?->default_duration_minutes" required />
    <x-text-field name="buffer_minutes" type="number" label="فاصله بعد از نوبت (دقیقه)" :value="$service?->buffer_minutes ?? 0" />
    <x-text-field name="required_clinical_staff_count" type="number" label="تعداد کادر بالینی" :value="$service?->required_clinical_staff_count ?? 0" />
    <x-text-field name="base_price" label="قیمت پایه اختیاری" :value="$service?->base_price" />
    <x-select-field name="booking_confirmation_mode" label="سیاست تأیید" :options="collect(App\Enums\BookingConfirmationMode::cases())->mapWithKeys(fn ($mode) => [$mode->value => $mode->label()])->all()" :value="$service?->booking_confirmation_mode" placeholder="تابع تنظیم کلینیک" />
    <x-text-field name="required_chair_capabilities" label="قابلیت لازم یونیت" :value="$service?->required_chair_capabilities ? implode('، ', $service->required_chair_capabilities) : null" />
    <x-checkbox-field name="requires_specialist" label="متخصص لازم است" :checked="$service?->requires_specialist ?? false" />
    <x-checkbox-field name="requires_chair" label="یونیت لازم است" :checked="$service?->requires_chair ?? true" />
    <x-checkbox-field name="is_bookable_online" label="قابل رزرو آنلاین" :checked="$service?->is_bookable_online ?? false" />
    <x-checkbox-field name="is_active" label="فعال" :checked="$service?->is_active ?? true" />
    <div class="md:col-span-2">
        <x-textarea-field name="description" label="توضیح" :value="$service?->description" />
        <p class="mt-1 text-xs leading-5 text-slate-500">مدت را خودتان تعیین کنید. سیستم یک ساعت را فرض نمی‌کند، تعداد کادر را ثابت نمی‌گیرد و واحد پول را حدس نمی‌زند. قابلیت یونیت را با ویرگول جدا کنید.</p>
    </div>
</div>
