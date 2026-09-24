<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('agent_conversations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('patient_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('channel', 16)->default('web');
            $table->string('locale', 8)->default('ar');
            $table->string('visitor_name')->nullable();
            $table->string('ip', 45)->nullable();
            $table->unsignedTinyInteger('satisfaction')->nullable();
            $table->timestamp('started_at');
            $table->timestamp('last_message_at')->nullable();
            $table->unsignedInteger('message_count')->default(0);
            $table->timestamps();

            $table->index('last_message_at');
            $table->index(['channel', 'last_message_at']);
        });

        Schema::create('agent_messages', function (Blueprint $table) {
            $table->id();
            $table->uuid('conversation_id');
            $table->string('role', 16);
            $table->text('body');
            $table->json('actions')->nullable();
            $table->json('results')->nullable();
            $table->json('forms')->nullable();
            $table->unsignedInteger('latency_ms')->nullable();
            $table->timestamps();

            $table->foreign('conversation_id')
                ->references('id')
                ->on('agent_conversations')
                ->cascadeOnDelete();
            $table->index(['conversation_id', 'id']);
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('agent_messages');
        Schema::dropIfExists('agent_conversations');
    }
};
