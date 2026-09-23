<?php

namespace App\Models;

use App\Models\Concerns\HasTranslatedAttributes;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Exists;

#[Fillable([
    'name_ar', 'name_en', 'slug', 'hotline', 'notes_ar', 'notes_en',
    'image_path', 'is_active', 'display_order',
])]
class InsuranceProvider extends Model
{
    use HasFactory, HasTranslatedAttributes;

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function clinicServices(): BelongsToMany
    {
        return $this->belongsToMany(ClinicService::class)->withTimestamps();
    }

    public function patients(): HasMany
    {
        return $this->hasMany(User::class);
    }

    /**
     * @return Collection<int, self>
     */
    public static function selectable(?int $currentId = null): Collection
    {
        return static::query()
            ->where(function (Builder $query) use ($currentId): void {
                $query->where('is_active', true);

                if ($currentId !== null) {
                    $query->orWhereKey($currentId);
                }
            })
            ->ordered()
            ->get();
    }

    /**
     * Validation rule that only accepts companies currently offered to patients.
     */
    public static function activeIdRule(?int $currentId = null): Exists
    {
        return Rule::exists((new self)->getTable(), 'id')->where(function ($query) use ($currentId): void {
            $query->where('is_active', true);

            if ($currentId !== null) {
                $query->orWhere('id', $currentId);
            }
        });
    }

    public function getNotesAttribute(): ?string
    {
        return $this->translated('notes');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('display_order')->orderBy('name_ar');
    }
}
