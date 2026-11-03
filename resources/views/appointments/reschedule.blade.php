<x-management-layout title="جابه‌جایی نوبت">
    <x-page-header title="جابه‌جایی نوبت" description="نوبت جدید به نوبت قبلی وصل می‌شود. تا وقتی نوبت جدید ثبت نشود، منابع قبلی آزاد نمی‌شوند.">
        <a href="{{ route('appointments.show', $appointment) }}" class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-teal-700">بازگشت</a>
    </x-page-header>
    <p class="mb-4 text-sm text-slate-600">نوبت فعلی: {{ $appointment->patient->fullName() }} — {{ $appointment->treatmentService->name }} — {{ $appointment->status->label() }}</p>
    @include('appointments._finder', [
        'finderAction' => route('appointments.reschedule', $appointment),
        'action' => route('appointments.reschedule.store', $appointment),
    ])
</x-management-layout>
