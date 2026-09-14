<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            // payments, notifications, seo, general
            $table->string('group', 64)->default('general');
            $table->json('value')->nullable();
            // Credentials are cast through Laravel's encrypter before hitting this column.
            $table->boolean('is_encrypted')->default(false);
            $table->timestamps();

            $table->index('group');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('settings');
    }
};
