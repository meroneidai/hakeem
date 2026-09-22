<?php

namespace Tests\Feature;

use App\Models\ContactInquiry;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ContactPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_contact_form_stores_an_inquiry(): void
    {
        $this->from('/contact')->post('/contact', [
            'name' => 'منى علي',
            'phone' => '01012345678',
            'email' => 'mona@example.com',
            'audience' => 'patient',
            'subject' => 'استفسار عن حجز',
            'message' => 'أريد معرفة مواعيد العيادة.',
        ])->assertRedirect('/contact')->assertSessionHas('status');

        $inquiry = ContactInquiry::query()->first();

        $this->assertNotNull($inquiry);
        $this->assertSame('منى علي', $inquiry->name);
        $this->assertSame(User::normalizePhone('01012345678'), $inquiry->phone);
        $this->assertSame('patient', $inquiry->audience);
        $this->assertSame('استفسار عن حجز', $inquiry->subject);
    }

    public function test_contact_form_requires_core_fields(): void
    {
        $this->from('/contact')
            ->post('/contact', [])
            ->assertRedirect('/contact')
            ->assertSessionHasErrors(['name', 'phone', 'audience', 'subject', 'message']);
    }
}
