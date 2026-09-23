<?php

namespace Tests\Feature;

use App\Enums\PaymentMode;
use App\Enums\RoleName;
use App\Models\User;
use App\Support\Settings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClinicPaymentSettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_reception_is_forbidden_from_clinic_payment_settings(): void
    {
        $context = $this->actingAsClinicOwner();
        $reception = User::factory()->create();
        $reception->assignRole(RoleName::Reception, $context['clinic']->id);

        $this->actingAs($reception)
            ->get('/clinic/payments')
            ->assertForbidden();
    }

    public function test_owner_saves_a_subset_of_platform_payment_modes(): void
    {
        app(Settings::class)->setMany([
            'payments.allowed_modes' => ['at_clinic', 'after_service', 'online'],
            'payments.default_mode' => 'at_clinic',
            'payments.allow_clinic_override' => true,
        ], 'payments');

        $context = $this->actingAsClinicOwner();

        $this->put('/clinic/payments', [
            'payment_modes' => ['after_service'],
            'default_payment_mode' => 'after_service',
        ])->assertRedirect();

        $context['clinic']->refresh();

        $this->assertSame(['after_service'], $context['clinic']->payment_modes);
        $this->assertSame('after_service', $context['clinic']->default_payment_mode);
    }

    public function test_clinic_override_blocks_a_platform_mode_on_booking(): void
    {
        app(Settings::class)->setMany([
            'payments.allowed_modes' => ['at_clinic', 'after_service'],
            'payments.default_mode' => 'at_clinic',
            'payments.allow_clinic_override' => true,
        ], 'payments');

        $provider = $this->seedListableProvider();
        $provider['clinic']->update([
            'payment_modes' => [PaymentMode::AtClinic->value],
            'default_payment_mode' => PaymentMode::AtClinic->value,
        ]);

        $this->actingAs(User::factory()->create())
            ->from('/book/doctors/'.$provider['doctor']->slug)
            ->post('/book/doctors/'.$provider['doctor']->slug, [
                'service_type_id' => $provider['serviceType']->id,
                'clinic_address_id' => $provider['address']->id,
                'scheduled_at' => now()->addDay()->toDateTimeString(),
                'payment_mode' => PaymentMode::AfterService->value,
            ])
            ->assertRedirect('/book/doctors/'.$provider['doctor']->slug)
            ->assertSessionHasErrors('payment_mode');
    }

    public function test_locked_platform_setting_forbids_clinic_payment_updates(): void
    {
        app(Settings::class)->set('payments.allow_clinic_override', false, 'payments');

        $this->actingAsClinicOwner();

        $this->get('/clinic/payments')->assertOk();

        $this->put('/clinic/payments', [
            'payment_modes' => ['at_clinic'],
            'default_payment_mode' => 'at_clinic',
        ])->assertForbidden();
    }
}
