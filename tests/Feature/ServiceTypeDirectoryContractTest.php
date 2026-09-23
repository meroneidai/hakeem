<?php

namespace Tests\Feature;

use App\Enums\ServiceTypeCode;
use App\Models\ClinicService;
use App\Models\ServiceType;
use Database\Seeders\EnsureClinicServiceOfferingsSeeder;
use Database\Seeders\ServiceTypeSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ServiceTypeDirectoryContractTest extends TestCase
{
    use RefreshDatabase;

    public function test_service_type_exposes_directory_helpers_used_by_public_pages(): void
    {
        $this->seed(ServiceTypeSeeder::class);

        $homeVisit = ServiceType::query()->where('code', ServiceTypeCode::HomeVisit->value)->firstOrFail();

        $this->assertTrue($homeVisit->isDoctorLed());
        $this->assertSame('home', $homeVisit->uiIcon());
        $this->assertSame(45, $homeVisit->durationMinutes());

        $clinicAppointment = ServiceType::query()->where('code', ServiceTypeCode::ClinicAppointment->value)->firstOrFail();

        $this->assertFalse($clinicAppointment->isDoctorLed());
        $this->assertSame('building', $clinicAppointment->uiIcon());
    }

    public function test_home_visit_directory_renders_without_500_after_seed(): void
    {
        $provider = $this->seedListableProvider();
        $this->seed(ServiceTypeSeeder::class);

        $homeVisit = ServiceType::query()->where('code', ServiceTypeCode::HomeVisit->value)->firstOrFail();

        ClinicService::query()->create([
            'clinic_id' => $provider['clinic']->id,
            'service_type_id' => $homeVisit->id,
            'specialty_id' => $provider['specialty']->id,
            'price' => 400,
            'is_active' => true,
        ]);

        $this->get('/services/home-visit')
            ->assertOk()
            ->assertSee($provider['doctor']->name_ar);
    }

    public function test_ensure_clinic_service_offerings_seeder_attaches_missing_active_types(): void
    {
        $provider = $this->seedListableProvider();
        $this->seed(ServiceTypeSeeder::class);

        $this->assertSame(0, ClinicService::query()->where('clinic_id', $provider['clinic']->id)->count());

        $this->seed(EnsureClinicServiceOfferingsSeeder::class);

        $activeTypeCount = ServiceType::query()->active()->count();

        $this->assertSame(
            $activeTypeCount,
            ClinicService::query()->where('clinic_id', $provider['clinic']->id)->count(),
        );
    }
}
