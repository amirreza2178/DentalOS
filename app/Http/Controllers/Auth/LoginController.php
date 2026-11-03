<?php

namespace App\Http\Controllers\Auth;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class LoginController extends Controller
{
    public function create(): View
    {
        return view('auth.login');
    }

    public function store(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ], [
            'email.required' => 'ایمیل را وارد کنید.',
            'password.required' => 'رمز عبور را وارد کنید.',
        ]);

        $key = 'login:'.$request->ip().'|'.strtolower($credentials['email']);

        if (RateLimiter::tooManyAttempts($key, 5)) {
            throw ValidationException::withMessages([
                'email' => 'تلاش‌های ورود بیش از حد مجاز است. چند دقیقه بعد دوباره تلاش کنید.',
            ]);
        }

        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            RateLimiter::hit($key, 60);

            throw ValidationException::withMessages([
                'email' => 'اطلاعات ورود درست نیست.',
            ]);
        }

        $user = $request->user();

        if ($user === null || ! $user->is_active) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            throw ValidationException::withMessages([
                'email' => 'این حساب غیرفعال است.',
            ]);
        }

        RateLimiter::clear($key);
        $request->session()->regenerate();

        return redirect()->intended($this->homePathFor($user));
    }

    private function homePathFor(User $user): string
    {
        if ($user->hasRole(UserRole::Admin, UserRole::Receptionist, UserRole::Specialist)) {
            return route('reception.day');
        }

        if ($user->hasRole(UserRole::ClinicalStaff)) {
            return route('clinical.tasks.index');
        }

        return route('dashboard');
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
