<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('promotions', function (Blueprint $table) {
            $table->id();
            // null = platform-wide campaign. FK added in Phase 2 with the clinics table.
            $table->unsignedBigInteger('clinic_id')->nullable()->index();
            $table->string('title_ar');
            $table->string('title_en');
            $table->string('slug')->unique();
            $table->text('description_ar')->nullable();
            $table->text('description_en')->nullable();
            // percentage | fixed | custom
            $table->string('discount_type', 16)->default('percentage');
            $table->decimal('discount_value', 10, 2)->nullable();
            $table->string('discount_details')->nullable();
            $table->string('banner_image_path')->nullable();
            $table->foreignId('specialty_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('service_type_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamp('starts_at');
            $table->timestamp('ends_at');
            $table->boolean('is_featured')->default(false);
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['is_active', 'starts_at', 'ends_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('promotions');
    }
};
