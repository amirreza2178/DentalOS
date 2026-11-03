@props(['title', 'description' => null])
<div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
    <div>
        <h1 class="text-2xl font-bold tracking-tight text-slate-950">{{ $title }}</h1>
        @if ($description)
            <p class="mt-1 max-w-2xl text-sm leading-6 text-slate-600">{{ $description }}</p>
        @endif
    </div>
    @if (trim($slot) !== '')
        <div class="flex flex-wrap gap-2">{{ $slot }}</div>
    @endif
</div>
