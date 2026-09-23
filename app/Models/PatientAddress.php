<?php

namespace App\Models;

use Database\Factories\PatientAddressFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'user_id', 'label', 'line', 'city_id', 'latitude', 'longitude', 'is_default',
])]
class PatientAddress extends Model
{
    /** @use HasFactory<PatientAddressFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'is_default' => 'boolean',
            'latitude' => 'decimal:7',
            'longitude' => 'decimal:7',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class);
    }

    public function displayName(): string
    {
        return $this->label !== '' ? $this->label.' — '.$this->line : $this->line;
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
}
