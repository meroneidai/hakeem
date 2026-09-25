@props(['clinic'])

@php
    $city = $clinic->primaryAddress?->city?->name
        ?? $clinic->addresses->pluck('city.name')->filter()->unique()->first();
    $fromPrice = $clinic->relationLoaded('services')
        ? $clinic->services->filter->is_active->map->effectivePrice()->filter(fn ($price) => $price > 0)->min()
        : null;
    $doctorsCount = $clinic->doctors_count
        ?? ($clinic->relationLoaded('doctors') ? $clinic->doctors->count() : null);
    $bookDoctor = $clinic->relationLoaded('doctors') ? $clinic->doctors->first() : null;
    $bookUrl = $bookDoctor
        ? route('book.doctors.create', $bookDoctor)
        : route('clinics.show', $clinic);
    $share = \App\Support\Deeplink::forRoute('clinics.show', $clinic);
@endphp

<article {{ $attributes->merge(['class' => '@container/card card flex h-full flex-col p-3 text-start sm:p-4']) }}>
    <a href="{{ route('clinics.show', $clinic) }}" class="flex min-w-0 items-start gap-2.5 hover:opacity-95 sm:gap-3">
        <x-media
            :src="\App\Support\PublicImage::url($clinic->logo_path)"
            :alt="$clinic->name"
            class="size-12 shrink-0 rounded-2xl sm:size-14"
        />
        <div class="min-w-0 flex-1">
            <h3 class="line-clamp-2 text-sm font-semibold text-ink-900 sm:text-base">{{ $clinic->name }}</h3>
            <p class="truncate text-xs text-ink-500 sm:text-sm">{{ $city }}</p>
            <x-rating class="mt-1" :average="$clinic->ratingAverage()" :count="$clinic->ratingCount()"/>
            <div class="mt-2 flex flex-wrap gap-1.5 text-xs text-ink-500">
                @if ($doctorsCount)
                    <span class="rounded-full bg-ink-50 px-2 py-0.5">{{ __('discover.doctors.count', ['count' => $doctorsCount]) }}</span>
                @endif
                @if ($fromPrice)
                    <span class="rounded-full bg-primary-50 px-2 py-0.5 font-medium text-primary-700">
                        {{ __('discover.from_price', ['price' => number_format((float) $fromPrice)]) }}
                    </span>
                @endif
            </div>
            @if ($clinic->isVerified())
                <x-badge tone="success" class="mt-2">{{ __('discover.verified') }}</x-badge>
            @endif
        </div>
    </a>
    <div class="mt-auto flex flex-col gap-1.5 pt-3 @[17rem]/card:flex-row sm:flex-row sm:gap-2 sm:pt-4">
        <x-button :href="$bookUrl" variant="accent" size="sm" class="w-full flex-1 px-2 text-xs sm:px-3 sm:text-sm">
            <x-icon name="calendar" class="size-4 shrink-0"/>
            <span class="truncate">{{ __('discover.book_now') }}</span>
        </x-button>
        <x-share-button :url="$share['web']" :app-url="$share['app']" :title="$clinic->name"/>
    </div>
</article>
