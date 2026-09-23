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
    | Display timezone
    |--------------------------------------------------------------------------
    | Stored timestamps stay in APP_TIMEZONE (UTC). Public booking screens
    | render in Egypt local time.
    */

    'display_timezone' => 'Africa/Cairo',

    /*
    |--------------------------------------------------------------------------
    | Notification channels
    |--------------------------------------------------------------------------
    | The in-app centre is always on; the rest are toggled per event type.
    */

    'notification_channels' => ['push', 'sms', 'whatsapp', 'email'],

    'country' => [
        'code' => 'EG',
        'dial' => '20',
        'flag' => '🇪🇬',
    ],

    'social_providers' => ['google', 'facebook', 'apple'],

    /*
    |--------------------------------------------------------------------------
    | Mobile app deeplinks
    |--------------------------------------------------------------------------
    | Share buttons use the HTTPS URL (universal / app link). The custom scheme
    | is for the Expo/React Native shell. Fill team/bundle/SHA-256 before store
    | release so /.well-known files verify.
    */

    'deeplinks' => [
        'scheme' => env('DEEPLINK_SCHEME', 'hakeem'),
        'ios_team_id' => env('DEEPLINK_IOS_TEAM_ID'),
        'ios_bundle_id' => env('DEEPLINK_IOS_BUNDLE_ID', 'eg.hakeem.app'),
        'ios_app_store_id' => env('DEEPLINK_IOS_APP_STORE_ID'),
        'android_package' => env('DEEPLINK_ANDROID_PACKAGE', 'eg.hakeem'),
        'android_sha256' => array_values(array_filter(array_map(
            'trim',
            explode(',', (string) env('DEEPLINK_ANDROID_SHA256', ''))
        ))),
    ],

    'notification_events' => [
        'booking_created',
        'booking_confirmed',
        'booking_rescheduled',
        'booking_cancelled',
        'booking_completed',
        'booking_reminder',
        'lab_result_ready',
        'prescription_issued',
        'record_access_request',
        'promotion_published',
        'complaint_opened',
        'review_published',
        'evaluation_booked',
        'lab_order_created',
        'lab_order_confirmed',
        'lab_order_completed',
        'lab_order_cancelled',
        'subscription_status',
        'referral_joined',
        'invoice_issued',
        'invoice_reminder',
    ],
];
