<?php

namespace App\Services;

use App\Enums\RoleName;
use App\Mail\PlatformNoticeMail;
use App\Models\Clinic;
use App\Models\InAppNotification;
use App\Models\User;
use App\Support\Branding;
use App\Support\MessageTemplates;
use App\Support\Settings;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;

/**
 * Routes a named platform event through the admin-configured channels.
 * Twilio SMS and SMTP send when keys exist; otherwise the attempt is logged.
 * In-app inbox rows are always persisted.
 */
class NotificationDispatcher
{
    public function __construct(
        private Settings $settings,
        private MessageTemplates $templates,
        private SmsGateway $sms,
        private Branding $branding,
    ) {}

    /**
     * @param  array<string, mixed>  $payload
     */
    public function send(string $event, array $payload = []): void
    {
        $matrix = $this->settings->get('notifications.channels', []) ?: [];
        $channels = $matrix[$event] ?? config('hakeem.notification_channels');

        $body = $this->templates->forEvent($event, $payload) ?: ($payload['body'] ?? null);

        foreach ($channels as $channel) {
            Log::info('hakeem.notification', [
                'event' => $event,
                'channel' => $channel,
                'configured' => $this->channelReady($channel),
                'body' => $body,
                'payload' => $payload,
            ]);
        }

        $this->storeInbox($event, $payload, $body);
        $this->deliverExternal($event, $payload, $body, $channels);
    }

    public function channelReady(string $channel): bool
    {
        return match ($channel) {
            'push' => filled(config('services.firebase.server_key')) || filled(config('services.firebase.credentials')),
            'sms' => $this->sms->ready(),
            'email' => filled(config('mail.from.address')),
            'whatsapp' => filled(config('services.whatsapp.token')),
            default => true,
        };
    }

    /**
     * @param  array<string, mixed>  $payload
     * @param  list<string>  $channels
     */
    private function deliverExternal(string $event, array $payload, ?string $body, array $channels): void
    {
        if ($body === null || $body === '') {
            return;
        }

        $patient = isset($payload['patient_id'])
            ? User::query()->find($payload['patient_id'])
            : (isset($payload['user_id']) ? User::query()->find($payload['user_id']) : null);

        if (! $patient) {
            return;
        }

        $otpEvents = in_array($event, ['account_verify', 'password_reset', 'referral_joined'], true);

        if (! $otpEvents && ! $this->branding->profileComplete($patient)) {
            return;
        }

        if (in_array('sms', $channels, true) && $this->sms->ready() && $patient->notify_sms && $patient->isPhoneVerified()) {
            $this->sms->send($patient->phone, $body);
        }

        if (in_array('email', $channels, true) && $this->channelReady('email') && $patient->notify_email && $patient->isEmailVerified()) {
            Mail::to($patient->email)->send(new PlatformNoticeMail(
                $patient,
                __('admin.notifications.events.'.$event),
                $body,
            ));
        }
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function storeInbox(string $event, array $payload, ?string $body = null): void
    {
        if (! Schema::hasTable('in_app_notifications')) {
            return;
        }

        $recipientIds = collect([
            $payload['patient_id'] ?? null,
            $payload['user_id'] ?? null,
        ]);

        if (! empty($payload['clinic_id'])) {
            $clinic = Clinic::query()->find($payload['clinic_id']);

            if ($clinic) {
                $recipientIds->push($clinic->owner_user_id);
                $recipientIds = $recipientIds->merge($clinic->staff()->allRelatedIds());
            }
        }

        if (str_starts_with($event, 'booking_') || $event === 'evaluation_booked' || str_starts_with($event, 'lab_order_')) {
            $recipientIds = $recipientIds->merge(
                User::query()
                    ->whereHas('roles', fn ($query) => $query->whereIn('name', [
                        RoleName::PlatformAdmin->value,
                        RoleName::SupportAgent->value,
                    ]))
                    ->pluck('id')
            );
        }

        $title = __('admin.notifications.events.'.$event);

        $users = User::query()
            ->with('roles')
            ->whereIn('id', $recipientIds->unique()->filter()->all())
            ->get();

        foreach ($users as $user) {
            InAppNotification::query()->create([
                'user_id' => $user->id,
                'event' => $event,
                'title' => $title,
                'body' => $payload['body'] ?? $body,
                'url' => $this->urlFor($user, $payload),
            ]);
        }
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function urlFor(User $user, array $payload): ?string
    {
        if (! empty($payload['url'])) {
            return $payload['url'];
        }

        if ($user->isInternalStaff() && ! empty($payload['clinic_id'])) {
            return route('admin.clinics.show', $payload['clinic_id']);
        }

        if ($user->isClinicStaff()) {
            if (! empty($payload['lab_order_id'])) {
                return route('clinic.lab-orders.index');
            }

            return route('clinic.queue.index');
        }

        if (! empty($payload['booking_id']) || ! empty($payload['lab_order_id'])) {
            return route('appointments.index');
        }

        return null;
    }
}
