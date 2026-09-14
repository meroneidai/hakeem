<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('support_tickets', function (Blueprint $table) {
            $table->id();
            $table->string('reference', 32)->unique();
            $table->foreignId('opened_by_user_id')->constrained('users')->cascadeOnDelete();
            // chat | whatsapp | email | phone | admin
            $table->string('channel', 32)->default('chat');
            $table->string('subject');
            // booking | payment | record_access | technical | other
            $table->string('category', 48)->default('other');
            // open | in_progress | waiting_on_customer | resolved | closed
            $table->string('status', 32)->default('open');
            // low | normal | high | urgent
            $table->string('priority', 16)->default('normal');
            $table->foreignId('assigned_agent_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('first_response_at')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'priority']);
        });

        Schema::create('support_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('support_ticket_id')->constrained()->cascadeOnDelete();
            $table->foreignId('sender_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('body');
            // Internal notes stay invisible to the ticket opener.
            $table->boolean('is_internal_note')->default(false);
            $table->timestamp('sent_at');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('support_messages');
        Schema::dropIfExists('support_tickets');
    }
};
