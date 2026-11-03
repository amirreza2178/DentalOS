@php
    use App\Enums\UserRole;

    $user = auth()->user();
    $isAdmin = $user->hasRole(UserRole::Admin);
    $isReceptionist = $user->hasRole(UserRole::Receptionist);
    $isSpecialist = $user->hasRole(UserRole::Specialist);
    $isClinicalStaff = $user->hasRole(UserRole::ClinicalStaff);
    $specialistOnly = $isSpecialist && ! $isAdmin && ! $isReceptionist;
    $receptionOps = $isAdmin || $isReceptionist;
    $scheduleLabel = $specialistOnly ? 'برنامه من' : 'مرکز فرمان';
    $homeRoute = $receptionOps || $isSpecialist
        ? 'reception.day'
        : ($isClinicalStaff ? 'clinical.tasks.index' : 'dashboard');

    $primary = [];
    $secondary = [];

    if ($receptionOps || $isSpecialist) {
        $primary[] = ['label' => $scheduleLabel, 'route' => 'reception.day', 'active' => 'reception.day'];
    }

    if ($receptionOps) {
        $primary[] = ['label' => 'بیماران', 'route' => 'patients.index', 'active' => 'patients.*'];
        $primary[] = ['label' => 'نوبت‌ها', 'route' => 'appointments.index', 'active' => 'appointments.*'];
        $primary[] = ['label' => 'درخواست‌های آنلاین', 'route' => 'booking.requests.index', 'active' => 'booking.requests.*'];
        $primary[] = ['label' => 'پیگیری‌ها', 'route' => 'clinical.follow-ups.index', 'active' => 'clinical.follow-ups.*'];
        $primary[] = ['label' => 'هفته', 'route' => 'reception.week', 'active' => 'reception.week'];
    } elseif ($isSpecialist) {
        $primary[] = ['label' => 'بیماران', 'route' => 'patients.index', 'active' => 'patients.*'];
        $primary[] = ['label' => 'نوبت‌ها', 'route' => 'appointments.index', 'active' => 'appointments.*'];
        $primary[] = ['label' => 'پیگیری‌ها', 'route' => 'clinical.follow-ups.index', 'active' => 'clinical.follow-ups.*'];
        $primary[] = ['label' => 'هفته', 'route' => 'reception.week', 'active' => 'reception.week'];
        $primary[] = ['label' => 'برنامه‌های درمانی', 'route' => 'clinical.plans.index', 'active' => 'clinical.plans.*'];
        $primary[] = ['label' => 'وظایف درمانی', 'route' => 'clinical.tasks.index', 'active' => 'clinical.tasks.*'];
    } elseif ($isClinicalStaff) {
        $primary[] = ['label' => 'وظایف درمانی', 'route' => 'clinical.tasks.index', 'active' => 'clinical.tasks.*'];
    }

    $secondary[] = ['label' => 'میز کار', 'route' => 'dashboard'];

    if ($isReceptionist && ! $isAdmin) {
        $secondary[] = ['label' => 'برنامه‌های درمانی', 'route' => 'clinical.plans.index', 'active' => 'clinical.plans.*'];
        $secondary[] = ['label' => 'وظایف درمانی', 'route' => 'clinical.tasks.index', 'active' => 'clinical.tasks.*'];
        $secondary[] = ['label' => 'متخصصان', 'route' => 'staff.index', 'params' => 'specialists'];
        $secondary[] = ['label' => 'کادر بالینی', 'route' => 'staff.index', 'params' => 'clinical-staff'];
        $secondary[] = ['label' => 'یونیت‌ها', 'route' => 'chairs.index'];
        $secondary[] = ['label' => 'خدمات', 'route' => 'services.index'];
    }

    if ($isAdmin) {
        $secondary[] = ['label' => 'برنامه‌های درمانی', 'route' => 'clinical.plans.index', 'active' => 'clinical.plans.*'];
        $secondary[] = ['label' => 'وظایف درمانی', 'route' => 'clinical.tasks.index', 'active' => 'clinical.tasks.*'];
        $secondary[] = ['label' => 'متخصصان', 'route' => 'staff.index', 'params' => 'specialists'];
        $secondary[] = ['label' => 'کادر بالینی', 'route' => 'staff.index', 'params' => 'clinical-staff'];
        $secondary[] = ['label' => 'پذیرش', 'route' => 'staff.index', 'params' => 'receptionists'];
        $secondary[] = ['label' => 'یونیت‌ها', 'route' => 'chairs.index'];
        $secondary[] = ['label' => 'خدمات', 'route' => 'services.index'];
        $secondary[] = ['label' => 'شیفت‌ها', 'route' => 'shifts.index', 'active' => 'shifts.*'];
        $secondary[] = ['label' => 'برنامه کارکنان', 'route' => 'schedules.index'];
        $secondary[] = ['label' => 'تعطیلات', 'route' => 'holidays.index'];
        $secondary[] = ['label' => 'تنظیمات', 'route' => 'settings.edit'];
    }

    if ($specialistOnly) {
        $secondary[] = ['label' => 'متخصصان', 'route' => 'staff.index', 'params' => 'specialists'];
        $secondary[] = ['label' => 'کادر بالینی', 'route' => 'staff.index', 'params' => 'clinical-staff'];
        $secondary[] = ['label' => 'یونیت‌ها', 'route' => 'chairs.index'];
        $secondary[] = ['label' => 'خدمات', 'route' => 'services.index'];
    }
