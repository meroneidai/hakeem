<?php

namespace Tests;

use App\Enums\PlanFeature;
use App\Enums\RoleName;
use App\Enums\ServiceTypeCode;
use App\Models\City;
use App\Models\Clinic;
use App\Models\ClinicAddress;
use App\Models\Doctor;
use App\Models\Governorate;
use App\Models\ServiceType;
use App\Models\Specialty;
use App\Models\SubscriptionPlan;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function seedRoles(): void
    {
        $this->seed(RoleSeeder::class);
    }

    protected function actingAsRole(RoleName $role): User
    {
        $this->seedRoles();

        $user = User::factory()->create();
        $user->assignRole($role);

        $this->actingAs($user);

        return $user;
    }

    /**
     * @return array{governorate: Governorate, city: City, specialty: Specialty, serviceType: ServiceType, plan: SubscriptionPlan}
     */
    protected function seedClinicCatalog(): array
    {
        $this->seedRoles();

        $governorate = Governorate::create([
            'name_ar' => 'القاهرة',
            'name_en' => 'Cairo',
            'slug' => 'cairo-test',
            'is_active' => true,
        ]);

        $city = City::create([
            'governorate_id' => $governorate->id,
            'name_ar' => 'مدينة نصر',
            'name_en' => 'Nasr City',
            'slug' => 'nasr-city-test',
            'is_active' => true,
        ]);

        $specialty = Specialty::create([
            'name_ar' => 'الباطنة',
            'name_en' => 'Internal Medicine',
            'slug' => 'internal-medicine-test',
            'category' => 'general',
            'is_active' => true,
        ]);

        $serviceType = ServiceType::create([
            'code' => ServiceTypeCode::ClinicAppointment->value,
            'name_ar' => 'كشف بالعيادة',
            'name_en' => 'Clinic appointment',
            'slug' => 'clinic-appointment-test',
            'is_active' => true,
        ]);

        $plan = SubscriptionPlan::create([
            'name_ar' => 'الأساسية',
            'name_en' => 'Starter',
            'slug' => 'starter-test',
            'monthly_price' => 0,
            'yearly_price' => 0,
            'is_default_free' => true,
            'is_active' => true,
        ]);

        foreach (PlanFeature::cases() as $feature) {
            $plan->featureFlags()->create([
                'feature_code' => $feature->value,
                'is_enabled' => true,
            ]);
        }

        return compact('governorate', 'city', 'specialty', 'serviceType', 'plan');
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array{governorate: Governorate, city: City, specialty: Specialty, serviceType: ServiceType, plan: SubscriptionPlan, clinic: Clinic, address: ClinicAddress, doctor: Doctor}
     */
    protected function seedListableProvider(array $overrides = []): array
    {
        $catalog = $this->seedClinicCatalog();

        $clinic = Clinic::factory()->verified()->create([
            'subscription_plan_id' => $catalog['plan']->id,
            'name_ar' => $overrides['clinic_name_ar'] ?? 'عيادة النور',
            'name_en' => $overrides['clinic_name_en'] ?? 'Al Noor Clinic',
        ]);

        $address = ClinicAddress::factory()->create([
            'clinic_id' => $clinic->id,
            'city_id' => $catalog['city']->id,
            'latitude' => 30.0444,
            'longitude' => 31.2357,
        ]);

        $doctor = Doctor::factory()->create([
            'specialty_id' => $catalog['specialty']->id,
            'name_ar' => $overrides['doctor_name_ar'] ?? 'د. سارة أحمد',
            'name_en' => $overrides['doctor_name_en'] ?? 'Dr. Sara Ahmed',
            'is_active' => $overrides['doctor_active'] ?? true,
            'years_of_experience' => $overrides['years'] ?? 12,
            'consultation_fee' => $overrides['fee'] ?? 350,
            'gender' => $overrides['gender'] ?? 'female',
        ]);

        $clinic->doctors()->attach($doctor);

        return ['clinic' => $clinic, 'address' => $address, 'doctor' => $doctor] + $catalog;
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array{user: User, clinic: Clinic, governorate: Governorate, city: City, specialty: Specialty, serviceType: ServiceType, plan: SubscriptionPlan}
     */
    protected function actingAsClinicOwner(array $overrides = []): array
    {
        $catalog = $this->seedClinicCatalog();

        $user = User::factory()->create($overrides);
        $clinic = Clinic::factory()->create([
            'owner_user_id' => $user->id,
            'subscription_plan_id' => $catalog['plan']->id,
            'email' => $user->email,
            'phone' => $user->phone,
        ]);
        $user->assignRole(RoleName::ClinicOwner, $clinic->id);
        $this->actingAs($user);

        return ['user' => $user, 'clinic' => $clinic] + $catalog;
    }
}
