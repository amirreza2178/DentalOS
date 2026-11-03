<x-management-layout title="میز کار">
    <x-page-header title="میز کار" description="عملیات روزانه از اینجا شروع می‌شود. آماری ساختگی نمایش داده نمی‌شود." />
    <div class="grid gap-3 sm:grid-cols-2">
        @if (auth()->user()->hasRole(\App\Enums\UserRole::Admin, \App\Enums\UserRole::Receptionist, \App\Enums\UserRole::Specialist))
            <a href="{{ route('reception.day') }}" class="rounded-2xl border border-slate-200 bg-white p-5 hover:border-teal-700 focus:outline-none focus:ring-2 focus:ring-teal-700">
                <p class="text-base font-semibold text-slate-900">برنامه امروز</p>
                <p class="mt-1 text-sm text-slate-600">نوبت‌های روز، حضور بیمار و ورود به ویزیت</p>
            </a>
            <a href="{{ route('clinical.follow-ups.index') }}" class="rounded-2xl border border-slate-200 bg-white p-5 hover:border-teal-700 focus:outline-none focus:ring-2 focus:ring-teal-700">
                <p class="text-base font-semibold text-slate-900">پیگیری‌ها</p>
                <p class="mt-1 text-sm text-slate-600">درخواست مراجعه بعدی بدون ساخت خودکار نوبت</p>
            </a>
            <a href="{{ route('clinical.plans.index') }}" class="rounded-2xl border border-slate-200 bg-white p-5 hover:border-teal-700 focus:outline-none focus:ring-2 focus:ring-teal-700">
                <p class="text-base font-semibold text-slate-900">برنامه‌های درمانی</p>
                <p class="mt-1 text-sm text-slate-600">ثبت و پیگیری تصمیم‌های بالینی متخصص</p>
            </a>
        @endif
        @if (auth()->user()->hasRole(\App\Enums\UserRole::Admin, \App\Enums\UserRole::Specialist, \App\Enums\UserRole::ClinicalStaff, \App\Enums\UserRole::Receptionist))
            <a href="{{ route('clinical.tasks.index') }}" class="rounded-2xl border border-slate-200 bg-white p-5 hover:border-teal-700 focus:outline-none focus:ring-2 focus:ring-teal-700">
                <p class="text-base font-semibold text-slate-900">وظایف درمانی</p>
                <p class="mt-1 text-sm text-slate-600">{{ auth()->user()->hasRole(\App\Enums\UserRole::ClinicalStaff) ? 'وظایف محول‌شده به شما' : 'وضعیت اقدامات محول‌شده' }}</p>
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
            <a href="{{ route('booking.requests.index') }}" class="rounded-2xl border border-slate-200 bg-white p-5 hover:border-teal-700 focus:outline-none focus:ring-2 focus:ring-teal-700">
                <p class="text-base font-semibold text-slate-900">درخواست‌های آنلاین</p>
                <p class="mt-1 text-sm text-slate-600">تأیید یا رد رزرو عمومی</p>
            </a>
        @endif
    </div>
</x-management-layout>
