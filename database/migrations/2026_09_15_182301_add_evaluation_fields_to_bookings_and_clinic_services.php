<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('clinic_services', function (Blueprint $table) {
            $table->boolean('requires_evaluation_first')->default(false)->after('is_active');
        });

        Schema::table('bookings', function (Blueprint $table) {
            $table->text('evaluation_notes')->nullable()->after('notes');
        });
    }

    public function down(): void
    {
        Schema::table('clinic_services', function (Blueprint $table) {
            $table->dropColumn('requires_evaluation_first');
        });

        Schema::table('bookings', function (Blueprint $table) {
            $table->dropColumn('evaluation_notes');
        });
    }
};
