<x-layouts.admin :title="__('admin.labs.create_test')">
    <x-page-header :title="__('admin.labs.create_test')"/>

    <form method="POST" action="{{ route('admin.lab-tests.store') }}" enctype="multipart/form-data">
        @csrf
        <x-card class="max-w-4xl">
            @include('admin.labs.tests._form')
            <x-slot:footer>
                <x-button :href="route('admin.lab-tests.index')" variant="ghost">{{ __('common.cancel') }}</x-button>
                <x-button variant="accent">{{ __('common.save') }}</x-button>
            </x-slot:footer>
        </x-card>
    </form>
</x-layouts.admin>
