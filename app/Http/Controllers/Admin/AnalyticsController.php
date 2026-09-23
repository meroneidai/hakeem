<?php

namespace App\Http\Controllers\Admin;

use App\Enums\BookingStatus;
use App\Enums\Permission;
use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\ExceptionReport;
use App\Models\LabOrder;
use App\Models\PageView;
use App\Models\Review;
use App\Models\SupportTicket;
use App\Services\MarketplaceInsights;
use App\Support\Audit;
use App\Support\Settings;
use App\Support\TrackingTags;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

class AnalyticsController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            'can:'.Permission::ViewAnalytics->value,
        ];
    }

    public function __invoke(MarketplaceInsights $insights, TrackingTags $tracking): View
    {
        $since = now()->subDays(30);
        $views = PageView::query()->where('created_at', '>=', $since);

        return view('admin.analytics.index', [
            'totals' => [
                'views_30' => (clone $views)->count(),
                'visitors_30' => (clone $views)->select('ip_hash')->distinct()->count(),
                'views_today' => PageView::query()->whereDate('created_at', now()->toDateString())->count(),
                'bookings_30' => Booking::query()->where('created_at', '>=', $since)->count(),
                'confirmed_30' => Booking::query()
                    ->where('created_at', '>=', $since)
                    ->whereIn('status', [
                        BookingStatus::Confirmed,
                        BookingStatus::InProgress,
                        BookingStatus::Completed,
                    ])
                    ->count(),
                'completed_30' => Booking::query()
                    ->where('created_at', '>=', $since)
                    ->where('status', BookingStatus::Completed)
                    ->count(),
                'lab_orders_30' => LabOrder::query()->where('created_at', '>=', $since)->count(),
                'evaluations_open' => Booking::query()
                    ->where('is_evaluation', true)
                    ->whereIn('status', [
                        BookingStatus::Pending->value,
                        BookingStatus::Confirmed->value,
                        BookingStatus::InProgress->value,
                    ])
                    ->count(),
                'reviews' => Review::query()->visible()->count(),
                'complaints_open' => SupportTicket::query()
                    ->unresolved()
                    ->where('category', 'complaint')
                    ->count(),
                'errors_open' => ExceptionReport::query()->whereNull('resolved_at')->count(),
            ],
            'insights' => $insights->snapshot($since),
            'topPaths' => PageView::query()
                ->where('created_at', '>=', $since)
                ->selectRaw('path, count(*) as total')
                ->groupBy('path')
                ->orderByDesc('total')
                ->limit(12)
                ->get(),
            'topReferrers' => $this->topReferrers($since),
            'dailyViews' => PageView::query()
                ->where('created_at', '>=', now()->subDays(13)->startOfDay())
                ->selectRaw('date(created_at) as day, count(*) as total')
                ->groupBy('day')
                ->orderBy('day')
                ->get()
                ->map(fn ($row) => [
                    'day' => Carbon::parse($row->day)->toDateString(),
                    'total' => (int) $row->total,
                ]),
            'tracking' => $tracking,
            'trackingValues' => $tracking->values(),
        ]);
    }

    public function update(Request $request, Settings $settings): RedirectResponse
    {
        abort_unless($request->user()?->can(Permission::ManagePaymentSettings->value), 403);

        $request->merge([
            'analytics' => [
                'ga_measurement_id' => $this->cleanId($request->input('analytics.ga_measurement_id')),
                'gtm_container_id' => $this->cleanId($request->input('analytics.gtm_container_id')),
                'google_ads_id' => $this->cleanId($request->input('analytics.google_ads_id')),
                'search_console_verification' => $this->cleanId($request->input('analytics.search_console_verification')),
            ],
        ]);

        $data = $request->validate([
            'analytics.ga_measurement_id' => ['nullable', 'string', 'max:32', 'regex:/^G-[A-Z0-9]+$/i'],
            'analytics.gtm_container_id' => ['nullable', 'string', 'max:32', 'regex:/^GTM-[A-Z0-9]+$/i'],
            'analytics.google_ads_id' => ['nullable', 'string', 'max:32', 'regex:/^AW-[0-9]+$/'],
            'analytics.search_console_verification' => ['nullable', 'string', 'max:120', 'regex:/^[A-Za-z0-9_-]+$/'],
        ]);

        $analytics = $data['analytics'] ?? [];

        $settings->setMany([
            'analytics.ga_measurement_id' => $analytics['ga_measurement_id'] ?? null,
            'analytics.gtm_container_id' => $analytics['gtm_container_id'] ?? null,
            'analytics.google_ads_id' => $analytics['google_ads_id'] ?? null,
            'analytics.search_console_verification' => $analytics['search_console_verification'] ?? null,
        ], 'analytics');

        Audit::log('settings.analytics_updated', changes: $analytics);

        return back()->with('status', __('admin.analytics.saved'));
    }

    /**
     * @return list<array{host: string, total: int}>
     */
    private function topReferrers(Carbon $since): array
    {
        $rows = PageView::query()
            ->where('created_at', '>=', $since)
            ->whereNotNull('referrer')
            ->where('referrer', '!=', '')
            ->selectRaw('referrer, count(*) as total')
            ->groupBy('referrer')
            ->orderByDesc('total')
            ->limit(40)
            ->get();

        return $rows
            ->map(function ($row): array {
                $host = parse_url((string) $row->referrer, PHP_URL_HOST);

                return [
                    'host' => is_string($host) && $host !== '' ? $host : (string) $row->referrer,
                    'total' => (int) $row->total,
                ];
            })
            ->groupBy('host')
            ->map(fn ($group, $host) => [
                'host' => (string) $host,
                'total' => (int) $group->sum('total'),
            ])
            ->sortByDesc('total')
            ->take(10)
            ->values()
            ->all();
    }

    private function cleanId(mixed $value): ?string
    {
        $id = is_string($value) ? trim($value) : '';

        return $id === '' ? null : $id;
    }
}
