@props(['clinic'])

@php
    $city = $clinic->primaryAddress?->city?->name
        ?? $clinic->addresses->pluck('city.name')->filter()->unique()->first();
    $fromPrice = $clinic->relationLoaded('services')
        ? $clinic->services->filter->is_active->map->effectivePrice()->filter(fn ($price) => $price > 0)->min()
        : null;
@endphp

<a href="{{ route('clinics.show', $clinic) }}" {{ $attributes->merge(['class' => 'card flex items-start gap-3 p-4 hover:ring-2 hover:ring-primary-200']) }}>
    <x-media
        :src="\App\Support\PublicImage::url($clinic->logo_path)"
        :alt="$clinic->name"
        class="size-14 shrink-0 rounded-2xl"
    />
    <div class="min-w-0 flex-1">
        <h3 class="font-semibold text-ink-900">{{ $clinic->name }}</h3>
        <p class="text-sm text-ink-500">{{ $city }}</p>
        <x-rating class="mt-1" :average="$clinic->ratingAverage()" :count="$clinic->ratingCount()"/>
        <div class="mt-2 flex flex-wrap gap-1.5 text-xs text-ink-500">
            @if ($clinic->doctors_count)
                <span class="rounded-full bg-ink-50 px-2 py-0.5">{{ __('discover.doctors.count', ['count' => $clinic->doctors_count]) }}</span>
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
