<?php

namespace App\Http\Controllers\Auth;

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
        $validated = $request->validate([
            'phone' => ['required', 'string', 'max:32'],
            'password' => ['required', 'string'],
        ]);

        $phone = User::normalizePhone($validated['phone']);
        $throttleKey = 'login:'.$phone.'|'.$request->ip();

        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            throw ValidationException::withMessages([
                'phone' => __('auth.throttle', ['seconds' => RateLimiter::availableIn($throttleKey)]),
            ]);
        }

        if (! Auth::attempt(['phone' => $phone, 'password' => $validated['password']], $request->boolean('remember'))) {
            RateLimiter::hit($throttleKey, 60);

            throw ValidationException::withMessages(['phone' => __('auth.failed')]);
        }

        if (! $request->user()->is_active) {
            Auth::logout();

            throw ValidationException::withMessages(['phone' => __('auth.inactive')]);
        }

        RateLimiter::clear($throttleKey);
        $request->session()->regenerate();
        $request->session()->put('locale', $request->user()->preferred_language);
        $request->user()->forceFill(['last_login_at' => now()])->save();

        return redirect()->intended($this->homeFor($request->user()));
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('status', __('auth.logged_out'));
    }

    private function homeFor(User $user): string
    {
        return $user->isInternalStaff()
            ? route('admin.dashboard')
            : ($user->isClinicStaff() ? route('clinic.dashboard') : url('/'));
    }
}
