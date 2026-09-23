<?php

namespace Tests\Feature;

use App\Enums\RoleName;
use App\Models\InsuranceProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InsuranceProviderTest extends TestCase
{
    use RefreshDatabase;

    public function test_platform_admin_creates_an_insurance_company_with_a_generated_slug(): void
    {
        $this->actingAsRole(RoleName::PlatformAdmin);

        $this->post('/admin/insurance-providers', [
            'name_ar' => 'مصر للتأمين',
            'name_en' => 'Misr Insurance',
            'hotline' => '19806',
            'is_active' => '1',
        ])->assertRedirect('/admin/insurance-providers');

        $provider = InsuranceProvider::firstWhere('name_en', 'Misr Insurance');

        $this->assertSame('misr-insurance', $provider->slug);
        $this->assertTrue($provider->is_active);
    }

    public function test_support_agents_cannot_manage_insurance_companies(): void
    {
        $this->actingAsRole(RoleName::SupportAgent);

        $this->get('/admin/insurance-providers')->assertForbidden();
    }

    public function test_clinic_owner_sets_accepted_insurance_per_service(): void
    {
        $context = $this->actingAsClinicOwner();
        $accepted = InsuranceProvider::factory()->create(['name_ar' => 'بوبا', 'name_en' => 'Bupa']);
        $declined = InsuranceProvider::factory()->create(['name_ar' => 'أكسا', 'name_en' => 'AXA']);

        $this->put('/clinic/services', [
            'services' => [
                $context['serviceType']->id => [
                    'enabled' => '1',
                    'price' => '250',
                    'insurance_providers' => [$accepted->id],
                ],
            ],
        ])->assertRedirect();

        $service = $context['clinic']->services()->firstOrFail();

        $this->assertSame([$accepted->id], $service->insuranceProviders->pluck('id')->all());
        $this->assertDatabaseMissing('clinic_service_insurance_provider', [
            'clinic_service_id' => $service->id,
            'insurance_provider_id' => $declined->id,
        ]);
    }

    public function test_inactive_insurance_companies_are_not_accepted_from_the_clinic_form(): void
    {
        $context = $this->actingAsClinicOwner();
        $retired = InsuranceProvider::factory()->inactive()->create();

        $this->put('/clinic/services', [
            'services' => [
                $context['serviceType']->id => [
                    'enabled' => '1',
                    'price' => '250',
                    'insurance_providers' => [$retired->id],
                ],
            ],
        ])->assertRedirect();

        $this->assertDatabaseEmpty('clinic_service_insurance_provider');
    }

    public function test_the_clinic_service_form_lists_active_insurance_companies(): void
    {
        $this->actingAsClinicOwner();
        $active = InsuranceProvider::factory()->create(['name_ar' => 'ميد رايت', 'name_en' => 'MedRight']);
        $retired = InsuranceProvider::factory()->inactive()->create(['name_ar' => 'شركة متوقفة', 'name_en' => 'Retired Insurer']);

        $this->get('/clinic/services')
            ->assertOk()
            ->assertSee(__('clinic.services.insurance'))
            ->assertSee($active->name_ar)
            ->assertDontSee($retired->name_ar);
    }
}
