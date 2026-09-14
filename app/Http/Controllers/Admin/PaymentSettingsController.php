<?php

namespace App\Http\Controllers\Admin;

use App\Enums\Permission;
use App\Http\Controllers\Controller;
use App\Support\Audit;
use App\Support\Settings;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class PaymentSettingsController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return ['can:'.Permission::ManagePaymentSettings->value];
    }

    public function edit(Settings $settings): View
    {
        return view('admin.payments.edit', [
            'allowedModes' => $settings->get('payments.allowed_modes', ['at_clinic']),
            'defaultMode' => $settings->get('payments.default_mode', 'at_clinic'),
            'allowClinicOverride' => $settings->bool('payments.allow_clinic_override', true),
            'gateway' => $settings->get('payments.gateway', 'none'),
            'gatewayKey' => $settings->get('payments.gateway_key'),
            'hasGatewaySecret' => filled($settings->get('payments.gateway_secret')),
        ]);
    }

    public function update(Request $request, Settings $settings): RedirectResponse
    {
        $validated = $request->validate([
            'allowed_modes' => ['required', 'array', 'min:1'],
            'allowed_modes.*' => [Rule::in(config('hakeem.payment_modes'))],
            'default_mode' => ['required', Rule::in(config('hakeem.payment_modes'))],
            'allow_clinic_override' => ['boolean'],
            'gateway' => ['required', Rule::in(array_keys(config('hakeem.payment_gateways')))],
            'gateway_key' => ['nullable', 'string', 'max:255'],
            'gateway_secret' => ['nullable', 'string', 'max:255'],
        ]);

        // The default mode must itself be one of the enabled modes.
        if (! in_array($validated['default_mode'], $validated['allowed_modes'], true)) {
            $validated['default_mode'] = $validated['allowed_modes'][0];
        }

        $settings->setMany([
            'payments.allowed_modes' => array_values($validated['allowed_modes']),
            'payments.default_mode' => $validated['default_mode'],
            'payments.allow_clinic_override' => $request->boolean('allow_clinic_override'),
            'payments.gateway' => $validated['gateway'],
            'payments.gateway_key' => $validated['gateway_key'] ?? null,
        ], 'payments');

        if (filled($validated['gateway_secret'] ?? null)) {
            $settings->set('payments.gateway_secret', $validated['gateway_secret'], 'payments', encrypted: true);
        }

        Audit::log('settings.payments_updated', changes: [
            'allowed_modes' => $validated['allowed_modes'],
            'default_mode' => $validated['default_mode'],
            'gateway' => $validated['gateway'],
        ]);

        return back()->with('status', __('admin.payments.saved'));
    }
}
