<?php

namespace App\Models;

use App\Enums\BookingStatus;
use App\Enums\PaymentMode;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Fillable([
    'patient_id', 'clinic_id', 'doctor_id', 'clinic_address_id', 'service_type_id',
    'clinic_service_id', 'promotion_id', 'scheduled_at', 'status', 'is_evaluation',
    'session_count', 'payment_mode', 'payment_status', 'patient_home_address',
    'notes', 'evaluation_notes',
])]
class Booking extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'scheduled_at' => 'datetime',
            'is_evaluation' => 'boolean',
            'status' => BookingStatus::class,
            'payment_mode' => PaymentMode::class,
        ];
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(User::class, 'patient_id');
    }

    public function clinic(): BelongsTo
    {
        return $this->belongsTo(Clinic::class);
    }

    public function doctor(): BelongsTo
    {
        return $this->belongsTo(Doctor::class);
    }

    public function address(): BelongsTo
    {
        return $this->belongsTo(ClinicAddress::class, 'clinic_address_id');
    }

    public function serviceType(): BelongsTo
    {
        return $this->belongsTo(ServiceType::class);
    }

    public function clinicService(): BelongsTo
    {
        return $this->belongsTo(ClinicService::class);
    }

    public function promotion(): BelongsTo
    {
        return $this->belongsTo(Promotion::class);
    }

    public function statusHistory(): HasMany
    {
        return $this->hasMany(BookingStatusHistory::class);
    }

    public function review(): HasOne
    {
        return $this->hasOne(Review::class);
    }

    public function canBeReviewedBy(?User $user): bool
    {
        return $user !== null
            && (int) $this->patient_id === (int) $user->id
            && $this->status === BookingStatus::Completed
            && ! $this->review()->exists();
    }

    public function isPaid(): bool
    {
        return $this->payment_status === 'paid';
    }

    public function scopeOnDate(Builder $query, string $date): Builder
    {
        return $query->whereDate('scheduled_at', $date);
    }
}
