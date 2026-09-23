<?php

namespace Tests\Feature\Admin;

use App\Enums\RoleName;
use App\Models\Doctor;
use App\Models\Specialty;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminDoctorsTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_doctor_list_shows_the_specialty_and_owning_clinic(): void
    {
        $provider = $this->seedListableProvider();
        $this->actingAsRole(RoleName::PlatformAdmin);

        $this->get('/admin/doctors')
            ->assertOk()
            ->assertSee($provider['doctor']->name_ar)
            ->assertSee($provider['specialty']->name_ar)
            ->assertSee($provider['clinic']->name_ar);
    }

    public function test_the_list_filters_by_specialty(): void
    {
        $provider = $this->seedListableProvider();
        $otherSpecialty = Specialty::create([
            'name_ar' => 'جلدية',
            'name_en' => 'Dermatology',
            'slug' => 'dermatology-test',
            'category' => 'general',
            'is_active' => true,
        ]);
        $otherDoctor = Doctor::factory()->create([
            'specialty_id' => $otherSpecialty->id,
            'name_ar' => 'د. كريم علي',
            'name_en' => 'Dr. Karim Ali',
        ]);
        $provider['clinic']->doctors()->attach($otherDoctor);
        $this->actingAsRole(RoleName::PlatformAdmin);

        $this->get('/admin/doctors?specialty='.$provider['specialty']->slug)
            ->assertOk()
            ->assertSee($provider['doctor']->name_ar)
            ->assertDontSee($otherDoctor->name_ar);
    }

    public function test_the_list_filters_by_status_and_keeps_inactive_doctors_visible_to_staff(): void
    {
        $provider = $this->seedListableProvider();
        $inactive = Doctor::factory()->create([
            'specialty_id' => $provider['specialty']->id,
            'name_ar' => 'د. هدى سالم',
            'name_en' => 'Dr. Hoda Salem',
            'is_active' => false,
        ]);
        $provider['clinic']->doctors()->attach($inactive);
        $this->actingAsRole(RoleName::PlatformAdmin);

        $this->get('/admin/doctors?status=inactive')
            ->assertOk()
            ->assertSee($inactive->name_ar)
            ->assertDontSee($provider['doctor']->name_ar);
    }

    public function test_patients_cannot_open_the_admin_doctor_list(): void
    {
        $this->actingAsRole(RoleName::Patient);

        $this->get('/admin/doctors')->assertForbidden();
    }
}
