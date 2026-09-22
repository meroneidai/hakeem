<?php

namespace Tests\Feature;

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class SiteAgentTest extends TestCase
{
    use RefreshDatabase;

    public function test_agent_api_searches_doctors(): void
    {
        $provider = $this->seedListableProvider();

        $this->postJson('/api/v1/agent/messages', ['message' => 'سارة'])
            ->assertOk()
            ->assertJsonPath('intent', 'search_doctor')
            ->assertJsonPath('source', 'rules')
            ->assertJsonPath('results.doctors.0.name', $provider['doctor']->name)
            ->assertJsonPath('cards.0.title', $provider['doctor']->name);
    }

    public function test_agent_requires_a_message(): void
    {
        $this->postJson('/api/v1/agent/messages', ['message' => ''])->assertUnprocessable();
    }

    public function test_guest_is_asked_to_sign_in_before_listing_appointments(): void
    {
        $this->postJson('/api/v1/agent/messages', ['message' => 'مواعيدي'])
            ->assertOk()
            ->assertJsonPath('intent', 'auth');
    }

    public function test_web_agent_cancels_an_open_booking_after_confirmation(): void
    {
        $this->seedRoles();
        $provider = $this->seedListableProvider();
        $patient = User::factory()->create();

        $booking = Booking::factory()->create([
            'patient_id' => $patient->id,
            'clinic_id' => $provider['clinic']->id,
            'doctor_id' => $provider['doctor']->id,
            'clinic_address_id' => $provider['address']->id,
            'service_type_id' => $provider['serviceType']->id,
            'status' => BookingStatus::Pending,
        ]);

        $first = $this->actingAs($patient)
            ->postJson('/agent/messages', ['message' => 'إلغاء الحجز'])
            ->assertOk()
            ->assertJsonPath('intent', 'cancel_confirm');

        $this->actingAs($patient)
            ->postJson('/agent/messages', [
                'message' => 'نعم',
                'conversation_id' => $first->json('conversation_id'),
            ])
            ->assertOk()
            ->assertJsonPath('intent', 'cancelled');

        $this->assertSame(BookingStatus::Cancelled, $booking->fresh()->status);
    }

    public function test_forwards_the_visitor_message_to_an_openai_compatible_hermes_chat(): void
    {
        Http::preventStrayRequests();
        $this->configureHermesChat('https://hermes.test/v1/chat/completions');

        Http::fake([
            'https://hermes.test/v1/chat/completions' => Http::response([
                'choices' => [[
                    'message' => [
                        'content' => 'وجدت طبيبة جلدية. [احجز الآن](https://eg.hakeem.com.sa/book/doctors/sara)',
                    ],
                ]],
            ]),
        ]);

        $this->postJson('/api/v1/agent/messages', ['message' => 'دكتور جلدية'])
            ->assertOk()
            ->assertJsonPath('source', 'hermes')
            ->assertJsonPath('intent', 'hermes')
            ->assertJsonPath('reply', 'وجدت طبيبة جلدية. [احجز الآن](https://eg.hakeem.com.sa/book/doctors/sara)')
            ->assertJsonPath('actions.0.label', 'احجز الآن')
            ->assertJsonPath('actions.0.url', 'https://eg.hakeem.com.sa/book/doctors/sara');

        Http::assertSent(fn (Request $request) => $request->url() === 'https://hermes.test/v1/chat/completions'
            && $request['messages'][0]['role'] === 'system'
            && collect($request['messages'])->contains(
                fn (array $message) => $message['role'] === 'user' && $message['content'] === 'دكتور جلدية'
            ));
    }

    public function test_forwards_the_visitor_message_to_a_hermes_webhook(): void
    {
        Http::preventStrayRequests();
        $this->configureHermesChat('https://hermes.test/inbound');

        Http::fake([
            'https://hermes.test/inbound' => Http::response([
                'reply' => 'هذه عيادات مطابقة.',
                'actions' => [[
                    'label' => 'عيادة النور',
                    'url' => 'https://eg.hakeem.com.sa/clinics/al-noor',
                ]],
                'results' => [
                    'clinics' => [[
                        'name' => 'عيادة النور',
                        'city' => 'القاهرة',
                        'url' => 'https://eg.hakeem.com.sa/clinics/al-noor',
                    ]],
                ],
            ]),
        ]);

        $this->postJson('/agent/messages', ['message' => 'عيادة في القاهرة'])
            ->assertOk()
            ->assertJsonPath('source', 'hermes')
            ->assertJsonPath('cards.0.title', 'عيادة النور')
            ->assertJsonPath('actions.0.url', 'https://eg.hakeem.com.sa/clinics/al-noor');

        Http::assertSent(fn (Request $request) => $request->url() === 'https://hermes.test/inbound'
            && $request['message'] === 'عيادة في القاهرة'
            && filled($request['conversation_id']));
    }

    public function test_returns_unavailable_when_hermes_chat_fails(): void
    {
        Http::preventStrayRequests();
        $this->configureHermesChat('https://hermes.test/v1/chat/completions');

        Http::fake([
            'https://hermes.test/v1/chat/completions' => Http::response(['error' => 'down'], 503),
        ]);

        $this->postJson('/api/v1/agent/messages', ['message' => 'دكتور أطفال'])
            ->assertOk()
            ->assertJsonPath('source', 'hermes')
            ->assertJsonPath('intent', 'error')
            ->assertJsonPath('reply', 'تعذر الوصول إلى الوكيل الآن. أعد المحاولة بعد لحظات.');
    }

    public function test_does_not_expose_the_customer_token_in_the_chat_response(): void
    {
        Http::preventStrayRequests();
        $this->configureHermesChat('https://hermes.test/v1/chat/completions');
        $this->seedRoles();
        $patient = User::factory()->create();

        Http::fake([
            'https://hermes.test/v1/chat/completions' => Http::response([
                'choices' => [[
                    'message' => ['content' => 'هذه مواعيدك.'],
                ]],
            ]),
        ]);

        $response = $this->actingAs($patient)
            ->postJson('/agent/messages', ['message' => 'مواعيدي'])
            ->assertOk()
            ->assertJsonPath('source', 'hermes');

        $this->assertStringNotContainsString('customer_token', $response->getContent());

        Http::assertSent(fn (Request $request) => filled(data_get($request->data(), 'hakeem.customer_token')));
    }

    public function test_drops_javascript_urls_from_hermes_actions(): void
    {
        Http::preventStrayRequests();
        $this->configureHermesChat('https://hermes.test/inbound');

        Http::fake([
            'https://hermes.test/inbound' => Http::response([
                'reply' => 'افتح هذا الرابط',
                'actions' => [
                    ['label' => 'سيء', 'url' => 'javascript:alert(1)'],
                    ['label' => 'حجز', 'url' => 'https://eg.hakeem.com.sa/book/doctors/sara'],
                ],
            ]),
        ]);

        $this->postJson('/api/v1/agent/messages', ['message' => 'احجز'])
            ->assertOk()
            ->assertJsonPath('actions.0.label', 'حجز')
            ->assertJsonPath('actions.0.url', 'https://eg.hakeem.com.sa/book/doctors/sara')
            ->assertJsonMissing(['url' => 'javascript:alert(1)']);
    }

    private function configureHermesChat(string $url): void
    {
        config([
            'services.hermes.chat_url' => $url,
            'services.hermes.chat_key' => 'chat-secret',
            'services.hermes.chat_model' => 'hermes-agent',
        ]);
    }
}
