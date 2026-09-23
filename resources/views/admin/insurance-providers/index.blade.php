<x-layouts.admin :title="__('admin.insurance_providers.heading')">
    <x-page-header :title="__('admin.insurance_providers.heading')" :subtitle="__('admin.insurance_providers.subheading')">
        <x-slot:actions>
            <form method="GET" class="flex items-center gap-2">
                <x-input name="q" :value="request('q')" :placeholder="__('common.search')" class="w-40"/>
                <x-button variant="secondary" size="sm">{{ __('common.filter') }}</x-button>
            </form>
            <x-button :href="route('admin.insurance-providers.create')" variant="accent">
                <x-icon name="plus" class="size-4"/>
                {{ __('admin.insurance_providers.create') }}
            </x-button>
        </x-slot:actions>
    </x-page-header>

    <x-table>
        <x-slot:head>
            <x-th>{{ __('common.name_ar') }}</x-th>
            <x-th>{{ __('common.name_en') }}</x-th>
            <x-th>{{ __('admin.insurance_providers.hotline') }}</x-th>
            <x-th>{{ __('admin.insurance_providers.services_count') }}</x-th>
            <x-th>{{ __('common.status') }}</x-th>
            <x-th class="text-end">{{ __('common.actions') }}</x-th>
        </x-slot:head>

        @forelse ($providers as $provider)
            <tr>
                <x-td class="font-medium text-ink-900">
                    <span class="flex items-center gap-2">
                        <x-media
                            :src="\App\Support\PublicImage::url($provider->image_path)"
                            :alt="$provider->name_ar"
                            class="size-8 rounded-lg"
                        />
                        <span>{{ $provider->name_ar }}</span>
                    </span>
                </x-td>
                <x-td>{{ $provider->name_en }}</x-td>
                <x-td class="tabular" dir="ltr">{{ $provider->hotline ?: '—' }}</x-td>
                <x-td class="tabular">{{ $provider->clinic_services_count }}</x-td>
                <x-td><x-status-dot :active="$provider->is_active"/></x-td>
                <x-td>
                    <x-row-actions :edit="route('admin.insurance-providers.edit', $provider)"
                                   :destroy="route('admin.insurance-providers.destroy', $provider)"/>
                </x-td>
            </tr>
        @empty
            <x-empty-state colspan="6"/>
        @endforelse

        @if ($providers->hasPages())
            <x-slot:footer>{{ $providers->links() }}</x-slot:footer>
        @endif
    </x-table>
</x-layouts.admin>
