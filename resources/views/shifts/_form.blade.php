@php($shift = $shift ?? null)
<div class="grid gap-4 md:grid-cols-2">
    <x-text-field name="name" label="نام شیفت" :value="$shift?->name" required />
    <x-select-field name="weekday" label="روز هفته" :options="$weekdays" :value="$shift?->weekday" required />
    <x-text-field name="starts_at" type="time" label="شروع" :value="$starts ?? null" required />
    <x-text-field name="ends_at" type="time" label="پایان" :value="$ends ?? null" required />
    <x-checkbox-field name="is_active" label="فعال" :checked="$shift?->is_active ?? true" />
</div>
