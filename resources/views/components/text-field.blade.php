@props(['name', 'label', 'type' => 'text', 'value' => null, 'required' => false])
<label class="block space-y-1" for="{{ $name }}">
    <span class="text-sm font-medium text-slate-700">{{ $label }} @if($required)<span class="text-rose-700">*</span>@endif</span>
    <input
        id="{{ $name }}"
        name="{{ $name }}"
        type="{{ $type }}"
        value="{{ old($name, $value) }}"
        @required($required)
        {{ $attributes->merge(['class' => 'w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm focus:border-teal-700 focus:outline-none focus:ring-2 focus:ring-teal-700/30']) }}
    >
    @error($name)
        <span class="text-sm text-rose-700">{{ $message }}</span>
    @enderror
</label>
