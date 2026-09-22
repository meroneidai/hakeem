<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\PasswordResetService;
use App\Support\AccountIdentifier;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class ResetPasswordController extends Controller
{
    public function create(Request $request): View
    {
        return view('auth.reset-password', [
            'identifier' => $request->string('identifier')->toString(),
            'token' => $request->string('token')->toString(),
            'channel' => $request->string('channel')->toString(),
        ]);
    }

    public function store(Request $request, PasswordResetService $resets): RedirectResponse
    {
        $validated = $request->validate([
            'identifier' => ['required', 'string', 'max:190'],
            'token' => ['required', 'string', 'max:255'],
            'password' => ['required', 'confirmed', Password::min(8)],
        ]);

        $identifier = AccountIdentifier::from($validated['identifier']);
        $user = $resets->reset($identifier, $validated['token'], $validated['password']);

        Auth::login($user, true);
        $request->session()->regenerate();
        $request->session()->put('locale', $user->preferred_language);

        return redirect()->route('account.edit')->with('status', __('auth.password_updated'));
    }
}
