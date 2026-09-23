@props(['offer'])

@php
    $share = \App\Support\Deeplink::forRoute('offers.show', $offer);
@endphp

<article {{ $attributes->merge(['class' => 'card flex flex-col p-4']) }}>
    <a href="{{ route('offers.show', $offer) }}" class="block hover:opacity-95">
        @if ($offer->banner_image_path)
            <x-media
                :src="\App\Support\PublicImage::url($offer->banner_image_path)"
                :alt="$offer->title"
                class="mb-3 h-36 w-full rounded-xl"
            />
        @endif
        <x-badge tone="accent">{{ $offer->category?->label() ?? __('admin.promotions.statuses.running') }}</x-badge>
        <h2 class="mt-2 font-semibold text-ink-900">{{ $offer->title }}</h2>
        <p class="mt-1 text-sm text-ink-500">{{ $offer->includes }}</p>
        @if ($offer->offer_price)
            <p class="mt-3 text-sm">
                @if ($offer->original_price)
                    <span class="text-ink-400 line-through">{{ number_format((float) $offer->original_price) }}</span>
                @endif
                <span class="ms-1 font-semibold text-ink-900">{{ number_format((float) $offer->offer_price) }} {{ __('common.currency') }}</span>
            </p>
        @endif
        <x-rating class="mt-2" :average="$offer->clinic?->ratingAverage()" :count="$offer->clinic?->ratingCount() ?? 0"/>
        @if ($offer->clinic)
            <p class="mt-2 text-xs text-ink-400">{{ $offer->clinic->name }}</p>
        @endif
    </a>
    <div class="mt-auto flex gap-2 pt-4">
        <x-button :href="route('offers.show', $offer)" variant="accent" size="sm" class="flex-1">
            <x-icon name="calendar" class="size-4"/>
            {{ __('discover.book_now') }}
        </x-button>
        <x-share-button :url="$share['web']" :app-url="$share['app']" :title="$offer->title"/>
    </div>
</article>
