<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Validation\ValidationException;

#[Fillable([
    'user_id', 'clinic_id', 'clocked_in_at', 'clocked_out_at',
    'duration_minutes', 'source', 'notes',
])]
class StaffAttendance extends Model
{
    protected function casts(): array
    {
        return [
            'clocked_in_at' => 'datetime',
            'clocked_out_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function clinic(): BelongsTo
    {
        return $this->belongsTo(Clinic::class);
    }

    public function isOpen(): bool
    {
        return $this->clocked_out_at === null;
    }

    public function clockOut(): self
    {
        if (! $this->isOpen()) {
            throw ValidationException::withMessages([
                'shift' => __('admin.attendance.already_closed'),
            ]);
        }

        $ended = now();
        $this->forceFill([
            'clocked_out_at' => $ended,
            'duration_minutes' => max(0, (int) $this->clocked_in_at->diffInMinutes($ended)),
        ])->save();

        return $this;
    }

    public function hours(): float
    {
        $minutes = $this->duration_minutes
            ?? ($this->isOpen() ? (int) $this->clocked_in_at->diffInMinutes(now()) : 0);

        return round($minutes / 60, 2);
    }

    public function scopeOpen(Builder $query): Builder
    {
        return $query->whereNull('clocked_out_at');
    }
}
