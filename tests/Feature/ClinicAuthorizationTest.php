<?php

namespace Tests\Feature;

use App\Enums\RoleName;
use App\Models\Clinic;
use App\Models\ClinicAddress;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClinicAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_from_the_clinic_dashboard(): void
    {
        $this->get('/clinic')->assertRedirect('/login');
    }

    public function test_patient_is_forbidden_from_the_clinic_dashboard(): void
    {
        $this->actingAsRole(RoleName::Patient);

        $this->get('/clinic')->assertForbidden();
    }

    public function test_reception_cannot_open_billing_or_add_doctors(): void
    {
        $context = $this->actingAsClinicOwner();

        $reception = User::factory()->create();
        $reception->assignRole(RoleName::Reception, $context['clinic']->id);
        $this->actingAs($reception);

        $this->get('/clinic')->assertOk();
        $this->get('/clinic/subscription')->assertForbidden();
        $this->get('/clinic/doctors/create')->assertForbidden();
        $this->get('/clinic/profile')->assertForbidden();
        $this->get('/clinic/staff')->assertForbidden();
    }

    public function test_owner_cannot_edit_another_clinic_branch(): void
    {
        $context = $this->actingAsClinicOwner();

        $otherOwner = User::factory()->create();
        $otherClinic = Clinic::factory()->create([
            'owner_user_id' => $otherOwner->id,
            'subscription_plan_id' => $context['plan']->id,
        ]);
        $otherAddress = ClinicAddress::factory()->create([
            'clinic_id' => $otherClinic->id,
            'city_id' => $context['city']->id,
        ]);

        $this->get('/clinic/addresses/'.$otherAddress->id.'/edit')->assertNotFound();
        $this->put('/clinic/addresses/'.$otherAddress->id, [
            'governorate_id' => $context['governorate']->id,
            'city_id' => $context['city']->id,
            'address_line' => 'stolen',
        ])->assertNotFound();
    }

    public function test_reception_login_lands_on_the_clinic_dashboard(): void
    {
        $context = $this->actingAsClinicOwner();

        $reception = User::factory()->create(['phone' => '201099988877']);
        $reception->assignRole(RoleName::Reception, $context['clinic']->id);

        $this->post('/logout');

        $this->post('/login', [
            'phone' => '01099988877',
            'password' => 'password',
        ])->assertRedirect('/clinic');
    }
}
