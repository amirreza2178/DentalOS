<x-management-layout title="یونیت‌ها">
    <x-page-header title="یونیت‌ها و صندلی‌ها" description="تعداد یونیت ثابت نیست و باید مطابق کلینیک ثبت شود.">
        @can('create', App\Models\Chair::class)
            <a href="{{ route('chairs.create') }}" class="rounded-lg bg-teal-700 px-4 py-2 text-sm font-semibold text-white hover:bg-teal-800 focus:outline-none focus:ring-2 focus:ring-teal-700">یونیت جدید</a>
        @endcan
    </x-page-header>
    <form method="GET" class="mb-4 flex flex-wrap gap-2">
        @foreach (['active' => 'فعال', 'inactive' => 'غیرفعال', 'all' => 'همه'] as $value => $label)
            <button name="status" value="{{ $value }}" class="rounded-lg border px-3 py-2 text-sm {{ $status === $value ? 'border-teal-700 bg-teal-700 text-white' : 'border-slate-300 bg-white' }} focus:outline-none focus:ring-2 focus:ring-teal-700">{{ $label }}</button>
        @endforeach
    </form>
    @if ($chairs->isEmpty())
        <x-empty-state title="یونیتی ثبت نشده است" description="شش یونیت فرض نشده است. هر یونیت را جداگانه اضافه کنید." />
    @else
        <div class="overflow-x-auto rounded-2xl border border-slate-200 bg-white">
            <table class="min-w-full text-sm">
                <thead class="bg-slate-50 text-slate-600"><tr><th class="px-4 py-3 text-right font-medium">کد</th><th class="px-4 py-3 text-right font-medium">نام</th><th class="px-4 py-3 text-right font-medium">وضعیت</th></tr></thead>
                <tbody>
                    @foreach ($chairs as $chair)
                        <tr class="border-t border-slate-100">
                            <td class="px-4 py-3">{{ $chair->code }}</td>
                            <td class="px-4 py-3">
                                @can('update', $chair)
                                    <a class="text-teal-800 hover:underline focus:outline-none focus:ring-2 focus:ring-teal-700" href="{{ route('chairs.edit', $chair) }}">{{ $chair->name }}</a>
                                @else
                                    {{ $chair->name }}
                                @endcan
                            </td>
                            <td class="px-4 py-3">{{ $chair->is_active ? 'فعال' : 'غیرفعال' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="mt-4">{{ $chairs->links() }}</div>
    @endif
</x-management-layout>
