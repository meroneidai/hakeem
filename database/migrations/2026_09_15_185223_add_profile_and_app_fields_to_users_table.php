<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->date('date_of_birth')->nullable()->after('name');
            $table->string('gender', 16)->nullable()->after('date_of_birth');
            $table->foreignId('city_id')->nullable()->after('gender')->constrained()->nullOnDelete();
            $table->timestamp('app_installed_at')->nullable()->after('last_login_at');
            $table->timestamp('last_app_seen_at')->nullable()->after('app_installed_at');
            $table->boolean('notify_email')->default(true)->after('preferred_language');
            $table->boolean('notify_sms')->default(true)->after('notify_email');
            $table->boolean('notify_push')->default(true)->after('notify_sms');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('city_id');
            $table->dropColumn([
                'date_of_birth',
                'gender',
                'app_installed_at',
                'last_app_seen_at',
                'notify_email',
                'notify_sms',
                'notify_push',
            ]);
        });
    }
};
