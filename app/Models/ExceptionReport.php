<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'user_id', 'exception_class', 'message', 'file', 'line', 'url', 'method',
    'status', 'trace', 'occurrences', 'last_seen_at', 'resolved_at',
])]
class ExceptionReport extends Model
{
    protected function casts(): array
    {
        return [
            'last_seen_at' => 'datetime',
            'resolved_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isResolved(): bool
    {
        return $this->resolved_at !== null;
    }
}
