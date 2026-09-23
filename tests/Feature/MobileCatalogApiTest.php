<?php

namespace Tests\Feature;

use App\Enums\CareDocumentType;
use App\Enums\LabOrderStatus;
use App\Enums\PaymentMode;
use App\Enums\RoleName;
use App\Models\CareDocument;
use App\Models\LabOrder;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MobileCatalogApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_returns_401_when_records_are_requested_without_a_token(): void
    {
        $this->getJson('/api/v1/records')->assertUnauthorized();
        $this->getJson('/api/v1/lab-orders')->assertUnauthorized();
        $this->postJson('/api/v1/auth/otp')->assertUnauthorized();
    }

    public function test_doctor_profile_endpoint_exposes_book_and_slot_urls(): void
    {
        $provider = $this->seedListableProvider();

        $this->getJson('/api/v1/doctors/'.$provider['doctor']->slug)
            ->assertOk()
            ->assertJsonPath('slug', $provider['doctor']->slug)
            ->assertJsonPath('book_url', route('book.doctors.create', $provider['doctor']))
            ->assertJsonPath('slots_url', route('api.v1.doctors.slots', $provider['doctor']))
            ->assertJsonPath('clinics.0.addresses.0.lat', 30.0444);
    }

    public function test_inactive_doctor_profile_endpoint_returns_404(): void
    {
        $provider = $this->seedListableProvider(['doctor_active' => false]);

        $this->getJson('/api/v1/doctors/'.$provider['doctor']->slug)->assertNotFound();
    }

    public function test_clinic_profile_endpoint_includes_map_coordinates(): void
    {
        $provider = $this->seedListableProvider();

        $this->getJson('/api/v1/clinics/'.$provider['clinic']->slug)
            ->assertOk()
            ->assertJsonPath('slug', $provider['clinic']->slug)
            ->assertJsonPath('addresses.0.lat', 30.0444)
            ->assertJsonPath('addresses.0.lng', 31.2357);
    }

    public function test_map_pins_list_verified_clinic_coordinates(): void
    {
        $provider = $this->seedListableProvider();

        $this->getJson('/api/v1/map/pins')
            ->assertOk()
            ->assertJsonPath('data.0.lat', 30.0444)
            ->assertJsonPath('data.0.url', route('clinics.show', $provider['clinic']));
    }

    public function test_patient_lists_own_records_and_cannot_read_another_patients(): void
    {
        $this->seedRoles();
        $provider = $this->seedListableProvider();
        $patient = User::factory()->create();
        $patient->assignRole(RoleName::Patient);
        $stranger = User::factory()->create();
        $stranger->assignRole(RoleName::Patient);

        $document = CareDocument::factory()->create([
            'patient_id' => $patient->id,
            'clinic_id' => $provider['clinic']->id,
            'doctor_id' => $provider['doctor']->id,
            'type' => CareDocumentType::Consultation,
            'title' => 'ملخص الزيارة',
        ]);

        $this->actingAs($patient, 'sanctum')
            ->getJson('/api/v1/records')
            ->assertOk()
            ->assertJsonPath('data.0.id', $document->id)
            ->assertJsonPath('data.0.title', 'ملخص الزيارة');

        $this->actingAs($patient, 'sanctum')
            ->getJson('/api/v1/records/'.$document->id)
            ->assertOk()
            ->assertJsonPath('data.verification_code', $document->verification_code);

        $this->actingAs($stranger, 'sanctum')
            ->getJson('/api/v1/records/'.$document->id)
            ->assertNotFound();
    }

    public function test_patient_lists_own_lab_orders_and_cannot_read_another_patients(): void
    {
        $this->seedRoles();
        $provider = $this->seedListableProvider();
        $patient = User::factory()->create();
        $patient->assignRole(RoleName::Patient);
        $stranger = User::factory()->create();
        $stranger->assignRole(RoleName::Patient);

        $order = LabOrder::query()->create([
            'reference' => 'HK-LAB-API-1',
            'patient_id' => $patient->id,
            'clinic_id' => $provider['clinic']->id,
            'clinic_address_id' => $provider['address']->id,
            'collection_mode' => 'clinic',
            'scheduled_at' => now()->addDay(),
            'status' => LabOrderStatus::Pending,
            'payment_mode' => PaymentMode::AtClinic,
            'total' => 180,
        ]);

        $this->actingAs($patient, 'sanctum')
            ->getJson('/api/v1/lab-orders')
            ->assertOk()
            ->assertJsonPath('data.0.id', $order->id)
            ->assertJsonPath('data.0.reference', 'HK-LAB-API-1');

        $this->actingAs($stranger, 'sanctum')
            ->getJson('/api/v1/lab-orders/'.$order->id)
            ->assertNotFound();
    }

    public function test_auth_otp_aliases_verify_the_patient_phone(): void
    {
        $this->seedRoles();
        $user = User::factory()->create(['phone_verified_at' => null]);
        $user->assignRole(RoleName::Patient);

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/auth/otp')
            ->assertOk()
            ->assertJsonPath('ok', true);

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/auth/otp/verify', ['code' => '123456'])
            ->assertOk()
            ->assertJsonPath('phone_verified', true);

        $this->assertTrue($user->fresh()->isPhoneVerified());
    }
}
