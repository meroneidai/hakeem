<?php

namespace App\Http\Controllers;

use App\Enums\CollectionMode;
use App\Enums\PaymentMode;
use App\Enums\ServiceTypeCode;
use App\Models\Clinic;
use App\Models\LabOrder;
use App\Models\ServiceType;
use App\Services\LabCart;
use App\Services\LabOrderManager;
use App\Services\PaymentOptions;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
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

        $lines = $this->cart->lines();
        $clinics = $this->orders->matchingClinics($lines);

        return view('labs.checkout', [
            'cart' => $this->cart,
            'clinics' => $clinics,
            'clinicQuotes' => $this->orders->checkoutQuotes($clinics, $lines),
            'addresses' => $request->user()->addresses()->orderByDesc('is_default')->orderBy('id')->get(),
            'slots' => $this->collectionSlots(),
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
            'scheduled_at' => ['nullable', 'date'],
            'collection_date' => ['required_without:scheduled_at', 'nullable', 'date', 'after_or_equal:today'],
            'collection_time' => ['required_without:scheduled_at', 'nullable', 'date_format:H:i'],
            'payment_mode' => ['required', Rule::enum(PaymentMode::class)],
            'patient_address_id' => ['nullable', 'integer'],
            'patient_home_address' => ['nullable', 'string', 'max:255'],
            'address_label' => ['nullable', 'string', 'max:80'],
            'save_address' => ['sometimes', 'boolean'],
            'latitude' => ['nullable', 'required_if:collection_mode,home', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'required_if:collection_mode,home', 'numeric', 'between:-180,180'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $validated['scheduled_at'] = $this->resolveSchedule($validated);
        $validated['save_address'] = $request->boolean('save_address');

        if (Carbon::parse($validated['scheduled_at'])->lte(now())) {
            throw ValidationException::withMessages([
                'collection_time' => __('labs.checkout.slot_past'),
            ]);
        }

        $clinic = Clinic::query()->listable()->findOrFail($validated['clinic_id']);
        $mode = $validated['payment_mode'] instanceof PaymentMode
            ? $validated['payment_mode']
            : PaymentMode::from($validated['payment_mode']);
        $collection = $validated['collection_mode'] instanceof CollectionMode
            ? $validated['collection_mode']
            : CollectionMode::from((string) $validated['collection_mode']);
        $serviceType = ServiceType::query()
            ->where(
                'code',
                $collection === CollectionMode::Home
                    ? ServiceTypeCode::HomeLabTest->value
                    : ServiceTypeCode::LabTest->value,
            )
            ->first();

        if (! $this->payments->allows($clinic, $mode, $serviceType)) {
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

        $labOrder->load([
            'clinic.primaryAddress.city',
            'address',
            'patientAddress',
            'items.labTest',
            'items.labPackage',
            'careDocuments',
        ]);

        return view('labs.confirmation', ['order' => $labOrder]);
    }

    /**
     * @param  array<string, mixed>  $validated
     */
    private function resolveSchedule(array $validated): string
    {
        if (filled($validated['collection_date'] ?? null) && filled($validated['collection_time'] ?? null)) {
            return $validated['collection_date'].' '.$validated['collection_time'];
        }

        return (string) $validated['scheduled_at'];
    }

    /**
     * @return list<string>
     */
    private function collectionSlots(): array
    {
        $slots = [];

        for ($hour = 7; $hour <= 21; $hour++) {
            foreach ([0, 30] as $minute) {
                if ($hour === 21 && $minute === 30) {
                    continue;
                }

                $slots[] = sprintf('%02d:%02d', $hour, $minute);
            }
        }

        return $slots;
    }
}
