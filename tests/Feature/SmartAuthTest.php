<?php

namespace Tests\Feature;

use App\Enums\RoleName;
use App\Mail\VerifyEmailMail;
use App\Models\InAppNotification;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class SmartAuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_patient_registers_with_email_and_signs_in_with_the_same_address(): void
    {
        $this->seedRoles();
        Mail::fake();

        $this->post('/register', [
            'name' => 'مريض البريد',
            'identifier' => 'patient@hakeem.test',
            'password' => 'secret-pass-1',
            'password_confirmation' => 'secret-pass-1',
        ])->assertRedirect(route('account.edit'));

        $user = User::query()->where('email', 'patient@hakeem.test')->first();

        $this->assertNotNull($user);
        $this->assertNull($user->phone);
        $this->assertTrue($user->hasRole(RoleName::Patient));
        $this->assertAuthenticatedAs($user);

        Mail::assertSent(VerifyEmailMail::class);

        $this->get('/account')
            ->assertOk()
            ->assertSee('patient@hakeem.test', false);

        $this->post('/logout');

        $this->post('/login', [
            'identifier' => 'patient@hakeem.test',
            'password' => 'secret-pass-1',
        ])->assertRedirect();

        $this->assertAuthenticatedAs($user);
    }

    public function test_referred_signup_credits_the_new_account_and_notifies_the_referrer(): void
    {
        $this->seedRoles();

        $referrer = User::factory()->create();

        $this->get('/register?ref='.$referrer->referral_code)->assertOk();

        $this->post('/register', [
            'name' => 'ضيف الدعوة',
            'phone' => '01033334444',
            'password' => 'password12',
            'password_confirmation' => 'password12',
        ])->assertRedirect();

        $friend = User::query()->where('phone', '201033334444')->first();

        $this->assertSame($referrer->id, $friend->referred_by_user_id);
        $this->assertSame('50.00', $friend->wallet_balance);
        $this->assertSame('0.00', $referrer->fresh()->wallet_balance);
        $this->assertTrue(
            InAppNotification::query()
                ->where('user_id', $referrer->id)
                ->where('event', 'referral_joined')
                ->exists()
        );
    }

    public function test_signed_in_patient_sees_account_links_on_web_and_completes_the_profile_banner(): void
    {
        $this->seedRoles();
        $provider = $this->seedListableProvider();
        $patient = User::factory()->create([
            'phone_verified_at' => null,
            'city_id' => null,
        ]);
        $patient->assignRole(RoleName::Patient);

        $this->actingAs($patient)
            ->get('/')
            ->assertOk()
            ->assertSee(route('account.edit'), false)
            ->assertSee(__('account.profile'))
            ->assertSee(__('account.incomplete'));

        $this->actingAs($patient)
            ->put('/account', [
                'name' => $patient->name,
                'preferred_language' => 'ar',
                'city_id' => $provider['city']->id,
                'phone' => $patient->phone,
                'email' => $patient->email,
            ])
            ->assertRedirect();

        $this->actingAs($patient->fresh())
            ->post('/account/phone/code')
            ->assertRedirect();

        $this->actingAs($patient->fresh())
            ->post('/account/phone/verify', ['code' => '123456'])
            ->assertRedirect()
            ->assertSessionHas('profile_complete');

        $this->assertTrue($patient->fresh()->isPhoneVerified());
        $this->assertSame($provider['city']->id, $patient->fresh()->city_id);

        $this->actingAs($patient->fresh())
            ->get('/account')
            ->assertOk()
            ->assertSee(__('account.complete'))
            ->assertDontSee(__('account.incomplete'));
    }

    public function test_api_accepts_email_login_and_exposes_profile_fields(): void
    {
        $this->seedRoles();
        $user = User::factory()->emailOnly()->create(['password' => 'password']);
        $user->assignRole(RoleName::Patient);

        $this->postJson('/api/v1/auth/login', [
            'identifier' => $user->email,
            'password' => 'password',
            'device_name' => 'pixel',
        ])
            ->assertOk()
            ->assertJsonPath('user.email', $user->email)
            ->assertJsonPath('user.profile_complete', false)
            ->assertJsonStructure(['token', 'user' => ['wallet_balance', 'referral_url']]);
    }
}
