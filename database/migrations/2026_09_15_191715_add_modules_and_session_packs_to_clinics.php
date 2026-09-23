<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('clinics', function (Blueprint $table) {
            $table->json('modules')->nullable()->after('default_payment_mode');
        });

        Schema::table('clinic_services', function (Blueprint $table) {
            $table->unsignedTinyInteger('session_count')->default(1)->after('duration_minutes');
        });

        Schema::table('bookings', function (Blueprint $table) {
            $table->unsignedTinyInteger('session_count')->default(1)->after('is_evaluation');
            $table->foreignId('clinic_service_id')->nullable()->after('service_type_id')->constrained()->nullOnDelete();
            $table->foreignId('promotion_id')->nullable()->after('clinic_service_id')->constrained()->nullOnDelete();
        });

        Schema::table('promotions', function (Blueprint $table) {
            $table->unsignedTinyInteger('session_count')->default(1)->after('offer_price');
        });
    }

    public function down(): void
    {
        Schema::table('promotions', function (Blueprint $table) {
            $table->dropColumn('session_count');
        });

        Schema::table('bookings', function (Blueprint $table) {
            $table->dropConstrainedForeignId('promotion_id');
            $table->dropConstrainedForeignId('clinic_service_id');
            $table->dropColumn('session_count');
        });

        Schema::table('clinic_services', function (Blueprint $table) {
            $table->dropColumn('session_count');
        });

        Schema::table('clinics', function (Blueprint $table) {
            $table->dropColumn('modules');
        });
    }
};
