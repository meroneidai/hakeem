<?php

namespace App\Models;

use App\Enums\WalletLedgerType;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

#[Fillable([
    'user_id', 'type', 'amount', 'source_type', 'source_id', 'note', 'created_by_user_id',
])]
class WalletLedger extends Model
{
    protected function casts(): array
    {
        return [
            'type' => WalletLedgerType::class,
            'amount' => 'decimal:2',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function source(): MorphTo
    {
        return $this->morphTo();
    }
}
