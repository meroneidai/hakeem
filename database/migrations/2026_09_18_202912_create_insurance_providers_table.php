<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('insurance_providers', function (Blueprint $table) {
            $table->id();
            $table->string('name_ar');
            $table->string('name_en');
            $table->string('slug')->unique();
            $table->string('hotline', 32)->nullable();
            $table->text('notes_ar')->nullable();
            $table->text('notes_en')->nullable();
            $table->string('image_path')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('display_order')->default(0);
            $table->timestamps();

            $table->index(['is_active', 'display_order']);
        });

        Schema::create('clinic_service_insurance_provider', function (Blueprint $table) {
            $table->id();
            $table->foreignId('clinic_service_id')->constrained()->cascadeOnDelete();
            $table->foreignId('insurance_provider_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['clinic_service_id', 'insurance_provider_id'], 'clinic_service_insurance_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('clinic_service_insurance_provider');
        Schema::dropIfExists('insurance_providers');
    }
};
