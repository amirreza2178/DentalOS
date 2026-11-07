<x-management-layout title="جستجوی پذیرش">
    <x-page-header title="جستجوی بیمار روی میز" description="نام، موبایل یا شناسه پرونده. موبایل مشترک پرونده‌ها را ادغام نمی‌کند.">
        <a href="{{ route('reception.day', ['date' => $date]) }}" class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-teal-700">بازگشت به میز</a>
    </x-page-header>

    <form method="GET" action="{{ route('reception.search') }}" class="mb-4 grid gap-3 rounded-2xl border border-slate-200 bg-white p-4 md:grid-cols-[1fr_16rem_auto]">
        <label class="block space-y-1" for="q">
            <span class="text-sm font-medium">عبارت جستجو</span>
            <input id="q" name="q" value="{{ $term }}" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-teal-700 focus:outline-none focus:ring-2 focus:ring-teal-700/30" autocomplete="off">
        </label>
        <x-jalali-date-field name="date" label="تاریخ جلالی" :value="$date" hint="نوبت‌های همان روز کنار نتیجه نشان داده می‌شود." />
        <button type="submit" class="self-end rounded-lg bg-teal-700 px-4 py-2 text-sm font-semibold text-white hover:bg-teal-800 focus:outline-none focus:ring-2 focus:ring-teal-700">جستجو</button>
    </form>

    @if ($term === '')
        <x-empty-state title="عبارت را وارد کنید" description="حداکثر ۲۰ نتیجه برای جلوگیری از افشای فهرست کامل برمی‌گردد." />
    @elseif ($patients->isEmpty())
        <x-empty-state title="نتیجه‌ای پیدا نشد" description="پرونده واقعی با این عبارت وجود ندارد." />
    @else
        <div class="space-y-3">
            @foreach ($patients as $patient)
                @php $dayAppointments = $appointmentsByPatient->get($patient->id, collect()); @endphp
                <article class="rounded-2xl border border-slate-200 bg-white p-4">
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div>
                            <h2 class="text-sm font-semibold">{{ $patient->fullName() }}</h2>
                            <p class="mt-1 text-xs text-slate-500">{{ $patient->code }}@if($patient->mobile) · {{ $patient->mobile }}@endif</p>
                        </div>
                        <div class="flex flex-wrap gap-3">
                            <a href="{{ route('appointments.create', ['patient_id' => $patient->id]) }}" class="text-sm font-semibold text-teal-800 hover:underline focus:outline-none focus:ring-2 focus:ring-teal-700">نوبت جدید</a>
                            <a href="{{ route('patients.show', $patient) }}" class="text-sm text-teal-800 hover:underline focus:outline-none focus:ring-2 focus:ring-teal-700">پرونده</a>
                        </div>
                    </div>
                    @if ($dayAppointments->isEmpty())
                        <p class="mt-3 text-sm text-slate-500">نوبتی برای این تاریخ ندارد.</p>
                    @else
                        <ul class="mt-3 space-y-2">
                            @foreach ($dayAppointments as $appointment)
                                <li class="flex flex-wrap items-center justify-between gap-2 rounded-lg bg-slate-50 px-3 py-2 text-sm">
                                    <div>
                                        <a href="{{ route('appointments.show', $appointment) }}" class="font-medium text-teal-900 hover:underline focus:outline-none focus:ring-2 focus:ring-teal-700">{{ $clinicDay->formatClock($appointment->starts_at) }} — {{ $appointment->treatmentService?->name }}</a>
                                        <span class="text-slate-600"> · {{ $appointment->status->label() }}</span>
                                    </div>
                                    @if ($canOperate && $appointment->status === \App\Enums\AppointmentStatus::Confirmed && ! $appointment->visit)
                                        <form method="POST" action="{{ route('reception.check-in', $appointment) }}" data-booking-submit>
                                            @csrf
                                            <button type="submit" class="rounded-lg bg-teal-700 px-3 py-1.5 text-xs font-semibold text-white hover:bg-teal-800 focus:outline-none focus:ring-2 focus:ring-teal-700">چک‌این</button>
                                        </form>
                                    @elseif ($appointment->visit)
                                        <a href="{{ route('reception.visits.show', $appointment->visit) }}" class="text-xs text-teal-800 hover:underline focus:outline-none focus:ring-2 focus:ring-teal-700">ویزیت</a>
                                    @endif
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </article>
            @endforeach
        </div>
    @endif
</x-management-layout>
