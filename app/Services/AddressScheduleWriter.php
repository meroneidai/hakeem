<?php

namespace App\Services;

use App\Enums\DayOfWeek;
use App\Models\AddressSchedule;
use App\Models\ClinicAddress;

class AddressScheduleWriter
{
    /**
     * Persist a full week of hours. Closed days are stored (not omitted) so the
     * editor always has a row per weekday.
     *
     * @param  array<int, array{open?: bool, open_time?: ?string, close_time?: ?string, break_start?: ?string, break_end?: ?string, slot_duration_minutes?: int}>  $days
     */
    public function sync(ClinicAddress $address, array $days, int $defaultSlotMinutes = 30): void
    {
        foreach (DayOfWeek::weekOrder() as $day) {
            $input = $days[$day->value] ?? [];
            $open = in_array((string) ($input['open'] ?? '0'), ['1', 'true', 'on'], true);

            AddressSchedule::query()->updateOrCreate(
                [
                    'clinic_address_id' => $address->id,
                    'day_of_week' => $day->value,
                ],
                [
                    'is_closed' => ! $open,
                    'open_time' => $open ? (($input['open_time'] ?? null) ?: '09:00') : '00:00',
                    'close_time' => $open ? (($input['close_time'] ?? null) ?: '17:00') : '00:00',
                    'break_start' => $open ? ($input['break_start'] ?? null) : null,
                    'break_end' => $open ? ($input['break_end'] ?? null) : null,
                    'slot_duration_minutes' => max(5, (int) ($input['slot_duration_minutes'] ?? $defaultSlotMinutes)),
                ],
            );
        }
    }

    /**
     * Saturday–Thursday 09:00–17:00, Friday closed — a sensible Egyptian default.
     */
    public function seedDefaults(ClinicAddress $address): void
    {
        $days = [];

        foreach (DayOfWeek::weekOrder() as $day) {
            $open = in_array($day, DayOfWeek::defaultWorkingDays(), true);
            $days[$day->value] = [
                'open' => $open,
                'open_time' => '09:00',
                'close_time' => '17:00',
                'slot_duration_minutes' => 30,
            ];
        }

        $this->sync($address, $days);
    }
}
