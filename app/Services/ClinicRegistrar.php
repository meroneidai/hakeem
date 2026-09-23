<?php

namespace App\Services;

use App\Enums\BillingCycle;
use App\Enums\ClinicModule;
use App\Enums\RoleName;
use App\Enums\ServiceTypeCode;
use App\Enums\VerificationStatus;
use App\Models\City;
use App\Models\Clinic;
use App\Models\DiscountCode;
use App\Models\Doctor;
use App\Models\ServiceType;
use App\Models\SubscriptionPlan;
use App\Models\User;
use App\Support\UniqueSlug;
use Illuminate\Support\Facades\DB;

class ClinicRegistrar
{
    public function __construct(
        private ClinicSubscriptionService $subscriptions,
        private AddressScheduleWriter $schedules,
    ) {}

    /**
     * @param  array{
     *     name: string,
     *     phone: string,
     *     email: string,
     *     password: string,
     *     preferred_language?: string,
     *     is_single_doctor: bool,
     *     clinic_name_ar: string,
     *     clinic_name_en: string,
     *     city_id: int,
     *     address_line: string,
     *     specialty_id: int,
     *     subscription_plan_id?: int|null,
     *     billing_cycle?: string,
     *     discount_code?: string|null,
     *     doctor_name_ar?: string|null,
     *     doctor_name_en?: string|null,
     * }  $data
     */
    public function register(array $data): User
    {
        return DB::transaction(function () use ($data) {
            $phone = User::normalizePhone($data['phone']);

            $user = User::create([
                'name' => $data['name'],
                'phone' => $phone,
                'email' => $data['email'],
                'password' => $data['password'],
                'preferred_language' => $data['preferred_language'] ?? app()->getLocale(),
                'email_verified_at' => now(),
            ]);

            $plan = $this->resolvePlan($data['subscription_plan_id'] ?? null);
            $discount = $this->resolveDiscount($data['discount_code'] ?? null, $plan);
            $cycle = BillingCycle::tryFrom($data['billing_cycle'] ?? '') ?? BillingCycle::Monthly;
            $isSingle = (bool) $data['is_single_doctor'];

            $clinic = Clinic::create([
                'owner_user_id' => $user->id,
                'name_ar' => $data['clinic_name_ar'],
                'name_en' => $data['clinic_name_en'],
                'slug' => UniqueSlug::for($data['clinic_name_en'], 'clinics'),
                'email' => $data['email'],
                'phone' => $phone,
                'is_single_doctor' => $isSingle,
                'subscription_plan_id' => $plan->id,
                'verification_status' => VerificationStatus::Pending,
                'is_active' => true,
                'modules' => ClinicModule::normalize($data['modules'] ?? []),
            ]);

            $user->assignRole(RoleName::ClinicOwner, $clinic->id);

            $this->subscriptions->subscribe($clinic, $plan, $cycle, $discount);

            $city = City::query()->findOrFail($data['city_id']);

            $address = $clinic->addresses()->create([
                'city_id' => $city->id,
                'label_ar' => $isSingle ? 'العيادة' : 'الفرع الرئيسي',
                'label_en' => $isSingle ? 'Clinic' : 'Main branch',
                'address_line' => $data['address_line'],
                'phone' => $phone,
                'is_primary' => true,
                'is_active' => true,
            ]);

            $this->schedules->seedDefaults($address);

            $this->enableClinicAppointment($clinic, (int) $data['specialty_id']);

            if ($isSingle) {
                $this->attachOwnerAsDoctor($clinic, $user, $data, (int) $data['specialty_id'], $address->id);
            }

            return $user->fresh(['roles', 'ownedClinics']);
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function attachOwnerAsDoctor(Clinic $clinic, User $user, array $data, int $specialtyId, int $addressId): void
    {
        $nameAr = $data['doctor_name_ar'] ?? $data['clinic_name_ar'];
        $nameEn = $data['doctor_name_en'] ?? $data['clinic_name_en'];

        $doctor = Doctor::create([
            'user_id' => $user->id,
            'name_ar' => $nameAr,
            'name_en' => $nameEn,
            'slug' => UniqueSlug::for($nameEn, 'doctors'),
            'specialty_id' => $specialtyId,
            'is_active' => true,
        ]);

        $clinic->doctors()->attach($doctor->id);
        $user->assignRole(RoleName::Doctor, $clinic->id);

        foreach ($clinic->addresses()->first()?->schedules()->where('is_closed', false)->get() ?? [] as $schedule) {
            $doctor->availability()->create([
                'clinic_address_id' => $addressId,
                'day_of_week' => $schedule->day_of_week,
                'open_time' => $schedule->open_time,
                'close_time' => $schedule->close_time,
            ]);
        }
    }

    private function enableClinicAppointment(Clinic $clinic, int $specialtyId): void
    {
        $serviceTypeId = ServiceType::query()
            ->where('code', ServiceTypeCode::ClinicAppointment->value)
            ->value('id');

        if (! $serviceTypeId) {
            return;
        }

        $clinic->services()->create([
            'service_type_id' => $serviceTypeId,
            'specialty_id' => $specialtyId,
            'price' => 0,
            'is_active' => true,
        ]);
    }

    private function resolvePlan(mixed $planId): SubscriptionPlan
    {
        if ($planId) {
            $plan = SubscriptionPlan::query()->active()->find($planId);

            if ($plan) {
                return $plan;
            }
        }

        return SubscriptionPlan::query()
            ->active()
            ->where('is_default_free', true)
            ->ordered()
            ->firstOrFail();
    }

    private function resolveDiscount(?string $code, SubscriptionPlan $plan): ?DiscountCode
    {
        if (! filled($code)) {
            return null;
        }

        $discount = DiscountCode::query()
            ->where('code', strtoupper(trim($code)))
            ->first();

        return $discount?->isRedeemable($plan) ? $discount : null;
    }
}
