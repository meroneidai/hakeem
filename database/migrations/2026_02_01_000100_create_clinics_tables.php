<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('clinics', function (Blueprint $table) {
            $table->id();
            $table->foreignId('owner_user_id')->constrained('users')->cascadeOnDelete();
            $table->string('name_ar');
            $table->string('name_en');
            $table->string('slug')->unique();
            $table->text('description_ar')->nullable();
            $table->text('description_en')->nullable();
            $table->string('logo_path')->nullable();
            $table->string('email');
            $table->string('phone', 32);
            // A solo clinic presents as a single doctor profile but keeps clinic mechanics.
            $table->boolean('is_single_doctor')->default(true);
            $table->foreignId('subscription_plan_id')->nullable()->constrained()->nullOnDelete();
            $table->string('verification_status', 16)->default('pending');
            $table->text('rejection_reason')->nullable();
            $table->timestamp('verified_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['verification_status', 'is_active']);
        });

        Schema::create('doctors', function (Blueprint $table) {
            $table->id();
            // Null until the doctor claims their profile with a login of their own.
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name_ar');
            $table->string('name_en');
            $table->string('slug')->unique();
            $table->foreignId('specialty_id')->nullable()->constrained()->nullOnDelete();
            $table->text('bio_ar')->nullable();
            $table->text('bio_en')->nullable();
            $table->string('credentials')->nullable();
            $table->string('profile_photo_path')->nullable();
            $table->unsignedSmallInteger('years_of_experience')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('clinic_doctor', function (Blueprint $table) {
            $table->id();
            $table->foreignId('clinic_id')->constrained()->cascadeOnDelete();
            $table->foreignId('doctor_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['clinic_id', 'doctor_id']);
        });

        Schema::create('clinic_addresses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('clinic_id')->constrained()->cascadeOnDelete();
            $table->foreignId('city_id')->constrained()->restrictOnDelete();
            $table->string('label_ar')->nullable();
            $table->string('label_en')->nullable();
            $table->string('address_line');
            $table->string('landmark')->nullable();
            $table->string('phone', 32)->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->boolean('is_primary')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['clinic_id', 'is_active']);
        });

        Schema::create('address_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('clinic_address_id')->constrained()->cascadeOnDelete();
            // ISO-8601 weekday: 1 = Monday … 7 = Sunday.
            $table->unsignedTinyInteger('day_of_week');
            $table->time('open_time');
            $table->time('close_time');
            $table->time('break_start')->nullable();
            $table->time('break_end')->nullable();
            $table->unsignedSmallInteger('slot_duration_minutes')->default(30);
            $table->boolean('is_closed')->default(false);
            $table->timestamps();

            $table->unique(['clinic_address_id', 'day_of_week'], 'address_schedule_unique');
        });

        Schema::create('doctor_address_availability', function (Blueprint $table) {
            $table->id();
            $table->foreignId('doctor_id')->constrained()->cascadeOnDelete();
            $table->foreignId('clinic_address_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('day_of_week');
            $table->time('open_time');
            $table->time('close_time');
            $table->timestamps();

            $table->unique(
                ['doctor_id', 'clinic_address_id', 'day_of_week', 'open_time'],
                'doctor_availability_unique'
            );
        });

        Schema::create('clinic_services', function (Blueprint $table) {
            $table->id();
            $table->foreignId('clinic_id')->constrained()->cascadeOnDelete();
            $table->foreignId('service_type_id')->constrained()->cascadeOnDelete();
            $table->foreignId('specialty_id')->nullable()->constrained()->nullOnDelete();
            $table->decimal('price', 10, 2)->default(0);
            $table->decimal('promo_price', 10, 2)->nullable();
            $table->unsignedSmallInteger('duration_minutes')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['clinic_id', 'service_type_id', 'specialty_id'], 'clinic_service_unique');
        });

        Schema::create('clinic_subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('clinic_id')->constrained()->cascadeOnDelete();
            $table->foreignId('subscription_plan_id')->constrained()->restrictOnDelete();
            $table->foreignId('discount_code_id')->nullable()->constrained()->nullOnDelete();
            $table->string('billing_cycle', 16)->default('monthly');
            $table->string('status', 16)->default('active');
            $table->decimal('amount', 10, 2)->default(0);
            $table->timestamp('current_period_start')->nullable();
            $table->timestamp('current_period_end')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamps();

            $table->index(['clinic_id', 'status']);
        });

        // role_user.clinic_id was created before clinics existed; wire up the FK now.
        Schema::table('role_user', function (Blueprint $table) {
            $table->foreign('clinic_id')->references('id')->on('clinics')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('role_user', function (Blueprint $table) {
            $table->dropForeign(['clinic_id']);
        });

        Schema::dropIfExists('clinic_subscriptions');
        Schema::dropIfExists('clinic_services');
        Schema::dropIfExists('doctor_address_availability');
        Schema::dropIfExists('address_schedules');
        Schema::dropIfExists('clinic_addresses');
        Schema::dropIfExists('clinic_doctor');
        Schema::dropIfExists('doctors');
        Schema::dropIfExists('clinics');
    }
};
