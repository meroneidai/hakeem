<?php

namespace App\Support;

use Illuminate\Support\Facades\Schema;

class ApplyIntegrationSettings
{
    public function __invoke(Settings $settings): void
    {
        if (! Schema::hasTable('settings')) {
            return;
        }

        $this->applyMail($settings);
        $this->applyFirebase($settings);
        $this->applySms($settings);
        $this->applyWhatsapp($settings);
        $this->applySocial($settings);
    }

    private function applyMail(Settings $settings): void
    {
        $host = $settings->get('integrations.mail_host');

        if (! filled($host)) {
            return;
        }

        config([
            'mail.default' => 'smtp',
            'mail.mailers.smtp.host' => $host,
            'mail.mailers.smtp.port' => (int) ($settings->get('integrations.mail_port') ?: 587),
            'mail.mailers.smtp.username' => $settings->get('integrations.mail_username'),
            'mail.mailers.smtp.password' => $settings->get('integrations.mail_password'),
            'mail.mailers.smtp.encryption' => $settings->get('integrations.mail_encryption') ?: 'tls',
            'mail.from.address' => $settings->get('integrations.mail_from_address') ?: config('mail.from.address'),
            'mail.from.name' => $settings->get('integrations.mail_from_name') ?: config('mail.from.name'),
        ]);
    }

    private function applyFirebase(Settings $settings): void
    {
        $map = [
            'integrations.firebase_project_id' => 'services.firebase.project_id',
            'integrations.firebase_web_api_key' => 'services.firebase.web_api_key',
            'integrations.firebase_server_key' => 'services.firebase.server_key',
            'integrations.firebase_credentials' => 'services.firebase.credentials',
            'integrations.firebase_auth_domain' => 'services.firebase.auth_domain',
            'integrations.firebase_vapid_key' => 'services.firebase.vapid_key',
        ];

        foreach ($map as $settingKey => $configKey) {
            $value = $settings->get($settingKey);
            if (filled($value)) {
                config([$configKey => $value]);
            }
        }
    }

    private function applySms(Settings $settings): void
    {
        if (filled($settings->get('integrations.sms_provider'))) {
            config(['services.sms.provider' => $settings->get('integrations.sms_provider')]);
        }
        if (filled($settings->get('integrations.sms_key'))) {
            config(['services.sms.key' => $settings->get('integrations.sms_key')]);
        }
        if (filled($settings->get('integrations.sms_sender'))) {
            config(['services.sms.sender' => $settings->get('integrations.sms_sender')]);
        }
        if (filled($settings->get('integrations.twilio_sid'))) {
            config(['services.sms.sid' => $settings->get('integrations.twilio_sid')]);
        }
    }

    private function applyWhatsapp(Settings $settings): void
    {
        if (filled($settings->get('integrations.whatsapp_token'))) {
            config(['services.whatsapp.token' => $settings->get('integrations.whatsapp_token')]);
        }
        if (filled($settings->get('integrations.whatsapp_phone_id'))) {
            config(['services.whatsapp.phone_id' => $settings->get('integrations.whatsapp_phone_id')]);
        }
    }

    private function applySocial(Settings $settings): void
    {
        $pairs = [
            'integrations.google_client_id' => 'services.google.client_id',
            'integrations.google_client_secret' => 'services.google.client_secret',
            'integrations.facebook_client_id' => 'services.facebook.client_id',
            'integrations.facebook_client_secret' => 'services.facebook.client_secret',
            'integrations.apple_client_id' => 'services.apple.client_id',
            'integrations.apple_client_secret' => 'services.apple.client_secret',
        ];

        foreach ($pairs as $settingKey => $configKey) {
            $value = $settings->get($settingKey);
            if (filled($value)) {
                config([$configKey => $value]);
            }
        }
    }
}
