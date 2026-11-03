<x-management-layout :title="$title">
    <x-page-header :title="$title" description="فهرست بر اساس نقش است. تعداد افراد از پیش فرض نشده است.">
        @can('create', App\Models\User::class)
            <a href="{{ route('staff.create', $directory) }}" class="rounded-lg bg-teal-700 px-4 py-2 text-sm font-semibold text-white hover:bg-teal-800 focus:outline-none focus:ring-2 focus:ring-teal-700">حساب جدید</a>
        @endcan
    </x-page-header>
    <form method="GET" class="mb-4 flex flex-wrap gap-2">
        @foreach (['active' => 'فعال', 'inactive' => 'غیرفعال', 'all' => 'همه'] as $value => $label)
            <button name="status" value="{{ $value }}" class="rounded-lg border px-3 py-2 text-sm {{ $status === $value ? 'border-teal-700 bg-teal-700 text-white' : 'border-slate-300 bg-white' }} focus:outline-none focus:ring-2 focus:ring-teal-700">{{ $label }}</button>
        @endforeach
    </form>
    @if ($accounts->isEmpty())
        <x-empty-state title="حسابی در این نما نیست" description="تا وقتی حساب واقعی ساخته نشود، این فهرست خالی می‌ماند." />
    @else
        <div class="overflow-x-auto rounded-2xl border border-slate-200 bg-white">
            <table class="min-w-full text-sm">
                <thead class="bg-slate-50 text-slate-600">
                    <tr>
                        <th class="px-4 py-3 text-right font-medium">نام</th>
                        <th class="px-4 py-3 text-right font-medium">صلاحیت</th>
                        <th class="px-4 py-3 text-right font-medium">وضعیت</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($accounts as $account)
                        <tr class="border-t border-slate-100">
                            <td class="px-4 py-3"><a class="font-medium text-teal-800 hover:underline focus:outline-none focus:ring-2 focus:ring-teal-700" href="{{ route('staff.show', [$directory, $account]) }}">{{ $account->name }}</a></td>
                            <td class="px-4 py-3">{{ $account->staffProfile?->qualification?->label() ?? '—' }}</td>
                            <td class="px-4 py-3">{{ $account->is_active ? 'فعال' : 'غیرفعال' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="mt-4">{{ $accounts->links() }}</div>
    @endif
</x-management-layout>
