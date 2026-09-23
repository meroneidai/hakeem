<?php

namespace App\Models;

use App\Enums\CareDocumentType;
use Database\Factories\CareDocumentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'patient_id', 'clinic_id', 'doctor_id', 'issued_by_user_id',
    'booking_id', 'lab_order_id', 'type', 'title', 'body', 'payload',
    'verification_code', 'issued_at', 'valid_from', 'valid_until',
])]
class CareDocument extends Model
{
    /** @use HasFactory<CareDocumentFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'type' => CareDocumentType::class,
            'payload' => 'array',
            'issued_at' => 'datetime',
            'valid_from' => 'date',
            'valid_until' => 'date',
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

    public function issuedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'issued_by_user_id');
    }

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    public function labOrder(): BelongsTo
    {
        return $this->belongsTo(LabOrder::class);
    }

    /**
     * @return list<array{name: string, dose?: string, frequency?: string, duration?: string, notes?: string}>
     */
    public function medications(): array
    {
        $rows = $this->payload['medications'] ?? [];

        return is_array($rows) ? array_values($rows) : [];
    }

    /**
     * @return list<array{name: string, value?: string, unit?: string, flag?: string, note?: string}>
     */
    public function resultRows(): array
    {
        $rows = $this->payload['results'] ?? [];

        return is_array($rows) ? array_values($rows) : [];
    }

    public function downloadName(): string
    {
        return 'hakeem-'.$this->type->value.'-'.$this->verification_code.'.pdf';
    }

    public function isOwnedBy(?User $user): bool
    {
        return $user !== null && (int) $this->patient_id === (int) $user->id;
    }

    public function canBeViewedBy(?User $user): bool
    {
        if ($this->isOwnedBy($user)) {
            return true;
        }

        return $user !== null && $user->belongsToClinic($this->clinic_id);
    }
}
