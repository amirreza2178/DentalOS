<x-management-layout title="ثبت برنامه درمانی">
    <x-page-header title="ثبت برنامه درمانی" description="{{ $patient->fullName() }} — {{ $patient->code }}. سیستم تشخیص یا پیشنهاد درمان تولید نمی‌کند.">
        <a href="{{ route('patients.show', $patient) }}" class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-teal-700">پرونده</a>
    </x-page-header>

    <form method="POST" action="{{ route('clinical.plans.store', $patient) }}" class="space-y-4 rounded-2xl border border-slate-200 bg-white p-4">
        @csrf
        @if ($visitId)
            <input type="hidden" name="patient_visit_id" value="{{ $visitId }}">
        @endif
        <x-textarea-field name="clinical_summary" label="تصمیم بالینی ثبت‌شده توسط متخصص" :value="old('clinical_summary')" required />
        <x-textarea-field name="operational_summary" label="خلاصه عملیاتی برای پذیرش / کادر (بدون جزئیات تشخیصی حساس)" :value="old('operational_summary')" />
        <label class="block space-y-1" for="status">
            <span class="text-sm font-medium">وضعیت</span>
            <select id="status" name="status" class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm focus:border-teal-700 focus:outline-none focus:ring-2 focus:ring-teal-700/30">
                @foreach ($statuses as $status)
                    <option value="{{ $status->value }}" @selected(old('status', 'active') === $status->value)>{{ $status->label() }}</option>
                @endforeach
            </select>
        </label>
        <button type="submit" class="rounded-lg bg-teal-700 px-4 py-2 text-sm font-semibold text-white hover:bg-teal-800 focus:outline-none focus:ring-2 focus:ring-teal-700">ثبت برنامه</button>
    </form>
</x-management-layout>
