<?php

namespace App\Models;

use App\Enums\ServiceTypeCode;
use App\Models\Concerns\HasTranslatedAttributes;
use App\Support\ServiceDuration;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'code', 'name_ar', 'name_en', 'slug', 'description_ar', 'description_en',
    'requires_clinic_address', 'requires_patient_address', 'requires_time_slot',
    'is_online', 'is_sensitive', 'allowed_payment_modes', 'default_duration_minutes',
    'icon', 'image_path', 'is_active', 'display_order',
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
            'allowed_payment_modes' => 'array',
            'default_duration_minutes' => 'integer',
        ];
    }

    public function durationMinutes(?int $clinicOverride = null): int
    {
        return ServiceDuration::resolve($clinicOverride, $this->default_duration_minutes);
    }

    public function enum(): ?ServiceTypeCode
    {
        return ServiceTypeCode::tryFrom($this->code);
    }

    public function isDoctorLed(): bool
    {
        return $this->enum()?->isDoctorLed() ?? false;
    }

    public function uiIcon(): string
    {
        return match ($this->code) {
            ServiceTypeCode::ClinicAppointment->value => 'building',
            ServiceTypeCode::HomeVisit->value => 'home',
            ServiceTypeCode::VideoConsultation->value => 'video',
            ServiceTypeCode::LabTest->value => 'beaker',
            ServiceTypeCode::HomeLabTest->value => 'truck',
            ServiceTypeCode::PsychiatricConsultation->value => 'chat',
            ServiceTypeCode::PhysicalTherapy->value => 'stethoscope',
            ServiceTypeCode::OccupationalTherapy->value => 'stethoscope',
            default => 'stethoscope',
        };
    }

    public function clinicServices(): HasMany
    {
        return $this->hasMany(ClinicService::class);
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
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
