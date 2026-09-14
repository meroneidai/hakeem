<?php

namespace Tests\Feature\Admin;

use App\Enums\RoleName;
use App\Models\Setting;
use App\Support\Settings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PlatformSettingsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAsRole(RoleName::PlatformAdmin);
    }

    public function test_payment_modes_are_saved(): void
    {
        $this->put('/admin/payments', [
            'allowed_modes' => ['online', 'at_clinic'],
            'default_mode' => 'online',
            'allow_clinic_override' => '1',
            'gateway' => 'paymob',
            'gateway_key' => 'pk_test_123',
        ])->assertRedirect();

        $settings = app(Settings::class);

        $this->assertSame(['online', 'at_clinic'], $settings->get('payments.allowed_modes'));
        $this->assertSame('online', $settings->get('payments.default_mode'));
        $this->assertSame('paymob', $settings->get('payments.gateway'));
    }

    /**
     * A default mode that isn't enabled would leave bookings unpayable.
     */
    public function test_default_mode_is_coerced_into_the_allowed_set(): void
    {
        $this->put('/admin/payments', [
            'allowed_modes' => ['at_clinic'],
            'default_mode' => 'online',
            'gateway' => 'none',
        ])->assertRedirect();

        $this->assertSame('at_clinic', app(Settings::class)->get('payments.default_mode'));
    }

    public function test_gateway_secret_is_encrypted_at_rest(): void
    {
        $this->put('/admin/payments', [
            'allowed_modes' => ['online'],
            'default_mode' => 'online',
            'gateway' => 'paymob',
            'gateway_secret' => 'super-secret-value',
        ])->assertRedirect();

        $row = Setting::firstWhere('key', 'payments.gateway_secret');

        $this->assertTrue($row->is_encrypted);
        $this->assertStringNotContainsString('super-secret-value', (string) $row->value);
        $this->assertSame('super-secret-value', app(Settings::class)->get('payments.gateway_secret'));
    }

    public function test_blank_secret_keeps_the_existing_value(): void
    {
        app(Settings::class)->set('payments.gateway_secret', 'original-secret', 'payments', encrypted: true);

        $this->put('/admin/payments', [
            'allowed_modes' => ['online'],
            'default_mode' => 'online',
            'gateway' => 'paymob',
            'gateway_secret' => '',
        ])->assertRedirect();

        $this->assertSame('original-secret', app(Settings::class)->get('payments.gateway_secret'));
    }

    public function test_notification_matrix_is_saved_per_event(): void
    {
        $this->put('/admin/notification-settings', [
            'matrix' => [
                'booking_created' => ['push' => '1', 'whatsapp' => '1'],
                'booking_reminder' => ['sms' => '1'],
            ],
        ])->assertRedirect();

        $matrix = app(Settings::class)->get('notifications.channels');

        $this->assertSame(['push', 'whatsapp'], $matrix['booking_created']);
        $this->assertSame(['sms'], $matrix['booking_reminder']);
        $this->assertSame([], $matrix['prescription_issued'], 'Unsubmitted events should end up with no channels.');
    }
}
