@php
    $share = \App\Support\Deeplink::forRoute('offers.show', $offer);
    $hasBooking = $offer->clinic
        && $offer->clinic->doctors->isNotEmpty()
        && $offer->clinic->addresses->isNotEmpty();
@endphp

<x-layouts.public :title="$offer->title">
    <x-catalog-hero :title="$offer->title" :subtitle="$offer->category?->label()">
        <x-slot:crumbs>
            <a href="{{ route('home') }}" class="hover:text-primary-700">{{ __('discover.nav.home') }}</a>
            <span aria-hidden="true">·</span>
            <a href="{{ route('offers.index') }}" class="hover:text-primary-700">{{ __('discover.nav.offers') }}</a>
        </x-slot:crumbs>
        <x-slot:actions>
            @if ($offer->discountPercent())
                <x-badge tone="accent">-{{ $offer->discountPercent() }}%</x-badge>
            @endif
            <x-share-button :url="$share['web']" :app-url="$share['app']" :title="$offer->title"/>
        </x-slot:actions>
    </x-catalog-hero>

    <div class="mx-auto max-w-6xl px-3 py-6 min-[390px]:px-4 sm:py-10">
        <div class="grid items-start gap-6 lg:grid-cols-[minmax(0,1.4fr)_minmax(18rem,0.85fr)] lg:gap-8">
            <div class="space-y-5">
                <div class="overflow-hidden rounded-[1.5rem] bg-white shadow-[0_12px_40px_rgba(15,42,95,0.08)] ring-1 ring-ink-100">
                    <div class="relative">
                        @if ($offer->banner_image_path)
                            <x-media
                                :src="\App\Support\PublicImage::url($offer->banner_image_path)"
                                :alt="$offer->title"
                                class="aspect-[16/9] w-full sm:aspect-[2/1]"
                            />
                        @else
                            <div class="grid aspect-[16/9] place-items-center bg-gradient-to-br from-accent-100 via-primary-50 to-white sm:aspect-[2/1]">
                                <x-icon name="megaphone" class="size-14 text-accent-500"/>
                            </div>
                        @endif
                        @if ($offer->discountPercent())
                            <span class="absolute start-3 top-3 rounded-full bg-accent-500 px-3 py-1 text-xs font-bold text-white shadow-lg sm:start-4 sm:top-4 sm:text-sm">
                                -{{ $offer->discountPercent() }}%
                            </span>
                        @endif
                    </div>

                    <div class="space-y-5 p-4 text-start sm:p-6">
                        @if ($offer->description)
                            <p class="text-sm leading-7 text-ink-600 sm:text-base">{{ $offer->description }}</p>
                        @endif

                        @if ($offer->includes)
                            <section class="rounded-2xl bg-primary-50/70 p-4 ring-1 ring-primary-100">
                                <div class="flex items-center gap-2">
                                    <span class="grid size-8 place-items-center rounded-full bg-primary-600 text-white">
                                        <x-icon name="check" class="size-4"/>
                                    </span>
                                    <h2 class="text-sm font-semibold text-ink-900">{{ __('offers.includes') }}</h2>
                                </div>
                                <p class="mt-2 text-sm leading-6 text-ink-600">{{ $offer->includes }}</p>
                            </section>
                        @endif

                        @if ($offer->conditions)
                            <section class="rounded-2xl bg-ink-50 p-4 ring-1 ring-ink-100">
                                <div class="flex items-center gap-2">
                                    <span class="grid size-8 place-items-center rounded-full bg-ink-200 text-ink-700">
                                        <x-icon name="shield" class="size-4"/>
                                    </span>
                                    <h2 class="text-sm font-semibold text-ink-900">{{ __('offers.conditions') }}</h2>
                                </div>
                                <p class="mt-2 text-sm leading-6 text-ink-600">{{ $offer->conditions }}</p>
                            </section>
                        @endif

                        @if ($offer->clinic)
                            <section class="rounded-2xl border border-ink-100 bg-white p-4">
                                <div class="flex items-center gap-2">
                                    <span class="grid size-8 place-items-center rounded-full bg-teal-50 text-teal-700">
                                        <x-icon name="building" class="size-4"/>
                                    </span>
                                    <h2 class="text-sm font-semibold text-ink-900">{{ __('offers.clinic') }}</h2>
                                </div>
                                <a href="{{ route('clinics.show', $offer->clinic) }}" class="mt-2 block text-sm font-semibold text-primary-700 hover:text-primary-800">
                                    {{ $offer->clinic->name }}
                                </a>
                                <x-rating class="mt-1" :average="$offer->clinic->ratingAverage()" :count="$offer->clinic->ratingCount()"/>
                                <div class="mt-3 space-y-2">
                                    @foreach ($offer->clinic->addresses as $address)
                                        <p class="text-sm text-ink-500">
                                            <span class="font-medium text-ink-700">{{ $address->displayName() }}</span>
                                            @if ($address->address_line)
                                                — {{ $address->address_line }}
                                            @endif
                                            @if ($address->hasMapPin())
                                                <a href="{{ $address->mapsUrl() }}" target="_blank" rel="noopener" class="ms-1 font-medium text-primary-600">
                                                    {{ __('clinic.addresses.map') }}
                                                </a>
                                            @endif
                                        </p>
                                    @endforeach
                                </div>
                            </section>
                        @endif
                    </div>
                </div>
            </div>

            <aside class="space-y-4 lg:sticky lg:top-24">
                <div class="overflow-hidden rounded-[1.5rem] bg-gradient-to-br from-primary-700 via-primary-800 to-primary-950 p-5 text-white shadow-[0_16px_40px_rgba(30,64,175,0.28)]">
                    <p class="text-xs font-medium text-primary-200">{{ __('offers.now') }}</p>
                    <div class="mt-2 flex flex-wrap items-end gap-2">
                        @if ($offer->offer_price)
                            <p class="text-3xl font-bold tabular tracking-tight">
                                {{ number_format((float) $offer->offer_price) }}
                                <span class="text-base font-medium text-primary-100">{{ __('common.currency') }}</span>
                            </p>
                        @endif
                        @if ($offer->original_price)
                            <p class="pb-1 text-sm text-primary-200 line-through">
                                {{ number_format((float) $offer->original_price) }}
                            </p>
                        @endif
                    </div>
                    @if ($offer->savings() > 0)
                        <p class="mt-2 inline-flex rounded-full bg-accent-500/90 px-2.5 py-1 text-xs font-semibold">
                            {{ __('offers.save', ['amount' => number_format($offer->savings())]) }}
                        </p>
                    @endif
                    <p class="mt-4 flex items-center gap-1.5 text-xs text-primary-100">
                        <x-icon name="clock" class="size-3.5"/>
                        {{ __('offers.until', ['date' => $offer->ends_at->translatedFormat('d M Y')]) }}
                    </p>
                </div>

                @if ($hasBooking)
                    @include('offers._book-form')
                @elseif (! auth()->check())
                    <div class="rounded-[1.25rem] bg-white p-5 text-start shadow-sm ring-1 ring-ink-100">
                        <p class="text-sm text-ink-500">{{ __('booking.login_first') }}</p>
                        <x-button :href="route('login')" variant="accent" class="mt-3 w-full">{{ __('auth.login') }}</x-button>
                    </div>
                @endif
            </aside>
        </div>
    </div>
</x-layouts.public>
