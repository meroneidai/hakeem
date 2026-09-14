<?php

namespace Tests\Feature;

use App\Enums\RoleName;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_screen_renders(): void
    {
        $this->get('/login')->assertOk()->assertSee('01xxxxxxxxx');
    }

    public function test_patient_registers_with_phone_name_and_password_only(): void
    {
        $this->seedRoles();

        $this->post('/register', [
            'name' => 'أحمد علي',
            'phone' => '01012345678',
            'password' => 'secret-pass-1',
            'password_confirmation' => 'secret-pass-1',
        ])->assertRedirect('/');

        $user = User::firstWhere('name', 'أحمد علي');

        $this->assertNotNull($user);
        $this->assertSame('201012345678', $user->phone, 'Phone should be normalised to 20-prefixed form.');
        $this->assertNull($user->email);
        $this->assertTrue($user->hasRole(RoleName::Patient));
        $this->assertAuthenticatedAs($user);
    }

    /**
     * Patients enter their number in several shapes; all must resolve to one account.
     */
    public function test_login_accepts_local_and_international_phone_formats(): void
    {
        $user = User::factory()->create(['phone' => '201112223334']);

        foreach (['01112223334', '+201112223334', '00201112223334', '201112223334'] as $format) {
            $this->post('/login', ['phone' => $format, 'password' => 'password'])
                ->assertRedirect();

            $this->assertAuthenticatedAs($user);
            $this->post('/logout');
        }
    }

    public function test_login_fails_with_wrong_password(): void
    {
        User::factory()->create(['phone' => '201112223334']);

        $this->post('/login', ['phone' => '01112223334', 'password' => 'wrong-password'])
            ->assertSessionHasErrors('phone');

        $this->assertGuest();
    }

    public function test_deactivated_account_cannot_log_in(): void
    {
        User::factory()->create(['phone' => '201112223334', 'is_active' => false]);

        $this->post('/login', ['phone' => '01112223334', 'password' => 'password'])
            ->assertSessionHasErrors('phone');

        $this->assertGuest();
    }

    public function test_locale_switch_persists_to_session_and_profile(): void
    {
        $user = User::factory()->create(['preferred_language' => 'ar']);

        $this->actingAs($user)
            ->from('/')
            ->post('/locale', ['locale' => 'en'])
            ->assertRedirect('/');

        $this->assertSame('en', $user->fresh()->preferred_language);
        $this->assertSame('en', session('locale'));
    }
}
