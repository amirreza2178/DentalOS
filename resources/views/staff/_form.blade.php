@php
    $account = $account ?? null;
    $profile = $account?->staffProfile;
    $role = \App\Support\StaffDirectory::role($directory);
    $qualificationOptions = collect($qualifications)->mapWithKeys(fn ($item) => [$item->value => $item->label()])->all();
@endphp
<div class="grid gap-4 md:grid-cols-2">
    <x-text-field name="name" label="نام" :value="$account?->name" required />
    <x-text-field name="email" type="email" label="ایمیل" :value="$account?->email" required />
    <x-text-field name="mobile" label="موبایل حساب" :value="$account?->mobile" />
    <x-text-field name="password" type="password" label="{{ $account ? 'رمز جدید' : 'رمز عبور' }}" :required="! $account" autocomplete="new-password" />
    <x-text-field name="password_confirmation" type="password" label="تکرار رمز" :required="! $account" autocomplete="new-password" />
    @if ($role->requiresStaffProfile())
        <x-select-field name="qualification" label="صلاحیت حرفه‌ای" :options="$qualificationOptions" :value="$profile->qualification ?? null" placeholder="انتخاب کنید" required />
        <x-text-field name="title" label="عنوان" :value="$profile->title ?? null" />
        <div class="md:col-span-2">
            <x-textarea-field name="professional_summary" label="خلاصه حرفه‌ای" :value="$profile->professional_summary ?? null" />
        </div>
        @if ($role === \App\Enums\UserRole::Specialist)
            <x-checkbox-field name="can_take_clinical_notes" label="اجازه ثبت یادداشت بالینی" :checked="$profile->can_take_clinical_notes ?? false" />
        @endif
    @endif
    <x-checkbox-field name="is_active" label="حساب فعال است" :checked="$account?->is_active ?? true" />
</div>
<p class="text-xs leading-5 text-slate-500">انتخاب صلاحیت برای کادر بالینی الزامی است و هیچ‌کس به‌صورت خودکار دندان‌پزشک دارای مجوز محسوب نمی‌شود.</p>
