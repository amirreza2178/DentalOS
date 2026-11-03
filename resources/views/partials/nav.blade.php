@php
    $user = auth()->user();
    $links = [
        ['label' => 'میز کار', 'route' => 'dashboard', 'show' => true],
        ['label' => 'میز پذیرش', 'route' => 'reception.day', 'active' => 'reception.*', 'show' => $user->hasRole(\App\Enums\UserRole::Admin, \App\Enums\UserRole::Receptionist, \App\Enums\UserRole::Specialist)],
        ['label' => 'درخواست‌های آنلاین', 'route' => 'booking.requests.index', 'active' => 'booking.requests.*', 'show' => $user->hasRole(\App\Enums\UserRole::Admin, \App\Enums\UserRole::Receptionist)],
        ['label' => 'پیگیری‌ها', 'route' => 'clinical.follow-ups.index', 'active' => 'clinical.follow-ups.*', 'show' => $user->hasRole(\App\Enums\UserRole::Admin, \App\Enums\UserRole::Receptionist, \App\Enums\UserRole::Specialist)],
        ['label' => 'برنامه‌های درمانی', 'route' => 'clinical.plans.index', 'active' => 'clinical.plans.*', 'show' => $user->hasRole(\App\Enums\UserRole::Admin, \App\Enums\UserRole::Receptionist, \App\Enums\UserRole::Specialist)],
        ['label' => 'وظایف درمانی', 'route' => 'clinical.tasks.index', 'active' => 'clinical.tasks.*', 'show' => $user->hasRole(\App\Enums\UserRole::Admin, \App\Enums\UserRole::Receptionist, \App\Enums\UserRole::Specialist, \App\Enums\UserRole::ClinicalStaff)],
        ['label' => 'بیماران', 'route' => 'patients.index', 'show' => $user->hasRole(\App\Enums\UserRole::Admin, \App\Enums\UserRole::Receptionist, \App\Enums\UserRole::Specialist)],
        ['label' => 'متخصصان', 'route' => 'staff.index', 'params' => 'specialists', 'show' => $user->hasRole(\App\Enums\UserRole::Admin, \App\Enums\UserRole::Receptionist, \App\Enums\UserRole::Specialist)],
        ['label' => 'کادر بالینی', 'route' => 'staff.index', 'params' => 'clinical-staff', 'show' => $user->hasRole(\App\Enums\UserRole::Admin, \App\Enums\UserRole::Receptionist, \App\Enums\UserRole::Specialist)],
        ['label' => 'پذیرش', 'route' => 'staff.index', 'params' => 'receptionists', 'show' => $user->hasRole(\App\Enums\UserRole::Admin)],
        ['label' => 'یونیت‌ها', 'route' => 'chairs.index', 'show' => $user->hasRole(\App\Enums\UserRole::Admin, \App\Enums\UserRole::Receptionist, \App\Enums\UserRole::Specialist)],
        ['label' => 'خدمات', 'route' => 'services.index', 'show' => $user->hasRole(\App\Enums\UserRole::Admin, \App\Enums\UserRole::Receptionist, \App\Enums\UserRole::Specialist)],
        ['label' => 'نوبت‌ها', 'route' => 'appointments.index', 'active' => 'appointments.*', 'show' => $user->hasRole(\App\Enums\UserRole::Admin, \App\Enums\UserRole::Receptionist, \App\Enums\UserRole::Specialist)],
        ['label' => 'شیفت‌ها', 'route' => 'shifts.index', 'active' => 'shifts.*', 'show' => $user->hasRole(\App\Enums\UserRole::Admin)],
        ['label' => 'برنامه کارکنان', 'route' => 'schedules.index', 'show' => $user->hasRole(\App\Enums\UserRole::Admin)],
        ['label' => 'تعطیلات', 'route' => 'holidays.index', 'show' => $user->hasRole(\App\Enums\UserRole::Admin)],
        ['label' => 'تنظیمات', 'route' => 'settings.edit', 'show' => $user->hasRole(\App\Enums\UserRole::Admin)],
    ];
@endphp
<div class="flex items-center justify-between px-4 py-4 lg:block">
    <a href="{{ route('dashboard') }}" class="block rounded-lg px-2 py-1 focus:outline-none focus:ring-2 focus:ring-teal-400">
        <span class="text-lg font-bold">DentalOS</span>
        <span class="mt-1 block text-xs text-slate-400">مدیریت کلینیک</span>
    </a>
</div>
<nav class="flex flex-col gap-1 px-3 pb-6" aria-label="ناوبری اصلی">
    @foreach ($links as $link)
        @continue(! $link['show'])
        @php
            $href = isset($link['params']) ? route($link['route'], $link['params']) : route($link['route']);
            $pattern = $link['active'] ?? $link['route'];
            $active = request()->routeIs($pattern) && (! isset($link['params']) || request()->route('directory') === $link['params']);
        @endphp
        <a href="{{ $href }}" @if($active) aria-current="page" @endif class="rounded-lg px-3 py-2 text-sm {{ $active ? 'bg-teal-700 font-semibold text-white' : 'text-slate-200 hover:bg-slate-800' }} focus:outline-none focus:ring-2 focus:ring-teal-400">
            {{ $link['label'] }}
        </a>
    @endforeach
</nav>
