<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('clinic_subscriptions', function (Blueprint $table) {
            $table->string('invoice_number', 32)->nullable()->unique();
            $table->string('payment_status', 16)->default('pending');
            $table->string('payment_method', 32)->nullable();
            $table->string('payment_reference', 120)->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('due_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamp('invoice_sent_at')->nullable();
            $table->timestamp('reminder_sent_at')->nullable();

            $table->index('payment_status');
        });

        $year = now()->year;
        $sequence = 1;

        DB::table('clinic_subscriptions')
            ->orderBy('id')
            ->get(['id', 'amount', 'current_period_end'])
            ->each(function (object $row) use (&$sequence, $year): void {
                $amount = (float) $row->amount;

                DB::table('clinic_subscriptions')->where('id', $row->id)->update([
                    'invoice_number' => sprintf('INV-%d-%04d', $year, $sequence),
                    'payment_status' => $amount <= 0 ? 'paid' : 'pending',
                    'paid_at' => $amount <= 0 ? now() : null,
                    'due_at' => $row->current_period_end,
                ]);

                $sequence++;
            });
    }

    public function down(): void
    {
        Schema::table('clinic_subscriptions', function (Blueprint $table) {
            $table->dropIndex(['payment_status']);
            $table->dropColumn([
                'invoice_number',
                'payment_status',
                'payment_method',
                'payment_reference',
                'paid_at',
                'due_at',
                'notes',
                'invoice_sent_at',
                'reminder_sent_at',
            ]);
        });
    }
};
