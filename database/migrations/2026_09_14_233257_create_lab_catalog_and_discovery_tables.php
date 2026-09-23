<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lab_tests', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->string('name_ar');
            $table->string('name_en');
            $table->string('category', 32);
            $table->text('description_ar')->nullable();
            $table->text('description_en')->nullable();
            $table->text('measures_ar')->nullable();
            $table->text('measures_en')->nullable();
            $table->text('preparation_ar')->nullable();
            $table->text('preparation_en')->nullable();
            $table->text('contains_ar')->nullable();
            $table->text('contains_en')->nullable();
            $table->string('sample_type', 32)->default('blood');
            $table->unsignedSmallInteger('fasting_hours')->nullable();
            $table->unsignedSmallInteger('turnaround_hours')->nullable();
            $table->decimal('suggested_price', 10, 2)->default(0);
            $table->unsignedSmallInteger('display_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['category', 'is_active']);
        });

        Schema::create('lab_packages', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->string('name_ar');
            $table->string('name_en');
            $table->text('description_ar')->nullable();
            $table->text('description_en')->nullable();
            $table->text('includes_ar')->nullable();
            $table->text('includes_en')->nullable();
            $table->text('conditions_ar')->nullable();
            $table->text('conditions_en')->nullable();
            $table->text('preparation_ar')->nullable();
            $table->text('preparation_en')->nullable();
            $table->decimal('original_price', 10, 2)->default(0);
            $table->decimal('package_price', 10, 2)->default(0);
            $table->unsignedSmallInteger('display_order')->default(0);
            $table->boolean('is_featured')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('lab_package_test', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lab_package_id')->constrained()->cascadeOnDelete();
            $table->foreignId('lab_test_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['lab_package_id', 'lab_test_id']);
        });

        Schema::create('clinic_lab_offerings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('clinic_id')->constrained()->cascadeOnDelete();
            $table->string('item_type', 16);
            $table->foreignId('lab_test_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('lab_package_id')->nullable()->constrained()->cascadeOnDelete();
            $table->decimal('price', 10, 2)->default(0);
            $table->decimal('promo_price', 10, 2)->nullable();
            $table->boolean('allows_home_collection')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['clinic_id', 'item_type', 'is_active']);
        });

        Schema::table('promotions', function (Blueprint $table) {
            $table->string('category', 32)->default('lab')->after('slug');
            $table->text('includes_ar')->nullable()->after('description_en');
            $table->text('includes_en')->nullable()->after('includes_ar');
            $table->text('conditions_ar')->nullable()->after('includes_en');
            $table->text('conditions_en')->nullable()->after('conditions_ar');
            $table->decimal('original_price', 10, 2)->nullable()->after('discount_details');
            $table->decimal('offer_price', 10, 2)->nullable()->after('original_price');
            $table->unsignedInteger('views_count')->default(0)->after('is_featured');
            $table->foreign('clinic_id')->references('id')->on('clinics')->nullOnDelete();
        });

        Schema::table('doctors', function (Blueprint $table) {
            $table->string('gender', 16)->nullable()->after('years_of_experience');
            $table->decimal('consultation_fee', 10, 2)->nullable()->after('gender');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->string('country_code', 2)->default('EG')->after('phone');
            $table->string('auth_provider', 32)->default('phone')->after('email');
            $table->string('provider_id')->nullable()->after('auth_provider');
            $table->string('firebase_uid')->nullable()->after('provider_id');

            $table->unique(['auth_provider', 'provider_id']);
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['auth_provider', 'provider_id']);
            $table->dropColumn(['country_code', 'auth_provider', 'provider_id', 'firebase_uid']);
        });

        Schema::table('doctors', function (Blueprint $table) {
            $table->dropColumn(['gender', 'consultation_fee']);
        });

        Schema::table('promotions', function (Blueprint $table) {
            $table->dropForeign(['clinic_id']);
            $table->dropColumn([
                'category', 'includes_ar', 'includes_en', 'conditions_ar', 'conditions_en',
                'original_price', 'offer_price', 'views_count',
            ]);
        });

        Schema::dropIfExists('clinic_lab_offerings');
        Schema::dropIfExists('lab_package_test');
        Schema::dropIfExists('lab_packages');
        Schema::dropIfExists('lab_tests');
    }
};
