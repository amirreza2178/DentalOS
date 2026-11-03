@props(['title', 'description' => null])
<div class="rounded-2xl border border-dashed border-slate-300 bg-white px-6 py-10 text-center">
    <h2 class="text-base font-semibold text-slate-800">{{ $title }}</h2>
    @if ($description)
        <p class="mx-auto mt-2 max-w-lg text-sm leading-6 text-slate-600">{{ $description }}</p>
    @endif
    @if (trim($slot) !== '')
        <div class="mt-4">{{ $slot }}</div>
    @endif
</div>
