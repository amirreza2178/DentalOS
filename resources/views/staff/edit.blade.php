<x-management-layout :title="'ویرایش '.$account->name">
    <x-page-header :title="'ویرایش '.$account->name" />
    <form method="POST" action="{{ route('staff.update', [$directory, $account]) }}" class="space-y-4 rounded-2xl border border-slate-200 bg-white p-4">
        @csrf
        @method('PUT')
        @include('staff._form')
        <button type="submit" class="rounded-lg bg-teal-700 px-4 py-2 text-sm font-semibold text-white hover:bg-teal-800 focus:outline-none focus:ring-2 focus:ring-teal-700">ذخیره</button>
    </form>
</x-management-layout>
