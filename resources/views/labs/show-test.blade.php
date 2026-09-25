<x-layouts.public :title="$test->name">
    <x-catalog-hero :title="$test->name" :subtitle="$test->category->label()">
        <x-slot:crumbs>
            <a href="{{ route('home') }}" class="hover:text-primary-700">{{ __('discover.nav.home') }}</a>
            <span aria-hidden="true">·</span>
            <a href="{{ route('labs.index') }}" class="hover:text-primary-700">{{ __('discover.nav.labs') }}</a>
        </x-slot:crumbs>
    </x-catalog-hero>

    <div class="mx-auto max-w-6xl px-3 py-6 min-[390px]:px-4 sm:py-10">
        <div class="grid items-start gap-6 lg:grid-cols-[minmax(0,1.4fr)_minmax(18rem,0.85fr)] lg:gap-8">
            <div class="overflow-hidden rounded-[1.5rem] bg-white shadow-[0_12px_40px_rgba(15,42,95,0.08)] ring-1 ring-ink-100">
                <x-media
                    :src="$test->imageUrl()"
                    :alt="$test->name"
                    class="aspect-[16/9] w-full sm:aspect-[2/1]"
                />

                <div class="space-y-5 p-4 text-start sm:p-6">
                    @if ($test->description)
                        <p class="text-sm leading-7 text-ink-600 sm:text-base">{{ $test->description }}</p>
                    @endif

                    <dl class="grid gap-3 sm:grid-cols-2">
                        <div class="rounded-2xl bg-primary-50/80 p-3.5 ring-1 ring-primary-100">
                            <dt class="text-xs font-medium text-primary-700">{{ __('labs.sample') }}</dt>
                            <dd class="mt-1 text-sm font-semibold text-ink-900">{{ $test->sample_type->label() }}</dd>
                        </div>
                        <div class="rounded-2xl bg-ink-50 p-3.5 ring-1 ring-ink-100">
                            <dt class="text-xs font-medium text-ink-500">{{ __('labs.preparation') }}</dt>
                            <dd class="mt-1 text-sm font-semibold text-ink-900">
                                {{ $test->fasting_hours ? __('labs.fasting', ['hours' => $test->fasting_hours]) : __('labs.no_fasting') }}
                            </dd>
                        </div>
                        @if ($test->turnaround_hours)
                            <div class="rounded-2xl bg-teal-50/80 p-3.5 ring-1 ring-teal-100 sm:col-span-2">
                                <dt class="text-xs font-medium text-teal-700">{{ __('labs.turnaround', ['hours' => $test->turnaround_hours]) }}</dt>
                            </div>
                        @endif
                    </dl>

                    @if ($test->measures)
                        <section class="rounded-2xl bg-primary-50/70 p-4 ring-1 ring-primary-100">
                            <h2 class="text-sm font-semibold text-ink-900">{{ __('labs.measures') }}</h2>
                            <p class="mt-2 text-sm leading-6 text-ink-600">{{ $test->measures }}</p>
                        </section>
                    @endif

                    @if ($test->contains)
                        <section class="rounded-2xl bg-ink-50 p-4 ring-1 ring-ink-100">
                            <h2 class="text-sm font-semibold text-ink-900">{{ __('labs.contains') }}</h2>
                            <p class="mt-2 text-sm leading-6 text-ink-600">{{ $test->contains }}</p>
                        </section>
                    @endif

                    @if ($test->preparation)
                        <section class="rounded-2xl bg-warning-50 p-4 ring-1 ring-warning-100">
                            <h2 class="text-sm font-semibold text-ink-900">{{ __('labs.preparation') }}</h2>
                            <p class="mt-2 text-sm leading-6 text-ink-600">{{ $test->preparation }}</p>
                        </section>
                    @endif

                    <x-lab-availability :offerings="$test->clinicOfferings"/>
                </div>
            </div>

            <aside class="space-y-4 lg:sticky lg:top-24">
                <div class="overflow-hidden rounded-[1.5rem] bg-gradient-to-br from-primary-700 via-primary-800 to-primary-950 p-5 text-white shadow-[0_16px_40px_rgba(30,64,175,0.28)]">
                    <p class="text-xs font-medium text-primary-200">{{ __('labs.suggested') }}</p>
                    <p class="mt-2 text-3xl font-bold tabular tracking-tight">
                        {{ number_format((float) $test->suggested_price) }}
                        <span class="text-base font-medium text-primary-100">{{ __('common.currency') }}</span>
                    </p>
                    <p class="mt-3 text-xs text-primary-100">{{ __('labs.cart.guide_price') }}</p>
                </div>

                <div class="rounded-[1.25rem] bg-white p-4 shadow-sm ring-1 ring-ink-100 sm:p-5">
                    <x-lab-cart-button type="test" :id="$test->id" size="lg" class="w-full"/>
                    <x-button :href="route('labs.cart')" variant="secondary" class="mt-3 w-full" size="sm">
                        <x-icon name="bag" class="size-4"/>
                        {{ __('labs.cart.heading') }}
                    </x-button>
                </div>
            </aside>
        </div>
    </div>
</x-layouts.public>
