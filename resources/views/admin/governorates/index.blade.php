<x-layouts.admin :title="__('admin.governorates.heading')">
    <x-page-header :title="__('admin.governorates.heading')" :subtitle="__('admin.governorates.subheading')">
        <x-slot:actions>
            <form method="GET" class="flex items-center gap-2">
                <x-input name="q" :value="request('q')" :placeholder="__('common.search')" class="w-48"/>
                <x-button variant="secondary" size="sm">{{ __('common.filter') }}</x-button>
            </form>
            <x-button :href="route('admin.governorates.create')" variant="accent">
                <x-icon name="plus" class="size-4"/>
                {{ __('admin.governorates.create') }}
            </x-button>
        </x-slot:actions>
    </x-page-header>

    <x-table>
        <x-slot:head>
            <x-th>{{ __('common.name_ar') }}</x-th>
            <x-th>{{ __('common.name_en') }}</x-th>
            <x-th>{{ __('common.slug') }}</x-th>
            <x-th>{{ __('admin.governorates.cities_count') }}</x-th>
            <x-th>{{ __('common.status') }}</x-th>
            <x-th class="text-end">{{ __('common.actions') }}</x-th>
        </x-slot:head>

        @forelse ($governorates as $governorate)
            <tr>
                <x-td class="font-medium text-ink-900">{{ $governorate->name_ar }}</x-td>
                <x-td>{{ $governorate->name_en }}</x-td>
                <x-td><code class="text-xs text-ink-500" dir="ltr">{{ $governorate->slug }}</code></x-td>
                <x-td>
                    <a href="{{ route('admin.cities.index', ['governorate_id' => $governorate->id]) }}"
                       class="tabular text-primary-600 hover:underline">{{ $governorate->cities_count }}</a>
                </x-td>
                <x-td><x-status-dot :active="$governorate->is_active"/></x-td>
                <x-td>
                    <x-row-actions :edit="route('admin.governorates.edit', $governorate)"
                                   :destroy="route('admin.governorates.destroy', $governorate)"/>
                </x-td>
            </tr>
        @empty
            <x-empty-state colspan="6"/>
        @endforelse

        @if ($governorates->hasPages())
            <x-slot:footer>{{ $governorates->links() }}</x-slot:footer>
        @endif
    </x-table>
</x-layouts.admin>
