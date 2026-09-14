<?php

namespace App\Models;

use App\Enums\RoleName;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

#[Fillable(['name', 'label_ar', 'label_en', 'description'])]
class Role extends Model
{
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class)->withPivot('clinic_id')->withTimestamps();
    }

    /**
     * `name` is the machine key here, so the localised text lives on `label_*`.
     */
    public function getLabelAttribute(): string
    {
        $locale = app()->getLocale() === 'en' ? 'en' : 'ar';

        return $this->{"label_{$locale}"} ?: $this->label_ar ?: $this->name;
    }

    public function enum(): ?RoleName
    {
        return RoleName::tryFrom($this->name);
    }
}
