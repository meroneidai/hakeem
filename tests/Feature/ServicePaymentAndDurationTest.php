<?php

namespace Tests\Feature;

use App\Enums\PaymentMode;
use App\Enums\RoleName;
use App\Models\ServiceType;
use App\Models\User;
use App\Support\Settings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ServicePaymentAndDurationTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_saves_service_payment_modes_and_duration(): void
    {
        $this->actingAsRole(RoleName::PlatformAdmin);

        $this->post('/admin/service-types', [
            'code' => 'video_consult',
            'name_ar' => 'استشارة أونلاين',
            'name_en' => 'Online consult',
            'is_online' => '1',
            'is_active' => '1',
            'requires_time_slot' => '1',
            'default_duration_minutes' => 15,
            'allowed_payment_modes' => [PaymentMode::Online->value],
        ])->assertRedirect('/admin/service-types');

        $type = ServiceType::query()->firstWhere('code', 'video_consult');

        $this->assertSame(['online'], $type->allowed_payment_modes);
        $this->assertSame(15, $type->default_duration_minutes);
        $this->assertTrue($type->is_online);
    }

    public function test_booking_rejects_a_payment_mode_the_service_does_not_allow(): void
    {
        $this->seedRoles();
        app(Settings::class)->setMany([
            'payments.allowed_modes' => ['online', 'at_clinic'],
            'payments.default_mode' => 'at_clinic',
        ], 'payments');

        $provider = $this->seedListableProvider();
        $provider['serviceType']->update([
            'allowed_payment_modes' => [PaymentMode::Online->value],
            'default_duration_minutes' => 15,
        ]);

        $this->actingAs(User::factory()->create())
            ->from('/book/doctors/'.$provider['doctor']->slug)
            ->post('/book/doctors/'.$provider['doctor']->slug, [
                'service_type_id' => $provider['serviceType']->id,
                'clinic_address_id' => $provider['address']->id,
                'scheduled_at' => now()->addDay()->format('Y-m-d H:i:s'),
                'payment_mode' => PaymentMode::AtClinic->value,
            ])
            ->assertRedirect('/book/doctors/'.$provider['doctor']->slug)
            ->assertSessionHasErrors('payment_mode');
    }

    public function test_booking_accepts_the_payment_mode_the_service_allows(): void
    {
        $this->seedRoles();
        app(Settings::class)->setMany([
            'payments.allowed_modes' => ['online', 'at_clinic'],
            'payments.default_mode' => 'at_clinic',
        ], 'payments');

        $provider = $this->seedListableProvider();
        $provider['serviceType']->update([
            'allowed_payment_modes' => [PaymentMode::Online->value],
        ]);

        $this->actingAs(User::factory()->create())
            ->post('/book/doctors/'.$provider['doctor']->slug, [
                'service_type_id' => $provider['serviceType']->id,
                'clinic_address_id' => $provider['address']->id,
                'scheduled_at' => now()->addDay()->format('Y-m-d H:i:s'),
                'payment_mode' => PaymentMode::Online->value,
            ])
            ->assertRedirect('/appointments');

        $this->assertDatabaseHas('bookings', [
            'service_type_id' => $provider['serviceType']->id,
            'payment_mode' => PaymentMode::Online->value,
        ]);
    }

    public function test_online_only_service_stays_bookable_when_the_clinic_omits_online(): void
    {
        $this->seedRoles();
        app(Settings::class)->setMany([
            'payments.allowed_modes' => ['online', 'at_clinic', 'after_service'],
            'payments.default_mode' => 'at_clinic',
            'payments.allow_clinic_override' => true,
        ], 'payments');

        $provider = $this->seedListableProvider();
        $provider['clinic']->update([
            'payment_modes' => [PaymentMode::AtClinic->value, PaymentMode::AfterService->value],
            'default_payment_mode' => PaymentMode::AtClinic->value,
        ]);
        $provider['serviceType']->update([
            'allowed_payment_modes' => [PaymentMode::Online->value],
            'is_online' => true,
        ]);

        $this->actingAs(User::factory()->create())
            ->get('/book/doctors/'.$provider['doctor']->slug.'?service_type_id='.$provider['serviceType']->id)
            ->assertOk()
            ->assertSee(__('booking.payment_mode.online'));

        $this->actingAs(User::factory()->create())
            ->post('/book/doctors/'.$provider['doctor']->slug, [
                'service_type_id' => $provider['serviceType']->id,
                'clinic_address_id' => $provider['address']->id,
                'scheduled_at' => now()->addDay()->format('Y-m-d H:i:s'),
                'payment_mode' => PaymentMode::Online->value,
            ])
            ->assertRedirect('/appointments');
    }

    public function test_booking_form_shows_duration_and_day_picker(): void
    {
        $this->seedRoles();
        $provider = $this->seedListableProvider();
        $provider['serviceType']->update(['default_duration_minutes' => 30]);

        $this->actingAs(User::factory()->create())
            ->get('/book/doctors/'.$provider['doctor']->slug.'?service_type_id='.$provider['serviceType']->id)
            ->assertOk()
            ->assertSee('duration_minutes', false)
            ->assertSee(__('booking.today'));
    }

    public function test_clinic_saves_a_preset_service_duration(): void
    {
        $context = $this->actingAsClinicOwner();

        $this->put(route('clinic.services.update'), [
            'services' => [
                $context['serviceType']->id => [
                    'enabled' => '1',
                    'price' => 200,
                    'duration_minutes' => 45,
                    'session_count' => 1,
                ],
            ],
        ])->assertRedirect();

        $this->assertDatabaseHas('clinic_services', [
            'clinic_id' => $context['clinic']->id,
            'service_type_id' => $context['serviceType']->id,
            'duration_minutes' => 45,
            'is_active' => true,
        ]);
    }
}
