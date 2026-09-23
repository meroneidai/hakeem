<?php

namespace App\Http\Controllers\Clinic;

use App\Enums\PaymentMode;
use App\Http\Controllers\Controller;
use App\Services\PaymentOptions;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class PaymentSettingsController extends Controller
{
    use ResolvesClinicContext;

    public function __construct(private PaymentOptions $payments) {}

    public function edit(Request $request): View
    {
        $this->authorizeManage($request);

        $clinic = $this->clinic($request);

        return view('clinic.payments.edit', [
            'clinic' => $clinic,
            'platformModes' => $this->payments->platformModes(),
            'allowedModes' => $this->payments->allowedModes($clinic),
            'defaultMode' => $this->payments->defaultMode($clinic),
            'mayOverride' => $this->payments->clinicMayOverride(),
            'gatewayReady' => $this->payments->isGatewayConfigured(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $this->authorizeManage($request);
        abort_unless($this->payments->clinicMayOverride(), 403);

        $platformValues = array_map(fn (PaymentMode $mode) => $mode->value, $this->payments->platformModes());

        $validated = $request->validate([
            'payment_modes' => ['required', 'array', 'min:1'],
            'payment_modes.*' => [Rule::in($platformValues)],
            'default_payment_mode' => ['required', Rule::in($platformValues)],
        ]);

        if (! in_array($validated['default_payment_mode'], $validated['payment_modes'], true)) {
            $validated['default_payment_mode'] = $validated['payment_modes'][0];
        }

        $this->clinic($request)->update([
            'payment_modes' => array_values($validated['payment_modes']),
            'default_payment_mode' => $validated['default_payment_mode'],
        ]);

        return back()->with('status', __('clinic.payments.saved'));
    }
}
