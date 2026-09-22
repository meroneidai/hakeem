<x-layouts.clinic :title="__('clinic.labs.heading')">
    <x-page-header :title="__('clinic.labs.heading')" :subtitle="__('clinic.labs.subtitle')"/>

    <form method="POST" action="{{ route('clinic.labs.update') }}">
        @csrf
        @method('PUT')

        <h2 class="mb-3 text-sm font-semibold text-ink-800">{{ __('labs.tests') }}</h2>
        <div class="space-y-3">
            @foreach ($tests as $test)
                @php($offering = $testOfferings->get($test->id))
                <x-card>
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div class="flex min-w-0 items-start gap-3">
                            <x-media
                                :src="\App\Support\PublicImage::url($test->image_path)"
                                :alt="$test->name"
                                class="size-12 shrink-0 rounded-xl"
                            />
                            <div>
                                <h3 class="font-semibold text-ink-900">{{ $test->name }}</h3>
                                <p class="mt-1 text-xs text-ink-400">{{ $test->category->label() }} · {{ $test->sample_type->label() }}</p>
                            </div>
                        </div>
                        <x-checkbox
                            :name="'tests['.$test->id.'][enabled]'"
                            :label="__('clinic.labs.enable')"
                            :checked="$offering?->is_active"
                        />
                    </div>
                    <div class="mt-4 grid gap-3 sm:grid-cols-3">
                        <x-field :label="__('clinic.services.price')" :name="'tests.'.$test->id.'.price'">
                            <x-input type="number" step="0.01" min="0" :name="'tests['.$test->id.'][price]'"
                                     :value="$offering?->price ?? $test->suggested_price" dir="ltr"/>
                        </x-field>
                        <x-field :label="__('clinic.services.promo_price')" :name="'tests.'.$test->id.'.promo_price'">
                            <x-input type="number" step="0.01" min="0" :name="'tests['.$test->id.'][promo_price]'"
                                     :value="$offering?->promo_price" dir="ltr"/>
                        </x-field>
                        <div class="flex items-end pb-1">
                            <x-checkbox
                                :name="'tests['.$test->id.'][home]'"
                                :label="__('clinic.labs.home')"
                                :checked="$offering?->allows_home_collection"
                            />
                        </div>
                    </div>
                </x-card>
            @endforeach
        </div>

        <h2 class="mb-3 mt-8 text-sm font-semibold text-ink-800">{{ __('labs.packages') }}</h2>
        <div class="space-y-3">
            @foreach ($packages as $package)
                @php($offering = $packageOfferings->get($package->id))
                <x-card>
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div class="flex min-w-0 items-start gap-3">
                            <x-media
                                :src="\App\Support\PublicImage::url($package->image_path)"
                                :alt="$package->name"
                                class="size-12 shrink-0 rounded-xl"
                            />
                            <div>
                                <h3 class="font-semibold text-ink-900">{{ $package->name }}</h3>
                                <p class="mt-1 text-xs text-ink-400">{{ $package->tests->pluck('name')->join(' · ') }}</p>
                            </div>
                        </div>
                        <x-checkbox
                            :name="'packages['.$package->id.'][enabled]'"
                            :label="__('clinic.labs.enable')"
                            :checked="$offering?->is_active"
                        />
                    </div>
                    <div class="mt-4 grid gap-3 sm:grid-cols-3">
                        <x-field :label="__('clinic.services.price')" :name="'packages.'.$package->id.'.price'">
                            <x-input type="number" step="0.01" min="0" :name="'packages['.$package->id.'][price]'"
                                     :value="$offering?->price ?? $package->package_price" dir="ltr"/>
                        </x-field>
                        <x-field :label="__('clinic.services.promo_price')" :name="'packages.'.$package->id.'.promo_price'">
                            <x-input type="number" step="0.01" min="0" :name="'packages['.$package->id.'][promo_price]'"
                                     :value="$offering?->promo_price" dir="ltr"/>
                        </x-field>
                        <div class="flex items-end pb-1">
                            <x-checkbox
                                :name="'packages['.$package->id.'][home]'"
                                :label="__('clinic.labs.home')"
                                :checked="$offering?->allows_home_collection"
                            />
                        </div>
                    </div>
                </x-card>
            @endforeach
        </div>

        <div class="mt-5 flex justify-end">
            <x-button variant="accent">{{ __('common.save_changes') }}</x-button>
        </div>
    </form>
</x-layouts.clinic>
