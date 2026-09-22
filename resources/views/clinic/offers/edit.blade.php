<x-layouts.clinic :title="__('clinic.offers.edit')">
    <x-page-header :title="__('clinic.offers.edit')" :subtitle="$offer->title_ar"/>

    <form method="POST" action="{{ route('clinic.offers.update', $offer) }}" enctype="multipart/form-data">
        @csrf
        @method('PUT')
        <x-card class="max-w-4xl">
            @include('clinic.offers._form')
            <x-slot:footer>
                <x-button :href="route('clinic.offers.index')" variant="ghost">{{ __('common.cancel') }}</x-button>
                <x-button variant="accent">{{ __('common.save_changes') }}</x-button>
            </x-slot:footer>
        </x-card>
    </form>
</x-layouts.clinic>
