<x-management-layout title="جزئیات پیگیری">
    <x-page-header title="پیگیری #{{ $followUp->id }}" description="{{ $followUp->status->label() }} — نوبت قطعی خودکار ساخته نمی‌شود.">
        <a href="{{ route('clinical.follow-ups.index') }}" class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-teal-700">فهرست</a>
        @if ($canSchedule)
            <a href="{{ route('appointments.create', ['follow_up_id' => $followUp->id]) }}" class="rounded-lg bg-teal-700 px-4 py-2 text-sm font-semibold text-white hover:bg-teal-800 focus:outline-none focus:ring-2 focus:ring-teal-700">تبدیل به نوبت</a>
        @endif
    </x-page-header>

    <dl class="mb-4 grid gap-3 rounded-2xl border border-slate-200 bg-white p-4 text-sm md:grid-cols-2">
        <div><dt class="text-xs text-slate-500">بیمار</dt><dd class="font-medium">{{ $followUp->patient?->fullName() }} — {{ $followUp->patient?->code }}</dd></div>
        <div><dt class="text-xs text-slate-500">موبایل</dt><dd>{{ $followUp->patient?->mobile ?: '—' }}</dd></div>
        <div><dt class="text-xs text-slate-500">بازه پیشنهادی</dt><dd>{{ $calendar->formatDate($followUp->proposed_window_start) }} تا {{ $calendar->formatDate($followUp->proposed_window_end) }}</dd></div>
        <div><dt class="text-xs text-slate-500">خدمت</dt><dd>{{ $followUp->treatmentService?->name ?? '—' }}</dd></div>
        <div><dt class="text-xs text-slate-500">ثبت‌کننده</dt><dd>{{ $followUp->createdBy?->name ?? '—' }}</dd></div>
        <div><dt class="text-xs text-slate-500">نوبت تبدیل‌شده</dt><dd>{{ $followUp->converted_appointment_id ? '#'.$followUp->converted_appointment_id : '—' }}</dd></div>
        <div class="md:col-span-2"><dt class="text-xs text-slate-500">یادداشت عملیاتی</dt><dd>{{ $followUp->operational_note ?: '—' }}</dd></div>
        <div class="md:col-span-2"><dt class="text-xs text-slate-500">نتیجه اقدام</dt><dd>{{ $followUp->outcome_note ?: '—' }}</dd></div>
    </dl>

    @if ($canOperate && $followUp->status->canBeScheduled())
        <div class="grid gap-4 md:grid-cols-2">
            <form method="POST" action="{{ route('clinical.follow-ups.offer', $followUp) }}" class="space-y-3 rounded-2xl border border-slate-200 bg-white p-4">
                @csrf
                <h2 class="text-base font-semibold">پیشنهاد به بیمار</h2>
                <x-textarea-field name="outcome_note" label="یادداشت اقدام" :value="old('outcome_note')" />
                <button type="submit" class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-teal-700">علامت پیشنهاد داده‌شده</button>
            </form>
            <form method="POST" action="{{ route('clinical.follow-ups.decline', $followUp) }}" class="space-y-3 rounded-2xl border border-slate-200 bg-white p-4">
                @csrf
                <h2 class="text-base font-semibold">رد پیگیری</h2>
                <x-textarea-field name="outcome_note" label="دلیل رد" :value="old('outcome_note')" />
                <button type="submit" class="rounded-lg border border-rose-300 px-4 py-2 text-sm font-medium text-rose-800 hover:bg-rose-50 focus:outline-none focus:ring-2 focus:ring-rose-600">رد بدون ساخت نوبت</button>
            </form>
        </div>
    @endif
</x-management-layout>
