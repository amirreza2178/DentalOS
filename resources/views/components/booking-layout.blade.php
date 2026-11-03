@props(['title', 'step' => null])
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    @include('partials.head', ['title' => $title])
</head>
<body class="dos-booking-shell min-h-screen font-sans text-[color:var(--dos-navy)] antialiased">
    <a href="#booking-content" class="sr-only focus:not-sr-only focus:absolute focus:right-4 focus:top-4 focus:z-50 focus:rounded-lg focus:bg-white focus:px-3 focus:py-2">رفتن به محتوا</a>
    <header class="border-b border-slate-200/80 bg-[color:var(--dos-navy)] text-white">
        <div class="mx-auto flex w-full max-w-3xl items-center justify-between gap-3 px-4 py-4">
            <a href="{{ route('home') }}" class="block min-w-0 focus:outline-none focus:ring-2 focus:ring-[color:var(--dos-accent)]">
                @if ($clinicIdentity->hasLogo())
                    <img src="{{ asset($clinicIdentity->logoPath()) }}" alt="{{ $clinicIdentity->clinicDisplayName() }}" class="h-9 w-auto max-w-[12rem] object-contain">
                @else
                    <span class="block text-lg font-bold leading-tight tracking-tight sm:text-xl">{{ $clinicIdentity->publicNavBrand() }}</span>
                @endif
                <span class="mt-1 block truncate text-xs text-slate-300">{{ $clinicIdentity->doctorTitle() }}</span>
            </a>
            @auth
                <a href="{{ route('dashboard') }}" class="shrink-0 rounded-lg border border-white/20 px-3 py-2 text-sm hover:bg-white/10 focus:outline-none focus:ring-2 focus:ring-[color:var(--dos-accent)]">میز کار</a>
            @else
                <a href="{{ route('login') }}" class="shrink-0 rounded-lg border border-white/20 px-3 py-2 text-sm hover:bg-white/10 focus:outline-none focus:ring-2 focus:ring-[color:var(--dos-accent)]">ورود کارکنان</a>
            @endauth
        </div>
    </header>

    <main id="booking-content" class="mx-auto w-full max-w-3xl px-4 py-6">
        @if ($step !== null)
            @php
                $labels = [1 => 'خدمت', 2 => 'تاریخ', 3 => 'ساعت', 4 => 'اطلاعات', 5 => 'نتیجه'];
            @endphp
            <nav aria-label="مراحل رزرو" class="mb-6">
                <ol class="flex flex-wrap gap-2 text-xs sm:text-sm">
                    @foreach ($labels as $number => $label)
                        <li class="rounded-full px-3 py-1.5 {{ (int) $step === $number ? 'bg-[color:var(--dos-navy)] font-semibold text-white' : ((int) $step > $number ? 'bg-[color:var(--dos-accent)]/30 text-[color:var(--dos-navy)]' : 'bg-white/70 text-slate-500') }}">
                            <span class="ml-1 tabular-nums">{{ $number }}</span>{{ $label }}
                        </li>
                    @endforeach
                </ol>
            </nav>
        @endif

        @if ($errors->any())
            <div class="mb-4 rounded-xl border border-amber-300 bg-amber-50 px-4 py-3 text-sm text-amber-950" role="alert">
                <ul class="space-y-1">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        {{ $slot }}
    </main>
</body>
</html>
