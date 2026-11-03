<x-management-layout :title="$account->name">
    <x-page-header :title="$account->name" :description="$account->role->label()">
        @can('update', $account)
            <a href="{{ route('staff.edit', [$directory, $account]) }}" class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-teal-700">ویرایش</a>
        @endcan
    </x-page-header>
    <dl class="grid gap-4 rounded-2xl border border-slate-200 bg-white p-4 sm:grid-cols-2">
        <div><dt class="text-xs text-slate-500">ایمیل</dt><dd>{{ $account->email }}</dd></div>
        <div><dt class="text-xs text-slate-500">موبایل</dt><dd>{{ $account->mobile ?: '—' }}</dd></div>
        <div><dt class="text-xs text-slate-500">صلاحیت</dt><dd>{{ $account->staffProfile?->qualification?->label() ?? '—' }}</dd></div>
        <div><dt class="text-xs text-slate-500">وضعیت</dt><dd>{{ $account->is_active ? 'فعال' : 'غیرفعال' }}</dd></div>
        <div class="sm:col-span-2"><dt class="text-xs text-slate-500">خلاصه حرفه‌ای</dt><dd class="whitespace-pre-wrap">{{ $account->staffProfile?->professional_summary ?: '—' }}</dd></div>
    </dl>
</x-management-layout>
