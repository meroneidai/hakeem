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
}
