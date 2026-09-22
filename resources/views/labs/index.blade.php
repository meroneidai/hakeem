<x-layouts.public :title="__('labs.heading')">
    <x-catalog-hero :title="__('labs.heading')" :subtitle="__('labs.subtitle')">
        <x-slot:crumbs>
            <a href="{{ route('home') }}" class="hover:text-primary-700">{{ __('discover.nav.home') }}</a>
            <span aria-hidden="true">·</span>
            <span class="text-ink-700">{{ __('discover.nav.labs') }}</span>
        </x-slot:crumbs>
    </x-catalog-hero>

    <div class="mx-auto max-w-6xl px-4 py-8" x-data="{ tab: @js($packages->isNotEmpty() ? 'packages' : 'tests') }">

        <form method="GET" class="mb-6 grid gap-2 sm:grid-cols-2 lg:grid-cols-6">
            <x-input name="q" :value="request('q')" :placeholder="__('common.search')"/>
            <x-select name="governorate" :placeholder="__('admin.cities.governorate')"
                      :options="$governorates->pluck('name', 'slug')->all()" :selected="request('governorate')"/>
            <x-select name="city" :placeholder="__('discover.doctors.any_city')"
                      :options="$governorates->flatMap->cities->pluck('name', 'slug')->all()" :selected="request('city')"/>
            <x-input name="max_price" type="number" min="0" :value="$maxPrice ?? ''" :placeholder="__('discover.search.max_price')"/>
            <label class="flex items-center gap-2 text-sm text-ink-600">
                <input type="checkbox" name="fasting" value="1" @checked($fasting ?? false) class="size-4 rounded border-ink-300 text-primary-600">
                {{ __('labs.fasting_only') }}
            </label>
            <x-button variant="secondary">{{ __('common.filter') }}</x-button>
            <div class="sm:col-span-2 lg:col-span-6 flex flex-wrap items-center gap-2">
            <a href="{{ route('labs.index', array_filter(['q' => request('q'), 'governorate' => request('governorate'), 'city' => request('city')])) }}"
               @class(['rounded-full px-3 py-1.5 text-sm font-medium', request('category') ? 'bg-ink-100 text-ink-600' : 'bg-primary-600 text-white'])>
                {{ __('labs.all_categories') }}
            </a>
            @foreach ($categories as $category)
                <a href="{{ route('labs.index', array_filter(['category' => $category->value, 'q' => request('q'), 'governorate' => request('governorate'), 'city' => request('city')])) }}"
                   @class(['rounded-full px-3 py-1.5 text-sm font-medium', $activeCategory === $category->value ? 'bg-primary-600 text-white' : 'bg-ink-100 text-ink-600 hover:bg-ink-200'])>
                    {{ $category->label() }}
                </a>
            @endforeach
            </div>
        </form>

        @if ($packages->isEmpty() && $tests->isEmpty())
            <x-card>
                <x-empty-state :message="($geoFiltered ?? false) ? __('labs.area_empty') : __('common.no_results')"/>
            </x-card>
        @else
            <div class="mb-5 flex gap-2 rounded-2xl bg-ink-50 p-1">
                @if ($packages->isNotEmpty())
                    <button type="button"
                            @click="tab = 'packages'"
                            :class="tab === 'packages' ? 'bg-white text-ink-900 shadow-sm' : 'text-ink-500 hover:text-ink-800'"
                            class="flex-1 rounded-xl px-4 py-2.5 text-sm font-semibold">
                        {{ __('labs.packages') }}
                        <span class="ms-1 tabular text-ink-400">{{ $packages->count() }}</span>
                    </button>
                @endif
                <button type="button"
                        @click="tab = 'tests'"
                        :class="tab === 'tests' ? 'bg-white text-ink-900 shadow-sm' : 'text-ink-500 hover:text-ink-800'"
                        class="flex-1 rounded-xl px-4 py-2.5 text-sm font-semibold">
                    {{ __('labs.tests') }}
                    <span class="ms-1 tabular text-ink-400">{{ $tests->count() }}</span>
                </button>
            </div>

            @if ($packages->isNotEmpty())
                <div x-show="tab === 'packages'" class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                    @foreach ($packages as $package)
                        <article class="card flex flex-col overflow-hidden p-0">
                            @if ($package->image_path)
                                <x-media
                                    :src="\App\Support\PublicImage::url($package->image_path)"
                                    :alt="$package->name"
                                    class="h-36 w-full rounded-none"
                                />
                            @endif
                            <div class="flex flex-1 flex-col p-4">
                                <div class="flex items-start justify-between gap-2">
                                    <x-badge tone="accent">{{ __('labs.packages') }}</x-badge>
                                    @if ($package->discountPercent())
                                        <x-badge tone="accent">{{ $package->discountPercent() }}%</x-badge>
                                    @endif
                                </div>
                                <h3 class="mt-2 font-semibold text-ink-900">
                                    <a href="{{ route('labs.packages.show', $package) }}" class="hover:text-primary-700">{{ $package->name }}</a>
                                </h3>
                                @if ($package->includes)
                                    <p class="mt-1 line-clamp-2 text-sm text-ink-500">{{ $package->includes }}</p>
                                @endif
                                @if ($package->tests->isNotEmpty())
                                    <p class="mt-2 text-xs text-ink-400">
                                        {{ $package->tests->pluck('name')->filter()->take(4)->implode(' · ') }}
                                    </p>
                                @endif
                                <x-lab-availability :offerings="$package->clinicOfferings"/>
                                <p class="mt-3 text-sm">
                                    @if ((float) $package->original_price > (float) $package->package_price)
                                        <span class="text-ink-400 line-through">{{ number_format((float) $package->original_price) }}</span>
                                    @endif
                                    <span class="ms-1 text-base font-semibold text-ink-900">{{ number_format((float) $package->package_price) }} {{ __('common.currency') }}</span>
                                </p>
                                <div class="mt-auto pt-4">
                                    <x-lab-cart-button type="package" :id="$package->id"/>
                                </div>
                            </div>
                        </article>
                    @endforeach
                </div>
            @endif

            <div @if ($packages->isNotEmpty()) x-show="tab === 'tests'" x-cloak @endif class="grid gap-3 md:grid-cols-2">
                @forelse ($tests as $test)
                    <article class="card p-4">
                        <div class="flex items-start justify-between gap-3">
                            <div class="flex min-w-0 items-start gap-3">
                                <x-media
                                    :src="\App\Support\PublicImage::url($test->image_path)"
                                    :alt="$test->name"
                                    class="size-12 shrink-0 rounded-xl"
                                />
                                <div>
                                    <x-badge tone="primary">{{ $test->category->label() }}</x-badge>
                                    <h3 class="mt-2 font-semibold text-ink-900">
                                        <a href="{{ route('labs.tests.show', $test) }}" class="hover:text-primary-700">{{ $test->name }}</a>
                                    </h3>
                                    <p class="mt-1 text-sm text-ink-500">{{ $test->measures }}</p>
                                </div>
                            </div>
                            <p class="shrink-0 text-sm font-semibold text-primary-700">
                                {{ number_format((float) $test->suggested_price) }} {{ __('common.currency') }}
                            </p>
                        </div>
                        <p class="mt-3 text-xs text-ink-400">
                            {{ $test->sample_type->label() }}
                            ·
                            {{ $test->fasting_hours ? __('labs.fasting', ['hours' => $test->fasting_hours]) : __('labs.no_fasting') }}
                        </p>
                        <x-lab-availability :offerings="$test->clinicOfferings"/>
                        <div class="mt-3">
                            <x-lab-cart-button type="test" :id="$test->id" variant="secondary"/>
                        </div>
                    </article>
                @empty
                    <x-card class="md:col-span-2">
                        <x-empty-state :message="__('common.no_results')"/>
                    </x-card>
                @endforelse
            </div>
        @endif
    </div>
</x-layouts.public>
