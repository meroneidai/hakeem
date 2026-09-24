<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class AgentPasswordForgotTest extends TestCase
{
    use RefreshDatabase;

    public function test_unknown_identifier_returns_validation_error(): void
    {
        $this->hermes()
            ->postJson('/api/agent/v1/customers/password/forgot', [
                'identifier' => '01099999999',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('identifier')
            ->assertJsonFragment(['الرقم أو الإيميل ده مش مسجّل عندنا.']);
    }

    public function test_known_identifier_still_returns_ok(): void
    {
        Mail::fake();
        User::factory()->create(['email' => 'patient@hakeem.test']);

        $this->hermes()
            ->postJson('/api/agent/v1/customers/password/forgot', [
                'identifier' => 'patient@hakeem.test',
            ])
            ->assertOk()
            ->assertJsonPath('ok', true)
            ->assertJsonPath('channel', 'email');
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
