@php($chair = $chair ?? null)
<div class="grid gap-4 md:grid-cols-2">
    <x-text-field name="code" label="کد" :value="$chair?->code" required />
    <x-text-field name="name" label="نام" :value="$chair?->name" required />
    <x-text-field name="sort_order" type="number" label="ترتیب نمایش" :value="$chair?->sort_order ?? 0" />
    <x-text-field name="capabilities" label="قابلیت‌ها" :value="$chair?->capabilities ? implode('، ', $chair->capabilities) : null" />
    <x-checkbox-field name="is_active" label="فعال" :checked="$chair?->is_active ?? true" />
    <div class="md:col-span-2">
        <x-textarea-field name="description" label="توضیح" :value="$chair?->description" />
    </div>
</div>
