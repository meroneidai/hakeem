<?php

namespace Tests\Feature;

use App\Enums\RoleName;
use App\Models\InsuranceProvider;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_screen_renders(): void
    {
        $this->get('/login')->assertOk()->assertSee('01xxxxxxxxx')->assertSee(__('auth.admin_login'));
    }

    public function test_patient_registers_with_phone_name_and_password_only(): void
    {
        $this->seedRoles();

        $this->post('/register', [
            'name' => 'أحمد علي',
            'phone' => '01012345678',
            'password' => 'secret-pass-1',
            'password_confirmation' => 'secret-pass-1',
        ])->assertRedirect(route('account.edit'));

        $user = User::firstWhere('name', 'أحمد علي');

        $this->assertNotNull($user);
        $this->assertSame('201012345678', $user->phone, 'Phone should be normalised to 20-prefixed form.');
        $this->assertNull($user->email);
        $this->assertTrue($user->hasRole(RoleName::Patient));
        $this->assertAuthenticatedAs($user);
        $this->assertNull($user->insurance_provider_id);
    }

    public function test_register_form_lists_active_insurance_companies_and_hides_inactive(): void
    {
        $active = InsuranceProvider::factory()->create([
            'name_ar' => 'شركة مصر للتأمين',
            'name_en' => 'Misr Insurance',
        ]);
        $inactive = InsuranceProvider::factory()->inactive()->create([
            'name_ar' => 'شركة تأمين موقوفة',
            'name_en' => 'Retired Insurer',
        ]);

        $this->get('/register')
            ->assertOk()
            ->assertSee(__('account.insurance'))
            ->assertSee($active->name_ar)
            ->assertDontSee($inactive->name_ar);
    }

    public function test_patient_registers_with_an_insurance_company(): void
    {
        $this->seedRoles();
        $provider = InsuranceProvider::factory()->create();

        $this->post('/register', [
            'name' => 'سارة محمد',
            'phone' => '01012345679',
            'password' => 'secret-pass-1',
            'password_confirmation' => 'secret-pass-1',
            'insurance_provider_id' => $provider->id,
        ])->assertRedirect(route('account.edit'));

        $user = User::firstWhere('name', 'سارة محمد');

        $this->assertSame($provider->id, $user->insurance_provider_id);
    }

    public function test_registration_rejects_an_inactive_insurance_company(): void
    {
        $this->seedRoles();
        $retired = InsuranceProvider::factory()->inactive()->create();

        $this->from('/register')
            ->post('/register', [
                'name' => 'مريض مرفوض',
                'phone' => '01012345680',
                'password' => 'secret-pass-1',
                'password_confirmation' => 'secret-pass-1',
                'insurance_provider_id' => $retired->id,
            ])
            ->assertRedirect('/register')
            ->assertSessionHasErrors('insurance_provider_id');

        $this->assertDatabaseMissing('users', ['name' => 'مريض مرفوض']);
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
            ->assertSessionHasErrors('identifier');

        $this->assertGuest();
    }

    public function test_deactivated_account_cannot_log_in(): void
    {
        User::factory()->create(['phone' => '201112223334', 'is_active' => false]);

        $this->post('/login', ['phone' => '01112223334', 'password' => 'password'])
            ->assertSessionHasErrors('identifier');

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
