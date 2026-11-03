<x-management-layout title="میز کار">
    <x-page-header title="میز کار" description="عملیات روزانه از اینجا شروع می‌شود. آماری ساختگی نمایش داده نمی‌شود." />
    <div class="grid gap-3 sm:grid-cols-2">
        @if (auth()->user()->hasRole(\App\Enums\UserRole::Admin, \App\Enums\UserRole::Receptionist, \App\Enums\UserRole::Specialist))
            <a href="{{ route('reception.day') }}" class="rounded-2xl border border-slate-200 bg-white p-5 hover:border-teal-700 focus:outline-none focus:ring-2 focus:ring-teal-700">
                <p class="text-base font-semibold text-slate-900">میز پذیرش امروز</p>
                <p class="mt-1 text-sm text-slate-600">نوبت‌های روز، چک‌این و صف انتظار</p>
            </a>
        @endif
        @if (auth()->user()->hasRole(\App\Enums\UserRole::Admin, \App\Enums\UserRole::Receptionist))
            <a href="{{ route('patients.create') }}" class="rounded-2xl border border-slate-200 bg-white p-5 hover:border-teal-700 focus:outline-none focus:ring-2 focus:ring-teal-700">
                <p class="text-base font-semibold text-slate-900">ثبت بیمار</p>
                <p class="mt-1 text-sm text-slate-600">پرونده جدید بدون آمار ساختگی</p>
            </a>
            <a href="{{ route('appointments.create') }}" class="rounded-2xl border border-slate-200 bg-white p-5 hover:border-teal-700 focus:outline-none focus:ring-2 focus:ring-teal-700">
                <p class="text-base font-semibold text-slate-900">نوبت داخلی</p>
                <p class="mt-1 text-sm text-slate-600">رزرو روی زمان آزاد با قواعد مشترک</p>
            </a>
            <a href="{{ route('reception.search') }}" class="rounded-2xl border border-slate-200 bg-white p-5 hover:border-teal-700 focus:outline-none focus:ring-2 focus:ring-teal-700">
                <p class="text-base font-semibold text-slate-900">جستجوی بیمار روی میز</p>
                <p class="mt-1 text-sm text-slate-600">نام، موبایل یا شناسه پرونده</p>
            </a>
        @endif
    </div>
</x-management-layout>
