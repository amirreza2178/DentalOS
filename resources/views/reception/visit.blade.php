<x-management-layout title="جزئیات ویزیت">
    <x-page-header title="ویزیت {{ $visit->patient?->fullName() }}" description="{{ $visit->status->label() }} — ویزیت از نوبت و صف جدا است.">
        <a href="{{ route('reception.day') }}" class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-teal-700">میز پذیرش</a>
        @if ($visit->appointment)
            <a href="{{ route('appointments.show', $visit->appointment) }}" class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-teal-700">نوبت</a>
        @endif
    </x-page-header>

    <dl class="mb-4 grid gap-3 rounded-2xl border border-slate-200 bg-white p-4 text-sm md:grid-cols-2">
        <div><dt class="text-xs text-slate-500">بیمار</dt><dd class="font-medium">{{ $visit->patient?->fullName() }} — {{ $visit->patient?->code }}</dd></div>
        <div><dt class="text-xs text-slate-500">وضعیت ویزیت</dt><dd class="font-medium">{{ $visit->status->label() }}</dd></div>
        <div><dt class="text-xs text-slate-500">خدمت</dt><dd>{{ $visit->appointment?->treatmentService?->name ?? '—' }}</dd></div>
        <div><dt class="text-xs text-slate-500">چک‌این‌کننده</dt><dd>{{ $visit->checkedInBy?->name ?? '—' }}</dd></div>
        <div><dt class="text-xs text-slate-500">ورود</dt><dd>{{ $visit->arrived_at ? $calendar->formatDate($visit->arrived_at).' '.$clinicDay->formatClock($visit->arrived_at) : '—' }}</dd></div>
        <div><dt class="text-xs text-slate-500">شروع / اتمام</dt><dd>{{ $visit->started_at ? $clinicDay->formatClock($visit->started_at) : '—' }} / {{ $visit->completed_at ? $clinicDay->formatClock($visit->completed_at) : '—' }}</dd></div>
    </dl>

    <div class="mb-4 flex flex-wrap gap-2">
        @if ($canOperate && $visit->status->canTransitionTo(\App\Enums\VisitStatus::InProgress))
            <form method="POST" action="{{ route('reception.visits.start', $visit) }}">
                @csrf
                <button type="submit" class="rounded-lg bg-teal-700 px-4 py-2 text-sm font-semibold text-white hover:bg-teal-800 focus:outline-none focus:ring-2 focus:ring-teal-700">شروع خدمت</button>
            </form>
        @endif
        @if ($canComplete && $visit->status->canTransitionTo(\App\Enums\VisitStatus::Completed))
            <form method="POST" action="{{ route('reception.visits.complete', $visit) }}">
                @csrf
                <button type="submit" class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-teal-700">اتمام ویزیت</button>
            </form>
        @endif
    </div>

    <section aria-labelledby="queue-history-heading">
        <h2 id="queue-history-heading" class="mb-2 text-base font-semibold">ورودی‌های صف</h2>
        @if ($visit->queueEntries->isEmpty())
            <x-empty-state title="ورودی صف ثبت نشده" />
        @else
            <ul class="space-y-2 text-sm">
                @foreach ($visit->queueEntries->sortByDesc('id') as $entry)
                    <li class="rounded-xl border border-slate-200 bg-white px-4 py-3">
                        ردیف {{ $entry->position }} — {{ $entry->status->label() }}
                        <span class="text-slate-500"> · تاریخ صف {{ $calendar->formatDate($entry->queue_date) }}</span>
                    </li>
                @endforeach
            </ul>
        @endif
    </section>
</x-management-layout>
