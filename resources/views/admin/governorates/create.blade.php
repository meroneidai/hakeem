<x-layouts.admin :title="__('admin.governorates.create')">
    <x-page-header :title="__('admin.governorates.create')"/>

    <form method="POST" action="{{ route('admin.governorates.store') }}">
        @csrf
        <x-card class="max-w-3xl">
            @include('admin.governorates._form')

            <x-slot:footer>
                <x-button :href="route('admin.governorates.index')" variant="ghost">{{ __('common.cancel') }}</x-button>
                <x-button variant="accent">{{ __('common.save') }}</x-button>
            </x-slot:footer>
        </x-card>
    </form>
</x-layouts.admin>
