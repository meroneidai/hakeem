<?php

namespace Tests\Feature;

use App\Enums\RoleName;
use App\Models\Clinic;
use App\Models\Doctor;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClinicRegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_clinic_registration_screen_renders(): void
    {
        $this->seedClinicCatalog();

        $this->get('/register/clinic')
            ->assertOk()
            ->assertSee(__('clinic.register.solo'));
    }

    public function test_solo_clinic_registers_owner_doctor_address_and_starter_plan(): void
    {
        $catalog = $this->seedClinicCatalog();

        $this->post('/register/clinic', [
            'clinic_type' => 'solo',
            'name' => 'سارة أحمد',
            'email' => 'sara@clinic.test',
            'phone' => '01011112222',
            'password' => 'secret-pass-1',
            'password_confirmation' => 'secret-pass-1',
            'clinic_name_ar' => 'عيادة النور',
            'clinic_name_en' => 'Al Noor Clinic',
            'governorate_id' => $catalog['governorate']->id,
            'city_id' => $catalog['city']->id,
            'address_line' => 'شارع التحرير',
            'specialty_id' => $catalog['specialty']->id,
            'subscription_plan_id' => $catalog['plan']->id,
            'billing_cycle' => 'monthly',
        ])->assertRedirect('/clinic');

        $user = User::query()->where('email', 'sara@clinic.test')->first();

        $this->assertNotNull($user);
        $this->assertSame('201011112222', $user->phone);
        $this->assertTrue($user->hasRole(RoleName::ClinicOwner));
        $this->assertTrue($user->hasRole(RoleName::Doctor));
        $this->assertAuthenticatedAs($user);

        $clinic = Clinic::query()->where('email', 'sara@clinic.test')->first();

        $this->assertNotNull($clinic);
        $this->assertTrue($clinic->is_single_doctor);
        $this->assertSame($catalog['plan']->id, $clinic->subscription_plan_id);
        $this->assertSame(1, $clinic->addresses()->count());
        $this->assertSame(1, $clinic->doctors()->count());
        $this->assertSame(7, $clinic->addresses()->first()->schedules()->count());
        $this->assertTrue($clinic->currentSubscription()->exists());
    }

    public function test_multi_doctor_clinic_registers_without_a_doctor_profile(): void
    {
        $catalog = $this->seedClinicCatalog();

        $this->post('/register/clinic', [
            'clinic_type' => 'multi',
            'name' => 'مالك العيادة',
            'email' => 'owner@clinic.test',
            'phone' => '01033334444',
            'password' => 'secret-pass-1',
            'password_confirmation' => 'secret-pass-1',
            'clinic_name_ar' => 'مجمع الشفاء',
            'clinic_name_en' => 'Al Shifa Medical',
            'governorate_id' => $catalog['governorate']->id,
            'city_id' => $catalog['city']->id,
            'address_line' => 'شارع النيل',
            'specialty_id' => $catalog['specialty']->id,
        ])->assertRedirect('/clinic');

        $clinic = Clinic::query()->where('email', 'owner@clinic.test')->first();

        $this->assertFalse($clinic->is_single_doctor);
        $this->assertSame(0, $clinic->doctors()->count());
        $this->assertSame(0, Doctor::count());
        $this->assertTrue($clinic->owner->hasRole(RoleName::ClinicOwner, $clinic->id));
        $this->assertFalse($clinic->owner->hasRole(RoleName::Doctor, $clinic->id));
    }

    public function test_clinic_registration_rejects_an_unknown_discount_code(): void
    {
        $catalog = $this->seedClinicCatalog();

        $this->from('/register/clinic')
            ->post('/register/clinic', [
                'clinic_type' => 'solo',
                'name' => 'سارة',
                'email' => 'sara@clinic.test',
                'phone' => '01011112222',
                'password' => 'secret-pass-1',
                'password_confirmation' => 'secret-pass-1',
                'clinic_name_ar' => 'عيادة النور',
                'clinic_name_en' => 'Al Noor Clinic',
                'governorate_id' => $catalog['governorate']->id,
                'city_id' => $catalog['city']->id,
                'address_line' => 'شارع التحرير',
                'specialty_id' => $catalog['specialty']->id,
                'discount_code' => 'NOPE',
            ])
            ->assertRedirect('/register/clinic')
            ->assertSessionHasErrors('discount_code');

        $this->assertSame(0, Clinic::count());
    }

    public function test_clinic_login_redirects_to_the_clinic_dashboard(): void
    {
        $context = $this->actingAsClinicOwner();

        $this->post('/logout');

        $this->post('/login', [
            'phone' => $context['user']->phone,
            'password' => 'password',
        ])->assertRedirect('/clinic');
    }
}
