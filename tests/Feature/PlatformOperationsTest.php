<?php

namespace Tests\Feature;

use App\Enums\RoleName;
use App\Models\Clinic;
use App\Models\ClinicAddress;
use App\Models\ExceptionReport;
use App\Models\StaffAttendance;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PlatformOperationsTest extends TestCase
{
    use RefreshDatabase;

    public function test_support_agent_can_open_users_and_errors_but_not_billing(): void
    {
        $this->actingAsRole(RoleName::SupportAgent);

        $this->get('/admin/users')->assertOk()->assertSee(__('admin.users.heading'));
        $this->get('/admin/errors')->assertOk()->assertSee(__('admin.errors.heading'));
        $this->get('/admin/billing')->assertForbidden();
        $this->get('/admin/staff')->assertForbidden();
    }

    public function test_admin_marks_a_patient_phone_as_verified(): void
    {
        $this->actingAsRole(RoleName::PlatformAdmin);
        $this->seedRoles();

        $patient = User::factory()->create(['name' => 'مريض التحقق', 'phone_verified_at' => null]);
        $patient->assignRole(RoleName::Patient);

        $this->get('/admin/users')->assertOk()->assertSee('مريض التحقق');

        $this->from('/admin/users/'.$patient->id)
            ->put('/admin/users/'.$patient->id, ['action' => 'verify_phone'])
            ->assertRedirect();

        $this->assertNotNull($patient->fresh()->phone_verified_at);
    }

    public function test_staff_clock_in_then_clock_out_records_hours(): void
    {
        $this->freezeTime();
        $admin = $this->actingAsRole(RoleName::PlatformAdmin);

        $this->post('/admin/attendance/clock-in')->assertRedirect();

        $this->assertDatabaseHas('staff_attendances', [
            'user_id' => $admin->id,
            'clocked_out_at' => null,
        ]);

        $this->from('/admin/attendance')
            ->post('/admin/attendance/clock-in')
            ->assertSessionHasErrors('shift');

        $this->travel(90)->minutes();
        $this->post('/admin/attendance/clock-out')->assertRedirect();

        $shift = StaffAttendance::query()->where('user_id', $admin->id)->first();
        $this->assertNotNull($shift->clocked_out_at);
        $this->assertSame(90, $shift->duration_minutes);
        $this->assertSame(1.5, $shift->hours());
    }

    public function test_admin_resolves_an_error_report(): void
    {
        $this->actingAsRole(RoleName::PlatformAdmin);

        $report = ExceptionReport::query()->create([
            'exception_class' => 'RuntimeException',
            'message' => 'queue worker failed',
            'file' => app_path('Services/BookingManager.php'),
            'line' => 10,
            'occurrences' => 2,
            'last_seen_at' => now(),
        ]);

        $this->get('/admin/errors/'.$report->id)
            ->assertOk()
            ->assertSee('queue worker failed');

        $this->put('/admin/errors/'.$report->id, ['action' => 'resolve'])->assertRedirect();

        $this->assertNotNull($report->fresh()->resolved_at);
    }

    public function test_admin_overview_api_returns_a_planned_agent_snapshot(): void
    {
        $admin = $this->actingAsRole(RoleName::PlatformAdmin);

        $this->actingAs($admin, 'sanctum')
            ->getJson('/api/v1/admin/overview')
            ->assertOk()
            ->assertJsonPath('agent.status', 'planned')
            ->assertJsonStructure(['insights' => ['bookings', 'top_clinics', 'top_doctors', 'top_services', 'top_labs']]);
    }

    public function test_patient_receives_403_from_the_admin_overview_api(): void
    {
        $this->seedRoles();
        $patient = User::factory()->create();
        $patient->assignRole(RoleName::Patient);

        $this->actingAs($patient, 'sanctum')
            ->getJson('/api/v1/admin/overview')
            ->assertForbidden();
    }

    public function test_clinics_directory_filters_by_city(): void
    {
        $provider = $this->seedListableProvider();

        $otherCity = $provider['governorate']->cities()->create([
            'name_ar' => 'مدينة أخرى',
            'name_en' => 'Other City',
            'slug' => 'other-city-test',
            'is_active' => true,
        ]);

        $otherClinic = Clinic::factory()->verified()->create([
            'subscription_plan_id' => $provider['plan']->id,
            'name_ar' => 'عيادة المدينة الأخرى',
            'name_en' => 'Other City Clinic',
        ]);
        ClinicAddress::factory()->create([
            'clinic_id' => $otherClinic->id,
            'city_id' => $otherCity->id,
            'latitude' => 30.1,
            'longitude' => 31.3,
        ]);

        $this->get('/clinics?city='.$provider['city']->slug)
            ->assertOk()
            ->assertSee($provider['clinic']->name_ar)
            ->assertDontSee('عيادة المدينة الأخرى');
    }
}
