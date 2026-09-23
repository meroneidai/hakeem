<x-layouts.public :title="$package->name">
    <x-catalog-hero :title="$package->name">
        <x-slot:crumbs>
            <a href="{{ route('home') }}" class="hover:text-primary-700">{{ __('discover.nav.home') }}</a>
            <span aria-hidden="true">·</span>
            <a href="{{ route('labs.index') }}" class="hover:text-primary-700">{{ __('discover.nav.labs') }}</a>
        </x-slot:crumbs>
        <x-slot:actions>
            @if ($package->discountPercent())
                <x-badge tone="accent">{{ $package->discountPercent() }}%</x-badge>
            @endif
        </x-slot:actions>
    </x-catalog-hero>

    <div class="mx-auto max-w-3xl px-4 py-8">
        <x-card class="space-y-4">
            @if ($package->image_path)
                <x-media
                    :src="\App\Support\PublicImage::url($package->image_path)"
                    :alt="$package->name"
                    class="h-48 w-full rounded-xl"
                />
            @endif
            <p class="text-sm text-ink-600">{{ $package->description }}</p>

            @if ($package->includes)
                <section>
                    <h2 class="text-sm font-semibold text-ink-800">{{ __('labs.includes') }}</h2>
                    <p class="mt-1 text-sm text-ink-600">{{ $package->includes }}</p>
                </section>
            @endif

            @if ($package->conditions)
                <section>
                    <h2 class="text-sm font-semibold text-ink-800">{{ __('labs.conditions') }}</h2>
                    <p class="mt-1 text-sm text-ink-600">{{ $package->conditions }}</p>
                </section>
            @endif

            @if ($package->preparation)
                <section>
                    <h2 class="text-sm font-semibold text-ink-800">{{ __('labs.preparation') }}</h2>
                    <p class="mt-1 text-sm text-ink-600">{{ $package->preparation }}</p>
                </section>
            @endif

            <p class="text-sm">
                <span class="text-ink-400 line-through">{{ number_format((float) $package->original_price) }}</span>
                <span class="ms-1 text-lg font-semibold text-ink-900">{{ number_format((float) $package->package_price) }} {{ __('common.currency') }}</span>
                @if ($package->savings() > 0)
                    <span class="ms-2 text-xs text-accent-600">{{ __('labs.save', ['amount' => number_format($package->savings())]) }}</span>
                @endif
            </p>

            <ul class="space-y-2 text-sm">
                @foreach ($package->tests as $test)
                    <li class="flex items-center justify-between rounded-lg bg-ink-50 px-3 py-2">
                        <a href="{{ route('labs.tests.show', $test) }}" class="font-medium text-ink-800 hover:text-primary-700">{{ $test->name }}</a>
                        <span class="text-ink-400">{{ number_format((float) $test->suggested_price) }}</span>
                    </li>
                @endforeach
            </ul>

            <x-lab-availability :offerings="$package->clinicOfferings"/>
            <x-lab-cart-button type="package" :id="$package->id"/>
        </x-card>
    </div>
</x-layouts.public>
