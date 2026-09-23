<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\AccountIdentifier;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
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
        $request->validate([
            'identifier' => ['nullable', 'string', 'max:190'],
            'phone' => ['nullable', 'string', 'max:32'],
            'email' => ['nullable', 'string', 'max:190'],
            'password' => ['required', 'string'],
        ]);

        $identifier = AccountIdentifier::fromRequest(
            $request->input('identifier'),
            $request->input('phone'),
            $request->input('email'),
        );

        $throttleKey = $identifier->throttleKey('login', $request->ip());

        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            throw ValidationException::withMessages([
                'identifier' => __('auth.throttle', ['seconds' => RateLimiter::availableIn($throttleKey)]),
            ]);
        }

        $user = $identifier->findUser();

        if (! $user || ! Hash::check($request->input('password'), $user->password)) {
            RateLimiter::hit($throttleKey, 60);

            throw ValidationException::withMessages(['identifier' => __('auth.failed')]);
        }

        if ($user->isInternalStaff()) {
            $shown = $user->email ?: $user->phone;
            RateLimiter::clear($throttleKey);

            return redirect()
                ->route('admin.login')
                ->with('status', __('auth.use_admin_login'))
                ->withInput(['identifier' => $shown, 'email' => $shown]);
        }

        if (! $user->is_active) {
            throw ValidationException::withMessages(['identifier' => __('auth.inactive')]);
        }

        Auth::login($user, $request->boolean('remember'));
        RateLimiter::clear($throttleKey);
        $request->session()->regenerate();
        $request->session()->put('locale', $user->preferred_language);
        $user->forceFill(['last_login_at' => now()])->save();

        return redirect()->intended($this->homeFor($user));
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
