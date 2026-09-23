<?php

namespace App\Services;

use App\Enums\BookingStatus;
use App\Models\Booking;

class BookingReminderService
{
    public function __construct(private NotificationDispatcher $notifications) {}

    public function sendDue(int $horizonHours = 24): int
    {
        $sent = 0;

        Booking::query()
            ->where('status', BookingStatus::Confirmed)
            ->whereNull('reminder_sent_at')
            ->where('scheduled_at', '>', now())
            ->where('scheduled_at', '<=', now()->addHours($horizonHours))
            ->with(['patient', 'clinic'])
            ->orderBy('scheduled_at')
            ->orderBy('id')
            ->each(function (Booking $booking) use (&$sent): void {
                $this->notifications->send('booking_reminder', [
                    'booking_id' => $booking->id,
                    'clinic_id' => $booking->clinic_id,
                    'patient_id' => $booking->patient_id,
                    'status' => $booking->status->value,
                    'scheduled_at' => $booking->scheduled_at?->toIso8601String(),
                    'name' => $booking->patient?->name,
                    'clinic' => $booking->clinic?->name,
                    'date' => $booking->scheduled_at?->format('Y-m-d H:i'),
                    'reference' => (string) $booking->id,
                ]);

                $booking->forceFill(['reminder_sent_at' => now()])->save();
                $sent++;
            });

        return $sent;
    }
}
