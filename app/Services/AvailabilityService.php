<?php

namespace App\Services;

use App\Enums\BookingStatus;
use App\Enums\DayOfWeek;
use App\Models\AddressSchedule;
use App\Models\Booking;
use App\Models\ClinicAddress;
use App\Models\ClinicService;
use App\Models\Doctor;
use App\Models\DoctorAddressAvailability;
use App\Models\ServiceType;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class AvailabilityService
{
    /**
     * Open statuses that occupy a doctor's calendar.
     *
     * @return list<BookingStatus>
     */
    public function blockingStatuses(): array
    {
        return [BookingStatus::Pending, BookingStatus::Confirmed, BookingStatus::InProgress];
    }

    /**
     * @return list<array{starts_at: string, label: string}>
     */
    public function slots(Doctor $doctor, ClinicAddress $address, ServiceType $serviceType, Carbon $day): array
    {
        $doctor->loadMissing('availability');
        $address->loadMissing('schedules');

        $windows = $this->windows($doctor, $address, $day);

        if ($windows === []) {
            return [];
        }

        $duration = $this->durationMinutes($address, $serviceType, $windows[0]['slot_minutes']);
        $taken = $this->openBookings($doctor, $day);
        $slots = [];

        foreach ($windows as $window) {
            for ($start = $window['open']; $start + $duration <= $window['close']; $start += $duration) {
                if ($this->overlapsBreak($start, $duration, $window['break_start'], $window['break_end'])) {
                    continue;
                }

                $at = $day->copy()->startOfDay()->addMinutes($start);

                if ($at->lessThanOrEqualTo(now())) {
                    continue;
                }

                if ($this->conflictsWith($taken, $at, $duration)) {
                    continue;
                }

                $slots[] = [
                    'starts_at' => $at->format('Y-m-d H:i:s'),
                    'label' => $at->format('H:i'),
                ];
            }
        }

        return $slots;
    }

    public function assertBookable(
        Doctor $doctor,
        ClinicAddress $address,
        ServiceType $serviceType,
        Carbon $scheduledAt,
        ?int $ignoreBookingId = null,
    ): void {
        if ($scheduledAt->lt(now()->copy()->subMinute())) {
            throw ValidationException::withMessages([
                'scheduled_at' => __('booking.slot_in_past'),
            ]);
        }

        $doctor->loadMissing('availability');
        $address->loadMissing('schedules');

        $windows = $this->windows($doctor, $address, $scheduledAt);
        $duration = $this->durationMinutes(
            $address,
            $serviceType,
            $windows[0]['slot_minutes'] ?? 30,
        );

        if ($this->hasConfiguredHours($doctor, $address)) {
            if ($windows === [] || ! $this->fitsWindow($windows, $this->minutesOfDay($scheduledAt), $duration)) {
                throw ValidationException::withMessages([
                    'scheduled_at' => __('booking.outside_hours'),
                ]);
            }
        }

        $held = $this->lockOpenBookings($doctor, $scheduledAt, $ignoreBookingId);

        if ($this->conflictsWith($held, $scheduledAt, $duration, $ignoreBookingId)) {
            throw ValidationException::withMessages([
                'scheduled_at' => __('booking.slot_taken'),
            ]);
        }
    }

    public function durationMinutes(ClinicAddress $address, ServiceType $serviceType, int $fallback = 30): int
    {
        $offeringMinutes = ClinicService::query()
            ->where('clinic_id', $address->clinic_id)
            ->where('service_type_id', $serviceType->id)
            ->value('duration_minutes');

        return $serviceType->durationMinutes($offeringMinutes ? (int) $offeringMinutes : null)
            ?: max(5, $fallback);
    }

    public function bookingDuration(Booking $booking): int
    {
        $fallback = 30;

        if ($booking->address) {
            $day = $booking->scheduled_at ?? now();
            $schedule = $booking->address->schedules
                ->first(fn (AddressSchedule $row) => $row->day_of_week === DayOfWeek::from($day->isoWeekday()));
            $fallback = (int) ($schedule?->slot_duration_minutes ?: 30);
        }

        return $booking->serviceType?->durationMinutes($booking->clinicService?->duration_minutes)
            ?: max(5, (int) ($booking->clinicService?->duration_minutes ?: $fallback));
    }

    /**
     * @return list<array{open: int, close: int, break_start: ?int, break_end: ?int, slot_minutes: int}>
     */
    public function windows(Doctor $doctor, ClinicAddress $address, Carbon $day): array
    {
        $weekday = DayOfWeek::from($day->isoWeekday());
        $doctorRows = $doctor->availability
            ->where('clinic_address_id', $address->id)
            ->where('day_of_week', $weekday)
            ->values();

        $schedule = $address->relationLoaded('schedules')
            ? $address->schedules->first(fn (AddressSchedule $row) => $row->day_of_week === $weekday)
            : $address->schedules()->where('day_of_week', $weekday->value)->first();

        $breakStart = $schedule?->hasBreak() ? $this->toMinutes((string) $schedule->break_start) : null;
        $breakEnd = $schedule?->hasBreak() ? $this->toMinutes((string) $schedule->break_end) : null;
        $slotMinutes = max(5, (int) ($schedule?->slot_duration_minutes ?: 30));

        if ($doctor->availability->contains(fn (DoctorAddressAvailability $row) => (int) $row->clinic_address_id === (int) $address->id)) {
            return $doctorRows->map(fn (DoctorAddressAvailability $row) => [
                'open' => $this->toMinutes($row->open_time),
                'close' => $this->toMinutes($row->close_time),
                'break_start' => $breakStart,
                'break_end' => $breakEnd,
                'slot_minutes' => $slotMinutes,
            ])->all();
        }

        if ($schedule === null || $schedule->is_closed) {
            return [];
        }

        return [[
            'open' => $this->toMinutes($schedule->open_time),
            'close' => $this->toMinutes($schedule->close_time),
            'break_start' => $breakStart,
            'break_end' => $breakEnd,
            'slot_minutes' => $slotMinutes,
        ]];
    }

    public function hasConfiguredHours(Doctor $doctor, ClinicAddress $address): bool
    {
        if ($doctor->availability->contains(fn (DoctorAddressAvailability $row) => (int) $row->clinic_address_id === (int) $address->id)) {
            return true;
        }

        if ($address->relationLoaded('schedules')) {
            return $address->schedules->isNotEmpty();
        }

        return $address->schedules()->exists();
    }

    /**
     * @param  list<array{open: int, close: int, break_start: ?int, break_end: ?int, slot_minutes: int}>  $windows
     */
    private function fitsWindow(array $windows, int $start, int $duration): bool
    {
        foreach ($windows as $window) {
            if ($start < $window['open'] || ($start + $duration) > $window['close']) {
                continue;
            }

            if ($this->overlapsBreak($start, $duration, $window['break_start'], $window['break_end'])) {
                continue;
            }

            return true;
        }

        return false;
    }

    /**
     * @param  Collection<int, Booking>  $bookings
     */
    private function conflictsWith(Collection $bookings, Carbon $start, int $duration, ?int $ignoreBookingId = null): bool
    {
        $end = $start->copy()->addMinutes($duration);

        return $bookings->contains(function (Booking $booking) use ($start, $end, $ignoreBookingId) {
            if ($ignoreBookingId !== null && (int) $booking->id === $ignoreBookingId) {
                return false;
            }

            $existingStart = $booking->scheduled_at;
            $existingEnd = $existingStart->copy()->addMinutes($this->bookingDuration($booking));

            return $existingStart->lt($end) && $existingEnd->gt($start);
        });
    }

    /**
     * @return Collection<int, Booking>
     */
    private function openBookings(Doctor $doctor, Carbon $day): Collection
    {
        return Booking::query()
            ->where('doctor_id', $doctor->id)
            ->whereIn('status', $this->blockingStatuses())
            ->whereDate('scheduled_at', $day->toDateString())
            ->with(['clinicService', 'address.schedules'])
            ->orderBy('scheduled_at')
            ->orderBy('id')
            ->get();
    }

    /**
     * @return Collection<int, Booking>
     */
    private function lockOpenBookings(Doctor $doctor, Carbon $scheduledAt, ?int $ignoreBookingId): Collection
    {
        return Booking::query()
            ->where('doctor_id', $doctor->id)
            ->whereIn('status', $this->blockingStatuses())
            ->when($ignoreBookingId, fn ($query) => $query->whereKeyNot($ignoreBookingId))
            ->where('scheduled_at', '>=', $scheduledAt->copy()->subHours(6))
            ->where('scheduled_at', '<', $scheduledAt->copy()->addHours(6))
            ->with(['clinicService', 'address.schedules'])
            ->lockForUpdate()
            ->orderBy('id')
            ->get();
    }

    private function overlapsBreak(int $start, int $duration, ?int $breakStart, ?int $breakEnd): bool
    {
        if ($breakStart === null || $breakEnd === null) {
            return false;
        }

        $end = $start + $duration;

        return $start < $breakEnd && $end > $breakStart;
    }

    private function minutesOfDay(Carbon $at): int
    {
        return ($at->hour * 60) + $at->minute;
    }

    private function toMinutes(string $time): int
    {
        [$hour, $minute] = array_map('intval', explode(':', substr($time, 0, 5)));

        return ($hour * 60) + $minute;
    }
}
