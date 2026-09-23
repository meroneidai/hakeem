<?php

namespace Database\Seeders;

use App\Models\Clinic;
use App\Models\ClinicService;
use App\Models\ServiceType;
use Illuminate\Database\Seeder;

/**
 * Attaches every active service type to listable clinics so /services pages
 * are not empty after a production seed that only created reference data.
 */
class EnsureClinicServiceOfferingsSeeder extends Seeder
{
    public function run(): void
    {
        $serviceTypes = ServiceType::query()->active()->ordered()->get();

        if ($serviceTypes->isEmpty()) {
            return;
        }

        Clinic::query()
            ->listable()
            ->with('doctors')
            ->orderBy('id')
            ->each(function (Clinic $clinic) use ($serviceTypes): void {
                $specialtyId = $clinic->doctors->first()?->specialty_id;

                foreach ($serviceTypes as $serviceType) {
                    ClinicService::query()->firstOrCreate(
                        [
                            'clinic_id' => $clinic->id,
                            'service_type_id' => $serviceType->id,
                        ],
                        [
                            'specialty_id' => $specialtyId,
                            'price' => 250,
                            'duration_minutes' => $serviceType->durationMinutes(),
                            'is_active' => true,
                            'requires_evaluation_first' => $serviceType->enum()?->isRehab() ?? false,
                        ],
                    );
                }
            });
    }
}
