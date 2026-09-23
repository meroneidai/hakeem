<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('service_types', function (Blueprint $table) {
            $table->json('allowed_payment_modes')->nullable()->after('is_sensitive');
            $table->unsignedSmallInteger('default_duration_minutes')->default(30)->after('allowed_payment_modes');
        });

        DB::table('service_types')->where('is_online', true)->update([
            'allowed_payment_modes' => json_encode(['online']),
            'default_duration_minutes' => 15,
        ]);

        DB::table('service_types')->where('code', 'psychiatric_consultation')->update([
            'allowed_payment_modes' => json_encode(['online']),
            'default_duration_minutes' => 45,
        ]);

        DB::table('service_types')->whereIn('code', ['physical_therapy', 'occupational_therapy', 'home_visit'])->update([
            'default_duration_minutes' => 45,
        ]);

        DB::table('service_types')->whereIn('code', ['lab_test', 'home_lab_test'])->update([
            'default_duration_minutes' => 15,
        ]);
    }

    public function down(): void
    {
        Schema::table('service_types', function (Blueprint $table) {
            $table->dropColumn(['allowed_payment_modes', 'default_duration_minutes']);
        });
    }
};
