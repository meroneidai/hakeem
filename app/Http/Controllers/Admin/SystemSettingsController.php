<?php

namespace App\Http\Controllers\Admin;

use App\Enums\Permission;
use App\Http\Controllers\Controller;
use App\Support\ApplyIntegrationSettings;
use App\Support\Audit;
use App\Support\ConnectionProbe;
use App\Support\MessageTemplates;
use App\Support\Settings;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\View\View;

class SystemSettingsController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return ['can:'.Permission::ManagePaymentSettings->value];
    }

    public function edit(Settings $settings, ConnectionProbe $probe, MessageTemplates $templates): View
    {
        return view('admin.system.edit', [
            'values' => $this->values($settings, $templates),
            'status' => $probe->snapshot(),
        ]);
    }

    public function update(Request $request, Settings $settings, ApplyIntegrationSettings $apply): RedirectResponse
    {
        $data = $request->validate($this->rules());

        $integrations = $data['integrations'] ?? [];
        $messages = $data['messages'] ?? [];

        $plain = [
            'integrations.mail_host' => $integrations['mail_host'] ?? null,
            'integrations.mail_port' => $integrations['mail_port'] ?? null,
            'integrations.mail_username' => $integrations['mail_username'] ?? null,
            'integrations.mail_encryption' => $integrations['mail_encryption'] ?? 'tls',
            'integrations.mail_from_address' => $integrations['mail_from_address'] ?? null,
            'integrations.mail_from_name' => $integrations['mail_from_name'] ?? null,
            'integrations.firebase_project_id' => $integrations['firebase_project_id'] ?? null,
            'integrations.firebase_web_api_key' => $integrations['firebase_web_api_key'] ?? null,
            'integrations.firebase_auth_domain' => $integrations['firebase_auth_domain'] ?? null,
            'integrations.firebase_vapid_key' => $integrations['firebase_vapid_key'] ?? null,
            'integrations.sms_provider' => $integrations['sms_provider'] ?? 'log',
            'integrations.sms_sender' => $integrations['sms_sender'] ?? null,
            'integrations.twilio_sid' => $integrations['twilio_sid'] ?? null,
            'integrations.google_client_id' => $integrations['google_client_id'] ?? null,
            'integrations.facebook_client_id' => $integrations['facebook_client_id'] ?? null,
            'integrations.apple_client_id' => $integrations['apple_client_id'] ?? null,
            'integrations.whatsapp_phone_id' => $integrations['whatsapp_phone_id'] ?? null,
        ];

        $settings->setMany($plain, 'integrations');

        $this->storeSecret($settings, 'integrations.mail_password', $integrations['mail_password'] ?? null);
        $this->storeSecret($settings, 'integrations.firebase_server_key', $integrations['firebase_server_key'] ?? null);
        $this->storeSecret($settings, 'integrations.firebase_credentials', $integrations['firebase_credentials'] ?? null);
        $this->storeSecret($settings, 'integrations.sms_key', $integrations['sms_key'] ?? null);
        $this->storeSecret($settings, 'integrations.whatsapp_token', $integrations['whatsapp_token'] ?? null);
        $this->storeSecret($settings, 'integrations.google_client_secret', $integrations['google_client_secret'] ?? null);
        $this->storeSecret($settings, 'integrations.facebook_client_secret', $integrations['facebook_client_secret'] ?? null);
        $this->storeSecret($settings, 'integrations.apple_client_secret', $integrations['apple_client_secret'] ?? null);

        if ($request->exists('messages')) {
            $messageValues = [];

            foreach (MessageTemplates::keys() as $key) {
                $short = str_replace('messages.', '', $key);
                $messageValues[$key] = $messages[$short] ?? null;
            }

            $settings->setMany($messageValues, 'messages');
        }

        $apply($settings);

        Audit::log('settings.integrations_updated');

        return back()->with('status', __('admin.integrations.saved'));
    }

    public function probe(Request $request, ConnectionProbe $probe): JsonResponse
    {
        $channel = $request->validate([
            'channel' => ['required', 'in:mail,firebase,sms,whatsapp'],
        ])['channel'];

        $result = $probe->probe($channel);

        return response()->json($result);
    }

    /**
     * @return array<string, mixed>
     */
    private function values(Settings $settings, MessageTemplates $templates): array
    {
        $keys = [
            'integrations.mail_host',
            'integrations.mail_port',
            'integrations.mail_username',
            'integrations.mail_encryption',
            'integrations.mail_from_address',
            'integrations.mail_from_name',
            'integrations.firebase_project_id',
            'integrations.firebase_web_api_key',
            'integrations.firebase_auth_domain',
            'integrations.firebase_vapid_key',
            'integrations.sms_provider',
            'integrations.sms_sender',
            'integrations.twilio_sid',
            'integrations.google_client_id',
            'integrations.facebook_client_id',
            'integrations.apple_client_id',
            'integrations.whatsapp_phone_id',
        ];

        $values = [];

        foreach ($keys as $key) {
            $values[$key] = $settings->get($key);
        }

        foreach ($templates->allResolved() as $key => $value) {
            $values[$key] = $value;
        }

        $values['has'] = [
            'mail_password' => filled($settings->get('integrations.mail_password')),
            'firebase_server_key' => filled($settings->get('integrations.firebase_server_key')),
            'firebase_credentials' => filled($settings->get('integrations.firebase_credentials')),
            'sms_key' => filled($settings->get('integrations.sms_key')),
            'whatsapp_token' => filled($settings->get('integrations.whatsapp_token')),
            'google_client_secret' => filled($settings->get('integrations.google_client_secret')),
            'facebook_client_secret' => filled($settings->get('integrations.facebook_client_secret')),
            'apple_client_secret' => filled($settings->get('integrations.apple_client_secret')),
        ];

        return $values;
    }

    /**
     * @return array<string, mixed>
     */
    private function rules(): array
    {
        return [
            'integrations.mail_host' => ['nullable', 'string', 'max:190'],
            'integrations.mail_port' => ['nullable', 'integer', 'min:1', 'max:65535'],
            'integrations.mail_username' => ['nullable', 'string', 'max:190'],
            'integrations.mail_password' => ['nullable', 'string', 'max:255'],
            'integrations.mail_encryption' => ['nullable', 'in:tls,ssl,none'],
            'integrations.mail_from_address' => ['nullable', 'email', 'max:190'],
            'integrations.mail_from_name' => ['nullable', 'string', 'max:120'],
            'integrations.firebase_project_id' => ['nullable', 'string', 'max:120'],
            'integrations.firebase_web_api_key' => ['nullable', 'string', 'max:255'],
            'integrations.firebase_server_key' => ['nullable', 'string', 'max:4000'],
            'integrations.firebase_credentials' => ['nullable', 'string', 'max:20000'],
            'integrations.firebase_auth_domain' => ['nullable', 'string', 'max:190'],
            'integrations.firebase_vapid_key' => ['nullable', 'string', 'max:255'],
            'integrations.sms_provider' => ['nullable', 'in:twilio,log'],
            'integrations.sms_key' => ['nullable', 'string', 'max:255'],
            'integrations.sms_sender' => ['nullable', 'string', 'max:64'],
            'integrations.twilio_sid' => ['nullable', 'string', 'max:64'],
            'integrations.whatsapp_token' => ['nullable', 'string', 'max:4000'],
            'integrations.whatsapp_phone_id' => ['nullable', 'string', 'max:64'],
            'integrations.google_client_id' => ['nullable', 'string', 'max:255'],
            'integrations.google_client_secret' => ['nullable', 'string', 'max:255'],
            'integrations.facebook_client_id' => ['nullable', 'string', 'max:255'],
            'integrations.facebook_client_secret' => ['nullable', 'string', 'max:255'],
            'integrations.apple_client_id' => ['nullable', 'string', 'max:255'],
            'integrations.apple_client_secret' => ['nullable', 'string', 'max:4000'],
            'messages.promo_sms' => ['nullable', 'string', 'max:480'],
            'messages.promo_push' => ['nullable', 'string', 'max:240'],
            'messages.promo_email_subject' => ['nullable', 'string', 'max:180'],
            'messages.promo_email_body' => ['nullable', 'string', 'max:4000'],
            'messages.sms_booking_created' => ['nullable', 'string', 'max:480'],
            'messages.sms_booking_confirmed' => ['nullable', 'string', 'max:480'],
            'messages.sms_booking_cancelled' => ['nullable', 'string', 'max:480'],
            'messages.sms_booking_reminder' => ['nullable', 'string', 'max:480'],
            'messages.sms_booking_completed' => ['nullable', 'string', 'max:480'],
            'messages.sms_evaluation_booked' => ['nullable', 'string', 'max:480'],
            'messages.sms_lab_order_created' => ['nullable', 'string', 'max:480'],
            'messages.sms_lab_order_confirmed' => ['nullable', 'string', 'max:480'],
            'messages.sms_lab_order_completed' => ['nullable', 'string', 'max:480'],
            'messages.sms_lab_order_cancelled' => ['nullable', 'string', 'max:480'],
        ];
    }

    private function storeSecret(Settings $settings, string $key, mixed $value): void
    {
        if (! filled($value)) {
            return;
        }

        $settings->set($key, $value, 'integrations', encrypted: true);
    }
}
