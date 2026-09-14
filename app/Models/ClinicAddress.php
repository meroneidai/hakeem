<?php

namespace App\Models;

use App\Enums\DayOfWeek;
use App\Models\Concerns\HasTranslatedAttributes;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

#[Fillable([
    'clinic_id', 'city_id', 'label_ar', 'label_en', 'address_line', 'landmark',
    'phone', 'latitude', 'longitude', 'is_primary', 'is_active',
])]
class ClinicAddress extends Model
{
    use HasFactory, HasTranslatedAttributes;

    protected function casts(): array
    {
        return [
            'is_primary' => 'boolean',
            'is_active' => 'boolean',
            'latitude' => 'decimal:7',
            'longitude' => 'decimal:7',
        ];
    }

    public function clinic(): BelongsTo
    {
        return $this->belongsTo(Clinic::class);
    }

    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class);
    }

    public function schedules(): HasMany
    {
        return $this->hasMany(AddressSchedule::class);
    }

    public function doctorAvailability(): HasMany
    {
        return $this->hasMany(DoctorAddressAvailability::class);
    }

    public function getLabelAttribute(): ?string
    {
        return $this->translated('label');
    }

    /**
     * Branch name for display, falling back to the city when unlabelled.
     */
    public function displayName(): string
    {
        return $this->label ?: ($this->city?->name ?? $this->address_line);
    }

    /**
     * Schedules keyed by ISO weekday so views can look up a day directly.
     *
     * @return Collection<int, AddressSchedule>
     */
    public function schedulesByDay()
    {
        return $this->schedules->keyBy(fn (AddressSchedule $s) => $s->day_of_week->value);
    }

    /**
     * Days this branch is open, in Egyptian week order.
     *
     * @return list<DayOfWeek>
     */
    public function openDays(): array
    {
        $byDay = $this->schedulesByDay();

        return array_values(array_filter(
            DayOfWeek::weekOrder(),
            fn (DayOfWeek $day) => $byDay->get($day->value)?->is_closed === false
        ));
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderByDesc('is_primary')->orderBy('id');
    }
}
