<x-management-layout title="ویرایش خدمت">
    <x-page-header title="ویرایش {{ $service->name }}" />
    <form method="POST" action="{{ route('services.update', $service) }}" class="space-y-4 rounded-2xl border border-slate-200 bg-white p-4">
        @csrf
        @method('PUT')
        @include('services._form')
        <button type="submit" class="rounded-lg bg-teal-700 px-4 py-2 text-sm font-semibold text-white focus:outline-none focus:ring-2 focus:ring-teal-700">ذخیره</button>
    </form>
</x-management-layout>
