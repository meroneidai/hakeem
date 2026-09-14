<x-layouts.admin :title="__('admin.service_types.create')">
    <x-page-header :title="__('admin.service_types.create')"/>

    <form method="POST" action="{{ route('admin.service-types.store') }}">
        @csrf
        <x-card class="max-w-3xl">
            @include('admin.service-types._form')

            <x-slot:footer>
                <x-button :href="route('admin.service-types.index')" variant="ghost">{{ __('common.cancel') }}</x-button>
                <x-button variant="accent">{{ __('common.save') }}</x-button>
            </x-slot:footer>
        </x-card>
    </form>
</x-layouts.admin>
