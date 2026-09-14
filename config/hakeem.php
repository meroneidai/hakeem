<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Locales
    |--------------------------------------------------------------------------
    | Arabic is the platform's primary language and default layout direction.
    */

    'locales' => [
        'ar' => ['native' => 'العربية', 'english' => 'Arabic', 'dir' => 'rtl'],
        'en' => ['native' => 'English', 'english' => 'English', 'dir' => 'ltr'],
    ],

    'default_locale' => 'ar',

    /*
    |--------------------------------------------------------------------------
    | Payments
    |--------------------------------------------------------------------------
    | Modes are enabled globally here and may be narrowed per clinic from the
    | admin dashboard (see md_files/03 §4).
    */

    'payment_modes' => ['online', 'at_clinic', 'after_service'],

    'payment_gateways' => [
        'none' => 'Not configured',
        'paymob' => 'Paymob',
        'fawry' => 'Fawry',
    ],

    'currency' => 'EGP',

    /*
    |--------------------------------------------------------------------------
    | Notification channels
    |--------------------------------------------------------------------------
    | The in-app centre is always on; the rest are toggled per event type.
    */

    'notification_channels' => ['push', 'sms', 'whatsapp', 'email'],

    'notification_events' => [
        'booking_created',
        'booking_confirmed',
        'booking_rescheduled',
        'booking_cancelled',
        'booking_reminder',
        'lab_result_ready',
        'prescription_issued',
        'record_access_request',
        'promotion_published',
        'subscription_status',
    ],
];
