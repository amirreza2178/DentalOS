<x-management-layout title="نوبت داخلی">
    <x-page-header title="نوبت داخلی" description="نوبت روی یک زمان آزاد، با همان قواعدی که بعداً رزرو آنلاین استفاده می‌کند." />
    @include('appointments._finder', [
        'finderAction' => route('appointments.create'),
        'action' => route('appointments.store'),
    ])
</x-management-layout>
