<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['support_ticket_id', 'sender_user_id', 'body', 'is_internal_note', 'sent_at'])]
class SupportMessage extends Model
{
    protected function casts(): array
    {
        return [
            'is_internal_note' => 'boolean',
            'sent_at' => 'datetime',
        ];
    }

    public function ticket(): BelongsTo
    {
        return $this->belongsTo(SupportTicket::class, 'support_ticket_id');
    }

    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_user_id');
    }
}
