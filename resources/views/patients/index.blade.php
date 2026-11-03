<x-management-layout title="بیماران">
    <x-page-header title="بیماران" description="جستجو با نام، موبایل یا شناسه پرونده. شماره مشترک خانواده پرونده‌ها را ادغام نمی‌کند.">
        @can('create', App\Models\Patient::class)
            <a href="{{ route('patients.create') }}" class="rounded-lg bg-teal-700 px-4 py-2 text-sm font-semibold text-white hover:bg-teal-800 focus:outline-none focus:ring-2 focus:ring-teal-700">بیمار جدید</a>
        @endcan
    </x-page-header>

    <form method="GET" class="mb-4 grid gap-3 rounded-2xl border border-slate-200 bg-white p-4 sm:grid-cols-[1fr_auto_auto]">
        <label class="block space-y-1" for="q">
            <span class="text-sm font-medium">جستجو</span>
            <input id="q" name="q" value="{{ $term }}" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-teal-700 focus:outline-none focus:ring-2 focus:ring-teal-700/30">
        </label>
        <label class="flex items-end gap-2 text-sm" for="archived">
            <input id="archived" name="archived" type="checkbox" value="1" @checked($archived) class="size-4 rounded border-slate-300 text-teal-700">
            بایگانی
        </label>
        <button type="submit" class="self-end rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-teal-700">اعمال</button>
    </form>

    @if ($patients->isEmpty())
        <x-empty-state title="پرونده‌ای برای نمایش نیست" description="اگر بیمار واقعی ثبت نشده باشد، این فهرست خالی می‌ماند." />
    @else
        <div class="overflow-x-auto rounded-2xl border border-slate-200 bg-white">
            <table class="min-w-full text-sm">
                <thead class="bg-slate-50 text-slate-600">
                    <tr>
                        <th class="px-4 py-3 text-right font-medium">شناسه</th>
                        <th class="px-4 py-3 text-right font-medium">نام</th>
                        <th class="px-4 py-3 text-right font-medium">موبایل</th>
                        <th class="px-4 py-3 text-right font-medium">وضعیت</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($patients as $patient)
                        <tr class="border-t border-slate-100">
                            <td class="px-4 py-3"><a class="font-medium text-teal-800 underline-offset-2 hover:underline focus:outline-none focus:ring-2 focus:ring-teal-700" href="{{ route('patients.show', $patient) }}">{{ $patient->code }}</a></td>
                            <td class="px-4 py-3">{{ $patient->fullName() }}</td>
                            <td class="px-4 py-3">{{ $patient->mobile ?: '—' }}</td>
                            <td class="px-4 py-3">{{ $patient->trashed() ? 'بایگانی' : $patient->status->label() }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="mt-4">{{ $patients->links() }}</div>
    @endif
</x-management-layout>
