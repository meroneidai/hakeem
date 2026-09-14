<?php

namespace Database\Seeders;

use App\Models\Specialty;
use Illuminate\Database\Seeder;

class SpecialtySeeder extends Seeder
{
    public function run(): void
    {
        $featured = [
            'general-practice', 'dentistry', 'pediatrics', 'dermatology',
            'physical-therapy', 'psychiatry', 'medical-laboratory', 'obstetrics-gynecology',
        ];

        foreach ($this->data() as $order => [$nameAr, $nameEn, $slug, $category]) {
            Specialty::updateOrCreate(
                ['slug' => $slug],
                [
                    'name_ar' => $nameAr,
                    'name_en' => $nameEn,
                    'category' => $category,
                    'is_active' => true,
                    'is_featured' => in_array($slug, $featured, true),
                    'display_order' => $order + 1,
                ],
            );
        }
    }

    /**
     * @return list<array{0: string, 1: string, 2: string, 3: string}>
     */
    private function data(): array
    {
        return [
            ['طب عام', 'General Practice', 'general-practice', 'general'],
            ['باطنة', 'Internal Medicine', 'internal-medicine', 'general'],
            ['أطفال وحديثي الولادة', 'Pediatrics', 'pediatrics', 'general'],
            ['نساء وتوليد', 'Obstetrics & Gynecology', 'obstetrics-gynecology', 'general'],
            ['جلدية', 'Dermatology', 'dermatology', 'general'],
            ['عظام', 'Orthopedics', 'orthopedics', 'general'],
            ['قلب وأوعية دموية', 'Cardiology', 'cardiology', 'general'],
            ['أنف وأذن وحنجرة', 'ENT', 'ent', 'general'],
            ['عيون', 'Ophthalmology', 'ophthalmology', 'general'],
            ['مسالك بولية', 'Urology', 'urology', 'general'],
            ['جراحة عامة', 'General Surgery', 'general-surgery', 'general'],
            ['مخ وأعصاب', 'Neurology', 'neurology', 'general'],
            ['صدر وجهاز تنفسي', 'Pulmonology', 'pulmonology', 'general'],
            ['كلى', 'Nephrology', 'nephrology', 'general'],
            ['غدد صماء وسكر', 'Endocrinology & Diabetes', 'endocrinology', 'general'],
            ['روماتيزم ومناعة', 'Rheumatology', 'rheumatology', 'general'],
            ['أورام', 'Oncology', 'oncology', 'general'],
            ['كبد وجهاز هضمي', 'Gastroenterology & Hepatology', 'gastroenterology', 'general'],
            ['تغذية علاجية', 'Clinical Nutrition', 'clinical-nutrition', 'general'],
            ['حساسية ومناعة', 'Allergy & Immunology', 'allergy-immunology', 'general'],

            ['أسنان', 'Dentistry', 'dentistry', 'dental'],
            ['تقويم أسنان', 'Orthodontics', 'orthodontics', 'dental'],
            ['جراحة الفم والأسنان', 'Oral Surgery', 'oral-surgery', 'dental'],
            ['زراعة أسنان', 'Dental Implants', 'dental-implants', 'dental'],
            ['تجميل أسنان', 'Cosmetic Dentistry', 'cosmetic-dentistry', 'dental'],
            ['أسنان أطفال', 'Pediatric Dentistry', 'pediatric-dentistry', 'dental'],

            ['جراحات التجميل', 'Plastic Surgery', 'plastic-surgery', 'cosmetic'],
            ['تجميل غير جراحي', 'Aesthetic Medicine', 'aesthetic-medicine', 'cosmetic'],
            ['ليزر وعلاج البشرة', 'Laser & Skin Treatment', 'laser-skin-treatment', 'cosmetic'],

            ['العناية بالبشرة', 'Skin Care', 'skin-care', 'beauty'],
            ['مساج واسترخاء', 'Massage & Wellness', 'massage-wellness', 'beauty'],

            ['علاج طبيعي', 'Physical Therapy', 'physical-therapy', 'physical_therapy'],
            ['تأهيل رياضي', 'Sports Rehabilitation', 'sports-rehabilitation', 'physical_therapy'],

            ['طب نفسي', 'Psychiatry', 'psychiatry', 'psychiatry'],
            ['علاج نفسي وإرشاد', 'Psychotherapy & Counselling', 'psychotherapy', 'psychiatry'],

            ['تحاليل طبية', 'Medical Laboratory', 'medical-laboratory', 'laboratory'],
            ['أشعة وتصوير طبي', 'Radiology & Imaging', 'radiology', 'laboratory'],
        ];
    }
}
