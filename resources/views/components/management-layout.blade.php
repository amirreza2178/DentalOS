@props(['title'])
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    @include('partials.head', ['title' => $title])
</head>
<body class="min-h-screen bg-slate-100 font-sans text-slate-900 antialiased">
    <a href="#content" class="sr-only focus:not-sr-only focus:absolute focus:right-4 focus:top-4 focus:z-50 focus:rounded-lg focus:bg-white focus:px-3 focus:py-2">رفتن به محتوا</a>
    <div class="lg:grid lg:min-h-screen lg:grid-cols-[16rem_1fr]">
        <aside id="app-sidebar" class="hidden border-b border-slate-800 bg-slate-900 text-slate-100 lg:flex lg:flex-col">
            @include('partials.nav')
        </aside>
        <div class="min-w-0">
            <header class="flex items-center justify-between gap-3 border-b border-slate-200 bg-white px-4 py-3">
                <button type="button" data-nav-toggle aria-controls="app-sidebar" aria-expanded="false" class="rounded-lg border border-slate-300 px-3 py-2 text-sm font-medium focus:outline-none focus:ring-2 focus:ring-teal-700 lg:hidden">
                    منو
                </button>
                <div class="min-w-0 text-sm">
                    <p class="truncate font-semibold">{{ auth()->user()->name }}</p>
                    <p class="truncate text-slate-500">{{ auth()->user()->role->label() }}</p>
                </div>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="rounded-lg border border-slate-300 px-3 py-2 text-sm font-medium hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-teal-700">خروج</button>
                </form>
            </header>
            <main id="content" class="mx-auto w-full max-w-6xl px-4 py-6">
                @if (session('status'))
                    <x-alert>{{ session('status') }}</x-alert>
                @endif
                @if (session('duplicate_notice'))
                    <x-alert tone="warning">{{ session('duplicate_notice') }}</x-alert>
                @endif
                @if ($errors->any())
                    <x-alert tone="warning" role="alert">
                        <ul class="space-y-1">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </x-alert>
                @endif
                {{ $slot }}
            </main>
        </div>
    </div>
</body>
</html>
