<x-management-layout title="{{ $patient->fullName() }}">
    <x-page-header title="{{ $patient->fullName() }}" description="شناسه {{ $patient->code }}">
        @can('update', $patient)
            <a href="{{ route('patients.edit', $patient) }}" class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-teal-700">ویرایش</a>
        @endcan
        @can('viewClinicalNotes', $patient)
            <a href="{{ route('patients.clinical-notes', $patient) }}" class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-teal-700">یادداشت بالینی</a>
        @endcan
    </x-page-header>

    @if ($duplicates->isNotEmpty())
        <x-alert tone="warning">این موبایل با {{ $duplicates->count() }} پرونده دیگر مشترک است. پرونده‌ها ادغام نشده‌اند.</x-alert>
    @endif

    <dl class="grid gap-4 rounded-2xl border border-slate-200 bg-white p-4 sm:grid-cols-2">
        <div><dt class="text-xs text-slate-500">موبایل</dt><dd>{{ $patient->mobile ?: '—' }}</dd></div>
        <div><dt class="text-xs text-slate-500">تماس دیگر</dt><dd>{{ $patient->alt_phone ?: '—' }}</dd></div>
        <div><dt class="text-xs text-slate-500">کد ملی</dt><dd>{{ $patient->national_id ?: '—' }}</dd></div>
        <div><dt class="text-xs text-slate-500">تاریخ تولد</dt><dd>{{ $birthDate ?: '—' }}</dd></div>
        <div><dt class="text-xs text-slate-500">جنسیت</dt><dd>{{ $patient->gender?->label() ?? '—' }}</dd></div>
        <div><dt class="text-xs text-slate-500">وضعیت</dt><dd>{{ $patient->trashed() ? 'بایگانی' : $patient->status->label() }}</dd></div>
        @can('viewAdminNotes', $patient)
            <div class="sm:col-span-2"><dt class="text-xs text-slate-500">یادداشت اداری</dt><dd class="whitespace-pre-wrap">{{ $patient->notes_admin ?: '—' }}</dd></div>
        @endcan
    </dl>

    <section class="mt-4 rounded-2xl border border-slate-200 bg-white p-4">
        <div class="mb-3 flex items-center justify-between gap-3">
            <h2 class="text-sm font-semibold">نوبت‌ها</h2>
            @can('create', App\Models\Appointment::class)
                <a href="{{ route('appointments.create', ['patient_id' => $patient->id]) }}" class="text-sm font-medium text-teal-800 hover:underline focus:outline-none focus:ring-2 focus:ring-teal-700">نوبت جدید</a>
            @endcan
        </div>
        @if ($appointments->isEmpty())
            <p class="text-sm text-slate-600">نوبتی برای این بیمار ثبت نشده است.</p>
        @else
            <ul class="space-y-2 text-sm">
                @foreach ($appointments as $appointment)
                    <li><a class="text-teal-800 hover:underline" href="{{ route('appointments.show', $appointment) }}">{{ $calendar->formatDate($appointment->starts_at) }} {{ $appointment->starts_at->timezone(config('app.timezone'))->format('H:i') }} — {{ $appointment->treatmentService?->name }} — {{ $appointment->status->label() }}</a></li>
                @endforeach
            </ul>
        @endif
    </section>

    <div class="mt-4 flex flex-wrap gap-2">
        @if ($patient->trashed())
            @can('restore', $patient)
                <form method="POST" action="{{ route('patients.restore', $patient) }}">
                    @csrf
                    <button type="submit" class="rounded-lg bg-teal-700 px-4 py-2 text-sm font-semibold text-white focus:outline-none focus:ring-2 focus:ring-teal-700">بازگردانی از بایگانی</button>
                </form>
            @endcan
        @else
            @can('archive', $patient)
                <form method="POST" action="{{ route('patients.archive', $patient) }}">
                    @csrf
                    <button type="submit" class="rounded-lg border border-rose-300 px-4 py-2 text-sm font-medium text-rose-800 hover:bg-rose-50 focus:outline-none focus:ring-2 focus:ring-rose-700">بایگانی</button>
                </form>
            @endcan
        @endif
    </div>
</x-management-layout>
