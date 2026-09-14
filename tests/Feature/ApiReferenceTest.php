<?php

namespace Tests\Feature;

use App\Enums\RoleName;
use App\Models\User;
use Database\Seeders\GeographySeeder;
use Database\Seeders\RoleSeeder;
use Database\Seeders\ServiceTypeSeeder;
use Database\Seeders\SpecialtySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApiReferenceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([RoleSeeder::class, GeographySeeder::class, SpecialtySeeder::class, ServiceTypeSeeder::class]);
    }

    public function test_geography_endpoint_returns_bilingual_names(): void
    {
        $this->getJson('/api/v1/reference/geography')
            ->assertOk()
            ->assertJsonStructure([['id', 'slug', 'name' => ['ar', 'en'], 'cities' => [['id', 'slug', 'name']]]]);
    }

    public function test_service_types_endpoint_exposes_the_booking_rules(): void
    {
        $response = $this->getJson('/api/v1/reference/service-types')->assertOk();

        $homeVisit = collect($response->json())->firstWhere('code', 'home_visit');

        $this->assertTrue($homeVisit['requires']['patient_address']);
        $this->assertFalse($homeVisit['requires']['clinic_address']);
    }

    public function test_me_endpoint_requires_a_token(): void
    {
        $this->getJson('/api/v1/me')->assertUnauthorized();
    }

    public function test_me_endpoint_returns_the_authenticated_user(): void
    {
        $user = User::factory()->create(['name' => 'منى إبراهيم']);
        $user->assignRole(RoleName::Patient);

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/me')
            ->assertOk()
            ->assertJsonPath('name', 'منى إبراهيم')
            ->assertJsonPath('roles.0', 'patient');
    }
}
