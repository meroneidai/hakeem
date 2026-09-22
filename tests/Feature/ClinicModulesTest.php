<?php

namespace Tests\Feature;

use App\Enums\ClinicModule;
use App\Models\Clinic;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClinicModulesTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_without_modules_hides_labs_and_offers(): void
    {
        $catalog = $this->seedClinicCatalog();

        $this->post('/register/clinic', $this->payload($catalog))
            ->assertRedirect('/clinic');

        $clinic = Clinic::query()->where('email', 'sara@clinic.test')->first();

        $this->assertSame([ClinicModule::Appointments->value], $clinic->modules);
        $this->assertFalse($clinic->hasModule(ClinicModule::Labs));
        $this->assertFalse($clinic->hasModule(ClinicModule::Promotions));

        $this->get('/clinic/labs')->assertNotFound();
        $this->get('/clinic/offers')->assertNotFound();
        $this->get('/clinic')->assertOk();
    }

    public function test_registration_with_labs_shows_lab_pages_only(): void
    {
        $catalog = $this->seedClinicCatalog();

        $this->post('/register/clinic', $this->payload($catalog, ['labs']))
            ->assertRedirect('/clinic');

        $clinic = Clinic::query()->where('email', 'sara@clinic.test')->first();

        $this->assertTrue($clinic->hasModule(ClinicModule::Labs));
        $this->assertFalse($clinic->hasModule(ClinicModule::Promotions));

        $this->get('/clinic/labs')->assertOk();
        $this->get('/clinic/lab-orders')->assertOk();
        $this->get('/clinic/offers')->assertNotFound();
    }

    /**
     * @param  array<string, mixed>  $catalog
     * @param  list<string>  $modules
     * @return array<string, mixed>
     */
    private function payload(array $catalog, array $modules = []): array
    {
        return [
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
            'modules' => $modules,
        ];
    }
}
