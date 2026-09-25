<?php

namespace Tests\Feature;

use App\Services\EdgeTextToSpeech;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AgentSpeechTest extends TestCase
{
    use RefreshDatabase;

    public function test_speech_endpoint_requires_text(): void
    {
        $this->postJson('/agent/speech', [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('text');
    }

    public function test_speech_endpoint_returns_audio_when_tts_succeeds(): void
    {
        $path = storage_path('app/private/tts/test-speech.mp3');
        if (! is_dir(dirname($path))) {
            mkdir(dirname($path), 0755, true);
        }
        file_put_contents($path, str_repeat('0', 128));

        $this->mock(EdgeTextToSpeech::class, function ($mock) use ($path) {
            $mock->shouldReceive('synthesize')
                ->once()
                ->with('أهلاً بك')
                ->andReturn($path);
        });

        $this->post('/agent/speech', ['text' => 'أهلاً بك'], [
            'Accept' => 'audio/mpeg',
        ])
            ->assertOk()
            ->assertHeader('content-type', 'audio/mpeg');
    }

    public function test_speech_endpoint_returns_502_when_tts_unavailable(): void
    {
        $this->mock(EdgeTextToSpeech::class, function ($mock) {
            $mock->shouldReceive('synthesize')->once()->andReturn(null);
        });

        $this->postJson('/agent/speech', ['text' => 'مرحبا'])
            ->assertStatus(502)
            ->assertJson(['error' => 'tts_unavailable']);
    }

    public function test_agent_messages_accept_voice_mode_and_locale(): void
    {
        $this->postJson('/agent/messages', [
            'message' => 'مساعدة',
            'conversation_id' => '11111111-1111-4111-8111-111111111111',
            'locale' => 'ar',
            'mode' => 'voice',
        ])
            ->assertOk()
            ->assertJsonPath('conversation_id', '11111111-1111-4111-8111-111111111111');
    }
}
