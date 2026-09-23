<?php

namespace App\Enums;

enum CareDocumentType: string
{
    case Consultation = 'consultation';
    case Prescription = 'prescription';
    case LabResult = 'lab_result';
    case SickLeave = 'sick_leave';
    case TreatmentPlan = 'treatment_plan';

    public function label(): string
    {
        return __('records.types.'.$this->value);
    }

    public function tone(): string
    {
        return match ($this) {
            self::Consultation => 'primary',
            self::Prescription => 'accent',
            self::LabResult => 'success',
            self::SickLeave => 'warning',
            self::TreatmentPlan => 'primary',
        };
    }

    /**
     * Types a clinic can issue after a visit.
     *
     * @return list<self>
     */
    public static function issuableAfterVisit(): array
    {
        return [
            self::Prescription,
            self::SickLeave,
            self::TreatmentPlan,
            self::Consultation,
        ];
    }

    public function isDownloadable(): bool
    {
        return true;
    }
}
