<?php

namespace Database\Seeders;

use App\Services\LoyaltyProgram;
use App\Support\MessageTemplates;
use App\Support\Settings;
use Illuminate\Database\Seeder;

class PlatformSettingsSeeder extends Seeder
{
    public function run(Settings $settings): void
    {
        // Online stays listed so services can require it. Collection still waits for a gateway.
        $settings->setMany([
            'payments.allowed_modes' => ['online', 'at_clinic', 'after_service'],
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
            'complaint_opened' => ['email'],
            'review_published' => ['push'],
            'evaluation_booked' => ['push', 'sms'],
            'booking_completed' => ['push'],
            'subscription_status' => ['email'],
            'invoice_issued' => ['email'],
            'invoice_reminder' => ['email', 'sms'],
        ], 'notifications');

        $settings->setMany([
            'general.support_whatsapp' => '01000000000',
            'general.support_phone' => '01000000001',
            'general.support_email' => 'support@hakeem.test',
        ], 'general');

        $settings->setMany(MessageTemplates::defaults(), 'messages');

        $settings->setMany(LoyaltyProgram::defaultSettings(), 'loyalty');

        $settings->setMany([
            'seo.site_title_ar' => 'حكيم',
            'seo.site_title_en' => 'Hakeem',
            'seo.default_description_ar' => 'ابحث واحجز عند أطباء وعيادات موثّقة في مصر — كشف، زيارة منزلية، تحاليل واستشارة عن بُعد.',
            'seo.default_description_en' => 'Find and book verified doctors and clinics in Egypt — clinic visits, home care, labs and teleconsultation.',
            'seo.keywords_ar' => 'حكيم, حجز طبيب, عيادات مصر, أطباء, تحاليل, زيارة منزلية, استشارة عن بعد, تأمين صحي',
            'seo.keywords_en' => 'Hakeem, book doctor Egypt, clinics, physicians, lab tests, home visit, teleconsultation, health insurance',
            'seo.og_image' => null,
            'seo.twitter_site' => '@hakeem',
            'seo.app_ios_url' => null,
            'seo.app_android_url' => null,
        ], 'seo');

        $settings->setMany([
            'branding.tagline_ar' => 'احجز طبيبك في أي مكان بمصر',
            'branding.tagline_en' => 'Book your doctor anywhere in Egypt',
        ], 'branding');
    }
}
