<x-management-layout title="جزئیات نوبت">
    <x-page-header title="نوبت {{ $appointment->patient->fullName() }}" description="{{ $appointment->status->label() }} — {{ $appointment->source->label() }}">
        <a href="{{ route('reception.day') }}" class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-teal-700">میز پذیرش</a>
        <a href="{{ route('appointments.index') }}" class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-teal-700">فهرست</a>
        @can('checkIn', $appointment)
            @if ($appointment->status === App\Enums\AppointmentStatus::Confirmed && ! $appointment->visit)
                <form method="POST" action="{{ route('reception.check-in', $appointment) }}" data-booking-submit>
                    @csrf
                    <button type="submit" class="rounded-lg bg-teal-700 px-4 py-2 text-sm font-semibold text-white hover:bg-teal-800 focus:outline-none focus:ring-2 focus:ring-teal-700">چک‌این</button>
                </form>
            @endif
        @endcan
        @if ($appointment->visit)
            <a href="{{ route('reception.visits.show', $appointment->visit) }}" class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-teal-700">ویزیت</a>
        @endif
        @can('reschedule', $appointment)
            @if ($appointment->status->canTransitionTo(App\Enums\AppointmentStatus::Rescheduled))
                <a href="{{ route('appointments.reschedule', $appointment) }}" class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-teal-700">جابه‌جایی</a>
            @endif
        @endcan
    </x-page-header>

    @if ($errors->any())
        <x-alert tone="warning" role="alert">{{ $errors->first() }}</x-alert>
    @endif

    <dl class="grid gap-4 rounded-2xl border border-slate-200 bg-white p-4 sm:grid-cols-2">
        <div><dt class="text-xs text-slate-500">بیمار</dt><dd><a class="text-teal-800 hover:underline" href="{{ route('patients.show', $appointment->patient) }}">{{ $appointment->patient->fullName() }}</a></dd></div>
        <div><dt class="text-xs text-slate-500">خدمت</dt><dd>{{ $appointment->treatmentService->name }}</dd></div>
        <div><dt class="text-xs text-slate-500">شروع</dt><dd>{{ $calendar->formatDate($appointment->starts_at) }} {{ $appointment->starts_at->timezone(config('app.timezone'))->format('H:i') }}</dd></div>
        <div><dt class="text-xs text-slate-500">پایان حضور</dt><dd>{{ $appointment->ends_at->timezone(config('app.timezone'))->format('H:i') }}</dd></div>
        <div><dt class="text-xs text-slate-500">ثبت</dt><dd>{{ $appointment->bookedBy?->name ?? '—' }}</dd></div>
        <div><dt class="text-xs text-slate-500">سیاست تأیید ذخیره‌شده</dt><dd>{{ $appointment->confirmation_mode ? (\App\Enums\BookingConfirmationMode::tryFrom($appointment->confirmation_mode)?->label() ?? $appointment->confirmation_mode) : 'تنظیم نشده' }}</dd></div>
        @if ($appointment->rescheduledFrom)
            <div class="sm:col-span-2"><dt class="text-xs text-slate-500">جابه‌جا شده از</dt><dd><a class="text-teal-800 hover:underline" href="{{ route('appointments.show', $appointment->rescheduledFrom) }}">نوبت {{ $appointment->rescheduled_from_id }}</a></dd></div>
        @endif
        @if ($appointment->rescheduledTo)
            <div class="sm:col-span-2"><dt class="text-xs text-slate-500">نوبت جایگزین</dt><dd><a class="text-teal-800 hover:underline" href="{{ route('appointments.show', $appointment->rescheduledTo) }}">نوبت {{ $appointment->rescheduledTo->id }}</a></dd></div>
        @endif
        <div class="sm:col-span-2"><dt class="text-xs text-slate-500">یادداشت پذیرش</dt><dd class="whitespace-pre-wrap">{{ $appointment->patient_notes ?: '—' }}</dd></div>
    </dl>

    <section class="mt-4 rounded-2xl border border-slate-200 bg-white p-4">
        <h2 class="mb-3 text-sm font-semibold">منابع</h2>
        @if ($appointment->allocations->isEmpty())
            <p class="text-sm text-slate-600">منبعی برای این نوبت ثبت نشده است.</p>
        @else
            <ul class="space-y-2 text-sm">
                @foreach ($appointment->allocations as $allocation)
                    <li>
                        {{ $allocation->resource_type->label() }}:
                        @if ($allocation->resource_type === App\Enums\ResourceType::Chair)
                            {{ $chairs[$allocation->resource_id]->name ?? '—' }}
                        @else
                            {{ $people[$allocation->resource_id]->name ?? '—' }}
                        @endif
                        تا {{ $allocation->ends_at->timezone(config('app.timezone'))->format('H:i') }}
                        @unless ($appointment->status->occupiesResources())
                            <span class="text-slate-500">— این وضعیت منبع را اشغال نمی‌کند</span>
                        @endunless
                    </li>
                @endforeach
            </ul>
        @endif
    </section>

    @include('partials.cancel-appointment-form', ['appointment' => $appointment])
</x-management-layout>
