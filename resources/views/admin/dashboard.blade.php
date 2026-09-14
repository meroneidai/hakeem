<x-layouts.admin :title="__('admin.dashboard.heading')">
    <x-page-header :title="__('admin.dashboard.heading')" :subtitle="__('admin.dashboard.subheading')"/>

    <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
        <x-stat :label="__('admin.dashboard.governorates')" :value="$counts['governorates']" :href="route('admin.governorates.index')"/>
        <x-stat :label="__('admin.dashboard.cities')" :value="$counts['cities']" :href="route('admin.cities.index')"/>
        <x-stat :label="__('admin.dashboard.specialties')" :value="$counts['specialties']" :href="route('admin.specialties.index')"/>
        <x-stat :label="__('admin.dashboard.service_types')" :value="$counts['service_types']" :href="route('admin.service-types.index')"/>
        <x-stat :label="__('admin.dashboard.plans')" :value="$counts['plans']" :href="route('admin.plans.index')" tone="accent"/>
        <x-stat :label="__('admin.dashboard.promotions_running')" :value="$counts['promotions_running']" :href="route('admin.promotions.index')" tone="accent"/>
        <x-stat :label="__('admin.dashboard.open_tickets')" :value="$counts['open_tickets']" :href="route('admin.support.index')" tone="warning"/>
        <x-stat :label="__('admin.dashboard.staff')" :value="$counts['staff']" :href="route('admin.staff.index')" tone="ink"/>
    </div>

    <div class="mt-6 grid gap-5 lg:grid-cols-2">
        <x-card :title="__('admin.dashboard.setup_checklist')" :subtitle="__('admin.dashboard.setup_hint')">
            <ul class="space-y-2.5">
                @foreach ($checklist as $key => $done)
                    <li class="flex items-center gap-2.5 text-sm">
                        <span @class([
                            'grid size-5 shrink-0 place-items-center rounded-full',
                            'bg-success-500 text-white' => $done,
                            'border border-ink-300 text-ink-300' => ! $done,
                        ])>
                            @if ($done)
                                <x-icon name="check" class="size-3"/>
                            @endif
                        </span>
                        <span class="{{ $done ? 'text-ink-500 line-through' : 'font-medium text-ink-800' }}">
                            {{ __("admin.checklist.$key") }}
                        </span>
                    </li>
                @endforeach
            </ul>
        </x-card>

        <x-card :title="__('admin.dashboard.recent_activity')">
            @if ($recentActivity->isEmpty())
                <x-empty-state/>
            @else
                <ul class="divide-y divide-ink-100">
                    @foreach ($recentActivity as $log)
                        <li class="flex items-baseline justify-between gap-3 py-2 text-sm">
                            <span class="min-w-0">
                                <span class="font-medium text-ink-800">{{ $log->user?->name ?? __('admin.audit.system') }}</span>
                                <span class="text-ink-400">·</span>
                                <code class="text-xs text-primary-600">{{ $log->action }}</code>
                            </span>
                            <span class="shrink-0 text-xs text-ink-400">{{ $log->created_at?->diffForHumans() }}</span>
                        </li>
                    @endforeach
                </ul>
            @endif
        </x-card>
    </div>

    <x-card class="mt-5" :title="__('admin.dashboard.coming_soon')" :subtitle="__('admin.dashboard.coming_soon_hint')">
        <div class="grid gap-3 sm:grid-cols-4">
            @foreach (['bookings', 'revenue', 'active_clinics', 'top_doctors'] as $placeholder)
                <div class="rounded-lg border border-dashed border-ink-300 bg-ink-50/50 p-4 text-center">
                    <span class="block text-xl font-semibold text-ink-300">—</span>
                    <span class="mt-1 block text-[11px] uppercase tracking-wide text-ink-400">{{ $placeholder }}</span>
                </div>
            @endforeach
        </div>
    </x-card>
</x-layouts.admin>
