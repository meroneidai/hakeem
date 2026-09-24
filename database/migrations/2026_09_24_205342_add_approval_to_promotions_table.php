<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('promotions', function (Blueprint $table) {
            $table->string('approval_status', 20)->default('approved')->after('is_active');
            $table->foreignId('reviewed_by_user_id')->nullable()->after('approval_status')->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable()->after('reviewed_by_user_id');
            $table->string('rejection_reason')->nullable()->after('reviewed_at');
            $table->index('approval_status');
        });
    }

    public function down(): void
    {
        Schema::table('promotions', function (Blueprint $table) {
            $table->dropConstrainedForeignId('reviewed_by_user_id');
            $table->dropColumn(['approval_status', 'reviewed_at', 'rejection_reason']);
        });
    }
};
