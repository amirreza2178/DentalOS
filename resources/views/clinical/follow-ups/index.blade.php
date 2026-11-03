<x-management-layout title="پیگیری‌ها">
    <x-page-header title="پیگیری‌ها" description="پیگیری یک درخواست عملیاتی است، نه نوبت قطعی.">
        <form method="GET" class="flex items-center gap-2 text-sm">
            <label class="inline-flex items-center gap-2">
                <input type="checkbox" name="actionable" value="1" @checked($actionable) class="size-4 rounded border-slate-300 text-teal-700" onchange="this.form.submit()">
                فقط نیازمند اقدام
            </label>
        </form>
    </x-page-header>

    @if ($followUps->isEmpty())
        <x-empty-state title="پیگیری‌ای برای نمایش نیست" />
    @else
        <ul class="space-y-2 text-sm">
            @foreach ($followUps as $item)
                <li class="rounded-2xl border border-slate-200 bg-white px-4 py-3">
                    <a class="font-semibold text-teal-800 hover:underline focus:outline-none focus:ring-2 focus:ring-teal-700" href="{{ route('clinical.follow-ups.show', $item) }}">
                        {{ $item->patient?->fullName() }} — {{ $item->status->label() }}
                    </a>
                    <p class="mt-1 text-slate-600">
                        بازه {{ $calendar->formatDate($item->proposed_window_start) }} تا {{ $calendar->formatDate($item->proposed_window_end) }}
                        · خدمت {{ $item->treatmentService?->name ?? 'نامشخص' }}
                    </p>
                    @if ($item->operational_note)
                        <p class="mt-1 text-slate-500">{{ $item->operational_note }}</p>
                    @endif
                </li>
            @endforeach
        </ul>
        <div class="mt-4">{{ $followUps->links() }}</div>
    @endif
</x-management-layout>
