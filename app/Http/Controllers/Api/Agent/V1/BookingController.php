<?php

namespace App\Http\Controllers\Api\Agent\V1;

use App\Enums\PaymentMode;
use App\Http\Controllers\Controller;
use App\Models\ClinicAddress;
use App\Models\Doctor;
use App\Models\ServiceType;
use App\Services\AgentCustomerAuthenticator;
use App\Services\BookingManager;
use App\Services\PaymentOptions;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class BookingController extends Controller
{
    public function __construct(
        private BookingManager $bookings,
        private PaymentOptions $payments,
    ) {}

    public function store(Request $request, AgentCustomerAuthenticator $auth): JsonResponse
    {
        $validated = $request->validate([
            'doctor_slug' => ['required', 'string', 'exists:doctors,slug'],
            'scheduled_at' => ['required', 'date', 'after:now'],
            'clinic_address_id' => ['nullable', 'integer', 'exists:clinic_addresses,id'],
            'service_type_id' => ['nullable', 'integer', 'exists:service_types,id'],
            'session_count' => ['nullable', 'integer', 'min:1', 'max:30'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'patient_home_address' => ['nullable', 'string', 'max:255'],
            'payment_mode' => ['nullable', Rule::enum(PaymentMode::class)],
        ]);

        $customer = $auth->require($request);

        $doctor = Doctor::query()
            ->with('specialty')
            ->where('slug', $validated['doctor_slug'])
            ->firstOrFail();

        abort_unless($doctor->is_active, 404);

        $clinicIds = method_exists($doctor->clinics()->getModel(), 'scopeListable')
            ? $doctor->clinics()->listable()->pluck('clinics.id')
            : $doctor->clinics()->where('is_active', true)->pluck('clinics.id');

        abort_if($clinicIds->isEmpty(), 404);

        $address = ClinicAddress::query()
            ->with('clinic')
            ->whereIn('clinic_id', $clinicIds)
            ->when($validated['clinic_address_id'] ?? null, fn ($query, $id) => $query->whereKey($id))
            ->where('is_active', true)
            ->orderByDesc('is_primary')
            ->orderBy('id')
            ->first();

        abort_unless($address, 404);

        $serviceType = ServiceType::query()
            ->when(
                $validated['service_type_id'] ?? null,
                fn ($query, $id) => $query->whereKey($id),
                fn ($query) => $query->where('code', 'clinic_appointment'),
            )
            ->first()
            ?? ServiceType::query()->where('is_active', true)->orderBy('display_order')->first();

        abort_unless($serviceType, 404);

        if ($serviceType->requires_patient_address && blank($validated['patient_home_address'] ?? null)) {
            throw ValidationException::withMessages([
                'patient_home_address' => __('booking.home_address_required'),
            ]);
        }

        $mode = isset($validated['payment_mode'])
            ? PaymentMode::from($validated['payment_mode'])
            : $this->payments->defaultMode($address->clinic, $serviceType);

        if (! $this->payments->allows($address->clinic, $mode, $serviceType)) {
            throw ValidationException::withMessages([
                'payment_mode' => __('booking.payment_not_offered'),
            ]);
        }

        $booking = $this->bookings->create([
            'patient_id' => $customer->id,
            'clinic_id' => $address->clinic_id,
            'doctor_id' => $doctor->id,
            'clinic_address_id' => $address->id,
            'service_type_id' => $serviceType->id,
            'scheduled_at' => $validated['scheduled_at'],
            'session_count' => $validated['session_count'] ?? 1,
            'notes' => $validated['notes'] ?? null,
            'patient_home_address' => $validated['patient_home_address'] ?? null,
            'payment_mode' => $mode,
        ], $customer);

        return response()->json([
            'ok' => true,
            'booking' => [
                'reference' => (string) $booking->id,
                'status' => $booking->status?->value ?? (string) $booking->status,
                'doctor' => $doctor->name,
                'specialty' => $doctor->specialty?->name_ar ?? $doctor->specialty?->name,
                'clinic' => $address->clinic?->name,
                'address' => $address->label_ar ?: $address->address_line,
                'service' => $serviceType->name_ar,
                'date' => $booking->scheduled_at->timezone('Africa/Cairo')->toDateString(),
                'time' => $booking->scheduled_at->timezone('Africa/Cairo')->format('H:i'),
                'fee' => (string) ($doctor->consultation_fee ?? ''),
                'currency' => 'EGP',
                'payment' => $mode->value,
            ],
        ], 201);
    }
}
