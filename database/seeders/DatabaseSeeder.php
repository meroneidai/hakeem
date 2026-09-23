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

        if (app()->environment('local')) {
            $this->call([
                DemoSupportSeeder::class,
                DemoClinicSeeder::class,
                DemoOfferSeeder::class,
                MarketplaceCatalogSeeder::class,
            ]);
        }
    }
}
