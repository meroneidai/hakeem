<x-layouts.admin :title="__('admin.cities.edit')">
    <x-page-header :title="__('admin.cities.edit')" :subtitle="$city->name_ar"/>

    <form method="POST" action="{{ route('admin.cities.update', $city) }}">
        @csrf
        @method('PUT')
        <x-card class="max-w-3xl">
            @include('admin.cities._form')

            <x-slot:footer>
                <x-button :href="route('admin.cities.index')" variant="ghost">{{ __('common.cancel') }}</x-button>
                <x-button variant="accent">{{ __('common.save_changes') }}</x-button>
            </x-slot:footer>
        </x-card>
    </form>
</x-layouts.admin>
