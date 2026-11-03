@php
    $patient = $patient ?? null;
    $genderOptions = collect(\App\Enums\Gender::cases())->mapWithKeys(fn ($gender) => [$gender->value => $gender->label()])->all();
    $statusOptions = collect(\App\Enums\PatientStatus::cases())->mapWithKeys(fn ($status) => [$status->value => $status->label()])->all();
@endphp
<div class="grid gap-4 md:grid-cols-2">
    <x-text-field name="first_name" label="نام" :value="$patient?->first_name" required />
    <x-text-field name="last_name" label="نام خانوادگی" :value="$patient?->last_name" required />
    <x-text-field name="code" label="شناسه پرونده" :value="$patient?->code" placeholder="خالی بماند تا خودکار ساخته شود" />
    <x-text-field name="national_id" label="کد ملی" :value="$patient?->national_id" />
    <x-text-field name="mobile" label="موبایل" :value="$patient?->mobile" />
    <x-text-field name="alt_phone" label="شماره تماس دیگر" :value="$patient?->alt_phone" />
    <x-text-field name="birth_date" label="تاریخ تولد" :value="$birthDate ?? null" placeholder="۱۴۰۴/۰۱/۰۱" />
    <x-select-field name="gender" label="جنسیت" :options="$genderOptions" :value="$patient?->gender" placeholder="انتخاب نشده" />
    <x-select-field name="status" label="وضعیت" :options="$statusOptions" :value="$patient?->status ?? \App\Enums\PatientStatus::Active" required />
    <div class="md:col-span-2">
        <x-textarea-field name="notes_admin" label="یادداشت اداری" :value="$patient?->notes_admin" />
        <p class="mt-1 text-xs leading-5 text-slate-500">این یادداشت بالینی نیست و جایگزین طرح درمان نمی‌شود.</p>
    </div>
</div>
