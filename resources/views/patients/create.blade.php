<x-management-layout title="بیمار جدید">
    <x-page-header title="بیمار جدید" description="موبایل یکتا نیست. اگر شماره تکراری باشد، هشدار داده می‌شود و پرونده جدا می‌ماند." />
    <form method="POST" action="{{ route('patients.store') }}" class="space-y-4 rounded-2xl border border-slate-200 bg-white p-4">
        @csrf
        @include('patients._form')
        <button type="submit" class="inline-flex min-h-11 items-center rounded-lg bg-teal-700 px-4 py-2 text-sm font-semibold text-white hover:bg-teal-800 focus:outline-none focus:ring-2 focus:ring-teal-700">ثبت پرونده</button>
    </form>
</x-management-layout>
