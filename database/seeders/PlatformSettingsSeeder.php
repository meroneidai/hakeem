<?php

namespace Database\Seeders;

use App\Support\Settings;
use Illuminate\Database\Seeder;

class PlatformSettingsSeeder extends Seeder
{
    public function run(Settings $settings): void
    {
        // Online payment stays off until a gateway is actually selected and keyed in.
        $settings->setMany([
            'payments.allowed_modes' => ['at_clinic', 'after_service'],
            'payments.default_mode' => 'at_clinic',
            'payments.allow_clinic_override' => true,
            'payments.gateway' => 'none',
            'payments.gateway_key' => null,
        ], 'payments');

        // Defaults mirror the notification matrix in md_files/05 §3-4.
        $settings->set('notifications.channels', [
            'booking_created' => ['push', 'sms'],
            'booking_confirmed' => ['push', 'sms'],
            'booking_rescheduled' => ['push', 'sms'],
            'booking_cancelled' => ['push', 'sms'],
            'booking_reminder' => ['push'],
            'lab_result_ready' => ['push', 'sms'],
            'prescription_issued' => ['push'],
            'record_access_request' => ['push', 'sms'],
            'promotion_published' => ['push'],
            'subscription_status' => ['email'],
        ], 'notifications');

        $settings->setMany([
            'general.support_whatsapp' => null,
            'general.support_email' => null,
        ], 'general');
    }
}
