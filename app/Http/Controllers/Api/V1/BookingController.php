<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\BookingStatus;
use App\Enums\PaymentMode;
use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\ClinicAddress;
use App\Models\Doctor;
use App\Models\ServiceType;
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

    public function index(Request $request): JsonResponse
    {
        $bookings = Booking::query()
            ->whereBelongsTo($request->user(), 'patient')
            ->with(['clinic', 'doctor.specialty', 'address.city', 'serviceType', 'clinicService'])
            ->orderByDesc('scheduled_at')
            ->orderByDesc('id')
            ->limit(50)
            ->get()
            ->map(fn (Booking $booking) => $this->payload($booking));

        return response()->json(['data' => $bookings]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'doctor_id' => ['required', 'integer', 'exists:doctors,id'],
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

        $doctor = Doctor::query()->findOrFail($validated['doctor_id']);
        abort_unless($doctor->is_active, 404);

        $clinicIds = $doctor->clinics()->listable()->pluck('clinics.id');
        abort_unless($clinicIds->isNotEmpty(), 404);

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

        $booking = $this->bookings->create([
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

        return response()->json([
            'data' => $this->payload($booking->load(['clinic', 'doctor.specialty', 'address.city', 'serviceType', 'clinicService'])),
        ], 201);
    }

    public function destroy(Request $request, Booking $booking): JsonResponse
    {
        abort_unless((int) $booking->patient_id === (int) $request->user()->id, 404);

        $this->bookings->transition($booking, BookingStatus::Cancelled, $request->user());

        return response()->json([
            'ok' => true,
            'status' => $booking->fresh()->status->value,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function payload(Booking $booking): array
    {
        return [
            'id' => $booking->id,
            'status' => $booking->status->value,
            'scheduled_at' => $booking->scheduled_at?->toIso8601String(),
            'is_evaluation' => $booking->is_evaluation,
            'payment_status' => $booking->payment_status,
            'clinic' => $booking->clinic?->name,
            'doctor' => $booking->doctor?->name,
            'service' => $booking->serviceType?->name,
            'duration_minutes' => $booking->durationMinutes(),
            'home_address' => $booking->patient_home_address,
            'video_url' => $booking->isVideoVisit() ? route('appointments.video', $booking) : null,
            'scheduled_at_local' => $booking->localScheduledAt()?->toIso8601String(),
            'cancellable' => $booking->status->canTransitionTo(BookingStatus::Cancelled),
        ];
    }
}
