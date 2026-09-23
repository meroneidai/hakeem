<?php

namespace Database\Seeders;

use App\Enums\PlanFeature;
use App\Models\SubscriptionPlan;
use Illuminate\Database\Seeder;

/**
 * The 3-plan system (md_files/03 §3). At launch Starter is free and unlimited to
 * drive adoption; Growth/Pro pricing is a placeholder the admin edits in the UI.
 */
class SubscriptionPlanSeeder extends Seeder
{
    public function run(): void
    {
        $allServices = [
            PlanFeature::ServiceClinicAppointment,
            PlanFeature::ServiceHomeVisit,
            PlanFeature::ServiceVideoConsultation,
            PlanFeature::ServiceLabTest,
            PlanFeature::ServiceHomeLabTest,
            PlanFeature::ServicePsychiatricConsultation,
            PlanFeature::ServicePhysicalTherapy,
            PlanFeature::ServiceOccupationalTherapy,
            PlanFeature::ServiceCatalogItems,
        ];

        $plans = [
            [
                'slug' => 'starter',
                'name_ar' => 'الأساسية',
                'name_en' => 'Starter',
                'description_ar' => 'مجانية بالكامل وبلا حدود — الخطة الافتراضية عند الإطلاق.',
                'description_en' => 'Completely free and unlimited — the default plan at launch.',
                'monthly_price' => 0,
                'yearly_price' => 0,
                'yearly_discount_pct' => 0,
                'booking_cap' => null,
                'doctor_cap' => null,
                'address_cap' => null,
                'is_default_free' => true,
                'features' => [
                    ...$allServices,
                    PlanFeature::MultipleAddresses,
                    PlanFeature::MultipleDoctors,
                    PlanFeature::ReceptionRole,
                    PlanFeature::ClinicPromotions,
                    PlanFeature::BasicAnalytics,
                    PlanFeature::PrescriptionBranding,
                ],
            ],
            [
                'slug' => 'growth',
                'name_ar' => 'النمو',
                'name_en' => 'Growth',
                'description_ar' => 'للعيادات المتنامية التي تحتاج تحليلات وحجوزات أكبر.',
                'description_en' => 'For growing clinics that need more volume and better analytics.',
                'monthly_price' => 750,
                'yearly_price' => 0,
                'yearly_discount_pct' => 15,
                'booking_cap' => 1500,
                'doctor_cap' => 10,
                'address_cap' => 3,
                'is_default_free' => false,
                'features' => [
                    ...$allServices,
                    PlanFeature::MultipleAddresses,
                    PlanFeature::MultipleDoctors,
                    PlanFeature::ReceptionRole,
                    PlanFeature::ClinicPromotions,
                    PlanFeature::BasicAnalytics,
                    PlanFeature::WhatsappAgent,
                    PlanFeature::PrescriptionBranding,
                ],
            ],
            [
                'slug' => 'pro',
                'name_ar' => 'الاحترافية',
                'name_en' => 'Pro',
                'description_ar' => 'كل المزايا بلا حدود، مع تحليلات متقدمة وظهور مميز.',
                'description_en' => 'Everything unlimited, plus advanced analytics and promoted placement.',
                'monthly_price' => 1500,
                'yearly_price' => 0,
                'yearly_discount_pct' => 20,
                'booking_cap' => null,
                'doctor_cap' => null,
                'address_cap' => null,
                'is_default_free' => false,
                'features' => PlanFeature::cases(),
            ],
        ];

        foreach ($plans as $order => $plan) {
            $features = $plan['features'];
            unset($plan['features']);

            $model = SubscriptionPlan::updateOrCreate(
                ['slug' => $plan['slug']],
                [...$plan, 'is_active' => true, 'display_order' => $order + 1],
            );

            $enabled = array_map(fn (PlanFeature $feature) => $feature->value, $features);

            foreach (PlanFeature::cases() as $feature) {
                $model->featureFlags()->updateOrCreate(
                    ['feature_code' => $feature->value],
                    ['is_enabled' => in_array($feature->value, $enabled, true)],
                );
            }
        }
    }
}
