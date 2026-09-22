<x-layouts.admin :title="__('admin.labs.packages_heading')">
    <x-page-header :title="__('admin.labs.packages_heading')" :subtitle="__('admin.labs.packages_subheading')">
        <x-slot:actions>
            <x-button :href="route('admin.lab-packages.create')" variant="accent">
                <x-icon name="plus" class="size-4"/>
                {{ __('admin.labs.create_package') }}
            </x-button>
        </x-slot:actions>
    </x-page-header>

    <x-table>
        <x-slot:head>
            <x-th>{{ __('common.name_ar') }}</x-th>
            <x-th>{{ __('common.name_en') }}</x-th>
            <x-th>{{ __('admin.labs.tests_count') }}</x-th>
            <x-th>{{ __('admin.labs.original_price') }}</x-th>
            <x-th>{{ __('admin.labs.package_price') }}</x-th>
            <x-th>{{ __('common.status') }}</x-th>
            <x-th class="text-end">{{ __('common.actions') }}</x-th>
        </x-slot:head>

        @forelse ($packages as $package)
            <tr>
                <x-td class="font-medium text-ink-900">
                    <span class="flex items-center gap-2">
                        <x-media
                            :src="\App\Support\PublicImage::url($package->image_path)"
                            :alt="$package->name_ar"
                            class="size-8 rounded-lg"
                        />
                        <span>
                            {{ $package->name_ar }}
                            @if ($package->is_featured)
                                <x-badge tone="accent" class="ms-1">{{ __('common.featured') }}</x-badge>
                            @endif
                        </span>
                    </span>
                </x-td>
                <x-td>{{ $package->name_en }}</x-td>
                <x-td>{{ $package->tests_count }}</x-td>
                <x-td class="tabular text-sm">{{ number_format((float) $package->original_price) }}</x-td>
                <x-td class="tabular text-sm font-medium">{{ number_format((float) $package->package_price) }} {{ __('common.currency') }}</x-td>
                <x-td><x-status-dot :active="$package->is_active"/></x-td>
                <x-td>
                    <x-row-actions :edit="route('admin.lab-packages.edit', $package)"
                                   :destroy="route('admin.lab-packages.destroy', $package)"/>
                </x-td>
            </tr>
        @empty
            <x-empty-state colspan="7"/>
        @endforelse

        @if ($packages->hasPages())
            <x-slot:footer>{{ $packages->links() }}</x-slot:footer>
        @endif
    </x-table>
</x-layouts.admin>
