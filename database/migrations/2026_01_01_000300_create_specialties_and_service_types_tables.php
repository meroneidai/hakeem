<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('specialties', function (Blueprint $table) {
            $table->id();
            $table->string('name_ar');
            $table->string('name_en');
            $table->string('slug')->unique();
            // general, dental, cosmetic, beauty, physical_therapy, psychiatry, ...
            $table->string('category', 64)->default('general');
            $table->text('description_ar')->nullable();
            $table->text('description_en')->nullable();
            $table->string('icon', 64)->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('is_featured')->default(false);
            $table->unsignedInteger('display_order')->default(0);
            $table->timestamps();

            $table->index(['category', 'is_active']);
        });

        Schema::create('service_types', function (Blueprint $table) {
            $table->id();
            // clinic_appointment, home_visit, video_consultation, lab_test, home_lab_test, psychiatric_consultation
            $table->string('code', 64)->unique();
            $table->string('name_ar');
            $table->string('name_en');
            $table->string('slug')->unique();
            $table->text('description_ar')->nullable();
            $table->text('description_en')->nullable();
            $table->boolean('requires_clinic_address')->default(true);
            $table->boolean('requires_patient_address')->default(false);
            $table->boolean('requires_time_slot')->default(true);
            $table->boolean('is_online')->default(false);
            $table->boolean('is_sensitive')->default(false);
            $table->string('icon', 64)->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('display_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('service_types');
        Schema::dropIfExists('specialties');
    }
};
