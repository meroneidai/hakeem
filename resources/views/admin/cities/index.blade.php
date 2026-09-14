<x-layouts.admin :title="__('admin.cities.heading')">
    <x-page-header :title="__('admin.cities.heading')" :subtitle="__('admin.cities.subheading')">
        <x-slot:actions>
            <form method="GET" class="flex items-center gap-2">
                <x-select name="governorate_id" :placeholder="__('admin.cities.filter_governorate')"
                          :options="$governorates->pluck('name', 'id')->all()"
                          :selected="request('governorate_id')" class="w-44"/>
                <x-input name="q" :value="request('q')" :placeholder="__('common.search')" class="w-40"/>
                <x-button variant="secondary" size="sm">{{ __('common.filter') }}</x-button>
            </form>
            <x-button :href="route('admin.cities.create', ['governorate_id' => request('governorate_id')])" variant="accent">
                <x-icon name="plus" class="size-4"/>
                {{ __('admin.cities.create') }}
            </x-button>
        </x-slot:actions>
    </x-page-header>

    <x-table>
        <x-slot:head>
            <x-th>{{ __('common.name_ar') }}</x-th>
            <x-th>{{ __('common.name_en') }}</x-th>
            <x-th>{{ __('admin.cities.governorate') }}</x-th>
            <x-th>{{ __('common.slug') }}</x-th>
            <x-th>{{ __('common.status') }}</x-th>
            <x-th class="text-end">{{ __('common.actions') }}</x-th>
        </x-slot:head>

        @forelse ($cities as $city)
            <tr>
                <x-td class="font-medium text-ink-900">{{ $city->name_ar }}</x-td>
                <x-td>{{ $city->name_en }}</x-td>
                <x-td>{{ $city->governorate->name }}</x-td>
                <x-td><code class="text-xs text-ink-500" dir="ltr">{{ $city->slug }}</code></x-td>
                <x-td><x-status-dot :active="$city->is_active"/></x-td>
                <x-td>
                    <x-row-actions :edit="route('admin.cities.edit', $city)"
                                   :destroy="route('admin.cities.destroy', $city)"/>
                </x-td>
            </tr>
        @empty
            <x-empty-state colspan="6"/>
        @endforelse

        @if ($cities->hasPages())
            <x-slot:footer>{{ $cities->links() }}</x-slot:footer>
        @endif
    </x-table>
</x-layouts.admin>
