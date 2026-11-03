<x-management-layout :title="'حساب '.$title">
    <x-page-header :title="'حساب جدید '.$title" />
    <form method="POST" action="{{ route('staff.store', $directory) }}" class="space-y-4 rounded-2xl border border-slate-200 bg-white p-4">
        @csrf
        @include('staff._form', ['account' => null])
        <button type="submit" class="rounded-lg bg-teal-700 px-4 py-2 text-sm font-semibold text-white hover:bg-teal-800 focus:outline-none focus:ring-2 focus:ring-teal-700">ثبت حساب</button>
    </form>
</x-management-layout>
