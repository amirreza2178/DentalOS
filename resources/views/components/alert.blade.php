@props(['tone' => 'success'])
@php
    $classes = $tone === 'warning'
        ? 'border-amber-300 bg-amber-50 text-amber-950'
        : 'border-teal-300 bg-teal-50 text-teal-950';
@endphp
<div {{ $attributes->merge(['class' => "mb-4 rounded-xl border px-4 py-3 text-sm {$classes}", 'role' => 'status']) }}>
    {{ $slot }}
</div>
