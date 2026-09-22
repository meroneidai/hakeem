<?php

namespace App\Models;

use App\Enums\BookingStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['booking_id', 'status', 'changed_by_user_id', 'changed_at'])]
class BookingStatusHistory extends Model
{
    public $timestamps = false;

    protected $table = 'booking_status_history';

    protected function casts(): array
    {
        return [
            'status' => BookingStatus::class,
            'changed_at' => 'datetime',
        ];
    }

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    public function changedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by_user_id');
    }
}
