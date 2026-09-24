<x-layouts.clinic :title="__('clinic.offers.heading')">
    <x-page-header :title="__('clinic.offers.heading')" :subtitle="__('clinic.offers.subtitle')">
        <x-slot:actions>
            @if ($access->canManage())
                <x-button :href="route('clinic.offers.create')" variant="accent">
                    <x-icon name="plus" class="size-4"/>
                    {{ __('clinic.offers.add') }}
                </x-button>
            @endif
        </x-slot:actions>
    </x-page-header>

    <x-table>
        <x-slot:head>
            <x-th>{{ __('common.title_ar') }}</x-th>
            <x-th>{{ __('admin.promotions.category') }}</x-th>
            <x-th>{{ __('admin.promotions.offer_price') }}</x-th>
            <x-th>{{ __('common.status') }}</x-th>
            <x-th class="text-end">{{ __('common.actions') }}</x-th>
        </x-slot:head>

        @forelse ($offers as $offer)
            <tr>
                <x-td class="font-medium text-ink-900">
                    <span class="flex items-center gap-2">
                        <x-media
                            :src="\App\Support\PublicImage::url($offer->banner_image_path)"
                            :alt="$offer->title_ar"
                            class="size-9 rounded-lg"
                        />
                        <span>
                            {{ $offer->title_ar }}
                            @if ($offer->is_featured)
                                <x-badge tone="accent" class="ms-1">{{ __('common.featured') }}</x-badge>
                            @endif
                        </span>
                    </span>
                </x-td>
                <x-td>{{ $offer->category?->label() }}</x-td>
                <x-td class="tabular text-sm">
                    @if ($offer->offer_price)
                        {{ number_format((float) $offer->offer_price) }} {{ __('common.currency') }}
                    @else
                        —
                    @endif
                </x-td>
                <x-td>
                    <x-badge :tone="['running' => 'success', 'scheduled' => 'primary', 'expired' => 'neutral', 'inactive' => 'warning', 'pending_approval' => 'warning', 'rejected' => 'danger'][$offer->status()] ?? 'neutral'">
                        {{ __('admin.promotions.statuses.'.$offer->status()) }}
                    </x-badge>
                </x-td>
                <x-td>
                    @if ($access->canManage())
                        <x-row-actions :edit="route('clinic.offers.edit', $offer)"
                                       :destroy="route('clinic.offers.destroy', $offer)"/>
                    @endif
                </x-td>
            </tr>
        @empty
            <x-empty-state colspan="5"/>
        @endforelse
    </x-table>
</x-layouts.clinic>
