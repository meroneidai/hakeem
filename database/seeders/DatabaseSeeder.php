<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            RoleSeeder::class,
            GeographySeeder::class,
            SpecialtySeeder::class,
            ServiceTypeSeeder::class,
            InsuranceProviderSeeder::class,
            SubscriptionPlanSeeder::class,
            PlatformSettingsSeeder::class,
            AdminUserSeeder::class,
            LabCatalogSeeder::class,
            MedicalArticleSeeder::class,
            SitePageSeeder::class,
        ]);

        // Demo catalog: local by default, or first-release when HAKEEM_SEED_DEMO=true.
        if ($this->shouldSeedDemoCatalog()) {
            $this->call([
                DemoSupportSeeder::class,
                DemoClinicSeeder::class,
                DemoOfferSeeder::class,
                MarketplaceCatalogSeeder::class,
            ]);
        }

        // Fill missing clinic↔service links so /services/{slug} is not empty after reference-only seeds.
        $this->call(EnsureClinicServiceOfferingsSeeder::class);
    }

    private function shouldSeedDemoCatalog(): bool
    {
        if (app()->environment('local')) {
            return true;
        }

        return filter_var((string) env('HAKEEM_SEED_DEMO', false), FILTER_VALIDATE_BOOLEAN);
    }
}
