<x-layouts.admin :title="__('admin.analytics.heading')">
    <x-page-header :title="__('admin.analytics.heading')" :subtitle="__('admin.analytics.subheading')"/>

    @can(\App\Enums\Permission::ManagePaymentSettings->value)
        <x-card :title="__('admin.analytics.google')" class="mb-6">
            <p class="mb-4 text-sm text-ink-500">{{ __('admin.analytics.google_hint') }}</p>
            <form method="POST" action="{{ route('admin.analytics.update') }}" class="grid gap-4 sm:grid-cols-2">
                @csrf
                @method('PUT')
                <x-field :label="__('admin.analytics.ga_id')" name="analytics.ga_measurement_id" :hint="__('admin.analytics.ga_hint')">
                    <x-input name="analytics[ga_measurement_id]" :value="$trackingValues['analytics.ga_measurement_id']" dir="ltr" placeholder="G-XXXXXXXX"/>
                </x-field>
                <x-field :label="__('admin.analytics.gtm_id')" name="analytics.gtm_container_id" :hint="__('admin.analytics.gtm_hint')">
                    <x-input name="analytics[gtm_container_id]" :value="$trackingValues['analytics.gtm_container_id']" dir="ltr" placeholder="GTM-XXXXXXX"/>
                </x-field>
                <x-field :label="__('admin.analytics.ads_id')" name="analytics.google_ads_id">
                    <x-input name="analytics[google_ads_id]" :value="$trackingValues['analytics.google_ads_id']" dir="ltr" placeholder="AW-000000000"/>
                </x-field>
                <x-field :label="__('admin.analytics.gsc_id')" name="analytics.search_console_verification" :hint="__('admin.analytics.gsc_hint')">
                    <x-input name="analytics[search_console_verification]" :value="$trackingValues['analytics.search_console_verification']" dir="ltr"/>
                </x-field>
                <div class="sm:col-span-2 flex items-center justify-between gap-3">
                    <p class="text-sm {{ $tracking->isLive() ? 'text-success-700' : 'text-ink-500' }}">
                        {{ $tracking->isLive() ? __('admin.analytics.tags_live') : __('admin.analytics.tags_missing') }}
                    </p>
                    <x-button variant="accent">{{ __('common.save_changes') }}</x-button>
                </div>
            </form>
        </x-card>
    @endcan

    <div class="mb-6 grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
        <x-stat :label="__('admin.analytics.views_today')" :value="$totals['views_today']"/>
        <x-stat :label="__('admin.analytics.views_30')" :value="$totals['views_30']"/>
        <x-stat :label="__('admin.analytics.visitors_30')" :value="$totals['visitors_30']"/>
        <x-stat :label="__('admin.analytics.bookings_30')" :value="$totals['bookings_30']"/>
        <x-stat :label="__('admin.analytics.confirmed_30')" :value="$totals['confirmed_30']"/>
        <x-stat :label="__('admin.analytics.completed_30')" :value="$totals['completed_30']"/>
        <x-stat :label="__('admin.analytics.lab_orders_30')" :value="$totals['lab_orders_30']"/>
        <x-stat :label="__('admin.analytics.evaluations')" :value="$totals['evaluations_open']"/>
        <x-stat :label="__('admin.analytics.reviews')" :value="$totals['reviews']"/>
        <x-stat :label="__('admin.analytics.complaints')" :value="$totals['complaints_open']"/>
        <x-stat :label="__('admin.analytics.errors_open')" :value="$totals['errors_open']" :href="route('admin.errors.index')"/>
    </div>

    <div class="grid gap-5 lg:grid-cols-2">
        <x-card :title="__('admin.analytics.daily')">
            <div class="space-y-2 text-sm">
                @forelse ($dailyViews as $row)
                    <div class="flex items-center justify-between">
                        <span class="text-ink-500">{{ $row['day'] }}</span>
                        <span class="font-semibold tabular text-ink-900">{{ $row['total'] }}</span>
                    </div>
                @empty
                    <p class="text-sm text-ink-400">{{ __('common.no_results') }}</p>
                @endforelse
            </div>
        </x-card>
        <x-card :title="__('admin.analytics.top_paths')">
            <ul class="space-y-2 text-sm">
                @forelse ($topPaths as $row)
                    <li class="flex justify-between gap-3">
                        <span class="truncate dir-ltr" dir="ltr">{{ $row->path }}</span>
                        <span class="tabular">{{ $row->total }}</span>
                    </li>
                @empty
                    <li class="text-ink-400">{{ __('common.no_results') }}</li>
                @endforelse
            </ul>
        </x-card>
        <x-card :title="__('admin.analytics.top_referrers')">
            <ul class="space-y-2 text-sm">
                @forelse ($topReferrers as $row)
                    <li class="flex justify-between gap-3">
                        <span dir="ltr">{{ $row['host'] }}</span>
                        <span class="tabular">{{ $row['total'] }}</span>
                    </li>
                @empty
                    <li class="text-ink-400">{{ __('admin.analytics.direct_only') }}</li>
                @endforelse
            </ul>
        </x-card>
        <x-card :title="__('admin.analytics.top_clinics')">
            <ul class="space-y-2 text-sm">
                @forelse ($insights['top_clinics'] as $clinic)
                    <li class="flex justify-between"><span>{{ $clinic->name }}</span><span class="tabular">{{ $clinic->period_bookings_count }}</span></li>
                @empty
                    <li class="text-ink-400">{{ __('common.no_results') }}</li>
                @endforelse
            </ul>
        </x-card>
        <x-card :title="__('admin.analytics.top_services')">
            <ul class="space-y-2 text-sm">
                @forelse ($insights['top_services'] as $service)
                    <li class="flex justify-between"><span>{{ $service->name }}</span><span class="tabular">{{ $service->period_bookings_count }}</span></li>
                @empty
                    <li class="text-ink-400">{{ __('common.no_results') }}</li>
                @endforelse
            </ul>
        </x-card>
        <x-card :title="__('admin.analytics.top_doctors')">
            <ul class="space-y-2 text-sm">
                @forelse ($insights['top_doctors'] as $doctor)
                    <li class="flex justify-between"><span>{{ $doctor->name }}</span><span class="tabular">{{ $doctor->period_bookings_count }}</span></li>
                @empty
                    <li class="text-ink-400">{{ __('common.no_results') }}</li>
                @endforelse
            </ul>
        </x-card>
        <x-card :title="__('admin.analytics.top_labs')">
            <ul class="space-y-2 text-sm">
                @forelse ($insights['top_labs'] as $lab)
                    <li class="flex justify-between"><span>{{ $lab['name'] }}</span><span class="tabular">{{ $lab['total'] }}</span></li>
                @empty
                    <li class="text-ink-400">{{ __('common.no_results') }}</li>
                @endforelse
            </ul>
        </x-card>
    </div>
</x-layouts.admin>
