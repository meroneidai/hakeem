<?php

namespace App\Http\Controllers;

use App\Enums\CollectionMode;
use App\Enums\PaymentMode;
use App\Models\Clinic;
use App\Models\LabOrder;
use App\Services\LabCart;
use App\Services\LabOrderManager;
use App\Services\PaymentOptions;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class LabCheckoutController extends Controller
{
    public function __construct(
        private LabCart $cart,
        private LabOrderManager $orders,
        private PaymentOptions $payments,
    ) {}

    public function create(Request $request): View|RedirectResponse
    {
        if ($this->cart->lines()->isEmpty()) {
            return redirect()->route('labs.cart')->with('error', __('labs.checkout.empty'));
        }

        $clinics = $this->orders->matchingClinics($this->cart->lines());

        return view('labs.checkout', [
            'cart' => $this->cart,
            'clinics' => $clinics,
            'paymentModes' => $this->payments->platformModes(),
            'defaultPaymentMode' => $this->payments->platformModes()[0] ?? PaymentMode::AtClinic,
            'gatewayReady' => $this->payments->isGatewayConfigured(),
            'selectedClinic' => Clinic::query()->listable()->find($request->integer('clinic_id')),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        if ($this->cart->lines()->isEmpty()) {
            return redirect()->route('labs.cart')->with('error', __('labs.checkout.empty'));
        }

        $validated = $request->validate([
            'clinic_id' => ['required', 'exists:clinics,id'],
            'clinic_address_id' => ['nullable', 'exists:clinic_addresses,id'],
            'collection_mode' => ['required', Rule::enum(CollectionMode::class)],
            'scheduled_at' => ['required', 'date', 'after:now'],
            'payment_mode' => ['required', Rule::enum(PaymentMode::class)],
            'patient_home_address' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $clinic = Clinic::query()->listable()->findOrFail($validated['clinic_id']);
        $mode = PaymentMode::from($validated['payment_mode']);

        if (! $this->payments->allows($clinic, $mode)) {
            throw ValidationException::withMessages([
                'payment_mode' => __('booking.payment_not_offered'),
            ]);
        }

        $order = $this->orders->create($request->user(), $this->cart->lines(), $validated);
        $this->cart->clear();

        return redirect()
            ->route('labs.orders.show', $order)
            ->with('status', __('labs.checkout.requested'));
    }

    public function show(Request $request, LabOrder $labOrder): View
    {
        abort_unless((int) $labOrder->patient_id === (int) $request->user()->id, 404);

        $labOrder->load(['clinic.primaryAddress.city', 'address', 'items.labTest', 'items.labPackage']);

        return view('labs.confirmation', ['order' => $labOrder]);
    }
}
