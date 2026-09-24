<?php

namespace App\Console\Commands;

use App\Models\AgentConversation;
use Illuminate\Console\Command;

class PruneAgentConversations extends Command
{
    protected $signature = 'agent:prune-conversations {--days= : Override config retention days}';

    protected $description = 'Delete agent conversations older than the retention window';

    public function handle(): int
    {
        $days = (int) ($this->option('days') ?: config('agent.retention_days', 90));

        if ($days < 1) {
            $this->error('Retention days must be at least 1.');

            return self::FAILURE;
        }

        $cutoff = now()->subDays($days);
        $deleted = 0;

        AgentConversation::query()
            ->where(function ($query) use ($cutoff) {
                $query->where('last_message_at', '<', $cutoff)
                    ->orWhere(function ($inner) use ($cutoff) {
                        $inner->whereNull('last_message_at')->where('started_at', '<', $cutoff);
                    });
            })
            ->orderBy('id')
            ->chunkById(200, function ($rows) use (&$deleted) {
                foreach ($rows as $conversation) {
                    $conversation->delete();
                    $deleted++;
                }
            });

        $this->info("Pruned {$deleted} agent conversation(s) older than {$days} day(s).");

        return self::SUCCESS;
    }
}
