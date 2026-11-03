<x-management-layout title="شیفت‌ها">
    <x-page-header title="شیفت‌های کاری" description="ساعت کار کلینیک برای هر روز جدا تنظیم می‌شود. الگوی ثابتی فرض نشده است.">
        @can('create', App\Models\WorkShift::class)
            <a href="{{ route('shifts.create') }}" class="rounded-lg bg-teal-700 px-4 py-2 text-sm font-semibold text-white hover:bg-teal-800 focus:outline-none focus:ring-2 focus:ring-teal-700">شیفت جدید</a>
        @endcan
    </x-page-header>
    @if ($shifts->isEmpty())
        <x-empty-state title="شیفتی ثبت نشده است" description="تا وقتی شیفت واقعی تعریف نشود، زمان آزادی برای نوبت محاسبه نمی‌شود." />
    @else
        <div class="overflow-x-auto rounded-2xl border border-slate-200 bg-white">
            <table class="min-w-full text-sm">
                <thead class="bg-slate-50 text-slate-600">
                    <tr>
                        <th class="px-4 py-3 text-right font-medium">نام</th>
                        <th class="px-4 py-3 text-right font-medium">روز</th>
                        <th class="px-4 py-3 text-right font-medium">ساعت</th>
                        <th class="px-4 py-3 text-right font-medium">استراحت</th>
                        <th class="px-4 py-3 text-right font-medium">وضعیت</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($shifts as $shift)
                        <tr class="border-t border-slate-100">
                            <td class="px-4 py-3"><a class="text-teal-800 hover:underline focus:outline-none focus:ring-2 focus:ring-teal-700" href="{{ route('shifts.edit', $shift) }}">{{ $shift->name }}</a></td>
                            <td class="px-4 py-3">{{ $shift->weekday->label() }}</td>
                            <td class="px-4 py-3">{{ \App\Support\ClockInput::short((string) $shift->starts_at) }} تا {{ \App\Support\ClockInput::short((string) $shift->ends_at) }}</td>
                            <td class="px-4 py-3">{{ $shift->breaks_count }}</td>
                            <td class="px-4 py-3">{{ $shift->is_active ? 'فعال' : 'غیرفعال' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</x-management-layout>
