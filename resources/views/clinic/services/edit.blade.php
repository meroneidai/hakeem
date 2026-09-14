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
                        <div>
                            <h3 class="font-semibold text-ink-900">{{ $type->name }}</h3>
                            <p class="mt-1 text-sm text-ink-500">{{ $type->description }}</p>
                            @unless ($allowed)
                                <x-badge tone="warning" class="mt-2">{{ __('clinic.services.locked') }}</x-badge>
                            @endunless
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
                        <div class="mt-4 grid gap-3 sm:grid-cols-3">
                            <x-field :label="__('clinic.services.price')" :name="'services.'.$type->id.'.price'">
                                <x-input type="number" step="0.01" min="0" :name="'services['.$type->id.'][price]'"
                                         :value="$service?->price ?? 0" dir="ltr"/>
                            </x-field>
                            <x-field :label="__('clinic.services.promo_price')" :name="'services.'.$type->id.'.promo_price'">
                                <x-input type="number" step="0.01" min="0" :name="'services['.$type->id.'][promo_price]'"
                                         :value="$service?->promo_price" dir="ltr"/>
                            </x-field>
                            <x-field :label="__('clinic.services.duration')" :name="'services.'.$type->id.'.duration_minutes'">
                                <x-input type="number" min="5" max="480" :name="'services['.$type->id.'][duration_minutes]'"
                                         :value="$service?->duration_minutes" dir="ltr"/>
                            </x-field>
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
