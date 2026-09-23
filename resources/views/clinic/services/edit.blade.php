<x-layouts.clinic :title="__('clinic.services.heading')">
    <x-page-header :title="__('clinic.services.heading')" :subtitle="__('clinic.services.subtitle')"/>

    <form method="POST" action="{{ route('clinic.services.update') }}">
        @csrf
        @method('PUT')

        <div class="space-y-3">
            @foreach ($types as $type)
                @php
                    $feature = \App\Enums\PlanFeature::forServiceType($type->code);
                    $allowed = ! $feature || $clinic->allows($feature);
                    $service = $enabled->get($type->id);
                @endphp

                <x-card>
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div class="flex min-w-0 items-start gap-3">
                            <x-media
                                :src="\App\Support\PublicImage::url($type->image_path)"
                                :alt="$type->name"
                                class="size-12 shrink-0 rounded-xl"
                            />
                            <div>
                                <h3 class="font-semibold text-ink-900">{{ $type->name }}</h3>
                                <p class="mt-1 text-sm text-ink-500">{{ $type->description }}</p>
                                @unless ($allowed)
                                    <x-badge tone="warning" class="mt-2">{{ __('clinic.services.locked') }}</x-badge>
                                @endunless
                            </div>
                        </div>
                        @if ($allowed)
                            <x-checkbox
                                :name="'services['.$type->id.'][enabled]'"
                                :label="__('clinic.services.enabled')"
                                :checked="$service?->is_active"
                            />
                        @endif
                    </div>

                    @if ($allowed)
                        <div class="mt-4 grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                            <x-field :label="__('clinic.services.price')" :name="'services.'.$type->id.'.price'">
                                <x-input type="number" step="0.01" min="0" :name="'services['.$type->id.'][price]'"
                                         :value="$service?->price ?? 0" dir="ltr"/>
                            </x-field>
                            <x-field :label="__('clinic.services.promo_price')" :name="'services.'.$type->id.'.promo_price'">
                                <x-input type="number" step="0.01" min="0" :name="'services['.$type->id.'][promo_price]'"
                                         :value="$service?->promo_price" dir="ltr"/>
                            </x-field>
                            <x-field :label="__('clinic.services.duration')" :name="'services.'.$type->id.'.duration_minutes'" :hint="__('clinic.services.duration_hint')">
                                <x-select
                                    :name="'services['.$type->id.'][duration_minutes]'"
                                    :options="\App\Support\ServiceDuration::options($service?->duration_minutes ?? $type->default_duration_minutes)"
                                    :selected="old('services.'.$type->id.'.duration_minutes', $service?->duration_minutes ?? $type->default_duration_minutes ?? 30)"
                                />
                            </x-field>
                            <x-field :label="__('clinic.services.session_count')" :name="'services.'.$type->id.'.session_count'">
                                <x-input type="number" min="1" max="30" :name="'services['.$type->id.'][session_count]'"
                                         :value="$service?->session_count ?? 1" dir="ltr"/>
                            </x-field>
                        </div>
                        <div class="mt-3">
                            <x-checkbox
                                :name="'services['.$type->id.'][requires_evaluation_first]'"
                                :label="__('clinic.services.requires_evaluation')"
                                :checked="$service?->requires_evaluation_first"
                            />
                        </div>

                        <div class="mt-4 rounded-2xl bg-ink-50/70 p-3 sm:p-4">
                            <div class="mb-2 flex flex-wrap items-baseline justify-between gap-2">
                                <h4 class="text-sm font-semibold text-ink-800">{{ __('clinic.services.insurance') }}</h4>
                                <p class="text-xs text-ink-500">{{ __('clinic.services.insurance_hint') }}</p>
                            </div>

                            @if ($insuranceProviders->isEmpty())
                                <p class="text-xs text-ink-500">{{ __('clinic.services.insurance_empty') }}</p>
                            @else
                                @php $accepted = $service?->insuranceProviders->pluck('id')->all() ?? []; @endphp
                                <div class="grid gap-2 sm:grid-cols-2 lg:grid-cols-3">
                                    @foreach ($insuranceProviders as $provider)
                                        <div class="rounded-xl bg-white px-3 py-2 ring-1 ring-ink-100">
                                            <x-checkbox
                                                :name="'services['.$type->id.'][insurance_providers][]'"
                                                :value="$provider->id"
                                                :label="$provider->name"
                                                :checked="in_array($provider->id, $accepted, true)"
                                            />
                                        </div>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    @endif
                </x-card>
            @endforeach
        </div>

        <div class="mt-5 flex justify-end">
            <x-button variant="accent">{{ __('common.save_changes') }}</x-button>
        </div>
    </form>
</x-layouts.clinic>
