<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('name', 64)->unique();
            $table->string('label_ar');
            $table->string('label_en');
            $table->text('description')->nullable();
            $table->timestamps();
        });

        Schema::create('role_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('role_id')->constrained()->cascadeOnDelete();
            // Scopes doctor/reception/clinic_owner assignments to a clinic (clinics land in Phase 2).
            $table->unsignedBigInteger('clinic_id')->nullable()->index();
            $table->timestamps();

            $table->unique(['user_id', 'role_id', 'clinic_id'], 'role_user_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('role_user');
        Schema::dropIfExists('roles');
    }
};
