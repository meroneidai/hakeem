<?php

namespace Tests\Feature\Admin;

use App\Enums\RoleName;
use App\Models\AuditLog;
use App\Models\City;
use App\Models\Governorate;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReferenceDataTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAsRole(RoleName::PlatformAdmin);
    }

    public function test_governorate_is_created_with_a_generated_slug(): void
    {
        $this->post('/admin/governorates', [
            'name_ar' => 'القاهرة',
            'name_en' => 'Cairo',
            'is_active' => '1',
        ])->assertRedirect('/admin/governorates');

        $governorate = Governorate::firstWhere('name_en', 'Cairo');

        $this->assertSame('cairo', $governorate->slug);
        $this->assertTrue($governorate->is_active);
    }

    public function test_admin_writes_are_recorded_in_the_audit_log(): void
    {
        $this->post('/admin/governorates', ['name_ar' => 'الجيزة', 'name_en' => 'Giza']);

        $this->assertDatabaseHas('audit_logs', ['action' => 'governorate.created']);

        $governorate = Governorate::firstWhere('name_en', 'Giza');

        $this->put('/admin/governorates/'.$governorate->id, [
            'name_ar' => 'الجيزة',
            'name_en' => 'Giza Governorate',
        ]);

        $log = AuditLog::where('action', 'governorate.updated')->firstOrFail();

        $this->assertSame('Giza', $log->changes['before']['name_en']);
        $this->assertSame('Giza Governorate', $log->changes['after']['name_en']);
    }

    public function test_duplicate_slugs_are_rejected(): void
    {
        Governorate::create(['name_ar' => 'القاهرة', 'name_en' => 'Cairo', 'slug' => 'cairo']);

        $this->post('/admin/governorates', [
            'name_ar' => 'القاهرة الجديدة',
            'name_en' => 'Cairo',
            'slug' => 'cairo',
        ])->assertSessionHasErrors('slug');
    }

    public function test_city_requires_a_governorate(): void
    {
        $this->post('/admin/cities', ['name_ar' => 'مدينة نصر', 'name_en' => 'Nasr City'])
            ->assertSessionHasErrors('governorate_id');
    }

    public function test_deleting_a_governorate_cascades_to_its_cities(): void
    {
        $governorate = Governorate::create(['name_ar' => 'القاهرة', 'name_en' => 'Cairo', 'slug' => 'cairo']);
        $city = City::create([
            'governorate_id' => $governorate->id,
            'name_ar' => 'مدينة نصر',
            'name_en' => 'Nasr City',
            'slug' => 'nasr-city',
        ]);

        $this->delete('/admin/governorates/'.$governorate->id)->assertRedirect();

        $this->assertDatabaseMissing('governorates', ['id' => $governorate->id]);
        $this->assertDatabaseMissing('cities', ['id' => $city->id]);
    }

    public function test_service_type_flags_are_persisted(): void
    {
        $this->post('/admin/service-types', [
            'code' => 'home_visit',
            'name_ar' => 'زيارة منزلية',
            'name_en' => 'Home Visit',
            'requires_patient_address' => '1',
            'requires_time_slot' => '1',
            'is_active' => '1',
        ])->assertRedirect('/admin/service-types');

        $this->assertDatabaseHas('service_types', [
            'code' => 'home_visit',
            'requires_patient_address' => true,
            'requires_clinic_address' => false,
            'is_online' => false,
        ]);
    }
}
