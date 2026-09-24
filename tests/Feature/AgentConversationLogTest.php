<?php

namespace Tests\Feature;

use App\Enums\RoleName;
use App\Models\AgentConversation;
use App\Services\SiteAgent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AgentConversationLogTest extends TestCase
{
    use RefreshDatabase;

    public function test_hermes_replies_are_persisted_for_admin_review(): void
    {
        config([
            'services.hermes.chat_url' => 'http://hermes.test/v1/chat/completions',
            'services.hermes.chat_key' => 'chat-key',
            'services.hermes.chat_model' => 'hakeem-agent',
        ]);

        Http::fake([
            'hermes.test/*' => Http::response([
                'choices' => [[
                    'message' => [
                        'content' => 'مرحباً، تقدر تحجز من هنا [[form:booking]] [[٩:٣٠ ص]]',
                    ],
                ]],
            ]),
        ]);

        $reply = app(SiteAgent::class)->reply('عايز أحجز', null);

        $this->assertSame('hermes', $reply['source']);
        $this->assertNotEmpty($reply['forms']);
        $this->assertSame('booking', $reply['forms'][0]['form']);
        $this->assertStringNotContainsString('[[form:booking]]', $reply['reply']);

        $conversation = AgentConversation::query()->find($reply['conversation_id']);
        $this->assertNotNull($conversation);
        $this->assertSame(2, $conversation->messages()->count());
        $this->assertDatabaseHas('agent_messages', [
            'conversation_id' => $conversation->id,
            'role' => 'assistant',
        ]);
    }

    public function test_admin_can_open_conversation_log(): void
    {
        $this->actingAsRole(RoleName::PlatformAdmin);

        $conversation = AgentConversation::query()->create([
            'channel' => 'web',
            'locale' => 'ar',
            'visitor_name' => 'زائر تجريبي',
            'started_at' => now(),
            'last_message_at' => now(),
            'message_count' => 1,
        ]);
        $conversation->messages()->create([
            'role' => 'user',
            'body' => 'مرحبا',
        ]);

        $this->get('/admin/agent-conversations')
            ->assertOk()
            ->assertSee('زائر تجريبي');

        $this->get('/admin/agent-conversations/'.$conversation->id)
            ->assertOk()
            ->assertSee('مرحبا');
    }

    public function test_prune_command_removes_old_conversations(): void
    {
        $old = AgentConversation::query()->create([
            'channel' => 'web',
            'locale' => 'ar',
            'started_at' => now()->subDays(120),
            'last_message_at' => now()->subDays(120),
            'message_count' => 0,
        ]);
        $fresh = AgentConversation::query()->create([
            'channel' => 'web',
            'locale' => 'ar',
            'started_at' => now(),
            'last_message_at' => now(),
            'message_count' => 0,
        ]);

        $this->artisan('agent:prune-conversations', ['--days' => 90])->assertSuccessful();

        $this->assertDatabaseMissing('agent_conversations', ['id' => $old->id]);
        $this->assertDatabaseHas('agent_conversations', ['id' => $fresh->id]);
    }
}