@endphp
<div class="flex items-center justify-between px-4 py-4 lg:block">
    <a href="{{ route($homeRoute) }}" class="block rounded-lg px-2 py-1 focus:outline-none focus:ring-2 focus:ring-teal-400">
        @if (($clinicIdentity ?? null)?->hasLogo())
            <img src="{{ asset($clinicIdentity->logoPath()) }}" alt="{{ $clinicIdentity->clinicDisplayName() }}" class="h-8 w-auto max-w-[11rem] object-contain">
        @else
            <span class="text-base font-bold leading-snug">{{ $clinicIdentity->clinicDisplayName() }}</span>
        @endif
        <span class="mt-1 block text-xs text-slate-400">{{ $clinicIdentity->doctorDisplayName() }}</span>
    </a>
</div>
<nav class="flex flex-col gap-1 px-3 pb-6" aria-label="ناوبری اصلی">
    @foreach ($primary as $link)
        @php
            $href = isset($link['params']) ? route($link['route'], $link['params']) : route($link['route']);
            $pattern = $link['active'] ?? $link['route'];
            $active = request()->routeIs($pattern) && (! isset($link['params']) || request()->route('directory') === $link['params']);
        @endphp
        <a href="{{ $href }}" @if($active) aria-current="page" @endif class="rounded-lg px-3 py-2 text-sm {{ $active ? 'bg-teal-700 font-semibold text-white' : 'text-slate-200 hover:bg-slate-800' }} focus:outline-none focus:ring-2 focus:ring-teal-400">
            {{ $link['label'] }}
        </a>
    @endforeach

    @if (count($secondary) > 0)
        <p class="mt-4 px-3 text-[11px] font-semibold tracking-wide text-slate-500">مدیریت / تنظیمات</p>
        @foreach ($secondary as $link)
            @php
                $href = isset($link['params']) ? route($link['route'], $link['params']) : route($link['route']);
                $pattern = $link['active'] ?? $link['route'];
                $active = request()->routeIs($pattern) && (! isset($link['params']) || request()->route('directory') === $link['params']);
            @endphp
            <a href="{{ $href }}" @if($active) aria-current="page" @endif class="rounded-lg px-3 py-2 text-sm {{ $active ? 'bg-teal-700 font-semibold text-white' : 'text-slate-400 hover:bg-slate-800 hover:text-slate-200' }} focus:outline-none focus:ring-2 focus:ring-teal-400">
                {{ $link['label'] }}
            </a>
        @endforeach
    @endif
</nav>
