@props(['name', 'label', 'checked' => false])
@php
    $isChecked = old($name, $checked);
@endphp
<div class="space-y-1">
    <input type="hidden" name="{{ $name }}" value="0">
    <label class="flex items-center gap-2 text-sm text-slate-700" for="{{ $name }}">
        <input id="{{ $name }}" name="{{ $name }}" type="checkbox" value="1" @checked(filter_var($isChecked, FILTER_VALIDATE_BOOLEAN)) class="size-4 rounded border-slate-300 text-teal-700 focus:ring-teal-700">
        <span>{{ $label }}</span>
    </label>
    @error($name)
        <span class="text-sm text-rose-700">{{ $message }}</span>
    @enderror
</div>
