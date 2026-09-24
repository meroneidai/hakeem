<?php

namespace Tests\Feature;

use App\Enums\OfferCategory;
use App\Enums\RoleName;
use App\Models\Booking;
use App\Models\Promotion;
use App\Models\SupportTicket;
use App\Models\User;
use App\Support\Settings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class AgentApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_returns_401_when_no_hermes_key_is_provided(): void
    {
        $this->getJson('/api/agent/v1')->assertUnauthorized()->assertJsonPath('code', 'hermes_key_required');
    }

    public function test_returns_401_when_the_hermes_key_is_wrong(): void
    {
        $this->withHeaders(['X-Hermes-Key' => 'not-the-key'])
            ->getJson('/api/agent/v1')
            ->assertUnauthorized();
    }

    public function test_manifest_lists_customer_service_tools_and_forbids_admin(): void
    {
        $response = $this->hermes()->getJson('/api/agent/v1')->assertOk();

        $this->assertSame('Hakeem Hermes Agent API', $response->json('name'));
        $this->assertContains('admin', $response->json('rules.cannot'));
        $this->assertContains('delete_records', $response->json('rules.cannot'));
        $this->assertTrue(collect($response->json('endpoints'))->contains(fn (array $endpoint) => $endpoint['name'] === 'search'));
        $this->assertFalse(collect($response->json('endpoints'))->contains(fn (array $endpoint) => str_contains($endpoint['path'], 'admin')));
    }

    public function test_admin_overview_is_not_on_the_agent_surface(): void
    {
        $this->hermes()->getJson('/api/agent/v1/admin/overview')->assertNotFound();
    }

    public function test_search_finds_listable_doctors(): void
    {
        $this->seedListableProvider();

        $this->hermes()
            ->getJson('/api/agent/v1/search?q=سارة&type=doctors')
            ->assertOk()
            ->assertJsonPath('doctors.0.name', 'د. سارة أحمد');
    }

    public function test_suggestions_include_matching_doctors_and_intents(): void
    {
        $this->seedListableProvider();

        $this->hermes()
            ->getJson('/api/agent/v1/suggestions?'.http_build_query(['q' => 'سارة']))
            ->assertOk()
            ->assertJsonFragment(['type' => 'doctor', 'label' => 'د. سارة أحمد']);

        $this->hermes()
            ->getJson('/api/agent/v1/suggestions?'.http_build_query(['q' => 'حساب']))
            ->assertOk()
            ->assertJsonFragment(['key' => 'register']);
    }

    public function test_help_returns_faq_and_contact_paths(): void
    {
        $this->hermes()
            ->getJson('/api/agent/v1/help')
            ->assertOk()
            ->assertJsonPath('faq.0.key', 'booking')
            ->assertJsonPath('contact.contact_url', route('contact'));
    }

    public function test_lists_running_offers_and_signup_campaign(): void
    {
        $this->travelTo('2026-09-15 12:00:00');

        Promotion::query()->create([
            'title_ar' => 'فحص شامل بخصم',
            'title_en' => 'Full checkup deal',
            'slug' => 'full-checkup-offer',
            'category' => OfferCategory::Checkup,
            'original_price' => 1470,
            'offer_price' => 890,
            'discount_type' => 'percentage',
            'discount_value' => 39,
            'starts_at' => now()->subDay(),
            'ends_at' => now()->addMonth(),
            'is_featured' => true,
            'is_active' => true,
        ]);

        app(Settings::class)->setMany([
            'loyalty.signup_bonus_enabled' => true,
            'loyalty.signup_bonus_amount' => 50,
            'loyalty.signup_bonus_starts_at' => '2026-09-01 00:00:00',
            'loyalty.signup_bonus_ends_at' => '2026-09-20 23:59:00',
            'loyalty.signup_headline_ar' => 'رصيد ترحيبي للتجربة',
        ], 'loyalty');

        $this->hermes()
            ->getJson('/api/agent/v1/offers')
            ->assertOk()
            ->assertJsonPath('offers.0.title', 'فحص شامل بخصم');

        $this->hermes()
            ->getJson('/api/agent/v1/campaigns')
            ->assertOk()
            ->assertJsonPath('signup.headline', 'رصيد ترحيبي للتجربة')
            ->assertJsonPath('offers.0.slug', 'full-checkup-offer');
    }

    public function test_registers_a_customer_and_returns_a_hermes_token(): void
    {
        $this->seedRoles();

        $this->hermes()
            ->postJson('/api/agent/v1/customers', [
                'name' => 'مريض هرمس',
                'phone' => '01077778888',
                'password' => 'password12',
            ])
            ->assertCreated()
            ->assertJsonPath('user.name', 'مريض هرمس')
            ->assertJsonPath('user.roles.0', 'patient')
            ->assertJsonStructure(['token', 'user' => ['id', 'phone']]);

        $this->assertDatabaseHas('users', ['phone' => '201077778888']);
    }

    public function test_returns_401_when_profile_is_requested_without_customer_auth(): void
    {
        $this->hermes()
            ->getJson('/api/agent/v1/customers/me')
            ->assertUnauthorized()
            ->assertJsonPath('code', 'customer_credentials_required')
            ->assertJsonPath('ask_for.0', 'identifier');
    }

    public function test_returns_profile_when_phone_and_password_are_provided(): void
    {
        $this->seedRoles();
        $user = User::factory()->create([
            'name' => 'منى إبراهيم',
            'phone' => '201055512345',
            'password' => 'password',
        ]);
        $user->assignRole(RoleName::Patient);

        $this->hermes()
            ->postJson('/api/agent/v1/customers/me', [
                'phone' => '01055512345',
                'password' => 'password',
            ])
            ->assertOk()
            ->assertJsonPath('user.name', 'منى إبراهيم')
            ->assertJsonStructure(['token', 'user']);
    }

    public function test_returns_profile_when_customer_bearer_token_is_provided_without_password(): void
    {
        $this->seedRoles();
        $user = User::factory()->create(['name' => 'حساب مسجّل']);
        $user->assignRole(RoleName::Patient);

        $this->hermes()
            ->actingAs($user, 'sanctum')
            ->getJson('/api/agent/v1/customers/me')
            ->assertOk()
            ->assertJsonPath('user.name', 'حساب مسجّل')
            ->assertJsonMissingPath('token');
    }

    public function test_returns_422_when_profile_password_is_wrong(): void
    {
        $this->seedRoles();
        User::factory()->create([
            'phone' => '201055512346',
            'password' => 'password',
        ]);

        $this->hermes()
            ->postJson('/api/agent/v1/customers/me', [
                'phone' => '201055512346',
                'password' => 'nope',
            ])
            ->assertUnprocessable();
    }

    public function test_updates_profile_when_the_customer_is_already_authenticated(): void
    {
        $this->seedRoles();
        $user = User::factory()->create(['name' => 'الاسم القديم']);
        $user->assignRole(RoleName::Patient);

        $this->hermes()
            ->actingAs($user, 'sanctum')
            ->putJson('/api/agent/v1/customers/me', [
                'name' => 'الاسم الجديد',
                'preferred_language' => 'ar',
            ])
            ->assertOk()
            ->assertJsonPath('user.name', 'الاسم الجديد');

        $this->assertSame('الاسم الجديد', $user->fresh()->name);
    }

    public function test_lists_only_the_authenticated_customer_bookings(): void
    {
        $this->seedRoles();
        $provider = $this->seedListableProvider();
        $patient = User::factory()->create();
        $stranger = User::factory()->create();
        $patient->assignRole(RoleName::Patient);
        $stranger->assignRole(RoleName::Patient);

        Booking::factory()->create([
            'patient_id' => $patient->id,
            'clinic_id' => $provider['clinic']->id,
            'doctor_id' => $provider['doctor']->id,
            'clinic_address_id' => $provider['address']->id,
            'service_type_id' => $provider['serviceType']->id,
        ]);

        Booking::factory()->create([
            'patient_id' => $stranger->id,
            'clinic_id' => $provider['clinic']->id,
            'doctor_id' => $provider['doctor']->id,
            'clinic_address_id' => $provider['address']->id,
            'service_type_id' => $provider['serviceType']->id,
        ]);

        $this->hermes()
            ->actingAs($patient, 'sanctum')
            ->getJson('/api/agent/v1/customers/bookings')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.doctor', 'د. سارة أحمد');
    }

    public function test_opens_a_support_ticket_for_a_visitor(): void
    {
        $this->seedRoles();

        $this->hermes()
            ->postJson('/api/agent/v1/support/tickets', [
                'name' => 'زائر الدعم',
                'phone' => '01022223333',
                'subject' => 'مشكلة في الحجز',
                'body' => 'لم يصل تأكيد الموعد.',
                'category' => 'booking',
            ])
            ->assertCreated()
            ->assertJsonStructure(['reference', 'status']);

        $this->assertDatabaseHas('support_tickets', [
            'subject' => 'مشكلة في الحجز',
            'category' => 'booking',
            'channel' => 'chat',
        ]);

        $this->assertSame(1, SupportTicket::query()->count());
    }

    public function test_sends_a_password_reset_and_accepts_the_phone_code(): void
    {
        $this->seedRoles();
        $user = User::factory()->withoutEmail()->create(['phone' => '201099988877']);
        $user->assignRole(RoleName::Patient);

        $this->hermes()
            ->postJson('/api/agent/v1/customers/password/forgot', [
                'phone' => '01099988877',
            ])
            ->assertOk()
            ->assertJsonPath('channel', 'phone');

        $this->hermes()
            ->postJson('/api/agent/v1/customers/password/reset', [
                'identifier' => '01099988877',
                'token' => '123456',
                'password' => 'new-secret-9',
            ])
            ->assertOk()
            ->assertJsonPath('user.phone', '201099988877')
            ->assertJsonStructure(['token']);
    }

    public function test_email_password_reset_rejects_missing_accounts_for_the_agent(): void
    {
        Mail::fake();

        $this->hermes()
            ->postJson('/api/agent/v1/customers/password/forgot', [
                'email' => 'missing@hakeem.test',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('identifier');

        Mail::assertNothingSent();
    }

    /**
     * @param  array<string, string>  $headers
     */
    private function hermes(array $headers = []): static
    {
        return $this->withHeaders(array_merge([
            'X-Hermes-Key' => 'testing-hermes-key',
        ], $headers));
    }
}
