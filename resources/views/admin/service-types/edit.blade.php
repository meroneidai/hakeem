<x-layouts.admin :title="__('admin.service_types.edit')">
    <x-page-header :title="__('admin.service_types.edit')" :subtitle="$serviceType->name_ar"/>

    <form method="POST" action="{{ route('admin.service-types.update', $serviceType) }}" enctype="multipart/form-data">
        @csrf
        @method('PUT')
        <x-card class="max-w-3xl">
            @include('admin.service-types._form')

            <x-slot:footer>
                <x-button :href="route('admin.service-types.index')" variant="ghost">{{ __('common.cancel') }}</x-button>
                <x-button variant="accent">{{ __('common.save_changes') }}</x-button>
            </x-slot:footer>
        </x-card>
    </form>
</x-layouts.admin>
