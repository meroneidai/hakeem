<?php

namespace Database\Seeders;

use App\Enums\RoleName;
use App\Models\Role;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        foreach (RoleName::cases() as $role) {
            Role::updateOrCreate(
                ['name' => $role->value],
                [
                    'label_ar' => $role->labelAr(),
                    'label_en' => $role->labelEn(),
                ],
            );
        }
    }
}
