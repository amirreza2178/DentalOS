<x-management-layout title="یونیت جدید">
    <x-page-header title="یونیت جدید" />
    <form method="POST" action="{{ route('chairs.store') }}" class="space-y-4 rounded-2xl border border-slate-200 bg-white p-4">
        @csrf
        @include('chairs._form', ['chair' => null])
        <button type="submit" class="rounded-lg bg-teal-700 px-4 py-2 text-sm font-semibold text-white focus:outline-none focus:ring-2 focus:ring-teal-700">ثبت</button>
    </form>
</x-management-layout>
