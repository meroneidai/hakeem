<?php

namespace Database\Seeders;

use App\Enums\ServiceTypeCode;
use App\Models\ServiceType;
use Illuminate\Database\Seeder;

/**
 * The six bookable service types (md_files/02 §1). All share one booking engine;
 * these flags are what the engine branches on.
 */
class ServiceTypeSeeder extends Seeder
{
    public function run(): void
    {
        $types = [
            [
                'code' => ServiceTypeCode::ClinicAppointment,
                'name_ar' => 'موعد بالعيادة',
                'name_en' => 'Clinic Appointment',
                'slug' => 'clinic-appointment',
                'description_ar' => 'حجز موعد في عيادة الطبيب — أكثر مسار استخدامًا على المنصة.',
                'description_en' => 'Book a visit at the doctor’s clinic — the most common path on the platform.',
                'requires_clinic_address' => true,
                'requires_patient_address' => false,
                'requires_time_slot' => true,
                'is_online' => false,
                'is_sensitive' => false,
            ],
            [
                'code' => ServiceTypeCode::HomeVisit,
                'name_ar' => 'زيارة منزلية',
                'name_en' => 'Home Visit',
                'slug' => 'home-visit',
                'description_ar' => 'الطبيب أو التمريض يزورك في منزلك.',
                'description_en' => 'A doctor or nurse visits you at home.',
                'requires_clinic_address' => false,
                'requires_patient_address' => true,
                'requires_time_slot' => true,
                'is_online' => false,
                'is_sensitive' => false,
            ],
            [
                'code' => ServiceTypeCode::VideoConsultation,
                'name_ar' => 'استشارة بالفيديو',
                'name_en' => 'Video Consultation',
                'slug' => 'video-consultation',
                'description_ar' => 'استشارة مباشرة بالفيديو مع إمكانية مشاركة التحاليل داخل المكالمة.',
                'description_en' => 'A live video consultation with in-call file sharing for prior results.',
                'requires_clinic_address' => false,
                'requires_patient_address' => false,
                'requires_time_slot' => true,
                'is_online' => true,
                'is_sensitive' => false,
            ],
            [
                'code' => ServiceTypeCode::LabTest,
                'name_ar' => 'تحاليل بالمعمل',
                'name_en' => 'Lab Test',
                'slug' => 'lab-test',
                'description_ar' => 'حجز تحاليل داخل المعمل أو العيادة، والنتائج تُضاف لسجلك الطبي.',
                'description_en' => 'Book tests at the lab or clinic; results are attached to your medical record.',
                'requires_clinic_address' => true,
                'requires_patient_address' => false,
                'requires_time_slot' => true,
                'is_online' => false,
                'is_sensitive' => false,
            ],
            [
                'code' => ServiceTypeCode::HomeLabTest,
                'name_ar' => 'تحاليل منزلية',
                'name_en' => 'Home Lab Test',
                'slug' => 'home-lab-test',
                'description_ar' => 'سحب العينة من المنزل في الوقت الذي تختاره.',
                'description_en' => 'At-home sample collection within the window you choose.',
                'requires_clinic_address' => false,
                'requires_patient_address' => true,
                'requires_time_slot' => true,
                'is_online' => false,
                'is_sensitive' => false,
            ],
            [
                'code' => ServiceTypeCode::PsychiatricConsultation,
                'name_ar' => 'استشارة نفسية أونلاين',
                'name_en' => 'Online Psychiatric Consultation',
                'slug' => 'psychiatric-consultation',
                'description_ar' => 'جلسة نفسية أونلاين بخصوصية مشددة، وإمكانية الحجز باسم عرض فقط.',
                'description_en' => 'An online mental-health session with stricter privacy and display-name-only booking.',
                'requires_clinic_address' => false,
                'requires_patient_address' => false,
                'requires_time_slot' => true,
                'is_online' => true,
                'is_sensitive' => true,
            ],
        ];

        foreach ($types as $order => $type) {
            ServiceType::updateOrCreate(
                ['code' => $type['code']->value],
                [...$type, 'code' => $type['code']->value, 'is_active' => true, 'display_order' => $order + 1],
            );
        }
    }
}
