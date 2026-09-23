@php
    $tones = ['running' => 'success', 'scheduled' => 'primary', 'expired' => 'neutral', 'inactive' => 'warning'];
@endphp

<x-layouts.admin :title="__('admin.promotions.heading')">
    <x-page-header :title="__('admin.promotions.heading')" :subtitle="__('admin.promotions.subheading')">
        <x-slot:actions>
            <x-button :href="route('admin.promotions.index', ['state' => 'running'])" variant="secondary" size="sm">
                {{ __('admin.promotions.statuses.running') }}
            </x-button>
            <x-button :href="route('admin.promotions.index')" variant="ghost" size="sm">{{ __('common.all') }}</x-button>
            <x-button :href="route('admin.promotions.create')" variant="accent">
                <x-icon name="plus" class="size-4"/>
                {{ __('admin.promotions.create') }}
            </x-button>
        </x-slot:actions>
    </x-page-header>

    <x-table>
        <x-slot:head>
            <x-th>{{ __('common.title_ar') }}</x-th>
            <x-th>{{ __('admin.promotions.scope') }}</x-th>
            <x-th>{{ __('admin.promotions.discount_value') }}</x-th>
            <x-th>{{ __('admin.promotions.starts_at') }}</x-th>
            <x-th>{{ __('admin.promotions.ends_at') }}</x-th>
            <x-th>{{ __('common.status') }}</x-th>
            <x-th class="text-end">{{ __('common.actions') }}</x-th>
        </x-slot:head>

        @forelse ($promotions as $promotion)
            <tr>
                <x-td class="font-medium text-ink-900">
                    <span class="flex items-start gap-2">
                        <x-media
                            :src="\App\Support\PublicImage::url($promotion->banner_image_path)"
                            :alt="$promotion->title_ar"
                            class="size-10 rounded-lg"
                        />
                        <span>
                            {{ $promotion->title_ar }}
                            @if ($promotion->is_featured)
                                <x-badge tone="accent" class="ms-1">{{ __('common.featured') }}</x-badge>
                            @endif
                            @if ($promotion->specialty || $promotion->serviceType)
                                <span class="mt-0.5 block text-xs text-ink-500">
                                    {{ collect([$promotion->specialty?->name, $promotion->serviceType?->name])->filter()->join(' · ') }}
                                </span>
                            @endif
                        </span>
                    </span>
                </x-td>
                <x-td class="text-sm">
                    {{ $promotion->isPlatformWide() ? __('admin.promotions.platform_wide') : __('admin.promotions.clinic_scoped') }}
                </x-td>
                <x-td class="tabular text-sm font-medium">
                    @if ($promotion->discount_type === 'custom')
                        {{ $promotion->discount_details }}
                    @elseif ($promotion->discount_type === 'percentage')
                        {{ number_format((float) $promotion->discount_value) }}%
                    @else
                        {{ number_format((float) $promotion->discount_value) }} {{ __('common.currency') }}
                    @endif
                </x-td>
                <x-td class="text-sm text-ink-500">{{ $promotion->starts_at->translatedFormat('d M Y') }}</x-td>
                <x-td class="text-sm text-ink-500">{{ $promotion->ends_at->translatedFormat('d M Y') }}</x-td>
                <x-td>
                    <x-badge :tone="$tones[$promotion->status()]">
                        {{ __('admin.promotions.statuses.'.$promotion->status()) }}
                    </x-badge>
                </x-td>
                <x-td>
                    <x-row-actions :edit="route('admin.promotions.edit', $promotion)"
                                   :destroy="route('admin.promotions.destroy', $promotion)"/>
                </x-td>
            </tr>
        @empty
            <x-empty-state colspan="7"/>
        @endforelse

        @if ($promotions->hasPages())
            <x-slot:footer>{{ $promotions->links() }}</x-slot:footer>
        @endif
    </x-table>
</x-layouts.admin>
