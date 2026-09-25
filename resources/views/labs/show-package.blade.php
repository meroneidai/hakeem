<x-layouts.public :title="$package->name">
    <x-catalog-hero :title="$package->name" :subtitle="__('labs.packages')">
        <x-slot:crumbs>
            <a href="{{ route('home') }}" class="hover:text-primary-700">{{ __('discover.nav.home') }}</a>
            <span aria-hidden="true">·</span>
            <a href="{{ route('labs.index') }}" class="hover:text-primary-700">{{ __('discover.nav.labs') }}</a>
        </x-slot:crumbs>
        <x-slot:actions>
            @if ($package->discountPercent())
                <x-badge tone="accent">-{{ $package->discountPercent() }}%</x-badge>
            @endif
        </x-slot:actions>
    </x-catalog-hero>

    <div class="mx-auto max-w-6xl px-3 py-6 min-[390px]:px-4 sm:py-10">
        <div class="grid items-start gap-6 lg:grid-cols-[minmax(0,1.4fr)_minmax(18rem,0.85fr)] lg:gap-8">
            <div class="overflow-hidden rounded-[1.5rem] bg-white shadow-[0_12px_40px_rgba(15,42,95,0.08)] ring-1 ring-ink-100">
                <div class="relative">
                    @if ($package->image_path)
                        <x-media
                            :src="\App\Support\PublicImage::url($package->image_path)"
                            :alt="$package->name"
                            class="aspect-[16/9] w-full sm:aspect-[2/1]"
                        />
                    @else
                        <div class="grid aspect-[16/9] place-items-center bg-gradient-to-br from-teal-100 via-primary-50 to-white sm:aspect-[2/1]">
                            <x-icon name="beaker" class="size-14 text-teal-600"/>
                        </div>
                    @endif
                    @if ($package->discountPercent())
                        <span class="absolute start-3 top-3 rounded-full bg-accent-500 px-3 py-1 text-xs font-bold text-white shadow-lg">
                            -{{ $package->discountPercent() }}%
                        </span>
                    @endif
                </div>

                <div class="space-y-5 p-4 text-start sm:p-6">
                    @if ($package->description)
                        <p class="text-sm leading-7 text-ink-600 sm:text-base">{{ $package->description }}</p>
                    @endif

                    @if ($package->includes)
                        <section class="rounded-2xl bg-teal-50/80 p-4 ring-1 ring-teal-100">
                            <div class="flex items-center gap-2">
                                <span class="grid size-8 place-items-center rounded-full bg-teal-600 text-white">
                                    <x-icon name="check" class="size-4"/>
                                </span>
                                <h2 class="text-sm font-semibold text-ink-900">{{ __('labs.includes') }}</h2>
                            </div>
                            <p class="mt-2 text-sm leading-6 text-ink-600">{{ $package->includes }}</p>
                        </section>
                    @endif

                    @if ($package->tests->isNotEmpty())
                        <section>
                            <h2 class="mb-3 text-sm font-semibold text-ink-900">{{ __('labs.tests') }}</h2>
                            <ul class="space-y-2">
                                @foreach ($package->tests as $test)
                                    <li class="flex items-center justify-between gap-3 rounded-2xl bg-ink-50 px-3.5 py-3 ring-1 ring-ink-100 transition hover:bg-white hover:ring-primary-200">
                                        <a href="{{ route('labs.tests.show', $test) }}" class="min-w-0 font-medium text-ink-800 hover:text-primary-700">
                                            {{ $test->name }}
                                        </a>
                                        <span class="shrink-0 text-sm tabular text-ink-400">{{ number_format((float) $test->suggested_price) }}</span>
                                    </li>
                                @endforeach
                            </ul>
                        </section>
                    @endif

                    @if ($package->conditions)
                        <section class="rounded-2xl bg-ink-50 p-4 ring-1 ring-ink-100">
                            <h2 class="text-sm font-semibold text-ink-900">{{ __('labs.conditions') }}</h2>
                            <p class="mt-2 text-sm leading-6 text-ink-600">{{ $package->conditions }}</p>
                        </section>
                    @endif

                    @if ($package->preparation)
                        <section class="rounded-2xl bg-warning-50 p-4 ring-1 ring-warning-100">
                            <h2 class="text-sm font-semibold text-ink-900">{{ __('labs.preparation') }}</h2>
                            <p class="mt-2 text-sm leading-6 text-ink-600">{{ $package->preparation }}</p>
                        </section>
                    @endif

                    <x-lab-availability :offerings="$package->clinicOfferings"/>
                </div>
            </div>

            <aside class="space-y-4 lg:sticky lg:top-24">
                <div class="overflow-hidden rounded-[1.5rem] bg-gradient-to-br from-teal-600 via-teal-700 to-primary-900 p-5 text-white shadow-[0_16px_40px_rgba(13,148,136,0.28)]">
                    <p class="text-xs font-medium text-teal-100">{{ __('labs.suggested') }}</p>
                    <div class="mt-2 flex flex-wrap items-end gap-2">
                        <p class="text-3xl font-bold tabular tracking-tight">
                            {{ number_format((float) $package->package_price) }}
                            <span class="text-base font-medium text-teal-100">{{ __('common.currency') }}</span>
                        </p>
                        @if ((float) $package->original_price > (float) $package->package_price)
                            <p class="pb-1 text-sm text-teal-100/80 line-through">
                                {{ number_format((float) $package->original_price) }}
                            </p>
                        @endif
                    </div>
                    @if ($package->savings() > 0)
                        <p class="mt-2 inline-flex rounded-full bg-accent-500/90 px-2.5 py-1 text-xs font-semibold">
                            {{ __('labs.save', ['amount' => number_format($package->savings())]) }}
                        </p>
                    @endif
                </div>

                <div class="rounded-[1.25rem] bg-white p-4 shadow-sm ring-1 ring-ink-100 sm:p-5">
                    <x-lab-cart-button type="package" :id="$package->id" size="lg" class="w-full"/>
                    <x-button :href="route('labs.cart')" variant="secondary" class="mt-3 w-full" size="sm">
                        <x-icon name="bag" class="size-4"/>
                        {{ __('labs.cart.heading') }}
                    </x-button>
                </div>
            </aside>
        </div>
    </div>
</x-layouts.public>
