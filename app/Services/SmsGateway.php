<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Twilio REST SMS for Egypt. When SID/token/from are missing the message is
 * written to the log instead of failing the request.
 */
class SmsGateway
{
    public function ready(): bool
    {
        return $this->provider() === 'twilio'
            && filled(config('services.sms.sid'))
            && filled(config('services.sms.key'))
            && filled(config('services.sms.sender'));
    }

    public function send(string $to, string $body): bool
    {
        $e164 = $this->toE164($to);

        if ($e164 === '') {
            return false;
        }

        if (! $this->ready()) {
            Log::info('hakeem.sms', [
                'provider' => $this->provider(),
                'to' => $e164,
                'body' => $body,
            ]);

            return $this->provider() === 'log';
        }

        $sid = (string) config('services.sms.sid');
        $payload = ['To' => $e164, 'Body' => $body];
        $from = (string) config('services.sms.sender');

        if (str_starts_with($from, 'MG')) {
            $payload['MessagingServiceSid'] = $from;
        } else {
            $payload['From'] = $from;
        }

        $response = Http::withBasicAuth($sid, (string) config('services.sms.key'))
            ->asForm()
            ->acceptJson()
            ->connectTimeout(3)
            ->timeout(12)
            ->post("https://api.twilio.com/2010-04-01/Accounts/{$sid}/Messages.json", $payload);

        if ($response->failed()) {
            Log::warning('hakeem.sms.failed', [
                'to' => $e164,
                'status' => $response->status(),
                'body' => $response->json(),
            ]);

            return false;
        }

        return true;
    }

    public function sendToUser(?User $user, string $body): bool
    {
        if (! $user || blank($user->phone) || ! $user->notify_sms) {
            return false;
        }

        return $this->send($user->phone, $body);
    }

    public function toE164(string $phone): string
    {
        $digits = User::normalizePhone($phone);

        if ($digits === '') {
            return '';
        }

        return str_starts_with($digits, '+') ? $digits : '+'.$digits;
    }

    private function provider(): string
    {
        return strtolower((string) (config('services.sms.provider') ?: 'log'));
    }
}
