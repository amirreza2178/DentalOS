@props(['name', 'label', 'options' => [], 'value' => null, 'required' => false, 'placeholder' => null])
@php
    $selected = old($name, $value instanceof \BackedEnum ? $value->value : $value);
@endphp
<label class="block space-y-1" for="{{ $name }}">
    <span class="text-sm font-medium text-slate-700">{{ $label }} @if($required)<span class="text-rose-700">*</span>@endif</span>
    <select id="{{ $name }}" name="{{ $name }}" @required($required) {{ $attributes->merge(['class' => 'w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm focus:border-teal-700 focus:outline-none focus:ring-2 focus:ring-teal-700/30']) }}>
        @if ($placeholder !== null)
            <option value="">{{ $placeholder }}</option>
        @endif
        @foreach ($options as $optionValue => $optionLabel)
            <option value="{{ $optionValue }}" @selected((string) $selected === (string) $optionValue)>{{ $optionLabel }}</option>
        @endforeach
    </select>
    @error($name)
        <span class="text-sm text-rose-700">{{ $message }}</span>
    @enderror
</label>
