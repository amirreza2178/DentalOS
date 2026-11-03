<x-management-layout title="نوبت داخلی">
    <x-page-header title="نوبت داخلی" description="نوبت روی یک زمان آزاد، با همان قواعدی که بعداً رزرو آنلاین استفاده می‌کند." />
    @if (! empty($followUp))
        <x-alert>
            تبدیل پیگیری #{{ $followUp->id }} به نوبت فقط با اقدام مجاز پذیرش انجام می‌شود. بدون این مسیر، نوبت قطعی ساخته نمی‌شود.
        </x-alert>
    @endif
    @include('appointments._finder', [
        'finderAction' => route('appointments.create', array_filter([
            'follow_up_id' => $followUp->id ?? null,
            'patient_id' => $patient->id ?? null,
            'service_id' => $service->id ?? null,
        ])),
        'action' => route('appointments.store'),
        'followUp' => $followUp ?? null,
    ])
</x-management-layout>
