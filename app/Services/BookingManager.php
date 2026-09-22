<?php

namespace App\Services;

use App\Enums\BookingStatus;
use App\Enums\PaymentMode;
use App\Enums\RoleName;
use App\Models\Booking;
use App\Models\Clinic;
use App\Models\ClinicAddress;
use App\Models\ClinicService;
use App\Models\Doctor;
use App\Models\ServiceType;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class BookingManager
{
    public function __construct(
        private NotificationDispatcher $notifications,
        private EvaluationSessionResolver $evaluations,
        private LoyaltyProgram $loyalty,
    ) {}

    /**
     * @param  array{
     *     patient_id: int,
     *     clinic_id: int,
     *     doctor_id: int,
     *     clinic_address_id: int,
     *     service_type_id: int,
     *     scheduled_at: mixed,
     *     notes?: ?string,
     *     payment_mode?: PaymentMode|string,
     *     status?: BookingStatus
     * }  $attributes
     */
    public function create(array $attributes, User $actor): Booking
    {
        return DB::transaction(function () use ($attributes, $actor) {
            $status = $attributes['status'] ?? BookingStatus::Pending;
            $patient = User::query()->findOrFail($attributes['patient_id']);
            $doctor = Doctor::query()->with('specialty')->findOrFail($attributes['doctor_id']);
            $serviceType = ServiceType::query()->findOrFail($attributes['service_type_id']);
            $offering = ClinicService::query()
                ->where('clinic_id', $attributes['clinic_id'])
                ->where('service_type_id', $attributes['service_type_id'])
                ->first();

            $sessionCount = max(1, min(30, (int) ($attributes['session_count'] ?? $offering?->session_count ?? 1)));

            $booking = new Booking([
                'patient_id' => $attributes['patient_id'],
                'clinic_id' => $attributes['clinic_id'],
                'doctor_id' => $attributes['doctor_id'],
                'clinic_address_id' => $attributes['clinic_address_id'],
                'service_type_id' => $attributes['service_type_id'],
                'clinic_service_id' => $attributes['clinic_service_id'] ?? $offering?->id,
                'promotion_id' => $attributes['promotion_id'] ?? null,
                'scheduled_at' => $attributes['scheduled_at'],
                'status' => $status,
                'is_evaluation' => $this->evaluations->shouldEvaluate(
                    $patient,
                    $doctor,
                    $serviceType,
                    $offering,
                    (int) $attributes['clinic_id'],
                ),
                'session_count' => $sessionCount,
                'payment_mode' => $attributes['payment_mode'] ?? PaymentMode::AtClinic,
                'payment_status' => 'unpaid',
                'notes' => $attributes['notes'] ?? null,
            ]);

            if ($status === BookingStatus::Confirmed) {
                $this->evaluations->assertAcceptable($booking);
            }

            $booking->save();

            $this->recordHistory($booking, $status, $actor);
            $event = $booking->is_evaluation ? 'evaluation_booked' : ($status === BookingStatus::Pending ? 'booking_created' : 'booking_confirmed');
            $this->notify($event, $booking);

            return $booking;
        });
    }

    /**
     * Reception walk-in / phone booking: find or create the patient, then confirm.
     *
     * @param  array{
     *     patient_phone: string,
     *     patient_name: string,
     *     doctor_id: int,
     *     clinic_address_id: int,
     *     service_type_id: int,
     *     scheduled_at: mixed,
     *     notes?: ?string,
     *     payment_mode?: PaymentMode|string|null
     * }  $attributes
     */
    public function createForClinic(Clinic $clinic, array $attributes, User $actor): Booking
    {
        $patient = $this->findOrCreatePatient($attributes['patient_phone'], $attributes['patient_name']);

        return $this->create([
            'patient_id' => $patient->id,
            'clinic_id' => $clinic->id,
            'doctor_id' => $attributes['doctor_id'],
            'clinic_address_id' => $attributes['clinic_address_id'],
            'service_type_id' => $attributes['service_type_id'],
            'scheduled_at' => $attributes['scheduled_at'],
            'notes' => $attributes['notes'] ?? null,
            'status' => BookingStatus::Confirmed,
            'payment_mode' => $attributes['payment_mode'] ?? null,
            'session_count' => $attributes['session_count'] ?? null,
            'promotion_id' => $attributes['promotion_id'] ?? null,
        ], $actor);
    }

    public function transition(Booking $booking, BookingStatus $status, User $actor): Booking
    {
        if (! $booking->status->canTransitionTo($status)) {
            throw ValidationException::withMessages([
                'status' => __('clinic.queue.invalid_transition'),
            ]);
        }

        if ($status === BookingStatus::Confirmed) {
            $this->evaluations->assertAcceptable($booking);
        }

        return DB::transaction(function () use ($booking, $status, $actor) {
            $booking->update(['status' => $status]);
            $this->recordHistory($booking, $status, $actor);

            $event = match ($status) {
                BookingStatus::Confirmed => 'booking_confirmed',
                BookingStatus::Cancelled => 'booking_cancelled',
                BookingStatus::Completed => 'booking_completed',
                default => 'booking_created',
            };

            if (in_array($status, [BookingStatus::Confirmed, BookingStatus::Cancelled, BookingStatus::Completed], true)) {
                $this->notify($event, $booking);
            }

            if ($status === BookingStatus::Completed) {
                $this->loyalty->rewardCompletedService($booking->patient);
            }

            return $booking->refresh();
        });
    }

    public function reschedule(Booking $booking, mixed $scheduledAt, User $actor): Booking
    {
        if (! $booking->status->isOpen()) {
            throw ValidationException::withMessages([
                'scheduled_at' => __('clinic.queue.cannot_reschedule'),
            ]);
        }

        return DB::transaction(function () use ($booking, $scheduledAt, $actor) {
            $booking->update(['scheduled_at' => $scheduledAt]);
            $this->recordHistory($booking, $booking->status, $actor);
            $this->notify('booking_rescheduled', $booking);

            return $booking->refresh();
        });
    }

    public function recordEvaluationNotes(Booking $booking, string $notes): Booking
    {
        $booking->update(['evaluation_notes' => $notes]);

        return $booking->refresh();
    }

    public function markPayment(Booking $booking, string $paymentStatus): Booking
    {
        $booking->update(['payment_status' => $paymentStatus]);

        return $booking->refresh();
    }

    public function doctorBelongsToClinic(Doctor $doctor, Clinic $clinic): bool
    {
        return $clinic->doctors()->whereKey($doctor->id)->exists();
    }

    public function addressBelongsToClinic(ClinicAddress $address, Clinic $clinic): bool
    {
        return (int) $address->clinic_id === (int) $clinic->id && $address->is_active;
    }

    private function findOrCreatePatient(string $phone, string $name): User
    {
        $normalized = User::normalizePhone($phone);

        $patient = User::query()->firstOrCreate(
            ['phone' => $normalized],
            [
                'name' => $name,
                'password' => Str::password(16),
                'preferred_language' => app()->getLocale(),
                'is_active' => true,
            ],
        );

        if (! $patient->hasRole(RoleName::Patient)) {
            $patient->assignRole(RoleName::Patient);
        }

        return $patient;
    }

    private function recordHistory(Booking $booking, BookingStatus $status, User $actor): void
    {
        $booking->statusHistory()->create([
            'status' => $status,
            'changed_by_user_id' => $actor->id,
            'changed_at' => now(),
        ]);
    }

    private function notify(string $event, Booking $booking): void
    {
        $booking->loadMissing(['patient', 'clinic']);

        $this->notifications->send($event, [
            'booking_id' => $booking->id,
            'clinic_id' => $booking->clinic_id,
            'patient_id' => $booking->patient_id,
            'status' => $booking->status->value,
            'scheduled_at' => $booking->scheduled_at?->toIso8601String(),
            'name' => $booking->patient?->name,
            'clinic' => $booking->clinic?->name,
            'date' => $booking->scheduled_at?->format('Y-m-d H:i'),
            'reference' => (string) $booking->id,
        ]);
    }
}
