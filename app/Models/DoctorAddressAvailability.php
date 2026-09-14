<?php

namespace App\Models;

use App\Enums\DayOfWeek;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['doctor_id', 'clinic_address_id', 'day_of_week', 'open_time', 'close_time'])]
class DoctorAddressAvailability extends Model
{
    protected $table = 'doctor_address_availability';

    protected function casts(): array
    {
        return ['day_of_week' => DayOfWeek::class];
    }

    public function doctor(): BelongsTo
    {
        return $this->belongsTo(Doctor::class);
    }

    public function address(): BelongsTo
    {
        return $this->belongsTo(ClinicAddress::class, 'clinic_address_id');
    }

    public function timeRange(): string
    {
        return substr($this->open_time, 0, 5).' – '.substr($this->close_time, 0, 5);
    }
}
