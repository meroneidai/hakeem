@props(['offer'])

@php
    $share = \App\Support\Deeplink::forRoute('offers.show', $offer);
@endphp

<article {{ $attributes->merge(['class' => 'card flex flex-col overflow-hidden p-0 text-start']) }}>
    <a href="{{ route('offers.show', $offer) }}" class="block hover:opacity-95">
        <div class="relative">
            @if ($offer->banner_image_path)
                <x-media
                    :src="\App\Support\PublicImage::url($offer->banner_image_path)"
                    :alt="$offer->title"
                    class="aspect-[16/10] w-full rounded-none"
                />
            @else
                <div class="grid aspect-[16/10] place-items-center bg-gradient-to-br from-accent-100 to-primary-50">
                    <x-icon name="megaphone" class="size-10 text-accent-500"/>
                </div>
            @endif
            @if ($offer->discountPercent())
                <span class="absolute start-3 top-3 rounded-full bg-accent-500 px-2.5 py-1 text-xs font-bold text-white shadow">
                    -{{ $offer->discountPercent() }}%
                </span>
            @endif
        </div>
        <div class="p-4">
            <x-badge tone="accent">{{ $offer->category?->label() ?? __('admin.promotions.statuses.running') }}</x-badge>
            <h2 class="mt-2 line-clamp-2 font-semibold text-ink-900">{{ $offer->title }}</h2>
            @if ($offer->includes)
                <p class="mt-1 line-clamp-2 text-sm text-ink-500">{{ $offer->includes }}</p>
            @endif
            @if ($offer->offer_price)
                <p class="mt-3 flex flex-wrap items-baseline gap-2">
                    <span class="text-lg font-bold tabular text-primary-800">
                        {{ number_format((float) $offer->offer_price) }}
                        <span class="text-xs font-medium text-ink-500">{{ __('common.currency') }}</span>
                    </span>
                    @if ($offer->original_price)
                        <span class="text-sm text-ink-400 line-through">{{ number_format((float) $offer->original_price) }}</span>
                    @endif
                </p>
            @endif
            <x-rating class="mt-2" :average="$offer->clinic?->ratingAverage()" :count="$offer->clinic?->ratingCount() ?? 0"/>
            @if ($offer->clinic)
                <p class="mt-2 truncate text-xs text-ink-400">{{ $offer->clinic->name }}</p>
            @endif
        </div>
    </a>
    <div class="mt-auto flex gap-2 border-t border-ink-50 p-4 pt-3">
        <x-button :href="route('offers.show', $offer)" variant="accent" size="sm" class="flex-1">
            <x-icon name="calendar" class="size-4"/>
            {{ __('discover.book_now') }}
        </x-button>
        <x-share-button :url="$share['web']" :app-url="$share['app']" :title="$offer->title"/>
    </div>
</article>
