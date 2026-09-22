<?php

namespace App\Services;

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\Specialty;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class SiteAgent
{
    public function __construct(
        private MarketplaceSearch $search,
        private BookingManager $bookings,
    ) {}

    /**
     * @return array{
     *     conversation_id: string,
     *     reply: string,
     *     intent: string,
     *     results: array<string, mixed>,
     *     actions: list<array{type: string, label: string, url?: string}>
     * }
     */
    public function reply(string $message, ?User $user, ?string $conversationId = null): array
    {
        $conversationId = $conversationId ?: (string) Str::uuid();
        $state = Cache::get($this->cacheKey($conversationId), []);
        $text = trim($message);

        if ($text === '') {
            return $this->payload($conversationId, 'help', __('agent.empty'), []);
        }

        if (($state['awaiting'] ?? null) === 'cancel_confirm') {
            return $this->confirmCancel($conversationId, $text, $user, (int) ($state['booking_id'] ?? 0));
        }

        $intent = $this->detectIntent($text);

        return match ($intent) {
            'appointments' => $this->appointments($conversationId, $user),
            'cancel' => $this->startCancel($conversationId, $user),
            'search_clinic' => $this->runSearch($conversationId, $text, 'clinics', __('agent.clinics_found')),
            'search_offer' => $this->runSearch($conversationId, $text, 'offers', __('agent.offers_found')),
            'search_service' => $this->runSearch($conversationId, $text, 'services', __('agent.services_found')),
            'help' => $this->payload($conversationId, 'help', __('agent.help'), [], [
                ['type' => 'link', 'label' => __('discover.search_doctors'), 'url' => route('search')],
                ['type' => 'link', 'label' => __('discover.nav.book'), 'url' => route('doctors.index')],
            ]),
            default => $this->searchDoctors($conversationId, $text),
        };
    }

    private function detectIntent(string $text): string
    {
        $normalized = mb_strtolower($text);

        if (preg_match('/\b(help|مساعد|كيف|what can)\b/u', $normalized)) {
            return 'help';
        }

        if (preg_match('/(الغ|إلغ|cancel)/u', $normalized)) {
            return 'cancel';
        }

        if (preg_match('/(مواعيدي|حجوزاتي|appointments?|my booking)/u', $normalized)) {
            return 'appointments';
        }

        if (preg_match('/(عيادة|مركز|clinic)/u', $normalized)) {
            return 'search_clinic';
        }

        if (preg_match('/(عرض|عروض|offer)/u', $normalized)) {
            return 'search_offer';
        }

        if (preg_match('/(تحليل|معمل|زيارة منزل|فيديو|lab|home visit|video)/u', $normalized)) {
            return 'search_service';
        }

        return 'search_doctor';
    }

    /**
     * @return array<string, mixed>
     */
    private function searchDoctors(string $conversationId, string $text): array
    {
        $specialty = Specialty::query()
            ->active()
            ->get()
            ->first(fn (Specialty $item) => mb_stripos($text, $item->name_ar) !== false
                || mb_stripos($text, $item->name_en) !== false);

        $filters = [
            'q' => $specialty?->name ?? $text,
            'type' => 'doctors',
            'specialty' => $specialty?->slug,
            'limit' => 5,
        ];

        $payload = $this->search->toPayload($filters);
        $count = count($payload['doctors']);

        $reply = $count > 0
            ? __('agent.doctors_found', ['count' => $count])
            : __('agent.no_results');

        $actions = collect($payload['doctors'])->take(3)->map(fn (array $doctor) => [
            'type' => 'link',
            'label' => __('discover.book_now').' — '.$doctor['name'],
            'url' => $doctor['book_url'],
        ])->values()->all();

        $actions[] = ['type' => 'link', 'label' => __('discover.search.heading'), 'url' => route('search', ['q' => $filters['q']])];

        return $this->payload($conversationId, 'search_doctor', $reply, $payload, $actions);
    }

    /**
     * @return array<string, mixed>
     */
    private function runSearch(string $conversationId, string $text, string $type, string $foundMessage): array
    {
        $payload = $this->search->toPayload(['q' => $text, 'type' => $type, 'limit' => 5]);
        $count = $payload['counts'][$type] ?? 0;

        return $this->payload(
            $conversationId,
            'search_'.$type,
            $count > 0 ? $foundMessage : __('agent.no_results'),
            $payload,
            [['type' => 'link', 'label' => __('discover.search.heading'), 'url' => route('search', ['q' => $text, 'type' => $type])]],
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function appointments(string $conversationId, ?User $user): array
    {
        if (! $user) {
            return $this->payload($conversationId, 'auth', __('agent.login_required'), [], [
                ['type' => 'link', 'label' => __('auth.login'), 'url' => route('login')],
            ]);
        }

        $bookings = Booking::query()
            ->whereBelongsTo($user, 'patient')
            ->with(['doctor', 'clinic', 'serviceType'])
            ->orderByDesc('scheduled_at')
            ->limit(5)
            ->get();

        if ($bookings->isEmpty()) {
            return $this->payload($conversationId, 'appointments', __('agent.no_appointments'), [], [
                ['type' => 'link', 'label' => __('discover.nav.book'), 'url' => route('doctors.index')],
            ]);
        }

        $lines = $bookings->map(function (Booking $booking) {
            return '#'.$booking->id.' · '.$booking->doctor?->name.' · '.$booking->status->label().' · '.$booking->scheduled_at?->timezone('Africa/Cairo')->format('Y-m-d H:i');
        })->implode("\n");

        return $this->payload($conversationId, 'appointments', __('agent.appointments')."\n".$lines, [
            'appointments' => $bookings->map(fn (Booking $booking) => [
                'id' => $booking->id,
                'status' => $booking->status->value,
                'doctor' => $booking->doctor?->name,
                'clinic' => $booking->clinic?->name,
                'scheduled_at' => $booking->scheduled_at?->toIso8601String(),
            ])->all(),
        ], [
            ['type' => 'link', 'label' => __('booking.my_appointments'), 'url' => route('appointments.index')],
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function startCancel(string $conversationId, ?User $user): array
    {
        if (! $user) {
            return $this->payload($conversationId, 'auth', __('agent.login_required'), [], [
                ['type' => 'link', 'label' => __('auth.login'), 'url' => route('login')],
            ]);
        }

        $booking = Booking::query()
            ->whereBelongsTo($user, 'patient')
            ->whereIn('status', [BookingStatus::Pending->value, BookingStatus::Confirmed->value])
            ->orderByDesc('scheduled_at')
            ->first();

        if (! $booking) {
            return $this->payload($conversationId, 'cancel', __('agent.nothing_to_cancel'), []);
        }

        Cache::put($this->cacheKey($conversationId), [
            'awaiting' => 'cancel_confirm',
            'booking_id' => $booking->id,
        ], now()->addMinutes(30));

        return $this->payload($conversationId, 'cancel_confirm', __('agent.confirm_cancel', [
            'id' => $booking->id,
            'doctor' => $booking->doctor?->name ?? '',
        ]), []);
    }

    /**
     * @return array<string, mixed>
     */
    private function confirmCancel(string $conversationId, string $text, ?User $user, int $bookingId): array
    {
        Cache::forget($this->cacheKey($conversationId));

        if (! preg_match('/^(نعم|أيوه|ايوه|yes|y|ok|أكد|تاكيد)$/iu', trim($text))) {
            return $this->payload($conversationId, 'cancel_aborted', __('agent.cancel_aborted'), []);
        }

        if (! $user || $bookingId < 1) {
            return $this->payload($conversationId, 'auth', __('agent.login_required'), []);
        }

        $booking = Booking::query()
            ->whereBelongsTo($user, 'patient')
            ->whereKey($bookingId)
            ->first();

        if (! $booking) {
            return $this->payload($conversationId, 'cancel', __('agent.nothing_to_cancel'), []);
        }

        try {
            $this->bookings->transition($booking, BookingStatus::Cancelled, $user);
        } catch (ValidationException) {
            return $this->payload($conversationId, 'cancel', __('agent.cannot_cancel'), []);
        }

        return $this->payload($conversationId, 'cancelled', __('agent.cancelled', ['id' => $booking->id]), []);
    }

    /**
     * @param  array<string, mixed>  $results
     * @param  list<array{type: string, label: string, url?: string}>  $actions
     * @return array<string, mixed>
     */
    private function payload(string $conversationId, string $intent, string $reply, array $results, array $actions = []): array
    {
        return [
            'conversation_id' => $conversationId,
            'reply' => $reply,
            'intent' => $intent,
            'results' => $results,
            'actions' => $actions,
        ];
    }

    private function cacheKey(string $conversationId): string
    {
        return 'site-agent:'.$conversationId;
    }
}
