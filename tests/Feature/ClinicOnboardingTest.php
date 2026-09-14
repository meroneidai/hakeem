<?php

namespace Tests\Feature;

use App\Enums\DayOfWeek;
use App\Models\ClinicAddress;
use App\Models\Doctor;
use App\Models\SubscriptionPlan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClinicOnboardingTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_adds_a_branch_with_default_hours(): void
    {
        $context = $this->actingAsClinicOwner();

        $this->post('/clinic/addresses', [
            'governorate_id' => $context['governorate']->id,
            'city_id' => $context['city']->id,
            'address_line' => 'شارع الهرم',
            'label_ar' => 'فرع الهرم',
            'label_en' => 'Haram branch',
            'is_primary' => '1',
            'is_active' => '1',
        ])->assertRedirect();

        $address = ClinicAddress::query()->first();

        $this->assertSame($context['clinic']->id, $address->clinic_id);
        $this->assertTrue($address->is_primary);
        $this->assertSame(7, $address->schedules()->count());
        $this->assertTrue($address->schedules()->where('day_of_week', DayOfWeek::Friday->value)->where('is_closed', true)->exists());
    }

    public function test_owner_saves_branch_hours(): void
    {
        $context = $this->actingAsClinicOwner();
        $address = ClinicAddress::factory()->create([
            'clinic_id' => $context['clinic']->id,
            'city_id' => $context['city']->id,
        ]);

        $days = [];
        foreach (DayOfWeek::weekOrder() as $day) {
            $days[$day->value] = [
                'open' => $day === DayOfWeek::Friday ? '0' : '1',
                'open_time' => '10:00',
                'close_time' => '18:00',
            ];
        }

        $this->put('/clinic/addresses/'.$address->id, [
            'governorate_id' => $context['governorate']->id,
            'city_id' => $context['city']->id,
            'address_line' => $address->address_line,
            'days' => $days,
        ])->assertRedirect();

        $saturday = $address->schedules()->where('day_of_week', DayOfWeek::Saturday->value)->first();

        $this->assertFalse($saturday->is_closed);
        $this->assertSame('10:00', substr((string) $saturday->open_time, 0, 5));
        $this->assertTrue($address->schedules()->where('day_of_week', DayOfWeek::Friday->value)->first()->is_closed);
    }

    public function test_owner_adds_a_doctor_linked_to_the_clinic(): void
    {
        $context = $this->actingAsClinicOwner();
        $address = ClinicAddress::factory()->create([
            'clinic_id' => $context['clinic']->id,
            'city_id' => $context['city']->id,
        ]);

        $this->post('/clinic/doctors', [
            'name_ar' => 'د. كريم',
            'name_en' => 'Dr. Karim',
            'specialty_id' => $context['specialty']->id,
            'address_ids' => [$address->id],
            'is_active' => '1',
        ])->assertRedirect('/clinic/doctors');

        $doctor = Doctor::query()->first();

        $this->assertTrue($context['clinic']->fresh()->doctors->contains($doctor));
        $this->assertSame($context['specialty']->id, $doctor->specialty_id);
    }

    public function test_owner_switches_subscription_plan(): void
    {
        $context = $this->actingAsClinicOwner();

        $growth = SubscriptionPlan::create([
            'name_ar' => 'النمو',
            'name_en' => 'Growth',
            'slug' => 'growth-test',
            'monthly_price' => 750,
            'yearly_price' => 0,
            'yearly_discount_pct' => 15,
            'is_default_free' => false,
            'is_active' => true,
        ]);

        $this->put('/clinic/subscription', [
            'subscription_plan_id' => $growth->id,
            'billing_cycle' => 'yearly',
        ])->assertRedirect();

        $this->assertSame($growth->id, $context['clinic']->fresh()->subscription_plan_id);
        $this->assertSame('yearly', $context['clinic']->currentSubscription->billing_cycle->value);
        $this->assertSame('7650.00', $context['clinic']->currentSubscription->amount);
    }

    public function test_owner_enables_a_service_with_a_price(): void
    {
        $context = $this->actingAsClinicOwner();

        $this->put('/clinic/services', [
            'services' => [
                $context['serviceType']->id => [
                    'enabled' => '1',
                    'price' => '250',
                    'duration_minutes' => '20',
                ],
            ],
        ])->assertRedirect();

        $service = $context['clinic']->services()->first();

        $this->assertTrue($service->is_active);
        $this->assertSame('250.00', $service->price);
        $this->assertSame(20, $service->duration_minutes);
    }
}
