<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    @include('partials.head', ['title' => 'ورود'])
</head>
<body class="flex min-h-screen items-center justify-center bg-slate-100 px-4 font-sans text-slate-900">
    <main class="w-full max-w-md rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
        <h1 class="text-2xl font-bold">ورود به DentalOS</h1>
        <p class="mt-2 text-sm leading-6 text-slate-600">ورود کارکنان کلینیک. درخواست نوبت عمومی بدون ورود در دسترس است.</p>
        <p class="mt-2 text-sm"><a href="{{ route('booking.services') }}" class="font-medium text-teal-800 hover:underline focus:outline-none focus:ring-2 focus:ring-teal-700">رفتن به رزرو آنلاین</a></p>
        <form method="POST" action="{{ route('login.store') }}" class="mt-6 space-y-4">
            @csrf
            <x-text-field name="email" type="email" label="ایمیل" required autocomplete="username" />
            <x-text-field name="password" type="password" label="رمز عبور" required autocomplete="current-password" />
            <label class="flex items-center gap-2 text-sm text-slate-700" for="remember">
                <input id="remember" name="remember" type="checkbox" value="1" class="size-4 rounded border-slate-300 text-teal-700 focus:ring-teal-700">
                مرا به خاطر بسپار
            </label>
            <button type="submit" class="w-full rounded-lg bg-teal-700 px-4 py-2 text-sm font-semibold text-white hover:bg-teal-800 focus:outline-none focus:ring-2 focus:ring-teal-700 focus:ring-offset-2">ورود</button>
        </form>
    </main>
</body>
</html>
