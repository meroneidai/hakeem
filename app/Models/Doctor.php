<?php

namespace App\Models;

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
    'credentials', 'profile_photo_path', 'years_of_experience', 'is_active',
])]
class Doctor extends Model
{
    use HasFactory, HasTranslatedAttributes;

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
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

    public function getBioAttribute(): ?string
    {
        return $this->translated('bio');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }
}
