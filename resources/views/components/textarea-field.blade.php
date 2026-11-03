@props(['name', 'label', 'value' => null, 'required' => false])
<label class="block space-y-1" for="{{ $name }}">
    <span class="text-sm font-medium text-slate-700">{{ $label }} @if($required)<span class="text-rose-700">*</span>@endif</span>
    <textarea id="{{ $name }}" name="{{ $name }}" rows="4" @required($required) {{ $attributes->merge(['class' => 'w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm focus:border-teal-700 focus:outline-none focus:ring-2 focus:ring-teal-700/30']) }}>{{ old($name, $value) }}</textarea>
    @error($name)
        <span class="text-sm text-rose-700">{{ $message }}</span>
    @enderror
</label>
