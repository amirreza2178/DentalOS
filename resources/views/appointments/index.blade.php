<x-management-layout title="نوبت‌ها">
    <x-page-header title="نوبت‌ها" description="{{ !empty($specialistScoped) ? 'فقط نوبت‌هایی که شما به‌عنوان متخصص در آن‌ها تخصیص دارید.' : 'رزرو زمانی جدا از ویزیت، صف و طرح درمان است.' }}">
        @can('create', App\Models\Appointment::class)
            <a href="{{ route('appointments.create') }}" class="rounded-lg bg-teal-700 px-4 py-2 text-sm font-semibold text-white hover:bg-teal-800 focus:outline-none focus:ring-2 focus:ring-teal-700">نوبت داخلی</a>
        @endcan
    </x-page-header>

    <form method="GET" class="mb-4 flex flex-wrap items-end gap-3 rounded-2xl border border-slate-200 bg-white p-4">
        <label class="block space-y-1" for="date">
            <span class="text-sm font-medium">تاریخ جلالی</span>
            <input id="date" name="date" value="{{ $date }}" placeholder="۱۴۰۴/۰۱/۰۱" inputmode="numeric" class="rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-teal-700 focus:outline-none focus:ring-2 focus:ring-teal-700/30">
        </label>
        <button type="submit" class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-teal-700">اعمال</button>
    </form>
    @if ($dateError)
        <x-alert tone="warning" role="alert">{{ $dateError }}</x-alert>
    @endif

    @if ($appointments->isEmpty())
        <x-empty-state title="نوبتی برای نمایش نیست" description="تا وقتی پذیرش نوبت واقعی ثبت نکند، این فهرست خالی می‌ماند." />
    @else
        <div class="overflow-x-auto rounded-2xl border border-slate-200 bg-white">
            <table class="min-w-full text-sm">
                <thead class="bg-slate-50 text-slate-600">
                    <tr>
                        <th class="px-4 py-3 text-right font-medium">زمان</th>
                        <th class="px-4 py-3 text-right font-medium">بیمار</th>
                        <th class="px-4 py-3 text-right font-medium">خدمت</th>
                        <th class="px-4 py-3 text-right font-medium">وضعیت</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($appointments as $appointment)
                        <tr class="border-t border-slate-100">
                            <td class="px-4 py-3"><a class="font-medium text-teal-800 hover:underline focus:outline-none focus:ring-2 focus:ring-teal-700" href="{{ route('appointments.show', $appointment) }}">{{ $calendar->formatDate($appointment->starts_at) }} {{ $appointment->starts_at->timezone(config('app.timezone'))->format('H:i') }}</a></td>
                            <td class="px-4 py-3">{{ $appointment->patient?->fullName() }}</td>
                            <td class="px-4 py-3">{{ $appointment->treatmentService?->name }}</td>
                            <td class="px-4 py-3">{{ $appointment->status->label() }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="mt-4">{{ $appointments->links() }}</div>
    @endif
</x-management-layout>
