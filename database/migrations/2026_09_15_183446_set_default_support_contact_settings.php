<?php

use App\Support\Settings;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('settings')) {
            return;
        }

        $settings = app(Settings::class);

        foreach ([
            'general.support_whatsapp' => '01000000000',
            'general.support_phone' => '01000000001',
            'general.support_email' => 'support@hakeem.test',
        ] as $key => $default) {
            if (! filled($settings->get($key))) {
                $settings->set($key, $default, 'general');
            }
        }
    }

    public function down(): void
    {
        // Defaults stay; an admin can clear them from the dashboard.
    }
};
