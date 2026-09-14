<?php

namespace Tests\Feature\Admin;

use App\Enums\RoleName;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_to_login(): void
    {
        $this->get('/admin')->assertRedirect('/login');
    }

    public function test_patients_cannot_reach_the_admin_area(): void
    {
        $this->actingAsRole(RoleName::Patient);

        $this->get('/admin')->assertForbidden();
    }

    public function test_platform_admin_reaches_the_dashboard(): void
    {
        $this->actingAsRole(RoleName::PlatformAdmin);

        $this->get('/admin')->assertOk();
    }

    /**
     * Support agents get the ticket inbox but none of the reference-data modules.
     */
    public function test_support_agent_permissions_are_scoped(): void
    {
        $this->actingAsRole(RoleName::SupportAgent);

        $this->get('/admin')->assertOk();
        $this->get('/admin/support')->assertOk();

        $this->get('/admin/governorates')->assertForbidden();
        $this->get('/admin/plans')->assertForbidden();
        $this->get('/admin/staff')->assertForbidden();
        $this->get('/admin/payments')->assertForbidden();
    }

    public function test_deactivated_staff_are_blocked_even_when_authenticated(): void
    {
        $this->seedRoles();

        $user = User::factory()->create(['is_active' => false]);
        $user->assignRole(RoleName::PlatformAdmin);

        $this->actingAs($user)->get('/admin')->assertForbidden();
    }
}
