<?php

namespace App\Models;

use App\Enums\CollectionMode;
use App\Enums\LabOrderStatus;
use App\Enums\PaymentMode;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'reference', 'patient_id', 'clinic_id', 'clinic_address_id', 'patient_address_id',
    'collection_mode', 'scheduled_at', 'status', 'payment_mode', 'payment_status', 'total',
    'patient_home_address', 'latitude', 'longitude', 'notes',
])]
class LabOrder extends Model
{
    protected function casts(): array
    {
        return [
            'scheduled_at' => 'datetime',
            'status' => LabOrderStatus::class,
            'payment_mode' => PaymentMode::class,
            'collection_mode' => CollectionMode::class,
            'total' => 'decimal:2',
            'latitude' => 'decimal:7',
            'longitude' => 'decimal:7',
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

    public function address(): BelongsTo
    {
        return $this->belongsTo(ClinicAddress::class, 'clinic_address_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(LabOrderItem::class);
    }

    public function patientAddress(): BelongsTo
    {
        return $this->belongsTo(PatientAddress::class);
    }

    public function careDocuments(): HasMany
    {
        return $this->hasMany(CareDocument::class);
    }

    public function isPaid(): bool
    {
        return $this->payment_status === 'paid';
    }

    public function scopeOnDate(Builder $query, string $date): Builder
    {
        return $query->whereDate('scheduled_at', $date);
    }

    public function hasMapPin(): bool
    {
        return $this->latitude !== null && $this->longitude !== null;
    }

    public function mapsUrl(): ?string
    {
        if (! $this->hasMapPin()) {
            return null;
        }

        return 'https://www.google.com/maps/dir/?api=1&destination='.$this->latitude.','.$this->longitude;
    }

    public function mapsEmbedUrl(): ?string
    {
        if (! $this->hasMapPin()) {
            return null;
        }

        return 'https://maps.google.com/maps?q='.$this->latitude.','.$this->longitude.'&z=16&output=embed';
    }
}
