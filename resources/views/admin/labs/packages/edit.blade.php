<x-layouts.admin :title="__('admin.labs.edit_package')">
    <x-page-header :title="__('admin.labs.edit_package')" :subtitle="$package->name_ar"/>

    <form method="POST" action="{{ route('admin.lab-packages.update', $package) }}" enctype="multipart/form-data">
        @csrf
        @method('PUT')
        <x-card class="max-w-4xl">
            @include('admin.labs.packages._form')
            <x-slot:footer>
                <x-button :href="route('admin.lab-packages.index')" variant="ghost">{{ __('common.cancel') }}</x-button>
                <x-button variant="accent">{{ __('common.save_changes') }}</x-button>
            </x-slot:footer>
        </x-card>
    </form>
</x-layouts.admin>
