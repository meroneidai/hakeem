<x-layouts.public :title="$test->name">
    <x-catalog-hero :title="$test->name" :subtitle="$test->category->label()">
        <x-slot:crumbs>
            <a href="{{ route('home') }}" class="hover:text-primary-700">{{ __('discover.nav.home') }}</a>
            <span aria-hidden="true">·</span>
            <a href="{{ route('labs.index') }}" class="hover:text-primary-700">{{ __('discover.nav.labs') }}</a>
        </x-slot:crumbs>
    </x-catalog-hero>

    <div class="mx-auto max-w-3xl px-4 py-8">

        <x-card class="space-y-4">
            <x-media
                :src="$test->imageUrl()"
                :alt="$test->name"
                class="h-48 w-full rounded-xl"
            />
            <p class="text-sm text-ink-600">{{ $test->description }}</p>

            @if ($test->measures)
                <section>
                    <h2 class="text-sm font-semibold text-ink-800">{{ __('labs.measures') }}</h2>
                    <p class="mt-1 text-sm text-ink-600">{{ $test->measures }}</p>
                </section>
            @endif

            @if ($test->contains)
                <section>
                    <h2 class="text-sm font-semibold text-ink-800">{{ __('labs.contains') }}</h2>
                    <p class="mt-1 text-sm text-ink-600">{{ $test->contains }}</p>
                </section>
            @endif

            @if ($test->preparation)
                <section>
                    <h2 class="text-sm font-semibold text-ink-800">{{ __('labs.preparation') }}</h2>
                    <p class="mt-1 text-sm text-ink-600">{{ $test->preparation }}</p>
                </section>
            @endif

            <dl class="grid gap-3 text-sm sm:grid-cols-2">
                <div>
                    <dt class="text-ink-400">{{ __('labs.sample') }}</dt>
                    <dd class="font-medium text-ink-800">{{ $test->sample_type->label() }}</dd>
                </div>
                <div>
                    <dt class="text-ink-400">{{ __('labs.suggested') }}</dt>
                    <dd class="font-medium text-ink-800">{{ number_format((float) $test->suggested_price) }} {{ __('common.currency') }}</dd>
                </div>
                <div>
                    <dt class="text-ink-400">{{ __('labs.preparation') }}</dt>
                    <dd class="font-medium text-ink-800">
                        {{ $test->fasting_hours ? __('labs.fasting', ['hours' => $test->fasting_hours]) : __('labs.no_fasting') }}
                    </dd>
                </div>
                @if ($test->turnaround_hours)
                    <div>
                        <dt class="text-ink-400">{{ __('labs.turnaround', ['hours' => $test->turnaround_hours]) }}</dt>
                    </div>
                @endif
            </dl>

            <x-lab-availability :offerings="$test->clinicOfferings"/>
            <x-lab-cart-button type="test" :id="$test->id"/>
        </x-card>
    </div>
</x-layouts.public>
