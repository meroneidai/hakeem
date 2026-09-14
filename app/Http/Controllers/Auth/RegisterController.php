<?php

namespace App\Http\Controllers\Auth;

use App\Enums\RoleName;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

/**
 * Patient registration — phone, name and password only (md_files/01 §6.1).
 * Everything else on the profile stays optional and deferred.
 */
class RegisterController extends Controller
{
    public function create(): View
    {
        return view('auth.register');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'phone' => ['required', 'string', 'max:32'],
            'password' => ['required', 'confirmed', Password::min(8)],
        ]);

        $phone = User::normalizePhone($validated['phone']);

        $request->validate(
            ['phone' => [Rule::unique('users', 'phone')->where(fn ($q) => $q->where('phone', $phone))]],
            [],
            ['phone' => __('auth.phone')],
        );

        $user = User::create([
            'name' => $validated['name'],
            'phone' => $phone,
            'password' => $validated['password'],
            'preferred_language' => app()->getLocale(),
        ]);

        $user->assignRole(RoleName::Patient);

        Auth::login($user, true);
        $request->session()->regenerate();

        return redirect()->intended(url('/'));
    }
}
