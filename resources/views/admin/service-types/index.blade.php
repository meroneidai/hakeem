<x-layouts.admin :title="__('admin.service_types.heading')">
    <x-page-header :title="__('admin.service_types.heading')" :subtitle="__('admin.service_types.subheading')">
        <x-slot:actions>
            <x-button :href="route('admin.service-types.create')" variant="accent">
                <x-icon name="plus" class="size-4"/>
                {{ __('admin.service_types.create') }}
            </x-button>
        </x-slot:actions>
    </x-page-header>

    <div class="grid gap-4 lg:grid-cols-2">
        @foreach ($serviceTypes as $type)
            <x-card>
                <div class="flex items-start justify-between gap-3">
                    <div class="flex min-w-0 items-start gap-3">
                        <x-media
                            :src="\App\Support\PublicImage::url($type->image_path)"
                            :alt="$type->name_ar"
                            class="size-12 shrink-0 rounded-xl"
                        />
                        <div class="min-w-0">
                            <h3 class="font-semibold text-ink-900">{{ $type->name_ar }}</h3>
                            <p class="text-sm text-ink-500" dir="ltr">{{ $type->name_en }}</p>
                            <code class="mt-1 block text-xs text-primary-600" dir="ltr">{{ $type->code }}</code>
                        </div>
                    </div>
                    <div class="flex shrink-0 flex-col items-end gap-2">
                        <x-status-dot :active="$type->is_active"/>
                        <x-row-actions
                            :edit="route('admin.service-types.edit', $type)"
                            :destroy="route('admin.service-types.destroy', $type)"
                        />
                    </div>
                </div>

                @if ($type->description_ar)
                    <p class="mt-3 text-sm text-ink-600">{{ $type->description_ar }}</p>
                @endif

                <div class="mt-3 flex flex-wrap gap-1.5">
                    @if ($type->requires_clinic_address)
                        <x-badge>{{ __('admin.service_types.requires_clinic_address') }}</x-badge>
                    @endif
                    @if ($type->requires_patient_address)
                        <x-badge>{{ __('admin.service_types.requires_patient_address') }}</x-badge>
                    @endif
                    @if ($type->requires_time_slot)
                        <x-badge>{{ __('admin.service_types.requires_time_slot') }}</x-badge>
                    @endif
                    @if ($type->is_online)
                        <x-badge tone="primary">{{ __('admin.service_types.is_online') }}</x-badge>
                    @endif
                    @if ($type->is_sensitive)
                        <x-badge tone="warning">{{ __('admin.service_types.is_sensitive') }}</x-badge>
                    @endif
                </div>
            </x-card>
        @endforeach
    </div>
</x-layouts.admin>
