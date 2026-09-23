<?php

namespace App\Console\Commands;

use App\Services\BookingReminderService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('bookings:send-reminders')]
#[Description('Send in-app reminders for confirmed visits in the next 24 hours')]
class SendBookingRemindersCommand extends Command
{
    public function handle(BookingReminderService $reminders): int
    {
        $sent = $reminders->sendDue();

        $this->info("Sent {$sent} booking reminder(s).");

        return self::SUCCESS;
    }
}
