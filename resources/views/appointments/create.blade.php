<x-management-layout title="نوبت داخلی">
    <x-page-header title="نوبت داخلی" description="برای بیمار شناخته‌شده: انتخاب خدمت و تاریخ، سپس تأیید یک زمان آزاد. قواعد با رزرو آنلاین یکی است.">
        <a href="{{ route('reception.day') }}" class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-teal-700">میز پذیرش</a>
    </x-page-header>
    @if (! empty($followUp))
        <x-alert>
            تبدیل پیگیری #{{ $followUp->id }} به نوبت فقط با اقدام مجاز پذیرش انجام می‌شود. بدون این مسیر، نوبت قطعی ساخته نمی‌شود.
        </x-alert>
    @endif
    @include('appointments._finder', [
        'finderAction' => route('appointments.create'),
        'action' => route('appointments.store'),
        'followUp' => $followUp ?? null,
    ])
</x-management-layout>
