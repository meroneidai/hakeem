<?php

namespace Database\Seeders;

use App\Enums\ClinicModule;
use App\Enums\RoleName;
use App\Enums\VerificationStatus;
use App\Models\City;
use App\Models\Clinic;
use App\Models\Specialty;
use App\Models\User;
use App\Services\ClinicRegistrar;
use Illuminate\Database\Seeder;

class DemoClinicSeeder extends Seeder
{
    public function run(): void
    {
        $existing = Clinic::query()->where('email', 'clinic@hakeem.test')->first();

        if ($existing) {
            $existing->update([
                'verification_status' => VerificationStatus::Verified,
                'verified_at' => $existing->verified_at ?? now(),
                'modules' => ClinicModule::normalize([
                    ClinicModule::Labs,
                    ClinicModule::Promotions,
                    ClinicModule::PhysicalTherapy,
                    ClinicModule::Dental,
                    ClinicModule::Cosmetic,
                    ClinicModule::Massage,
                ]),
            ]);

            return;
        }

        $city = City::query()->orderBy('id')->first();
        $specialty = Specialty::query()->orderBy('id')->first();

        if (! $city || ! $specialty) {
            return;
        }

        $phone = env('HAKEEM_CLINIC_PHONE', '01111111111');
        $password = env('HAKEEM_CLINIC_PASSWORD', 'password');

        $owner = app(ClinicRegistrar::class)->register([
            'name' => 'عيادة النور',
            'phone' => $phone,
            'email' => 'clinic@hakeem.test',
            'password' => $password,
            'preferred_language' => 'ar',
            'is_single_doctor' => true,
            'clinic_name_ar' => 'عيادة النور',
            'clinic_name_en' => 'Al Noor Clinic',
            'city_id' => $city->id,
            'address_line' => 'شارع التحرير، الدور الأرضي',
            'specialty_id' => $specialty->id,
            'doctor_name_ar' => 'د. سارة أحمد',
            'doctor_name_en' => 'Dr. Sara Ahmed',
        ]);

        $clinic = $owner->ownedClinics()->first();
        $clinic->update([
            'verification_status' => VerificationStatus::Verified,
            'verified_at' => now(),
            'modules' => ClinicModule::normalize([
                ClinicModule::Labs,
                ClinicModule::Promotions,
                ClinicModule::PhysicalTherapy,
                ClinicModule::Dental,
                ClinicModule::Cosmetic,
                ClinicModule::Massage,
            ]),
        ]);

        $reception = User::updateOrCreate(
            ['phone' => User::normalizePhone('01222222222')],
            [
                'name' => 'موظف الاستقبال',
                'email' => 'reception@hakeem.test',
                'password' => $password,
                'preferred_language' => 'ar',
                'is_active' => true,
            ],
        );

        $reception->assignRole(RoleName::Reception, $clinic->id);
    }
}
