<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bookings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('clinic_id')->constrained()->cascadeOnDelete();
            $table->foreignId('doctor_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('clinic_address_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('service_type_id')->constrained()->restrictOnDelete();
            $table->timestamp('scheduled_at');
            $table->string('status', 32)->default('pending');
            $table->boolean('is_evaluation')->default(false);
            $table->string('payment_mode', 32)->default('at_clinic');
            $table->string('payment_status', 32)->default('unpaid');
            $table->string('patient_home_address')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['clinic_id', 'status', 'scheduled_at']);
            $table->index(['patient_id', 'scheduled_at']);
            $table->index(['doctor_id', 'scheduled_at']);
        });

        Schema::create('booking_status_history', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id')->constrained()->cascadeOnDelete();
            $table->string('status', 32);
            $table->foreignId('changed_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('changed_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('booking_status_history');
        Schema::dropIfExists('bookings');
    }
};
