<?php

namespace App\Support;

use App\Enums\BookingStatus;
use App\Enums\OfferApprovalStatus;
use App\Enums\VerificationStatus;
use App\Models\AgentConversation;
use App\Models\Booking;
use App\Models\Clinic;
use App\Models\Promotion;
use App\Models\SupportTicket;

final class AdminNavBadges
{
    /**
     * Counts of items that still need admin attention.
     *
     * @return array{bookings: int, promotions: int, support: int, agent: int, clinics: int}
     */
    public function all(): array
    {
        return [
            'bookings' => Booking::query()->where('status', BookingStatus::Pending)->count(),
            'promotions' => Promotion::query()->where('approval_status', OfferApprovalStatus::Pending)->count(),
            'support' => SupportTicket::unresolved()->count(),
            'agent' => AgentConversation::query()
                ->where(function ($query) {
                    $query->whereNull('admin_seen_at')
                        ->orWhereColumn('last_message_at', '>', 'admin_seen_at');
                })
                ->count(),
            'clinics' => Clinic::query()->where('verification_status', VerificationStatus::Pending)->count(),
        ];
    }
}
