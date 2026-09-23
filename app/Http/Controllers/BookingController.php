<?php

namespace App\Http\Controllers;

use App\Enums\PaymentMode;
use App\Models\ClinicAddress;
use App\Models\Doctor;
use App\Models\Promotion;
use App\Models\ServiceType;
use App\Services\BookingManager;
use App\Services\PaymentOptions;
use App\Support\BookingDays;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class BookingController extends Controller
{
    public function __construct(
        private BookingManager $bookings,
        private PaymentOptions $payments,
    ) {}

    public function create(Request $request, Doctor $doctor): View
    {
        abort_unless($doctor->is_active, 404);

        $doctor->load([
            'specialty',
            'availability.address.city',
            'clinics' => fn ($query) => $query->listable()->with([
                'addresses' => fn ($addresses) => $addresses->active()->with(['city', 'clinic']),
                'services' => fn ($services) => $services->active()->with('serviceType'),
            ]),
        ]);

        abort_unless($doctor->clinics->isNotEmpty(), 404);

        $serviceTypes = $doctor->clinics
            ->flatMap(fn ($clinic) => $clinic->services->pluck('serviceType'))
            ->filter()
            ->unique('id')
            ->values();

        if ($serviceTypes->isEmpty()) {
            $serviceTypes = ServiceType::query()->active()->ordered()->get();
        }

        $addresses = $doctor->clinics
            ->flatMap(fn ($clinic) => $clinic->addresses)
            ->unique('id')
            ->values();

        $firstClinic = $doctor->clinics->first();
        $paymentModes = collect($this->payments->platformModes())
            ->merge($doctor->clinics->flatMap(fn ($clinic) => $this->payments->allowedModes($clinic)))
            ->unique(fn (PaymentMode $mode) => $mode->value)
            ->values()
            ->all();

        $maxSessions = (int) $doctor->clinics
            ->flatMap(fn ($clinic) => $clinic->services)
            ->max('session_count') ?: 1;

        $selectedServiceTypeId = $request->integer('service_type_id') ?: null;
        $selectedType = $serviceTypes->firstWhere('id', $selectedServiceTypeId);

        return view('bookings.create', [
            'doctor' => $doctor,
            'serviceTypes' => $serviceTypes,
            'selectedServiceTypeId' => $selectedServiceTypeId,
            'addresses' => $addresses,
            'paymentModes' => $paymentModes,
            'defaultPaymentMode' => $this->payments->defaultMode($firstClinic, $selectedType),
            'gatewayReady' => $this->payments->isGatewayConfigured(),
            'maxSessions' => max(1, $maxSessions),
            'requiresEvaluation' => $doctor->specialty?->category === 'physical_therapy',
            'dayOptions' => BookingDays::upcoming(),
            'serviceFlags' => $serviceTypes->mapWithKeys(function (ServiceType $type) use ($doctor) {
                $offering = $doctor->clinics
                    ->flatMap(fn ($clinic) => $clinic->services)
                    ->firstWhere('service_type_id', $type->id);

                return [(string) $type->id => [
                    'requires_patient_address' => (bool) $type->requires_patient_address,
                    'is_online' => (bool) $type->is_online,
                    'duration_minutes' => $type->durationMinutes($offering?->duration_minutes),
                    'payment_modes' => $type->allowed_payment_modes ?? [],
                ]];
            })->all(),
            'clinicModes' => $doctor->clinics->mapWithKeys(fn ($clinic) => [
                (string) $clinic->id => $this->payments->allowedValues($clinic),
            ])->all(),
            'addressClinics' => $addresses->mapWithKeys(fn ($address) => [
                (string) $address->id => (string) $address->clinic_id,
            ])->all(),
        ]);
    }

    public function store(Request $request, Doctor $doctor): RedirectResponse
    {
        abort_unless($doctor->is_active, 404);

        $clinicIds = $doctor->clinics()->listable()->pluck('clinics.id');

        abort_unless($clinicIds->isNotEmpty(), 404);

        $validated = $request->validate([
            'service_type_id' => ['required', 'exists:service_types,id'],
            'clinic_address_id' => ['required', 'exists:clinic_addresses,id'],
            'scheduled_at' => ['required', 'date', 'after:now'],
            'session_count' => ['nullable', 'integer', 'min:1', 'max:30'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'patient_home_address' => [
                Rule::requiredIf(fn () => (bool) ServiceType::query()->whereKey($request->input('service_type_id'))->value('requires_patient_address')),
                'nullable',
                'string',
                'max:255',
            ],
            'payment_mode' => ['nullable', Rule::enum(PaymentMode::class)],
        ]);

        $address = ClinicAddress::query()
            ->whereKey($validated['clinic_address_id'])
            ->whereIn('clinic_id', $clinicIds)
            ->with('clinic')
            ->first();

        abort_unless($address, 404);

        $serviceType = ServiceType::query()->findOrFail($validated['service_type_id']);
        $rawMode = $validated['payment_mode'] ?? null;
        $mode = $rawMode instanceof PaymentMode
            ? $rawMode
            : ($rawMode ? PaymentMode::from($rawMode) : $this->payments->defaultMode($address->clinic, $serviceType));

        if (! $this->payments->allows($address->clinic, $mode, $serviceType)) {
            throw ValidationException::withMessages([
                'payment_mode' => __('booking.payment_not_offered'),
            ]);
        }

        $this->bookings->create([
            'patient_id' => $request->user()->id,
            'clinic_id' => $address->clinic_id,
            'doctor_id' => $doctor->id,
            'clinic_address_id' => $address->id,
            'service_type_id' => $validated['service_type_id'],
            'scheduled_at' => $validated['scheduled_at'],
            'session_count' => $validated['session_count'] ?? 1,
            'notes' => $validated['notes'] ?? null,
            'patient_home_address' => $validated['patient_home_address'] ?? null,
            'payment_mode' => $mode,
        ], $request->user());

        return redirect()
            ->route('appointments.index')
            ->with('status', __('booking.requested'));
    }

    public function storeOffer(Request $request, Promotion $offer): RedirectResponse
    {
        abort_unless($offer->isRunning() && $offer->clinic_id, 404);

        $offer->load(['clinic.doctors', 'clinic.addresses', 'serviceType']);

        abort_unless($offer->clinic?->isVerified() && $offer->clinic->is_active, 404);

        $validated = $request->validate([
            'doctor_id' => ['required', 'exists:doctors,id'],
            'clinic_address_id' => ['required', 'exists:clinic_addresses,id'],
            'scheduled_at' => ['required', 'date', 'after:now'],
            'session_count' => ['nullable', 'integer', 'min:1', 'max:30'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'payment_mode' => ['nullable', Rule::enum(PaymentMode::class)],
        ]);

        $doctor = $offer->clinic->doctors->firstWhere('id', (int) $validated['doctor_id']);
        $address = $offer->clinic->addresses->firstWhere('id', (int) $validated['clinic_address_id']);

        abort_unless($doctor && $address && $address->is_active, 404);

        $serviceTypeId = $offer->service_type_id
            ?? $offer->clinic->services()->active()->value('service_type_id')
            ?? ServiceType::query()->where('code', 'clinic_appointment')->value('id');

        abort_unless($serviceTypeId, 404);

        $serviceType = ServiceType::query()->find($serviceTypeId);
        $rawMode = $validated['payment_mode'] ?? null;
        $mode = $rawMode instanceof PaymentMode
            ? $rawMode
            : ($rawMode ? PaymentMode::from($rawMode) : $this->payments->defaultMode($offer->clinic, $serviceType));

        if (! $this->payments->allows($offer->clinic, $mode, $serviceType)) {
            throw ValidationException::withMessages([
                'payment_mode' => __('booking.payment_not_offered'),
            ]);
        }

        $this->bookings->create([
            'patient_id' => $request->user()->id,
            'clinic_id' => $offer->clinic_id,
            'doctor_id' => $doctor->id,
            'clinic_address_id' => $address->id,
            'service_type_id' => $serviceTypeId,
            'promotion_id' => $offer->id,
            'scheduled_at' => $validated['scheduled_at'],
            'session_count' => $validated['session_count'] ?? $offer->session_count ?? 1,
            'notes' => $validated['notes'] ?? null,
            'payment_mode' => $mode,
        ], $request->user());

        return redirect()
            ->route('appointments.index')
            ->with('status', __('booking.requested'));
    }
}
