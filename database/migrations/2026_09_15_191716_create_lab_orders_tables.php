<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lab_orders', function (Blueprint $table) {
            $table->id();
            $table->string('reference', 24)->unique();
            $table->foreignId('patient_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('clinic_id')->constrained()->cascadeOnDelete();
            $table->foreignId('clinic_address_id')->nullable()->constrained()->nullOnDelete();
            $table->string('collection_mode', 16)->default('clinic');
            $table->timestamp('scheduled_at');
            $table->string('status', 32)->default('pending');
            $table->string('payment_mode', 32)->default('at_clinic');
            $table->string('payment_status', 32)->default('unpaid');
            $table->decimal('total', 10, 2)->default(0);
            $table->string('patient_home_address')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['clinic_id', 'status', 'scheduled_at']);
            $table->index(['patient_id', 'scheduled_at']);
        });

        Schema::create('lab_order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lab_order_id')->constrained()->cascadeOnDelete();
            $table->string('item_type', 16);
            $table->foreignId('lab_test_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('lab_package_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedSmallInteger('qty')->default(1);
            $table->decimal('unit_price', 10, 2);
            $table->decimal('line_total', 10, 2);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lab_order_items');
        Schema::dropIfExists('lab_orders');
    }
};
