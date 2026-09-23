<?php

namespace Tests\Feature;

use App\Enums\RoleName;
use App\Support\MessageTemplates;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MessageTemplateDefaultsTest extends TestCase
{
    use RefreshDatabase;

    public function test_system_form_shows_default_message_templates(): void
    {
        $this->actingAsRole(RoleName::PlatformAdmin);

        $defaults = MessageTemplates::defaults();

        $this->get('/admin/system')
            ->assertOk()
            ->assertSee(__('admin.integrations.messages'))
            ->assertSee($defaults['messages.sms_booking_created'], false)
            ->assertSee($defaults['messages.sms_lab_order_created'], false);
    }

    public function test_admin_can_override_a_template(): void
    {
        $this->actingAsRole(RoleName::PlatformAdmin);

        $this->put('/admin/system', [
            'messages' => [
                'sms_booking_created' => 'نص معدل للحجز {name}',
            ],
        ])->assertRedirect();

        $this->get('/admin/system')
            ->assertOk()
            ->assertSee('نص معدل للحجز {name}', false)
            ->assertSee(MessageTemplates::defaults()['messages.sms_booking_confirmed'], false);
    }
}
