<?php

namespace App\Models;

use App\Enums\ServiceTypeCode;
use App\Models\Concerns\HasTranslatedAttributes;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'code', 'name_ar', 'name_en', 'slug', 'description_ar', 'description_en',
    'requires_clinic_address', 'requires_patient_address', 'requires_time_slot',
    'is_online', 'is_sensitive', 'icon', 'is_active', 'display_order',
])]
class ServiceType extends Model
{
    use HasTranslatedAttributes;

    protected function casts(): array
    {
        return [
            'requires_clinic_address' => 'boolean',
            'requires_patient_address' => 'boolean',
            'requires_time_slot' => 'boolean',
            'is_online' => 'boolean',
            'is_sensitive' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function enum(): ?ServiceTypeCode
    {
        return ServiceTypeCode::tryFrom($this->code);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('display_order')->orderBy('id');
    }
}
