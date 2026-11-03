<x-management-layout title="ثبت پیگیری">
    <x-page-header title="ثبت نیاز به مراجعه بعدی" description="{{ $patient->fullName() }} — این فرم نوبت قطعی نمی‌سازد.">
        <a href="{{ route('patients.show', $patient) }}" class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-teal-700">پرونده</a>
    </x-page-header>

    <form method="POST" action="{{ route('clinical.follow-ups.store', $patient) }}" class="space-y-4 rounded-2xl border border-slate-200 bg-white p-4">
        @csrf
        @if ($visitId)
            <input type="hidden" name="patient_visit_id" value="{{ $visitId }}">
        @endif
        <x-text-field name="proposed_window_start" label="شروع بازه پیشنهادی (جلالی)" :value="old('proposed_window_start')" placeholder="۱۴۰۴/۰۸/۲۰" required />
        <x-text-field name="proposed_window_end" label="پایان بازه پیشنهادی (جلالی)" :value="old('proposed_window_end')" placeholder="۱۴۰۴/۰۸/۳۰" required />
        <label class="block space-y-1" for="treatment_service_id">
            <span class="text-sm font-medium">خدمت پیشنهادی (اختیاری)</span>
            <select id="treatment_service_id" name="treatment_service_id" class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm focus:border-teal-700 focus:outline-none focus:ring-2 focus:ring-teal-700/30">
                <option value="">بدون خدمت مشخص</option>
                @foreach ($services as $service)
                    <option value="{{ $service->id }}" @selected((string) old('treatment_service_id') === (string) $service->id)>{{ $service->name }}</option>
                @endforeach
            </select>
        </label>
        <x-textarea-field name="operational_note" label="یادداشت عملیاتی برای پذیرش" :value="old('operational_note')" />
        <button type="submit" class="rounded-lg bg-teal-700 px-4 py-2 text-sm font-semibold text-white hover:bg-teal-800 focus:outline-none focus:ring-2 focus:ring-teal-700">ثبت پیگیری</button>
    </form>
</x-management-layout>
