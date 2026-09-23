<?php

namespace Tests\Feature;

use App\Enums\RoleName;
use App\Enums\VerificationStatus;
use App\Models\ClinicAddress;
use App\Models\Doctor;
use App\Models\Specialty;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CatalogImageUploadTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_from_specialty_image_upload(): void
    {
        $this->post('/admin/specialties', [
            'name_ar' => 'قلب',
            'name_en' => 'Cardiology',
            'category' => 'general',
            'image' => UploadedFile::fake()->image('heart.jpg'),
        ])->assertRedirect('/admin/login');
    }

    public function test_patient_cannot_upload_specialty_image(): void
    {
        $this->actingAsRole(RoleName::Patient);

        $this->post('/admin/specialties', [
            'name_ar' => 'قلب',
            'name_en' => 'Cardiology',
            'category' => 'general',
            'image' => UploadedFile::fake()->image('heart.jpg'),
        ])->assertForbidden();
    }

    public function test_admin_specialty_form_includes_image_upload(): void
    {
        $this->actingAsRole(RoleName::PlatformAdmin);

        $this->get('/admin/specialties/create')
            ->assertOk()
            ->assertSee(__('common.image'))
            ->assertSee('enctype="multipart/form-data"', false)
            ->assertSee('name="image"', false);
    }

    public function test_admin_stores_specialty_image_and_homepage_renders_it(): void
    {
        Storage::fake('public');

        $this->actingAsRole(RoleName::PlatformAdmin);

        $file = UploadedFile::fake()->image('cardiology.jpg', 80, 80);

        $this->post('/admin/specialties', [
            'name_ar' => 'قلب',
            'name_en' => 'Cardiology',
            'category' => 'general',
            'is_active' => '1',
            'image' => $file,
        ])->assertRedirect('/admin/specialties');

        $specialty = Specialty::query()->where('slug', 'cardiology')->first();

        $this->assertNotNull($specialty);
        $this->assertNotNull($specialty->image_path);
        Storage::disk('public')->assertExists($specialty->image_path);

        $this->get('/')
            ->assertOk()
            ->assertSee('storage/'.$specialty->image_path, false);
    }

    public function test_admin_rejects_non_image_specialty_upload(): void
    {
        Storage::fake('public');

        $this->actingAsRole(RoleName::PlatformAdmin);

        $this->from('/admin/specialties/create')
            ->post('/admin/specialties', [
                'name_ar' => 'قلب',
                'name_en' => 'Cardiology',
                'category' => 'general',
                'image' => UploadedFile::fake()->create('notes.pdf', 40, 'application/pdf'),
            ])
            ->assertRedirect('/admin/specialties/create')
            ->assertSessionHasErrors(['image' => 'حقل الصورة يجب أن يكون صورة.']);

        $this->assertSame(0, Specialty::query()->count());
    }

    public function test_deleting_specialty_removes_the_stored_image(): void
    {
        Storage::fake('public');

        $this->actingAsRole(RoleName::PlatformAdmin);

        $this->post('/admin/specialties', [
            'name_ar' => 'قلب',
            'name_en' => 'Cardiology',
            'category' => 'general',
            'is_active' => '1',
            'image' => UploadedFile::fake()->image('heart.jpg'),
        ])->assertRedirect();

        $specialty = Specialty::query()->first();
        $path = $specialty->image_path;

        Storage::disk('public')->assertExists($path);

        $this->delete('/admin/specialties/'.$specialty->id)->assertRedirect('/admin/specialties');

        $this->assertModelMissing($specialty);
        Storage::disk('public')->assertMissing($path);
    }

    public function test_clinic_owner_stores_doctor_photo(): void
    {
        Storage::fake('public');

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
            'photo' => UploadedFile::fake()->image('karim.jpg', 120, 120),
        ])->assertRedirect('/clinic/doctors');

        $doctor = Doctor::query()->first();

        $this->assertNotNull($doctor->profile_photo_path);
        Storage::disk('public')->assertExists($doctor->profile_photo_path);

        $context['clinic']->update([
            'verification_status' => VerificationStatus::Verified,
            'verified_at' => now(),
        ]);

        $this->get('/doctors')
            ->assertOk()
            ->assertSee('storage/'.$doctor->profile_photo_path, false);
    }
}
