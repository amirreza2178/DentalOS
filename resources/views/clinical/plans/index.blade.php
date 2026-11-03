<x-management-layout title="برنامه‌های درمانی">
    <x-page-header title="برنامه‌های درمانی" description="برنامه درمانی از نوبت و ویزیت جدا است و فقط با ثبت صریح متخصص ساخته می‌شود." />

    @if ($plans->isEmpty())
        <x-empty-state title="برنامه درمانی ثبت نشده" description="تا وقتی متخصص برنامه‌ای ثبت نکند، این فهرست خالی می‌ماند." />
    @else
        <div class="overflow-x-auto rounded-2xl border border-slate-200 bg-white">
            <table class="min-w-full text-sm">
                <thead class="bg-slate-50 text-slate-600">
                    <tr>
                        <th class="px-4 py-3 text-right font-medium">بیمار</th>
                        <th class="px-4 py-3 text-right font-medium">وضعیت</th>
                        <th class="px-4 py-3 text-right font-medium">ثبت‌کننده</th>
                        <th class="px-4 py-3 text-right font-medium">خلاصه عملیاتی</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($plans as $plan)
                        <tr class="border-t border-slate-100">
                            <td class="px-4 py-3">
                                <a class="font-medium text-teal-800 hover:underline focus:outline-none focus:ring-2 focus:ring-teal-700" href="{{ route('clinical.plans.show', $plan) }}">
                                    {{ $plan->patient?->fullName() }} — {{ $plan->patient?->code }}
                                </a>
                            </td>
                            <td class="px-4 py-3">{{ $plan->status->label() }}</td>
                            <td class="px-4 py-3">{{ $plan->createdBy?->name ?? '—' }}</td>
                            <td class="px-4 py-3">{{ $plan->operational_summary ?: '—' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="mt-4">{{ $plans->links() }}</div>
    @endif
</x-management-layout>
