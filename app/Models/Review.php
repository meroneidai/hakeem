<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'booking_id', 'patient_id', 'doctor_id', 'clinic_id',
    'overall', 'wait_time', 'staff', 'cleanliness', 'body',
    'is_visible', 'clinic_response', 'responded_at',
])]
class Review extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'overall' => 'integer',
            'wait_time' => 'integer',
            'staff' => 'integer',
            'cleanliness' => 'integer',
            'is_visible' => 'boolean',
            'responded_at' => 'datetime',
        ];
    }

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(User::class, 'patient_id');
    }

    public function doctor(): BelongsTo
    {
        return $this->belongsTo(Doctor::class);
    }

    public function clinic(): BelongsTo
    {
        return $this->belongsTo(Clinic::class);
    }

    public function scopeVisible(Builder $query): Builder
    {
        return $query->where('is_visible', true);
    }
}
