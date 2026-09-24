<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\InsuranceProvider;
use App\Services\PatientRegistrar;
use App\Support\AccountIdentifier;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class RegisterController extends Controller
{
    public function create(Request $request): View
    {
        if ($request->filled('ref')) {
            $request->session()->put('referral_code', strtoupper(trim($request->string('ref')->toString())));
        }

        return view('auth.register', [
            'insuranceProviders' => InsuranceProvider::selectable(),
        ]);
    }

    public function store(Request $request, PatientRegistrar $registrar): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'identifier' => ['nullable', 'string', 'max:190'],
            'phone' => ['nullable', 'string', 'max:32'],
            'email' => ['nullable', 'string', 'max:190'],
            'password' => ['required', 'confirmed', Password::min(8)],
            'insurance_provider_id' => ['nullable', 'integer', InsuranceProvider::activeIdRule()],
            'ref' => ['nullable', 'string', 'max:32'],
        ]);

        $identifier = AccountIdentifier::fromRequest(
            $validated['identifier'] ?? null,
            $validated['phone'] ?? null,
            $validated['email'] ?? null,
        );

        $user = $registrar->registerAndLogin(
            $validated['name'],
            $identifier,
            $validated['password'],
            $request->session()->pull('referral_code') ?: ($validated['ref'] ?? null),
            $validated['insurance_provider_id'] ?? null,
        );

        $request->session()->regenerate();
        $request->session()->put('locale', $user->preferred_language);

        $status = $identifier->channel === 'email'
            ? __('auth.verify_sent_email')
            : __('auth.verify_sent_phone');

        return redirect()->intended(route('account.edit'))->with('status', $status);
    }
}
