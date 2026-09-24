<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'conversation_id', 'role', 'body', 'actions', 'results', 'forms', 'latency_ms',
])]
class AgentMessage extends Model
{
    protected function casts(): array
    {
        return [
            'actions' => 'array',
            'results' => 'array',
            'forms' => 'array',
            'latency_ms' => 'integer',
        ];
    }

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(AgentConversation::class, 'conversation_id');
    }
}
