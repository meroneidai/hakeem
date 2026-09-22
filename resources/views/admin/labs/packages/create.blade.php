<x-layouts.admin :title="__('admin.labs.create_package')">
    <x-page-header :title="__('admin.labs.create_package')"/>

    <form method="POST" action="{{ route('admin.lab-packages.store') }}" enctype="multipart/form-data">
        @csrf
        <x-card class="max-w-4xl">
            @include('admin.labs.packages._form')
            <x-slot:footer>
                <x-button :href="route('admin.lab-packages.index')" variant="ghost">{{ __('common.cancel') }}</x-button>
                <x-button variant="accent">{{ __('common.save') }}</x-button>
            </x-slot:footer>
        </x-card>
    </form>
</x-layouts.admin>
