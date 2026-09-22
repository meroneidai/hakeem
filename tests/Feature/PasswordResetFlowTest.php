<?php

namespace Tests\Feature;

use App\Mail\ResetPasswordMail;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class PasswordResetFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_email_reset_link_updates_the_password(): void
    {
        Mail::fake();
        $user = User::factory()->create(['email' => 'reset@hakeem.test']);

        $this->get('/password/forgot')->assertOk();

        $this->post('/password/forgot', [
            'identifier' => 'reset@hakeem.test',
        ])->assertRedirect();

        $url = null;
        Mail::assertSent(ResetPasswordMail::class, function (ResetPasswordMail $mail) use (&$url, $user): bool {
            $url = $mail->url;

            return $mail->hasTo($user->email);
        });

        $this->assertNotNull($url);

        $query = [];
        parse_str((string) parse_url($url, PHP_URL_QUERY), $query);

        $this->get('/password/reset?'.http_build_query($query))->assertOk();

        $this->post('/password/reset', [
            'identifier' => $query['identifier'],
            'token' => $query['token'],
            'password' => 'new-secret-9',
            'password_confirmation' => 'new-secret-9',
        ])->assertRedirect(route('account.edit'));

        $this->assertAuthenticatedAs($user->fresh());

        $this->post('/logout');

        $this->post('/login', [
            'identifier' => 'reset@hakeem.test',
            'password' => 'new-secret-9',
        ])->assertRedirect();
    }

    public function test_phone_reset_uses_the_sms_code(): void
    {
        $user = User::factory()->withoutEmail()->create(['phone' => '201099988877']);

        $this->post('/password/forgot', [
            'identifier' => '01099988877',
        ])->assertRedirect();

        $this->post('/password/reset', [
            'identifier' => '01099988877',
            'token' => '123456',
            'password' => 'new-secret-9',
            'password_confirmation' => 'new-secret-9',
        ])->assertRedirect(route('account.edit'));

        $this->assertAuthenticatedAs($user->fresh());
    }
}
