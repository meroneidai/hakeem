<x-layouts.clinic :title="__('clinic.offers.add')">
    <x-page-header :title="__('clinic.offers.add')"/>

    <form method="POST" action="{{ route('clinic.offers.store') }}" enctype="multipart/form-data">
        @csrf
        <x-card class="max-w-4xl">
            @include('clinic.offers._form')
            <x-slot:footer>
                <x-button :href="route('clinic.offers.index')" variant="ghost">{{ __('common.cancel') }}</x-button>
                <x-button variant="accent">{{ __('common.save') }}</x-button>
            </x-slot:footer>
        </x-card>
    </form>
</x-layouts.clinic>
