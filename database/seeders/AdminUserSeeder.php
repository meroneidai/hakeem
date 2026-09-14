<?php

namespace Database\Seeders;

use App\Enums\RoleName;
use App\Models\User;
use Illuminate\Database\Seeder;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::updateOrCreate(
            ['phone' => User::normalizePhone(env('HAKEEM_ADMIN_PHONE', '01000000000'))],
            [
                'name' => 'مدير المنصة',
                'email' => 'admin@hakeem.test',
                'password' => env('HAKEEM_ADMIN_PASSWORD', 'password'),
                'preferred_language' => 'ar',
                'is_active' => true,
                'email_verified_at' => now(),
            ],
        );

        $admin->assignRole(RoleName::PlatformAdmin);

        $support = User::updateOrCreate(
            ['phone' => User::normalizePhone('01000000001')],
            [
                'name' => 'موظف الدعم',
                'email' => 'support@hakeem.test',
                'password' => env('HAKEEM_ADMIN_PASSWORD', 'password'),
                'preferred_language' => 'ar',
                'is_active' => true,
                'email_verified_at' => now(),
            ],
        );

        $support->assignRole(RoleName::SupportAgent);
    }
}
