<?php

namespace App\Support;

use Illuminate\Support\Facades\Http;

class ConnectionProbe
{
    /**
     * @return array<string, array{ready: bool, detail: string}>
     */
    public function snapshot(): array
    {
        return [
            'mail' => $this->mail(false),
            'firebase' => $this->firebase(false),
            'sms' => $this->sms(),
            'whatsapp' => $this->whatsapp(),
        ];
    }

    /**
     * @return array{ready: bool, detail: string}
     */
    public function probe(string $channel): array
    {
        return match ($channel) {
            'mail' => $this->mail(true),
            'firebase' => $this->firebase(true),
            'sms' => $this->sms(),
            'whatsapp' => $this->whatsapp(),
            default => ['ready' => false, 'detail' => __('admin.integrations.unknown_channel')],
        };
    }

    /**
     * @return array{ready: bool, detail: string}
     */
    public function mail(bool $live = false): array
    {
        $host = (string) config('mail.mailers.smtp.host');
        $port = (int) config('mail.mailers.smtp.port', 587);

        if ($host === '' || $port < 1) {
            return ['ready' => false, 'detail' => __('admin.integrations.mail_missing')];
        }

        if (! $live) {
            return ['ready' => true, 'detail' => __('admin.integrations.mail_configured', ['host' => $host])];
        }

        $errno = 0;
        $errstr = '';
        $socket = @fsockopen($host, $port, $errno, $errstr, 3);

        if (! is_resource($socket)) {
            return ['ready' => false, 'detail' => $errstr !== '' ? $errstr : __('admin.integrations.unreachable')];
        }

        fclose($socket);

        return ['ready' => true, 'detail' => __('admin.integrations.mail_ok', ['host' => $host, 'port' => $port])];
    }

    /**
     * @return array{ready: bool, detail: string}
     */
    public function firebase(bool $live = false): array
    {
        $project = (string) config('services.firebase.project_id');
        $webKey = (string) config('services.firebase.web_api_key');
        $serverKey = (string) config('services.firebase.server_key');
        $credentials = (string) config('services.firebase.credentials');

        if ($project === '' || ($webKey === '' && $serverKey === '' && $credentials === '')) {
            return ['ready' => false, 'detail' => __('admin.integrations.firebase_missing')];
        }

        if ($credentials !== '') {
            $decoded = json_decode($credentials, true);
            $pathOk = is_file($credentials);
            if (! is_array($decoded) && ! $pathOk) {
                return ['ready' => false, 'detail' => __('admin.integrations.firebase_credentials_invalid')];
            }
        }

        if ($live && $webKey !== '') {
            try {
                $response = Http::timeout(4)->get(
                    'https://www.googleapis.com/identitytoolkit/v3/relyingparty/getProjectConfig',
                    ['key' => $webKey],
                );

                if ($response->failed() && $response->status() >= 500) {
                    return ['ready' => false, 'detail' => __('admin.integrations.firebase_http', ['status' => $response->status()])];
                }
            } catch (\Throwable $exception) {
                return ['ready' => false, 'detail' => $exception->getMessage()];
            }
        }

        return ['ready' => true, 'detail' => __('admin.integrations.firebase_ok', ['project' => $project])];
    }

    /**
     * @return array{ready: bool, detail: string}
     */
    public function sms(): array
    {
        $provider = strtolower((string) (config('services.sms.provider') ?: 'log'));

        if ($provider === 'twilio') {
            if (! filled(config('services.sms.sid')) || ! filled(config('services.sms.key')) || ! filled(config('services.sms.sender'))) {
                return ['ready' => false, 'detail' => __('admin.integrations.sms_missing')];
            }

            return ['ready' => true, 'detail' => __('admin.integrations.sms_ok')];
        }

        if (! filled(config('services.sms.key'))) {
            return ['ready' => false, 'detail' => __('admin.integrations.sms_missing')];
        }

        return ['ready' => true, 'detail' => __('admin.integrations.sms_ok')];
    }

    /**
     * @return array{ready: bool, detail: string}
     */
    public function whatsapp(): array
    {
        if (! filled(config('services.whatsapp.token')) || ! filled(config('services.whatsapp.phone_id'))) {
            return ['ready' => false, 'detail' => __('admin.integrations.whatsapp_missing')];
        }

        return ['ready' => true, 'detail' => __('admin.integrations.whatsapp_ok')];
    }
}
