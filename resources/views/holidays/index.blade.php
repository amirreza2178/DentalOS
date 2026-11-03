<x-management-layout title="تعطیلات">
    <x-page-header title="تعطیلات" description="روز بسته‌بودن کلینیک را خودتان ثبت کنید. تقویم تعطیل رسمی فرض نشده است." />
    @if ($errors->any())
        <x-alert tone="warning" role="alert">{{ $errors->first() }}</x-alert>
    @endif
    <form method="POST" action="{{ route('holidays.store') }}" class="mb-6 grid gap-3 rounded-2xl border border-slate-200 bg-white p-4 md:grid-cols-4">
        @csrf
        <x-text-field name="holiday_date" label="تاریخ جلالی" required />
        <x-text-field name="title" label="عنوان" required />
        <x-checkbox-field name="is_closed" label="کلینیک بسته است" :checked="true" />
        <button type="submit" class="self-end rounded-lg bg-teal-700 px-4 py-2 text-sm font-semibold text-white focus:outline-none focus:ring-2 focus:ring-teal-700">ثبت تعطیلی</button>
    </form>
    @if ($holidays->isEmpty())
        <x-empty-state title="تعطیلی ثبت نشده است" description="تا وقتی روزی را تعطیل نکنید، فقط شیفت‌ها زمان کار را محدود می‌کنند." />
    @else
        <div class="overflow-x-auto rounded-2xl border border-slate-200 bg-white">
            <table class="min-w-full text-sm">
                <thead class="bg-slate-50 text-slate-600"><tr><th class="px-4 py-3 text-right font-medium">تاریخ</th><th class="px-4 py-3 text-right font-medium">عنوان</th><th class="px-4 py-3 text-right font-medium">وضعیت</th><th class="px-4 py-3"></th></tr></thead>
                <tbody>
                    @foreach ($holidays as $holiday)
                        <tr class="border-t border-slate-100">
                            <td class="px-4 py-3">{{ $calendar->formatDate($holiday->holiday_date) }}</td>
                            <td class="px-4 py-3">{{ $holiday->title }}</td>
                            <td class="px-4 py-3">{{ $holiday->is_closed ? 'بسته' : 'باز' }}</td>
                            <td class="px-4 py-3">
                                <form method="POST" action="{{ route('holidays.destroy', $holiday) }}">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-sm text-rose-800 hover:underline focus:outline-none focus:ring-2 focus:ring-rose-700">حذف</button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</x-management-layout>
