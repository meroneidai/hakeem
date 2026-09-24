<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'id', 'patient_id', 'channel', 'locale', 'visitor_name', 'ip',
    'satisfaction', 'started_at', 'last_message_at', 'admin_seen_at', 'message_count',
])]
class AgentConversation extends Model
{
    use HasUuids;

    protected function casts(): array
    {
        return [
            'started_at' => 'datetime',
            'last_message_at' => 'datetime',
            'admin_seen_at' => 'datetime',
            'message_count' => 'integer',
            'satisfaction' => 'integer',
        ];
    }

    public function markSeenByAdmin(): void
    {
        $this->forceFill(['admin_seen_at' => now()])->save();
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(User::class, 'patient_id');
    }

    public function messages(): HasMany
    {
        return $this->hasMany(AgentMessage::class, 'conversation_id');
    }

    public function latestMessage(): HasMany
    {
        return $this->messages()->latest('id');
    }
}
