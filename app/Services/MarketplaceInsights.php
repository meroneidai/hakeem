<?php

namespace App\Services;

use App\Enums\BookingStatus;
use App\Enums\LabOrderStatus;
use App\Enums\RoleName;
use App\Models\Booking;
use App\Models\Clinic;
use App\Models\ClinicSubscription;
use App\Models\Doctor;
use App\Models\LabOrder;
use App\Models\LabOrderItem;
use App\Models\ServiceType;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class MarketplaceInsights
{
    /**
     * @return array<string, mixed>
     */
    public function snapshot(?Carbon $since = null): array
    {
        $since ??= now()->subDays(30);

        $bookings = Booking::query()->where('created_at', '>=', $since);
        $labOrders = LabOrder::query()->where('created_at', '>=', $since);

        return [
            'since' => $since->toDateString(),
            'bookings' => (clone $bookings)->count(),
            'completed' => (clone $bookings)->where('status', BookingStatus::Completed)->count(),
            'paid' => (clone $bookings)->where('payment_status', 'paid')->count(),
            'lab_orders' => (clone $labOrders)->count(),
            'lab_completed' => (clone $labOrders)->where('status', LabOrderStatus::Completed)->count(),
            'patients' => User::query()->whereHas('roles', fn ($query) => $query->where('name', RoleName::Patient->value))->count(),
            'new_patients' => User::query()
                ->whereHas('roles', fn ($query) => $query->where('name', RoleName::Patient->value))
                ->where('created_at', '>=', $since)
                ->count(),
            'clinics' => Clinic::query()->count(),
            'active_clinics' => Clinic::query()->listable()->count(),
            'phone_verified' => User::query()->whereNotNull('phone_verified_at')->count(),
            'app_installed' => User::query()->whereNotNull('app_installed_at')->count(),
            'subscription_amount' => (float) ClinicSubscription::query()
                ->where('current_period_start', '>=', $since)
                ->sum('amount'),
            'top_clinics' => $this->topClinics($since),
            'top_doctors' => $this->topDoctors($since),
            'top_services' => $this->topServices($since),
            'top_labs' => $this->topLabs($since),
        ];
    }

    /**
     * @return Collection<int, Clinic>
     */
    public function topClinics(Carbon $since, int $limit = 8): Collection
    {
        return Clinic::query()
            ->withCount(['bookings as period_bookings_count' => fn ($query) => $query->where('created_at', '>=', $since)])
            ->orderByDesc('period_bookings_count')
            ->orderBy('name_ar')
            ->limit($limit)
            ->get();
    }

    /**
     * @return Collection<int, Doctor>
     */
    public function topDoctors(Carbon $since, int $limit = 8): Collection
    {
        return Doctor::query()
            ->with('specialty')
            ->withCount(['bookings as period_bookings_count' => fn ($query) => $query->where('created_at', '>=', $since)])
            ->orderByDesc('period_bookings_count')
            ->orderBy('name_ar')
            ->limit($limit)
            ->get();
    }

    /**
     * @return Collection<int, ServiceType>
     */
    public function topServices(Carbon $since, int $limit = 8): Collection
    {
        return ServiceType::query()
            ->withCount(['bookings as period_bookings_count' => fn ($query) => $query->where('created_at', '>=', $since)])
            ->orderByDesc('period_bookings_count')
            ->ordered()
            ->limit($limit)
            ->get();
    }

    /**
     * @return Collection<int, array{name: string, total: int}>
     */
    public function topLabs(?Carbon $since = null, int $limit = 8): Collection
    {
        $since ??= now()->subDays(30);

        $items = LabOrderItem::query()
            ->whereHas('order', function ($query) use ($since) {
                $query->where('created_at', '>=', $since)
                    ->where('status', '!=', LabOrderStatus::Cancelled);
            })
            ->with(['labTest', 'labPackage'])
            ->get()
            ->groupBy(function (LabOrderItem $item): string {
                return $item->item_type.':'.($item->lab_test_id ?? $item->lab_package_id);
            })
            ->map(function (Collection $rows) {
                $first = $rows->first();

                return [
                    'name' => $first?->catalogItem()?->name ?? '#'.($first?->lab_test_id ?? $first?->lab_package_id),
                    'total' => $rows->sum('qty'),
                ];
            })
            ->sortByDesc('total')
            ->take($limit)
            ->values();

        return $items;
    }
}
