<x-management-layout title="هفته پذیرش">
    <x-page-header title="نمای هفتگی" description="هفته از شنبه تا جمعه (قرارداد موقت جلالی). مرز روزها با timezone برنامه {{ $timezoneLabel }} محاسبه می‌شود و هنوز ساعت رسمی کلینیک نیست.">
        <a href="{{ route('reception.day', ['date' => $date]) }}" class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-teal-700">نمای روزانه</a>
    </x-page-header>

    <div class="mb-4 flex flex-wrap items-center gap-2">
        <a href="{{ route('reception.week', ['date' => $prevDate]) }}" class="rounded-lg border border-slate-300 px-3 py-2 text-sm font-medium hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-teal-700">هفته قبل</a>
        <p class="text-sm font-semibold">{{ $weekStartJalali }} تا {{ $weekEndJalali }}</p>
        <a href="{{ route('reception.week', ['date' => $nextDate]) }}" class="rounded-lg border border-slate-300 px-3 py-2 text-sm font-medium hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-teal-700">هفته بعد</a>
    </div>
    @if ($dateError)
        <x-alert tone="warning" role="alert">{{ $dateError }}</x-alert>
    @endif

    <div class="grid gap-3 md:grid-cols-2 xl:grid-cols-4">
        @foreach ($days as $day)
            <section class="rounded-2xl border border-slate-200 bg-white p-3">
                <div class="mb-2 flex items-center justify-between gap-2">
                    <h2 class="text-sm font-semibold">{{ $day['jalali'] }}</h2>
                    <a href="{{ route('reception.day', ['date' => $day['jalali']]) }}" class="text-xs text-teal-800 hover:underline focus:outline-none focus:ring-2 focus:ring-teal-700">روز</a>
                </div>
                @if ($day['appointments']->isEmpty())
                    <p class="text-xs text-slate-500">نوبتی نیست</p>
                @else
                    <ul class="space-y-2">
                        @foreach ($day['appointments'] as $appointment)
                            <li class="rounded-lg border border-slate-100 bg-slate-50 px-2 py-2 text-xs">
                                <a href="{{ route('appointments.show', $appointment) }}" class="font-semibold text-teal-900 hover:underline focus:outline-none focus:ring-2 focus:ring-teal-700">{{ $clinicDay->formatClock($appointment->starts_at) }} — {{ $appointment->patient?->fullName() }}</a>
                                <p class="mt-1 text-slate-600">{{ $appointment->treatmentService?->name }}</p>
                                <p class="mt-1">{{ $appointment->status->label() }}@if($appointment->visit) · {{ $appointment->visit->status->label() }}@endif</p>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </section>
        @endforeach
    </div>
</x-management-layout>
