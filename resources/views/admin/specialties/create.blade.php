<x-layouts.admin :title="__('admin.specialties.create')">
    <x-page-header :title="__('admin.specialties.create')"/>

    <form method="POST" action="{{ route('admin.specialties.store') }}">
        @csrf
        <x-card class="max-w-3xl">
            @include('admin.specialties._form')

            <x-slot:footer>
                <x-button :href="route('admin.specialties.index')" variant="ghost">{{ __('common.cancel') }}</x-button>
                <x-button variant="accent">{{ __('common.save') }}</x-button>
            </x-slot:footer>
        </x-card>
    </form>
</x-layouts.admin>
