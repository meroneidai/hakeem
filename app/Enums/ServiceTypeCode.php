<?php

namespace App\Enums;

enum ServiceTypeCode: string
{
    case ClinicAppointment = 'clinic_appointment';
    case HomeVisit = 'home_visit';
    case VideoConsultation = 'video_consultation';
    case LabTest = 'lab_test';
    case HomeLabTest = 'home_lab_test';
    case PsychiatricConsultation = 'psychiatric_consultation';
    case PhysicalTherapy = 'physical_therapy';
    case OccupationalTherapy = 'occupational_therapy';

    public function isRehab(): bool
    {
        return in_array($this, [self::PhysicalTherapy, self::OccupationalTherapy], true);
    }

    /**
     * Patients pick a doctor first (video, home visit, psychiatry).
     */
    public function isDoctorLed(): bool
    {
        return in_array($this, [self::HomeVisit, self::VideoConsultation, self::PsychiatricConsultation], true);
    }
}
