<?php

use Illuminate\Support\Facades\Schedule;

Schedule::command('bookings:send-reminders')
    ->hourly()
    ->withoutOverlapping();
