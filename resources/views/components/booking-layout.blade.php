@props(['title', 'step' => null])
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    @include('partials.head', ['title' => $title])
    <style>
        :root {
            --dos-navy: #0B1220;
            --dos-accent: #55E6D0;
            --dos-bg: #F5F7FA;
        }
    </style>
</head>
<body class="min-h-screen font-sans antialiased text-[color:var(--dos-navy)]" style="background: radial-gradient(circle at top right, rgba(85,230,208,0.18), transparent 42%), linear-gradient(180deg, #F5F7FA 0%, #EEF2F7 100%);">
    <a href="#booking-content" class="sr-only focus:not-sr-only focus:absolute focus:right-4 focus:top-4 focus:z-50 focus:rounded-lg focus:bg-white focus:px-3 focus:py-2">رفتن به محتوا</a>
    <header class="border-b border-slate-200/80 bg-[color:var(--dos-navy)] text-white">
        <div class="mx-auto flex w-full max-w-3xl items-center justify-between gap-3 px-4 py-4">
            <a href="{{ route('home') }}" class="block focus:outline-none focus:ring-2 focus:ring-[color:var(--dos-accent)]">
                <span class="text-xl font-bold tracking-tight">DentalOS</span>
                <span class="mt-0.5 block text-xs text-slate-300">درخواست نوبت آنلاین</span>
            </a>
            @auth
                <a href="{{ route('dashboard') }}" class="rounded-lg border border-white/20 px-3 py-2 text-sm hover:bg-white/10 focus:outline-none focus:ring-2 focus:ring-[color:var(--dos-accent)]">میز کار</a>
            @else
                <a href="{{ route('login') }}" class="rounded-lg border border-white/20 px-3 py-2 text-sm hover:bg-white/10 focus:outline-none focus:ring-2 focus:ring-[color:var(--dos-accent)]">ورود کارکنان</a>
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
