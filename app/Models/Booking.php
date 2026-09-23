<?php

namespace App\Models;

use App\Enums\BookingStatus;
use App\Enums\PaymentMode;
use App\Support\ServiceDuration;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;

#[Fillable([
    'patient_id', 'clinic_id', 'doctor_id', 'clinic_address_id', 'service_type_id',
    'clinic_service_id', 'promotion_id', 'scheduled_at', 'status', 'is_evaluation',
    'session_count', 'payment_mode', 'payment_status', 'patient_home_address',
    'video_room_token', 'notes', 'evaluation_notes',
])]
class Booking extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'scheduled_at' => 'datetime',
            'reminder_sent_at' => 'datetime',
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

    public function careDocuments(): HasMany
    {
        return $this->hasMany(CareDocument::class);
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

    public function durationMinutes(): int
    {
        return $this->serviceType?->durationMinutes($this->clinicService?->duration_minutes)
            ?? ServiceDuration::resolve($this->clinicService?->duration_minutes);
    }

    public function localScheduledAt(): ?Carbon
    {
        return $this->scheduled_at?->copy()->timezone(config('hakeem.display_timezone'));
    }

    public function isVideoVisit(): bool
    {
        return filled($this->video_room_token);
    }

    public function videoEmbedUrl(): ?string
    {
        if (! $this->isVideoVisit()) {
            return null;
        }

        return 'https://meet.jit.si/hakeem-'.$this->video_room_token;
    }

    public function canAccessVideo(?User $user): bool
    {
        if (! $this->isVideoVisit() || $user === null) {
            return false;
        }

        return (int) $this->patient_id === (int) $user->id
            || $user->belongsToClinic($this->clinic_id);
    }

    public function canJoinVideo(?User $user): bool
    {
        return $this->canAccessVideo($user)
            && in_array($this->status, [BookingStatus::Confirmed, BookingStatus::InProgress], true);
    }

    public function scopeOnDate(Builder $query, string $date): Builder
    {
        return $query->whereDate('scheduled_at', $date);
    }
}
