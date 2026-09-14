<?php

namespace App\Models;

use App\Enums\DayOfWeek;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'clinic_address_id', 'day_of_week', 'open_time', 'close_time',
    'break_start', 'break_end', 'slot_duration_minutes', 'is_closed',
])]
class AddressSchedule extends Model
{
    protected function casts(): array
    {
        return [
            'day_of_week' => DayOfWeek::class,
            'is_closed' => 'boolean',
        ];
    }

    public function address(): BelongsTo
    {
        return $this->belongsTo(ClinicAddress::class, 'clinic_address_id');
    }

    /**
     * Times are stored as `time` columns; trim the seconds for display and inputs.
     */
    public function timeRange(): string
    {
        return $this->formatTime($this->open_time).' – '.$this->formatTime($this->close_time);
    }

    public function formatTime(?string $value): string
    {
        return $value === null ? '' : substr($value, 0, 5);
    }

    public function hasBreak(): bool
    {
        return filled($this->break_start) && filled($this->break_end);
    }

    /**
     * Number of bookable slots this day yields, excluding any break window.
     */
    public function slotCount(): int
    {
        if ($this->is_closed) {
            return 0;
        }

        $minutes = $this->minutesBetween($this->open_time, $this->close_time);

        if ($this->hasBreak()) {
            $minutes -= $this->minutesBetween($this->break_start, $this->break_end);
        }

        return max(0, intdiv($minutes, max(1, $this->slot_duration_minutes)));
    }

    private function minutesBetween(string $from, string $to): int
    {
        [$fh, $fm] = array_map('intval', explode(':', substr($from, 0, 5)));
        [$th, $tm] = array_map('intval', explode(':', substr($to, 0, 5)));

        return max(0, ($th * 60 + $tm) - ($fh * 60 + $fm));
    }
}
