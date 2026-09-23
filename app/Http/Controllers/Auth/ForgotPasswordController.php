<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\PasswordResetService;
use App\Support\AccountIdentifier;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class ForgotPasswordController extends Controller
{
    public function create(): View
    {
        return view('auth.forgot-password');
    }

    public function store(Request $request, PasswordResetService $resets): RedirectResponse
    {
        $request->validate([
            'identifier' => ['nullable', 'string', 'max:190'],
            'phone' => ['nullable', 'string', 'max:32'],
            'email' => ['nullable', 'string', 'max:190'],
        ]);

        $identifier = AccountIdentifier::fromRequest(
            $request->input('identifier'),
            $request->input('phone'),
            $request->input('email'),
        );

        $throttleKey = $identifier->throttleKey('password-forgot', $request->ip());

        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            throw ValidationException::withMessages([
                'identifier' => __('auth.throttle', ['seconds' => RateLimiter::availableIn($throttleKey)]),
            ]);
        }

        RateLimiter::hit($throttleKey, 600);

        $channel = $resets->send($identifier);
        $shown = $identifier->email ?? $identifier->phone ?? $identifier->raw;

        return redirect()
            ->route('password.reset', ['identifier' => $shown, 'channel' => $channel])
            ->with('status', $channel === 'phone' ? __('auth.reset_sent_phone') : __('auth.reset_sent_email'));
    }
}
