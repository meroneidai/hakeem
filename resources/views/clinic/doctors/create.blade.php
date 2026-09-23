<x-layouts.clinic :title="__('clinic.doctors.add')">
    <x-page-header :title="__('clinic.doctors.add')"/>

    <form method="POST" action="{{ route('clinic.doctors.store') }}" enctype="multipart/form-data">
        @csrf
        <x-card class="max-w-3xl">
            @include('clinic.doctors._form')
            <x-slot:footer>
                <x-button :href="route('clinic.doctors.index')" variant="ghost">{{ __('common.cancel') }}</x-button>
                <x-button variant="accent">{{ __('common.save') }}</x-button>
            </x-slot:footer>
        </x-card>
    </form>
</x-layouts.clinic>
