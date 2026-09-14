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
            SubscriptionPlanSeeder::class,
            PlatformSettingsSeeder::class,
            AdminUserSeeder::class,
        ]);

        if (app()->environment('local')) {
            $this->call([
                DemoSupportSeeder::class,
                DemoClinicSeeder::class,
            ]);
        }
    }
}
