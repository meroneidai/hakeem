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
    'reference', 'patient_id', 'clinic_id', 'clinic_address_id', 'collection_mode',
    'scheduled_at', 'status', 'payment_mode', 'payment_status', 'total',
    'patient_home_address', 'notes',
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

    public function isPaid(): bool
    {
        return $this->payment_status === 'paid';
    }

    public function scopeOnDate(Builder $query, string $date): Builder
    {
        return $query->whereDate('scheduled_at', $date);
    }
}
