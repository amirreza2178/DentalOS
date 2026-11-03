<x-management-layout title="میز پذیرش">
    <x-page-header title="میز پذیرش" description="جستجو، نوبت روز، چک‌این و صف در یک صفحه. منطقه زمانی برنامه هنوز {{ $timezoneLabel }} است و به‌عنوان ساعت رسمی کلینیک اعلام نشده.">
        <a href="{{ route('reception.week', ['date' => $date]) }}" class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-teal-700">نمای هفتگی</a>
        <a href="{{ route('reception.search', ['date' => $date]) }}" class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-teal-700">جستجوی بیمار</a>
        @if ($canOperate)
            <a href="{{ route('appointments.create') }}" class="rounded-lg bg-teal-700 px-4 py-2 text-sm font-semibold text-white hover:bg-teal-800 focus:outline-none focus:ring-2 focus:ring-teal-700">نوبت داخلی</a>
        @endif
    </x-page-header>

    <form method="GET" class="mb-4 flex flex-wrap items-end gap-3 rounded-2xl border border-slate-200 bg-white p-4">
        <label class="block space-y-1" for="date">
            <span class="text-sm font-medium">تاریخ جلالی روز</span>
            <input id="date" name="date" value="{{ $date }}" placeholder="۱۴۰۴/۰۱/۰۱" inputmode="numeric" class="rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-teal-700 focus:outline-none focus:ring-2 focus:ring-teal-700/30">
        </label>
        <button type="submit" class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-teal-700">نمایش روز</button>
    </form>
    @if ($dateError)
        <x-alert tone="warning" role="alert">{{ $dateError }}</x-alert>
    @endif

    <div class="grid gap-4 xl:grid-cols-[1.4fr_1fr]">
        <section class="min-w-0 space-y-3" aria-labelledby="day-appointments-heading">
            <h2 id="day-appointments-heading" class="text-base font-semibold">نوبت‌های روز</h2>
            @if ($appointments->isEmpty())
                <x-empty-state title="نوبتی برای این روز نیست" description="تا وقتی نوبت واقعی ثبت نشود، فهرست خالی می‌ماند.">
                    @if ($canOperate)
                        <a href="{{ route('appointments.create') }}" class="inline-flex rounded-lg bg-teal-700 px-4 py-2 text-sm font-semibold text-white hover:bg-teal-800 focus:outline-none focus:ring-2 focus:ring-teal-700">ثبت نوبت داخلی</a>
                    @endif
                </x-empty-state>
            @else
                <div class="overflow-x-auto rounded-2xl border border-slate-200 bg-white">
                    <table class="min-w-full text-sm">
                        <thead class="bg-slate-50 text-slate-600">
                            <tr>
                                <th class="px-3 py-3 text-right font-medium">ساعت</th>
                                <th class="px-3 py-3 text-right font-medium">بیمار</th>
                                <th class="px-3 py-3 text-right font-medium">خدمت</th>
                                <th class="px-3 py-3 text-right font-medium">نوبت</th>
                                <th class="px-3 py-3 text-right font-medium">ویزیت / صف</th>
                                <th class="px-3 py-3 text-right font-medium">اقدام</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($appointments as $appointment)
                                @php
                                    $visit = $appointment->visit;
                                    $queueEntry = $visit?->latestQueueEntry;
                                    $late = $appointment->status === \App\Enums\AppointmentStatus::Confirmed
                                        && $appointment->starts_at->lt(now(config('app.timezone')));
                                @endphp
                                <tr class="border-t border-slate-100 align-top">
                                    <td class="px-3 py-3 whitespace-nowrap">
                                        <a class="font-medium text-teal-800 hover:underline focus:outline-none focus:ring-2 focus:ring-teal-700" href="{{ route('appointments.show', $appointment) }}">{{ $clinicDay->formatClock($appointment->starts_at) }}</a>
                                        @if ($late)
                                            <span class="mt-1 block text-xs text-amber-800">دیرکرد نسبت به ساعت نوبت</span>
                                        @endif
                                    </td>
                                    <td class="px-3 py-3">
                                        <div class="font-medium">{{ $appointment->patient?->fullName() }}</div>
                                        <div class="text-xs text-slate-500">{{ $appointment->patient?->code }}</div>
                                    </td>
                                    <td class="px-3 py-3">{{ $appointment->treatmentService?->name }}</td>
                                    <td class="px-3 py-3">{{ $appointment->status->label() }}</td>
                                    <td class="px-3 py-3">
                                        @if ($visit)
                                            <a class="text-teal-800 hover:underline focus:outline-none focus:ring-2 focus:ring-teal-700" href="{{ route('reception.visits.show', $visit) }}">{{ $visit->status->label() }}</a>
                                            @if ($queueEntry)
                                                <div class="text-xs text-slate-500">صف: {{ $queueEntry->status->label() }} — ردیف {{ $queueEntry->position }}</div>
                                            @endif
                                        @else
                                            <span class="text-slate-500">چک‌این نشده</span>
                                        @endif
                                    </td>
                                    <td class="px-3 py-3">
                                        <div class="flex flex-col items-stretch gap-2">
                                            @if ($canOperate && $appointment->status === \App\Enums\AppointmentStatus::Confirmed && ! $visit)
                                                <form method="POST" action="{{ route('reception.check-in', $appointment) }}">
                                                    @csrf
                                                    <button type="submit" class="w-full rounded-lg bg-teal-700 px-3 py-2 text-xs font-semibold text-white hover:bg-teal-800 focus:outline-none focus:ring-2 focus:ring-teal-700">چک‌این</button>
                                                </form>
                                            @endif
                                            @if ($visit)
                                                <a href="{{ route('reception.visits.show', $visit) }}" class="rounded-lg border border-slate-300 px-3 py-2 text-center text-xs font-medium hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-teal-700">جزئیات ویزیت</a>
                                            @endif
                                            @can('reschedule', $appointment)
                                                @if ($appointment->status->canTransitionTo(\App\Enums\AppointmentStatus::Rescheduled))
                                                    <a href="{{ route('appointments.reschedule', $appointment) }}" class="rounded-lg border border-slate-300 px-3 py-2 text-center text-xs font-medium hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-teal-700">جابه‌جایی</a>
                                                @endif
                                            @endcan
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </section>

        <section class="min-w-0 space-y-3" aria-labelledby="day-queue-heading">
            <div class="flex flex-wrap items-center justify-between gap-2">
                <h2 id="day-queue-heading" class="text-base font-semibold">صف انتظار فعال</h2>
                @if ($canOperate)
                    <form method="POST" action="{{ route('reception.queue.call-next') }}">
                        @csrf
                        <input type="hidden" name="date" value="{{ $date }}">
                        <button type="submit" class="rounded-lg bg-slate-900 px-3 py-2 text-xs font-semibold text-white hover:bg-slate-800 focus:outline-none focus:ring-2 focus:ring-teal-700">فراخوان بعدی</button>
                    </form>
                @endif
            </div>
            @if ($queue->isEmpty())
                <x-empty-state title="صف خالی است" description="بعد از چک‌این، بیمار اینجا دیده می‌شود." />
            @else
                <ol class="space-y-2">
                    @foreach ($queue as $entry)
                        <li class="rounded-2xl border border-slate-200 bg-white p-4">
                            <div class="flex flex-wrap items-start justify-between gap-3">
                                <div>
                                    <p class="text-sm font-semibold">{{ $entry->position }}. {{ $entry->visit?->patient?->fullName() }}</p>
                                    <p class="mt-1 text-xs text-slate-500">{{ $entry->visit?->appointment?->treatmentService?->name }}</p>
                                    <p class="mt-2 text-sm">وضعیت: <span class="font-medium">{{ $entry->status->label() }}</span></p>
                                    <p class="text-xs text-slate-500">ورود: {{ $clinicDay->formatClock($entry->arrived_at) }}
                                        @if ($entry->status === \App\Enums\QueueStatus::Waiting)
                                            — انتظار حدود {{ max(0, $entry->arrived_at->diffInMinutes(now())) }} دقیقه
                                        @endif
                                    </p>
                                </div>
                                <div class="flex flex-col gap-2">
                                    @if ($canOperate && in_array($entry->status, [\App\Enums\QueueStatus::Waiting, \App\Enums\QueueStatus::Called], true))
                                        <form method="POST" action="{{ route('reception.visits.start', $entry->visit) }}">
                                            @csrf
                                            <button type="submit" class="w-full rounded-lg bg-teal-700 px-3 py-2 text-xs font-semibold text-white hover:bg-teal-800 focus:outline-none focus:ring-2 focus:ring-teal-700">شروع خدمت</button>
                                        </form>
                                    @endif
                                    @can('complete', $entry->visit)
                                        @if ($entry->status === \App\Enums\QueueStatus::InService)
                                            <form method="POST" action="{{ route('reception.visits.complete', $entry->visit) }}">
                                                @csrf
                                                <button type="submit" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-xs font-medium hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-teal-700">اتمام ویزیت</button>
                                            </form>
                                        @endif
                                    @endcan
                                    @if ($canOperate && $entry->status !== \App\Enums\QueueStatus::InService)
                                        <form method="POST" action="{{ route('reception.queue.remove', $entry) }}" onsubmit="return confirm('از صف خارج شود؟')">
                                            @csrf
                                            <button type="submit" class="w-full rounded-lg border border-rose-300 px-3 py-2 text-xs font-medium text-rose-800 hover:bg-rose-50 focus:outline-none focus:ring-2 focus:ring-rose-700">حذف از صف</button>
                                        </form>
                                    @endif
                                </div>
                            </div>
                        </li>
                    @endforeach
                </ol>
            @endif
        </section>
    </div>
</x-management-layout>
