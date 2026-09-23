<?php

namespace App\Models;

use App\Models\Concerns\HasRatings;
use App\Models\Concerns\HasTranslatedAttributes;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'user_id', 'name_ar', 'name_en', 'slug', 'specialty_id', 'bio_ar', 'bio_en',
    'credentials', 'profile_photo_path', 'years_of_experience', 'gender',
    'consultation_fee', 'is_active',
])]
class Doctor extends Model
{
    use HasFactory, HasRatings, HasTranslatedAttributes;

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'consultation_fee' => 'decimal:2',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function specialty(): BelongsTo
    {
        return $this->belongsTo(Specialty::class);
    }

    public function clinics(): BelongsToMany
    {
        return $this->belongsToMany(Clinic::class)->withTimestamps();
    }

    public function availability(): HasMany
    {
        return $this->hasMany(DoctorAddressAvailability::class);
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }

    public function getBioAttribute(): ?string
    {
        return $this->translated('bio');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Doctors who may appear in patient-facing directories and booking.
     */
    public function scopeListable(Builder $query): Builder
    {
        return $query->active()->whereHas('clinics', fn (Builder $clinics) => $clinics->listable());
    }

    /**
     * Doctors at listable clinics that currently offer this service.
     */
    public function scopeOffering(Builder $query, ServiceType $serviceType): Builder
    {
        return $query->whereHas(
            'clinics',
            fn (Builder $clinics) => $clinics->listable()->whereHas(
                'services',
                fn (Builder $services) => $services->active()->where('service_type_id', $serviceType->id)
            )
        );
    }
}
