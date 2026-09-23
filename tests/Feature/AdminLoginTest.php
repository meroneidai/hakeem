<?php

namespace Tests\Feature;

use App\Enums\RoleName;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminLoginTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_login_screen_renders(): void
    {
        $this->get('/admin/login')
            ->assertOk()
            ->assertSee(__('auth.admin_login_title'));
    }

    public function test_platform_admin_signs_in_with_email_and_password(): void
    {
        $this->seedRoles();

        $admin = User::factory()->create([
            'email' => 'admin@hakeem.test',
            'phone' => '201000000000',
        ]);
        $admin->assignRole(RoleName::PlatformAdmin);

        $this->post('/admin/login', [
            'email' => 'admin@hakeem.test',
            'password' => 'password',
        ])->assertRedirect('/admin');

        $this->assertAuthenticatedAs($admin);
    }

    public function test_platform_admin_signs_in_with_phone_on_admin_login(): void
    {
        $this->seedRoles();

        $admin = User::factory()->create([
            'email' => 'admin@hakeem.test',
            'phone' => '201000000000',
        ]);
        $admin->assignRole(RoleName::PlatformAdmin);

        $this->post('/admin/login', [
            'email' => '01000000000',
            'password' => 'password',
        ])->assertRedirect('/admin');

        $this->assertAuthenticatedAs($admin);
    }

    public function test_patient_cannot_use_admin_login(): void
    {
        $this->seedRoles();

        $patient = User::factory()->create(['email' => 'patient@hakeem.test']);
        $patient->assignRole(RoleName::Patient);

        $this->post('/admin/login', [
            'email' => 'patient@hakeem.test',
            'password' => 'password',
        ])->assertSessionHasErrors('identifier');

        $this->assertGuest();
    }

    public function test_internal_staff_cannot_use_phone_login(): void
    {
        $this->seedRoles();

        $admin = User::factory()->create(['phone' => '201000000000']);
        $admin->assignRole(RoleName::PlatformAdmin);

        $this->post('/login', [
            'phone' => '01000000000',
            'password' => 'password',
        ])
            ->assertRedirect('/admin/login')
            ->assertSessionHas('status');

        $this->assertGuest();
    }

    public function test_social_redirect_flashes_when_provider_is_not_configured(): void
    {
        $this->from('/login')
            ->get('/auth/google/redirect')
            ->assertRedirect('/login')
            ->assertSessionHas('error');
    }
}
