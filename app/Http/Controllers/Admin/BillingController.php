<?php

namespace App\Http\Controllers\Admin;

use App\Enums\BookingStatus;
use App\Enums\Permission;
use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\ClinicSubscription;
use App\Services\MarketplaceInsights;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\View\View;

class BillingController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return ['can:'.Permission::ManageSubscriptionPlans->value];
    }

    public function __invoke(MarketplaceInsights $insights): View
    {
        $since = now()->subDays(30);

        $subscriptions = ClinicSubscription::query()
            ->with(['clinic', 'plan', 'discountCode'])
            ->latest('current_period_start')
            ->paginate(25);

        return view('admin.billing.index', [
            'subscriptions' => $subscriptions,
            'totals' => [
                'subscription_amount' => (float) ClinicSubscription::query()->sum('amount'),
                'period_amount' => (float) ClinicSubscription::query()
                    ->where('current_period_start', '>=', $since)
                    ->sum('amount'),
                'paid_bookings' => Booking::query()
                    ->where('created_at', '>=', $since)
                    ->where('payment_status', 'paid')
                    ->count(),
                'completed_bookings' => Booking::query()
                    ->where('created_at', '>=', $since)
                    ->where('status', BookingStatus::Completed)
                    ->count(),
            ],
            'insights' => $insights->snapshot($since),
        ]);
    }
}
