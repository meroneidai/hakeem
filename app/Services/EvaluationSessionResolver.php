<?php

namespace App\Services;

use App\Enums\BookingStatus;
use App\Enums\ServiceTypeCode;
use App\Models\Booking;
use App\Models\ClinicService;
use App\Models\Doctor;
use App\Models\ServiceType;
use App\Models\User;
use Illuminate\Validation\ValidationException;

class EvaluationSessionResolver
{
    /**
     * First rehab visit at this clinic, or first psychiatric visit with this doctor,
     * is always an evaluation session.
     */
    public function shouldEvaluate(
        User $patient,
        Doctor $doctor,
        ServiceType $serviceType,
        ?ClinicService $offering = null,
        ?int $clinicId = null,
    ): bool {
        if (! $this->requiresEvaluation($doctor, $serviceType, $offering)) {
            return false;
        }

        if ($this->isRehab($doctor, $serviceType, $offering) && $clinicId) {
            return ! $this->evaluationQuery($patient, clinicId: $clinicId)->exists();
        }

        return ! $this->evaluationQuery($patient, doctorId: $doctor->id)->exists();
    }

    public function requiresEvaluation(Doctor $doctor, ServiceType $serviceType, ?ClinicService $offering = null): bool
    {
        if ($offering?->requires_evaluation_first) {
            return true;
        }

        $code = ServiceTypeCode::tryFrom($serviceType->code);

        if ($code === ServiceTypeCode::PsychiatricConsultation || $code?->isRehab()) {
            return true;
        }

        $doctor->loadMissing('specialty');

        return $doctor->specialty?->category === 'physical_therapy';
    }

    public function isRehab(Doctor $doctor, ServiceType $serviceType, ?ClinicService $offering = null): bool
    {
        $code = ServiceTypeCode::tryFrom($serviceType->code);

        if ($code?->isRehab()) {
            return true;
        }

        if ($offering?->specialty?->category === 'physical_therapy') {
            return true;
        }

        $doctor->loadMissing('specialty');

        return $doctor->specialty?->category === 'physical_therapy';
    }

    /**
     * Reception may confirm a rehab follow-up only after a completed evaluation
     * at the same clinic. The evaluation visit itself can always be accepted.
     */
    public function assertAcceptable(Booking $booking): void
    {
        $booking->loadMissing(['doctor.specialty', 'serviceType', 'clinicService.specialty']);

        if (! $booking->doctor || ! $booking->serviceType) {
            return;
        }

        if (! $this->isRehab($booking->doctor, $booking->serviceType, $booking->clinicService)) {
            return;
        }

        if ($booking->is_evaluation) {
            return;
        }

        if ($this->hasCompletedEvaluationAtClinic($booking->patient, (int) $booking->clinic_id, $booking->id)) {
            return;
        }

        throw ValidationException::withMessages([
            'status' => __('booking.evaluation_required'),
        ]);
    }

    public function hasCompletedEvaluationAtClinic(User $patient, int $clinicId, ?int $exceptBookingId = null): bool
    {
        return $this->evaluationQuery($patient, clinicId: $clinicId)
            ->where('status', BookingStatus::Completed->value)
            ->when($exceptBookingId, fn ($query) => $query->whereKeyNot($exceptBookingId))
            ->exists();
    }

    private function evaluationQuery(User $patient, ?int $clinicId = null, ?int $doctorId = null)
    {
        return Booking::query()
            ->where('patient_id', $patient->id)
            ->where('is_evaluation', true)
            ->whereNotIn('status', [BookingStatus::Cancelled->value, BookingStatus::NoShow->value])
            ->when($clinicId, fn ($query) => $query->where('clinic_id', $clinicId))
            ->when($doctorId, fn ($query) => $query->where('doctor_id', $doctorId));
    }
}
