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
use Illuminate\Support\Facades\Schema;

final class AdminNavBadges
{
    /**
     * Counts of items that still need admin attention.
     *
     * @return array{bookings: int, promotions: int, support: int, agent: int, clinics: int}
     */
    public function all(): array
    {
        $promotionsPending = 0;
        if (Schema::hasColumn((new Promotion)->getTable(), 'approval_status')) {
            $promotionsPending = Promotion::query()->where('approval_status', OfferApprovalStatus::Pending)->count();
        }

        $agentUnread = 0;
        if (Schema::hasTable('agent_conversations')) {
            $agentQuery = AgentConversation::query();
            if (Schema::hasColumn('agent_conversations', 'admin_seen_at')) {
                $agentQuery->where(function ($query) {
                    $query->whereNull('admin_seen_at')
                        ->orWhereColumn('last_message_at', '>', 'admin_seen_at');
                });
            }
            $agentUnread = $agentQuery->count();
        }

        return [
            'bookings' => Booking::query()->where('status', BookingStatus::Pending)->count(),
            'promotions' => $promotionsPending,
            'support' => SupportTicket::unresolved()->count(),
            'agent' => $agentUnread,
            'clinics' => Clinic::query()->where('verification_status', VerificationStatus::Pending)->count(),
        ];
    }
}
