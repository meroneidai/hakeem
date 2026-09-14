<x-layouts.clinic :title="__('clinic.addresses.add')">
    <x-page-header :title="__('clinic.addresses.add')"/>

    <form method="POST" action="{{ route('clinic.addresses.store') }}">
        @csrf
        <x-card class="max-w-3xl">
            @include('clinic.addresses._form')
            <x-slot:footer>
                <x-button :href="route('clinic.addresses.index')" variant="ghost">{{ __('common.cancel') }}</x-button>
                <x-button variant="accent">{{ __('common.save') }}</x-button>
            </x-slot:footer>
        </x-card>
    </form>
</x-layouts.clinic>
