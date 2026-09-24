<?php

namespace Tests\Feature\Admin;

use App\Enums\RoleName;
use App\Models\Booking;
use App\Models\Doctor;
use App\Models\ServiceType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminCatalogDestroyTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_delete_a_service_type_without_bookings(): void
    {
        $this->actingAsRole(RoleName::PlatformAdmin);

        $type = ServiceType::query()->create([
            'code' => 'demo_wipe',
            'name_ar' => 'خدمة تجريبية',
            'name_en' => 'Demo wipe',
            'slug' => 'demo-wipe',
            'is_active' => true,
            'display_order' => 99,
            'default_duration_minutes' => 30,
        ]);

        $this->from('/admin/service-types')
            ->delete('/admin/service-types/'.$type->id)
            ->assertRedirect(route('admin.service-types.index'));

        $this->assertDatabaseMissing('service_types', ['id' => $type->id]);
    }

    public function test_admin_cannot_delete_a_service_type_with_bookings(): void
    {
        $provider = $this->seedListableProvider();
        $this->actingAsRole(RoleName::PlatformAdmin);

        Booking::factory()->create([
            'clinic_id' => $provider['clinic']->id,
            'doctor_id' => $provider['doctor']->id,
            'service_type_id' => $provider['serviceType']->id,
            'clinic_address_id' => $provider['address']->id,
        ]);

        $this->from('/admin/service-types')
            ->delete('/admin/service-types/'.$provider['serviceType']->id)
            ->assertRedirect();

        $this->assertDatabaseHas('service_types', ['id' => $provider['serviceType']->id]);
    }

    public function test_admin_can_delete_a_patient_without_bookings(): void
    {
        $this->actingAsRole(RoleName::PlatformAdmin);
        $this->seedRoles();

        $patient = User::factory()->create(['name' => 'مريض للحذف']);
        $patient->assignRole(RoleName::Patient);

        $this->from('/admin/users/'.$patient->id)
            ->delete('/admin/users/'.$patient->id)
            ->assertRedirect(route('admin.users.index'));

        $this->assertDatabaseMissing('users', ['id' => $patient->id]);
    }

    public function test_admin_can_delete_a_clinic_and_a_doctor(): void
    {
        $provider = $this->seedListableProvider();
        $this->actingAsRole(RoleName::PlatformAdmin);

        $orphan = Doctor::factory()->create([
            'specialty_id' => $provider['specialty']->id,
            'name_ar' => 'د. للحذف',
            'name_en' => 'Dr. Delete Me',
        ]);

        $this->from('/admin/doctors')
            ->delete('/admin/doctors/'.$orphan->id)
            ->assertRedirect(route('admin.doctors.index'));

        $this->assertDatabaseMissing('doctors', ['id' => $orphan->id]);

        $clinicId = $provider['clinic']->id;

        $this->from('/admin/clinics/'.$clinicId)
            ->delete('/admin/clinics/'.$clinicId)
            ->assertRedirect(route('admin.clinics.index'));

        $this->assertDatabaseMissing('clinics', ['id' => $clinicId]);
    }

    public function test_admin_can_toggle_doctor_active_flag(): void
    {
        $provider = $this->seedListableProvider();
        $this->actingAsRole(RoleName::PlatformAdmin);

        $this->from('/admin/doctors')
            ->put('/admin/doctors/'.$provider['doctor']->id, ['action' => 'toggle_active'])
            ->assertRedirect();

        $this->assertFalse($provider['doctor']->fresh()->is_active);
    }
}
