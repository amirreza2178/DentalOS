@php
    $refreshLabel = $clinicDay->formatClock($refreshedAt);
@endphp
<x-management-layout :title="$specialistScoped ? 'برنامه امروز من' : 'مرکز فرمان پذیرش'" :auto-refresh-seconds="$autoRefreshSeconds">
    <div class="dos-command-shell -mx-4 -mt-6 mb-6 border-b border-slate-200/80 px-4 py-6 sm:px-0 sm:py-7">
        <div class="mx-auto w-full max-w-6xl px-4 sm:px-0">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                <div class="min-w-0">
                    <p class="text-sm font-semibold text-teal-800">{{ $clinicIdentity->clinicDisplayName() }}</p>
                    <h1 class="mt-1 text-2xl font-bold tracking-tight text-[color:var(--dos-navy)] sm:text-3xl">
                        {{ $specialistScoped ? 'برنامه امروز من' : 'مرکز فرمان پذیرش' }}
                    </h1>
                    <p class="mt-2 max-w-2xl text-sm leading-6 text-slate-600">
                        برنامه روز {{ $date }}
                        @if ($specialistScoped)
                            — فقط نوبت‌های تخصیص‌یافته به شما؛ عملیات صف و تأیید آنلاین با پذیرش است
                        @else
                            — نوبت، صف، درخواست آنلاین و پیگیری در یک نگاه
                        @endif
                    </p>
                    <p class="mt-1 text-xs text-slate-500">
                        آخرین به‌روزرسانی صفحه: {{ $refreshLabel }}
                        @if ($autoRefreshSeconds)
                            · تازه‌سازی آرام هر {{ $autoRefreshSeconds }} ثانیه هنگام بیکاری (بدون وب‌سوکت)
                        @endif
                        · منطقه زمانی کلینیک: {{ $timezoneLabel }}
                    </p>
                </div>
                <div class="flex flex-wrap gap-2">
                    <a href="{{ route('reception.day', ['date' => $date]) }}" class="inline-flex min-h-11 items-center rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-medium hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-teal-700">تازه‌سازی</a>
                    <a href="{{ route('reception.week', ['date' => $date]) }}" class="inline-flex min-h-11 items-center rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-medium hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-teal-700">هفته</a>
                    @if ($canOperate)
                        <a href="{{ route('appointments.create') }}" class="inline-flex min-h-11 items-center rounded-lg bg-[color:var(--dos-navy)] px-4 py-2 text-sm font-semibold text-white hover:opacity-95 focus:outline-none focus:ring-2 focus:ring-[color:var(--dos-accent)]">نوبت داخلی</a>
                    @endif
                </div>
            </div>

            @if ($canOperate)
                <div class="mt-5 flex flex-col gap-2 rounded-2xl border border-slate-200 bg-white/90 p-3 shadow-sm sm:flex-row sm:items-center">
                    <form method="GET" action="{{ route('reception.search') }}" class="flex min-w-0 flex-1 flex-col gap-2 sm:flex-row sm:items-center">
                        <input type="hidden" name="date" value="{{ $date }}">
                        <label class="sr-only" for="command-search">جستجوی سریع بیمار</label>
                        <input id="command-search" name="q" type="search" placeholder="نام، موبایل یا کد پرونده…" autocomplete="off" class="min-h-11 w-full grow rounded-xl border border-slate-300 px-4 text-sm focus:border-teal-700 focus:outline-none focus:ring-2 focus:ring-teal-700/30">
                        <button type="submit" class="inline-flex min-h-11 shrink-0 items-center justify-center rounded-xl bg-teal-700 px-5 text-sm font-semibold text-white hover:bg-teal-800 focus:outline-none focus:ring-2 focus:ring-teal-700">جستجو</button>
                    </form>
                    <a href="{{ route('patients.create') }}" class="inline-flex min-h-11 shrink-0 items-center justify-center rounded-xl border border-teal-700 bg-white px-4 text-sm font-semibold text-teal-900 hover:bg-teal-50 focus:outline-none focus:ring-2 focus:ring-teal-700">ثبت بیمار جدید</a>
                </div>
            @endif

            <form method="GET" class="mt-3 flex flex-wrap items-end gap-3">
                <label class="block space-y-1" for="date">
                    <span class="text-xs font-medium text-slate-600">تاریخ جلالی</span>
                    <input id="date" name="date" value="{{ $date }}" placeholder="۱۴۰۴/۰۱/۰۱" inputmode="numeric" class="min-h-11 rounded-lg border border-slate-300 bg-white px-3 text-sm focus:border-teal-700 focus:outline-none focus:ring-2 focus:ring-teal-700/30">
                </label>
                @if (($onlineScope ?? 'day') === 'all')
                    <input type="hidden" name="online" value="all">
                @endif
                <button type="submit" class="min-h-11 rounded-lg border border-slate-300 bg-white px-4 text-sm font-medium hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-teal-700">نمایش روز</button>
            </form>
        </div>
    </div>

    @if ($dateError)
        <x-alert tone="warning" role="alert">{{ $dateError }}</x-alert>
    @endif

    @if ($alerts->isNotEmpty())
        <div class="mb-5 space-y-2" aria-label="هشدارهای عملیاتی">
            @foreach ($alerts as $alert)
                <div class="flex flex-wrap items-center justify-between gap-2 rounded-xl border px-4 py-3 text-sm
                    {{ $alert['tone'] === 'attention' ? 'border-amber-300 bg-amber-50 text-amber-950' : ($alert['tone'] === 'warning' ? 'border-rose-200 bg-rose-50 text-rose-950' : 'border-slate-200 bg-white text-slate-800') }}">
                    <p class="font-medium">{{ $alert['label'] }}</p>
                    @if (! empty($alert['href']))
                        <a href="{{ $alert['href'] }}" class="font-semibold underline-offset-2 hover:underline focus:outline-none focus:ring-2 focus:ring-teal-700">مشاهده</a>
                    @endif
                </div>
            @endforeach
        </div>
    @endif

    @if ($specialistScoped && $nextClinical)
        <section class="mb-5 rounded-2xl border border-teal-200 bg-white px-4 py-4 sm:px-5" aria-labelledby="next-clinical-heading">
            <p class="text-xs font-semibold tracking-wide text-teal-800">اقدام بالینی بعدی</p>
            <h2 id="next-clinical-heading" class="dos-title-md mt-1">
                {{ $clinicDay->formatClock($nextClinical->starts_at) }} · {{ $nextClinical->patient?->fullName() }}
            </h2>
            <p class="dos-meta mt-1">
                {{ $nextClinical->treatmentService?->name }}
                · {{ $nextClinical->status->label() }}
            </p>
            <div class="mt-3 flex flex-wrap gap-2">
                <a href="{{ route('appointments.show', $nextClinical) }}" class="inline-flex min-h-11 items-center rounded-lg bg-[color:var(--dos-navy)] px-4 text-sm font-semibold text-white hover:opacity-95 focus:outline-none focus:ring-2 focus:ring-[color:var(--dos-accent)]">جزئیات نوبت</a>
                @if ($nextClinical->patient)
                    <a href="{{ route('patients.show', $nextClinical->patient) }}" class="inline-flex min-h-11 items-center rounded-lg border border-slate-300 px-4 text-sm font-medium hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-teal-700">پرونده بیمار</a>
                    <a href="{{ route('clinical.plans.create', $nextClinical->patient) }}" class="inline-flex min-h-11 items-center rounded-lg border border-slate-300 px-4 text-sm font-medium hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-teal-700">برنامه درمانی</a>
                @endif
            </div>
        </section>
    @elseif ($specialistScoped)
        <p class="mb-5 rounded-xl border border-dashed border-slate-300 bg-white/70 px-4 py-3 text-sm text-slate-600">برای امروز نوبت تخصیص‌یافته‌ای در صف اقدام بالینی نیست.</p>
    @endif

    <div class="mb-5 grid grid-cols-2 gap-2 sm:grid-cols-3 {{ $specialistScoped ? 'xl:grid-cols-3' : 'xl:grid-cols-6' }}" aria-label="خلاصه عملیاتی روز">
        @foreach (($specialistScoped
            ? [
                ['label' => 'نوبت من', 'value' => $counts['schedule']],
                ['label' => 'پیش‌رو', 'value' => $upcoming->count()],
                ['label' => 'تغییر یافته', 'value' => $counts['cancelled_rescheduled']],
            ]
            : [
                ['label' => 'نوبت فعال', 'value' => $counts['schedule']],
                ['label' => 'در انتظار', 'value' => $counts['waiting']],
                ['label' => 'فراخوان', 'value' => $counts['called']],
                ['label' => 'در خدمت', 'value' => $counts['in_service']],
                ['label' => 'آنلاین', 'value' => $counts['pending_online']],
                ['label' => 'پیگیری', 'value' => $counts['follow_ups']],
            ]
        ) as $stat)
            <div class="rounded-xl border border-slate-200 bg-white px-3 py-3">
                <p class="dos-meta">{{ $stat['label'] }}</p>
                <p class="mt-1 text-2xl font-bold tabular-nums text-[color:var(--dos-navy)]">{{ $stat['value'] }}</p>
            </div>
        @endforeach
    </div>

    <div class="grid gap-5 xl:grid-cols-[1.35fr_1fr]">
        <div class="min-w-0 space-y-5">
            <section aria-labelledby="upcoming-heading">
                <div class="mb-3 flex items-center justify-between gap-2">
                    <h2 id="upcoming-heading" class="text-base font-semibold text-[color:var(--dos-navy)]">نوبت‌های پیش‌رو</h2>
                </div>
                @if ($upcoming->isEmpty())
                    <p class="rounded-xl border border-dashed border-slate-300 bg-white/70 px-4 py-3 text-sm text-slate-600">نوبت تأیید/در انتظار پیش‌رویی برای ادامه روز نیست.</p>
                @else
                    <ul class="space-y-2">
                        @foreach ($upcoming as $appointment)
                            <li class="flex flex-wrap items-center justify-between gap-3 rounded-xl border border-slate-200 bg-white px-4 py-3">
                                <div class="min-w-0">
                                    <p class="font-semibold text-[color:var(--dos-navy)]">{{ $clinicDay->formatClock($appointment->starts_at) }} · {{ $appointment->patient?->fullName() }}</p>
                                    <p class="mt-0.5 text-xs text-slate-500">{{ $appointment->treatmentService?->name }} · {{ $appointment->status->label() }}</p>
                                </div>
                                <div class="flex flex-wrap gap-2">
                                    @if ($canOperate && $appointment->status === \App\Enums\AppointmentStatus::PendingApproval)
                                        <form method="POST" action="{{ route('booking.requests.approve', $appointment) }}">
                                            @csrf
                                            <input type="hidden" name="return_to" value="reception.day">
                                            <input type="hidden" name="return_date" value="{{ $date }}">
                                            <button type="submit" class="min-h-10 rounded-lg bg-teal-700 px-3 text-xs font-semibold text-white hover:bg-teal-800 focus:outline-none focus:ring-2 focus:ring-teal-700">تأیید</button>
                                        </form>
                                    @endif
                                    @if ($canOperate && $appointment->status === \App\Enums\AppointmentStatus::Confirmed)
                                        <form method="POST" action="{{ route('reception.check-in', $appointment) }}">
                                            @csrf
                                            <button type="submit" class="min-h-10 rounded-lg bg-teal-700 px-3 text-xs font-semibold text-white hover:bg-teal-800 focus:outline-none focus:ring-2 focus:ring-teal-700">چک‌این</button>
                                        </form>
                                    @endif
                                </div>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </section>

            <section aria-labelledby="day-appointments-heading">
                <h2 id="day-appointments-heading" class="mb-3 text-base font-semibold text-[color:var(--dos-navy)]">برنامه امروز</h2>
                @if ($schedule->isEmpty())
                    <x-empty-state title="نوبت فعالی برای این روز نیست" description="تا وقتی نوبت واقعی ثبت نشود، فهرست خالی می‌ماند.">
                        @if ($canOperate)
                            <a href="{{ route('appointments.create') }}" class="inline-flex rounded-lg bg-teal-700 px-4 py-2 text-sm font-semibold text-white hover:bg-teal-800 focus:outline-none focus:ring-2 focus:ring-teal-700">ثبت نوبت داخلی</a>
                        @endif
                    </x-empty-state>
                @else
                    <div class="space-y-3 md:hidden">
                        @foreach ($schedule as $appointment)
                            @php
                                $visit = $appointment->visit;
                                $queueEntry = $visit?->latestQueueEntry;
                                $late = $appointment->status === \App\Enums\AppointmentStatus::Confirmed
                                    && $appointment->starts_at->lt(now(config('app.timezone')));
                            @endphp
                            <article class="rounded-2xl border border-slate-200 bg-white p-4 {{ $appointment->status === \App\Enums\AppointmentStatus::PendingApproval ? 'border-amber-300' : '' }}">
                                <div class="flex items-start justify-between gap-3">
                                    <div>
                                        <p class="text-lg font-bold tabular-nums text-[color:var(--dos-navy)]">{{ $clinicDay->formatClock($appointment->starts_at) }}</p>
                                        <p class="mt-1 font-semibold">{{ $appointment->patient?->fullName() }}</p>
                                        <p class="text-xs text-slate-500">{{ $appointment->patient?->code }} · {{ $appointment->treatmentService?->name }}</p>
                                    </div>
                                    <span class="rounded-full px-2.5 py-1 text-xs font-medium {{ $appointment->status === \App\Enums\AppointmentStatus::PendingApproval ? 'bg-amber-100 text-amber-950' : ($appointment->status === \App\Enums\AppointmentStatus::Confirmed ? 'bg-teal-50 text-teal-900' : 'bg-slate-100 text-slate-700') }}">{{ $appointment->status->label() }}</span>
                                </div>
                                @if ($late)
                                    <p class="mt-2 text-xs text-amber-800">دیرکرد نسبت به ساعت نوبت</p>
                                @endif
                                @if ($appointment->status === \App\Enums\AppointmentStatus::PendingApproval)
                                    <p class="mt-2 text-xs text-amber-900">تا تأیید پذیرش، چک‌این ممکن نیست.</p>
                                @endif
                                <p class="mt-2 text-sm text-slate-600">
                                    @if ($visit)
                                        ویزیت: {{ $visit->status->label() }}
                                        @if ($queueEntry)
                                            · صف {{ $queueEntry->status->label() }} ({{ $queueEntry->position }})
                                        @endif
                                    @elseif ($appointment->status !== \App\Enums\AppointmentStatus::PendingApproval)
                                        چک‌این نشده
                                    @endif
                                </p>
                                <div class="mt-3 flex flex-wrap gap-2">
                                    @if ($canOperate && $appointment->status === \App\Enums\AppointmentStatus::PendingApproval)
                                        <form method="POST" action="{{ route('booking.requests.approve', $appointment) }}">
                                            @csrf
                                            <input type="hidden" name="return_to" value="reception.day">
                                            <input type="hidden" name="return_date" value="{{ $date }}">
                                            <button type="submit" class="min-h-10 rounded-lg bg-teal-700 px-3 text-xs font-semibold text-white">تأیید آنلاین</button>
                                        </form>
                                        <a href="{{ route('booking.requests.show', $appointment) }}#reject" class="inline-flex min-h-10 items-center rounded-lg border border-amber-300 bg-amber-50 px-3 text-xs font-medium text-amber-950">رد با دلیل</a>
                                    @endif
                                    @if ($canOperate && $appointment->status === \App\Enums\AppointmentStatus::Confirmed && ! $visit)
                                        <form method="POST" action="{{ route('reception.check-in', $appointment) }}">
                                            @csrf
                                            <button type="submit" class="min-h-10 rounded-lg bg-teal-700 px-3 text-xs font-semibold text-white">چک‌این</button>
                                        </form>
                                    @endif
                                    @can('reschedule', $appointment)
                                        @if ($appointment->status->canTransitionTo(\App\Enums\AppointmentStatus::Rescheduled))
                                            <a href="{{ route('appointments.reschedule', $appointment) }}" class="inline-flex min-h-10 items-center rounded-lg border border-slate-300 px-3 text-xs font-medium">جابه‌جایی</a>
                                        @endif
                                    @endcan
                                    @can('cancel', $appointment)
                                        @if ($appointment->status->canTransitionTo(\App\Enums\AppointmentStatus::Cancelled))
                                            <form method="POST" action="{{ route('appointments.cancel', $appointment) }}" onsubmit="return confirm('این نوبت لغو شود؟')">
                                                @csrf
                                                <input type="hidden" name="return_to" value="reception.day">
                                                <input type="hidden" name="return_date" value="{{ $date }}">
                                                <button type="submit" class="inline-flex min-h-10 items-center rounded-lg border border-rose-300 px-3 text-xs font-medium text-rose-800 hover:bg-rose-50">لغو</button>
                                            </form>
                                        @endif
                                    @endcan
                                    @if ($visit)
                                        <a href="{{ route('reception.visits.show', $visit) }}" class="inline-flex min-h-10 items-center rounded-lg border border-slate-300 px-3 text-xs font-medium">ویزیت</a>
                                    @endif
                                    <a href="{{ route('appointments.show', $appointment) }}" class="inline-flex min-h-10 items-center rounded-lg border border-slate-300 px-3 text-xs font-medium">نوبت</a>
                                </div>
                            </article>
                        @endforeach
                    </div>

                    <div class="hidden overflow-x-auto rounded-2xl border border-slate-200 bg-white md:block">
                        <table class="min-w-full text-sm">
                            <thead class="bg-slate-50 text-slate-600">
                                <tr>
                                    <th class="px-3 py-3 text-right font-medium">ساعت</th>
                                    <th class="px-3 py-3 text-right font-medium">بیمار</th>
                                    <th class="px-3 py-3 text-right font-medium">خدمت</th>
                                    <th class="px-3 py-3 text-right font-medium">وضعیت</th>
                                    <th class="px-3 py-3 text-right font-medium">ویزیت / صف</th>
                                    <th class="px-3 py-3 text-right font-medium">اقدام</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($schedule as $appointment)
                                    @php
                                        $visit = $appointment->visit;
                                        $queueEntry = $visit?->latestQueueEntry;
                                        $late = $appointment->status === \App\Enums\AppointmentStatus::Confirmed
                                            && $appointment->starts_at->lt(now(config('app.timezone')));
                                    @endphp
                                    <tr class="border-t border-slate-100 align-top {{ $appointment->status === \App\Enums\AppointmentStatus::PendingApproval ? 'bg-amber-50/40' : '' }}">
                                        <td class="px-3 py-3 whitespace-nowrap">
                                            <a class="font-medium text-teal-800 hover:underline focus:outline-none focus:ring-2 focus:ring-teal-700" href="{{ route('appointments.show', $appointment) }}">{{ $clinicDay->formatClock($appointment->starts_at) }}</a>
                                            @if ($late)
                                                <span class="mt-1 block text-xs text-amber-800">دیرکرد</span>
                                            @endif
                                        </td>
                                        <td class="px-3 py-3">
                                            <div class="font-medium">{{ $appointment->patient?->fullName() }}</div>
                                            <div class="text-xs text-slate-500">{{ $appointment->patient?->code }}</div>
                                        </td>
                                        <td class="px-3 py-3">{{ $appointment->treatmentService?->name }}</td>
                                        <td class="px-3 py-3">
                                            <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-medium {{ $appointment->status === \App\Enums\AppointmentStatus::PendingApproval ? 'bg-amber-100 text-amber-950' : ($appointment->status === \App\Enums\AppointmentStatus::Confirmed ? 'bg-teal-50 text-teal-900' : 'bg-slate-100 text-slate-700') }}">{{ $appointment->status->label() }}</span>
                                        </td>
                                        <td class="px-3 py-3">
                                            @if ($visit)
                                                <a class="text-teal-800 hover:underline focus:outline-none focus:ring-2 focus:ring-teal-700" href="{{ route('reception.visits.show', $visit) }}">{{ $visit->status->label() }}</a>
                                                @if ($queueEntry)
                                                    <div class="text-xs text-slate-500">صف: {{ $queueEntry->status->label() }} — ردیف {{ $queueEntry->position }}</div>
                                                @endif
                                            @elseif ($appointment->status === \App\Enums\AppointmentStatus::PendingApproval)
                                                <span class="text-amber-900">در انتظار تأیید</span>
                                            @else
                                                <span class="text-slate-500">چک‌این نشده</span>
                                            @endif
                                        </td>
                                        <td class="px-3 py-3">
                                            <div class="flex flex-col items-stretch gap-2">
                                                @if ($canOperate && $appointment->status === \App\Enums\AppointmentStatus::PendingApproval)
                                                    <form method="POST" action="{{ route('booking.requests.approve', $appointment) }}">
                                                        @csrf
                                                        <input type="hidden" name="return_to" value="reception.day">
                                                        <input type="hidden" name="return_date" value="{{ $date }}">
                                                        <button type="submit" class="w-full rounded-lg bg-teal-700 px-3 py-2 text-xs font-semibold text-white hover:bg-teal-800 focus:outline-none focus:ring-2 focus:ring-teal-700">تأیید آنلاین</button>
                                                    </form>
                                                    <a href="{{ route('booking.requests.show', $appointment) }}" class="rounded-lg border border-slate-300 px-3 py-2 text-center text-xs font-medium hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-teal-700">بررسی</a>
                                                @endif
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
                                                @can('cancel', $appointment)
                                                    @if ($appointment->status->canTransitionTo(\App\Enums\AppointmentStatus::Cancelled))
                                                        <form method="POST" action="{{ route('appointments.cancel', $appointment) }}" onsubmit="return confirm('این نوبت لغو شود؟')">
                                                            @csrf
                                                            <input type="hidden" name="return_to" value="reception.day">
                                                            <input type="hidden" name="return_date" value="{{ $date }}">
                                                            <button type="submit" class="w-full rounded-lg border border-rose-300 px-3 py-2 text-xs font-medium text-rose-800 hover:bg-rose-50 focus:outline-none focus:ring-2 focus:ring-rose-700">لغو</button>
                                                        </form>
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

            @if ($cancelledOrRescheduled->isNotEmpty())
                <section aria-labelledby="changed-heading">
                    <h2 id="changed-heading" class="mb-3 text-base font-semibold text-[color:var(--dos-navy)]">لغو / جابه‌جایی امروز</h2>
                    <ul class="space-y-2">
                        @foreach ($cancelledOrRescheduled as $appointment)
                            <li class="rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm">
                                <span class="font-medium">{{ $clinicDay->formatClock($appointment->starts_at) }}</span>
                                · {{ $appointment->patient?->fullName() }}
                                · <span class="text-slate-600">{{ $appointment->status->label() }}</span>
                            </li>
                        @endforeach
                    </ul>
                </section>
            @endif
        </div>

        <div class="min-w-0 space-y-5">
            <section aria-labelledby="day-queue-heading">
                <div class="mb-3 flex flex-wrap items-center justify-between gap-2">
                    <h2 id="day-queue-heading" class="text-base font-semibold text-[color:var(--dos-navy)]">صف انتظار</h2>
                    @if ($canOperate)
                        <form method="POST" action="{{ route('reception.queue.call-next') }}">
                            @csrf
                            <input type="hidden" name="date" value="{{ $date }}">
                            <button type="submit" class="min-h-10 rounded-lg bg-[color:var(--dos-navy)] px-3 text-xs font-semibold text-white hover:opacity-95 focus:outline-none focus:ring-2 focus:ring-[color:var(--dos-accent)]">فراخوان بعدی</button>
                        </form>
                    @endif
                </div>

                @forelse ([
                    'در انتظار' => $waiting,
                    'فراخوان‌شده' => $called,
                    'در خدمت' => $inService,
                ] as $label => $entries)
                    <div class="mb-3">
                        <p class="mb-2 text-xs font-semibold tracking-wide text-slate-500">{{ $label }} ({{ $entries->count() }})</p>
                        @if ($entries->isEmpty())
                            <p class="rounded-lg border border-dashed border-slate-200 bg-white/60 px-3 py-2 text-xs text-slate-500">خالی</p>
                        @else
                            <ol class="space-y-2">
                                @foreach ($entries as $entry)
                                    <li class="rounded-2xl border border-slate-200 bg-white p-4">
                                        <div class="flex flex-wrap items-start justify-between gap-3">
                                            <div>
                                                <p class="text-sm font-semibold">{{ $entry->position }}. {{ $entry->visit?->patient?->fullName() }}</p>
                                                <p class="mt-1 text-xs text-slate-500">{{ $entry->visit?->appointment?->treatmentService?->name }}</p>
                                                <p class="mt-2 text-sm">وضعیت: <span class="font-medium">{{ $entry->status->label() }}</span></p>
                                                <p class="text-xs text-slate-500">ورود: {{ $clinicDay->formatClock($entry->arrived_at) }}
                                                    @if ($entry->status === \App\Enums\QueueStatus::Waiting)
                                                        — انتظار حدود {{ max(0, (int) round($entry->arrived_at->diffInMinutes(now()))) }} دقیقه
                                                    @endif
                                                </p>
                                            </div>
                                            <div class="flex w-full flex-col gap-2 sm:w-auto">
                                                @if ($canOperate && in_array($entry->status, [\App\Enums\QueueStatus::Waiting, \App\Enums\QueueStatus::Called], true))
                                                    <form method="POST" action="{{ route('reception.visits.start', $entry->visit) }}">
                                                        @csrf
                                                        <button type="submit" class="w-full min-h-10 rounded-lg bg-teal-700 px-3 text-xs font-semibold text-white hover:bg-teal-800 focus:outline-none focus:ring-2 focus:ring-teal-700">شروع خدمت</button>
                                                    </form>
                                                @endif
                                                @can('complete', $entry->visit)
                                                    @if ($entry->status === \App\Enums\QueueStatus::InService)
                                                        <form method="POST" action="{{ route('reception.visits.complete', $entry->visit) }}">
                                                            @csrf
                                                            <button type="submit" class="w-full min-h-10 rounded-lg border border-slate-300 px-3 text-xs font-medium hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-teal-700">اتمام ویزیت</button>
                                                        </form>
                                                    @endif
                                                @endcan
                                                @if ($canOperate && $entry->status !== \App\Enums\QueueStatus::InService)
                                                    <form method="POST" action="{{ route('reception.queue.remove', $entry) }}" onsubmit="return confirm('از صف خارج شود؟')">
                                                        @csrf
                                                        <button type="submit" class="w-full min-h-10 rounded-lg border border-rose-300 px-3 text-xs font-medium text-rose-800 hover:bg-rose-50 focus:outline-none focus:ring-2 focus:ring-rose-700">حذف از صف</button>
                                                    </form>
                                                @endif
                                            </div>
                                        </div>
                                    </li>
                                @endforeach
                            </ol>
                        @endif
                    </div>
                @empty
                @endforelse
            </section>

            @if ($canOperate)
                <section aria-labelledby="online-heading">
                    <div class="mb-3 flex flex-wrap items-center justify-between gap-2">
                        <h2 id="online-heading" class="text-base font-semibold text-[color:var(--dos-navy)]">درخواست‌های آنلاین</h2>
                        <div class="flex flex-wrap items-center gap-3">
                            @if (($onlineScope ?? 'day') === 'day')
                                <a href="{{ route('reception.day', ['date' => $date, 'online' => 'all']) }}#online-heading" class="text-xs font-semibold text-teal-800 hover:underline focus:outline-none focus:ring-2 focus:ring-teal-700">همه روزها@if (($pendingOnlineAllCount ?? 0) > $pendingOnline->count()) ({{ $pendingOnlineAllCount }})@endif</a>
                            @else
                                <a href="{{ route('reception.day', ['date' => $date]) }}#online-heading" class="text-xs font-semibold text-teal-800 hover:underline focus:outline-none focus:ring-2 focus:ring-teal-700">همین روز</a>
                            @endif
                            <a href="{{ route('booking.requests.index') }}" class="text-xs font-semibold text-slate-600 hover:underline focus:outline-none focus:ring-2 focus:ring-teal-700">فهرست کامل</a>
                        </div>
                    </div>
                    <p class="mb-2 text-xs text-slate-500">
                        @if (($onlineScope ?? 'day') === 'day')
                            نمایش پیش‌فرض: درخواست‌های معلق همین روز جلالی.
                        @else
                            نمایش همه درخواست‌های معلق، صرف‌نظر از روز نوبت.
                        @endif
                    </p>
                    @if ($pendingOnline->isEmpty())
                        <p class="rounded-xl border border-dashed border-slate-200 bg-white/70 px-4 py-3 text-sm text-slate-600">
                            @if (($onlineScope ?? 'day') === 'day' && ($pendingOnlineAllCount ?? 0) > 0)
                                برای این روز درخواست معلقی نیست؛ {{ $pendingOnlineAllCount }} مورد در روزهای دیگر هست.
                            @else
                                درخواست معلقی نیست.
                            @endif
                        </p>
                    @else
                        <ul class="space-y-2">
                            @foreach ($pendingOnline as $request)
                                <li class="rounded-xl border border-slate-200 bg-white p-4">
                                    <p class="font-semibold">{{ $request->patient?->fullName() }}</p>
                                    <p class="mt-1 text-xs text-slate-500">{{ $calendar->formatDate($request->starts_at) }} {{ $clinicDay->formatClock($request->starts_at) }} · {{ $request->treatmentService?->name }}</p>
                                    <div class="mt-3 flex flex-wrap gap-2">
                                        <a href="{{ route('booking.requests.show', $request) }}" class="inline-flex min-h-10 items-center rounded-lg border border-slate-300 px-3 text-xs font-medium">بررسی</a>
                                        <form method="POST" action="{{ route('booking.requests.approve', $request) }}" data-booking-submit>
                                            @csrf
                                            <input type="hidden" name="return_to" value="reception.day">
                                            <input type="hidden" name="return_date" value="{{ $date }}">
                                            <button type="submit" class="min-h-10 rounded-lg bg-teal-700 px-3 text-xs font-semibold text-white">تأیید</button>
                                        </form>
                                    </div>
                                    <form method="POST" action="{{ route('booking.requests.reject', $request) }}" data-booking-submit class="mt-3 space-y-2 border-t border-slate-100 pt-3">
                                        @csrf
                                        <input type="hidden" name="return_to" value="reception.day">
                                        <input type="hidden" name="return_date" value="{{ $date }}">
                                        <label class="block space-y-1" for="reject-reason-{{ $request->id }}">
                                            <span class="text-xs font-medium text-slate-600">دلیل رد <span class="text-rose-700">*</span></span>
                                            <input id="reject-reason-{{ $request->id }}" name="decision_reason" required minlength="3" maxlength="500" class="w-full min-h-11 rounded-lg border border-slate-300 px-3 py-2 text-xs focus:border-teal-700 focus:outline-none focus:ring-2 focus:ring-teal-700/30">
                                            @error('decision_reason')
                                                <span class="block text-xs text-rose-700">{{ $message }}</span>
                                            @enderror
                                        </label>
                                        <button type="submit" class="min-h-10 rounded-lg border border-rose-300 px-3 text-xs font-medium text-rose-800 hover:bg-rose-50">رد درخواست</button>
                                    </form>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </section>

                <section aria-labelledby="followups-heading">
                    <div class="mb-3 flex items-center justify-between gap-2">
                        <h2 id="followups-heading" class="text-base font-semibold text-[color:var(--dos-navy)]">پیگیری‌های نیازمند اقدام</h2>
                        <a href="{{ route('clinical.follow-ups.index', ['actionable' => 1]) }}" class="text-xs font-semibold text-teal-800 hover:underline focus:outline-none focus:ring-2 focus:ring-teal-700">همه</a>
                    </div>
                    @if ($actionableFollowUps->isEmpty())
                        <p class="rounded-xl border border-dashed border-slate-200 bg-white/70 px-4 py-3 text-sm text-slate-600">پیگیری قابل اقدامی نیست.</p>
                    @else
                        <ul class="space-y-2">
                            @foreach ($actionableFollowUps as $followUp)
                                <li class="rounded-xl border border-slate-200 bg-white p-4">
                                    <p class="font-semibold">{{ $followUp->patient?->fullName() }}</p>
                                    <p class="mt-1 text-xs text-slate-500">{{ $followUp->treatmentService?->name }} · {{ $followUp->status->label() }}</p>
                                    <a href="{{ route('clinical.follow-ups.show', $followUp) }}" class="mt-3 inline-flex min-h-10 items-center rounded-lg border border-slate-300 px-3 text-xs font-medium">باز کردن</a>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </section>
            @endif
        </div>
    </div>
</x-management-layout>
