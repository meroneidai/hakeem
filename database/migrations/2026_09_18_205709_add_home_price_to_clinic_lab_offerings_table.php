<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('clinic_lab_offerings', function (Blueprint $table) {
            $table->decimal('home_price', 10, 2)->nullable()->after('promo_price');
        });
    }

    public function down(): void
    {
        Schema::table('clinic_lab_offerings', function (Blueprint $table) {
            $table->dropColumn('home_price');
        });
    }
};
