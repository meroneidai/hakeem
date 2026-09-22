<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('specialties', function (Blueprint $table) {
            $table->string('image_path')->nullable()->after('icon');
        });

        Schema::table('service_types', function (Blueprint $table) {
            $table->string('image_path')->nullable()->after('icon');
        });

        Schema::table('lab_tests', function (Blueprint $table) {
            $table->string('image_path')->nullable()->after('suggested_price');
        });

        Schema::table('lab_packages', function (Blueprint $table) {
            $table->string('image_path')->nullable()->after('package_price');
        });
    }

    public function down(): void
    {
        Schema::table('specialties', function (Blueprint $table) {
            $table->dropColumn('image_path');
        });

        Schema::table('service_types', function (Blueprint $table) {
            $table->dropColumn('image_path');
        });

        Schema::table('lab_tests', function (Blueprint $table) {
            $table->dropColumn('image_path');
        });

        Schema::table('lab_packages', function (Blueprint $table) {
            $table->dropColumn('image_path');
        });
    }
};
