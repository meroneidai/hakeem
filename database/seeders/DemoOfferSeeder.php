<?php

namespace Database\Seeders;

use App\Enums\OfferCategory;
use App\Enums\ServiceTypeCode;
use App\Models\Clinic;
use App\Models\Promotion;
use App\Models\ServiceType;
use Illuminate\Database\Seeder;

class DemoOfferSeeder extends Seeder
{
    public function run(): void
    {
        $clinic = Clinic::query()->where('email', 'clinic@hakeem.test')->first()
            ?? Clinic::query()->first();
        $labService = ServiceType::query()->where('code', ServiceTypeCode::LabTest->value)->first();
        $appointment = ServiceType::query()->where('code', ServiceTypeCode::ClinicAppointment->value)->first();

        $offers = [
            [
                'title_ar' => 'فحص شامل بخصم 39%',
                'title_en' => 'Full checkup 39% off',
                'slug' => 'full-checkup-offer',
                'category' => OfferCategory::Checkup,
                'original_price' => 1470,
                'offer_price' => 890,
                'discount_type' => 'percentage',
                'discount_value' => 39,
                'includes_ar' => 'CBC، دهون، كبد، كلى، سكر، TSH، بول، ESR.',
                'includes_en' => 'CBC, lipids, liver, kidney, glucose, TSH, urine, ESR.',
                'conditions_ar' => 'ساري حتى نهاية الشهر. بعد سحب العينة لا يُسترد المبلغ. من سن 16.',
                'conditions_en' => 'Valid until month end. Non-refundable after sampling. Age 16+.',
                'description_ar' => 'عرض محدود على باقة الفحص الشامل — نفس أسلوب عروض فيزيتا للمعامل.',
                'description_en' => 'Limited checkup package offer, in the style of Vezeeta lab deals.',
                'is_featured' => true,
            ],
            [
                'title_ar' => 'باقة الحديد بخصم 38%',
                'title_en' => 'Iron studies 38% off',
                'slug' => 'iron-offer',
                'category' => OfferCategory::Lab,
                'original_price' => 630,
                'offer_price' => 390,
                'discount_type' => 'percentage',
                'discount_value' => 38,
                'includes_ar' => 'CBC + فيريتين + حديد + TIBC.',
                'includes_en' => 'CBC + ferritin + iron + TIBC.',
                'conditions_ar' => 'صيام 8 ساعات. لا يشمل الاستشارة الطبية.',
                'conditions_en' => '8-hour fast. Consultation not included.',
                'description_ar' => 'لتقييم الأنيميا ونقص الحديد.',
                'description_en' => 'For anaemia and iron deficiency.',
                'is_featured' => true,
            ],
            [
                'title_ar' => 'عرض جديد',
                'title_en' => 'New featured offer',
                'slug' => 'aard-gdyd',
                'category' => OfferCategory::Checkup,
                'original_price' => 900,
                'offer_price' => 590,
                'discount_type' => 'percentage',
                'discount_value' => 34,
                'session_count' => 1,
                'includes_ar' => 'كشف + تحليل أساسي حسب اختيار العيادة.',
                'includes_en' => 'Consult plus a basic lab panel chosen by the clinic.',
                'conditions_ar' => 'ساري في فروع العيادة المشاركة. غير قابل للجمع مع عرض آخر.',
                'conditions_en' => 'Valid at participating branches. Not combinable with another offer.',
                'description_ar' => 'عرض يمكن حجزه مباشرة من الصفحة مع تأكيد من الاستقبال.',
                'description_en' => 'An offer patients can book from this page, confirmed by reception.',
                'is_featured' => true,
            ],
        ];

        foreach ($offers as $offer) {
            Promotion::updateOrCreate(
                ['slug' => $offer['slug']],
                [
                    ...$offer,
                    'clinic_id' => $clinic?->id,
                    'service_type_id' => $offer['slug'] === 'aard-gdyd'
                        ? ($appointment?->id ?? $labService?->id)
                        : $labService?->id,
                    'starts_at' => now()->subDay(),
                    'ends_at' => now()->addMonth(),
                    'is_active' => true,
                ],
            );
        }
    }
}
