<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subscription_plans', function (Blueprint $table) {
            $table->id();
            $table->string('name_ar');
            $table->string('name_en');
            $table->string('slug')->unique();
            $table->text('description_ar')->nullable();
            $table->text('description_en')->nullable();
            $table->decimal('monthly_price', 10, 2)->default(0);
            $table->decimal('yearly_price', 10, 2)->default(0);
            $table->unsignedTinyInteger('yearly_discount_pct')->default(0);
            // null = unlimited
            $table->unsignedInteger('booking_cap')->nullable();
            $table->unsignedInteger('doctor_cap')->nullable();
            $table->unsignedInteger('address_cap')->nullable();
            $table->boolean('is_default_free')->default(false);
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('display_order')->default(0);
            $table->timestamps();
        });

        Schema::create('plan_feature_flags', function (Blueprint $table) {
            $table->id();
            $table->foreignId('subscription_plan_id')->constrained()->cascadeOnDelete();
            $table->string('feature_code', 96);
            $table->boolean('is_enabled')->default(false);
            $table->timestamps();

            $table->unique(['subscription_plan_id', 'feature_code'], 'plan_feature_unique');
        });

        Schema::create('discount_codes', function (Blueprint $table) {
            $table->id();
            $table->string('code', 64)->unique();
            $table->string('description')->nullable();
            // percentage | fixed
            $table->string('discount_type', 16)->default('percentage');
            $table->decimal('discount_value', 10, 2);
            // null = applies to every plan
            $table->foreignId('subscription_plan_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamp('valid_from')->nullable();
            $table->timestamp('valid_to')->nullable();
            $table->unsignedInteger('max_uses')->nullable();
            $table->unsignedInteger('times_used')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('discount_codes');
        Schema::dropIfExists('plan_feature_flags');
        Schema::dropIfExists('subscription_plans');
    }
};
