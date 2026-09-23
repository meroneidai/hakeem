<x-layouts.admin :title="__('admin.insurance_providers.edit')">
    <x-page-header :title="__('admin.insurance_providers.edit')" :subtitle="$provider->name_ar"/>

    <form method="POST" action="{{ route('admin.insurance-providers.update', $provider) }}" enctype="multipart/form-data">
        @csrf
        @method('PUT')
        <x-card class="max-w-3xl">
            @include('admin.insurance-providers._form')

            <x-slot:footer>
                <x-button :href="route('admin.insurance-providers.index')" variant="ghost">{{ __('common.cancel') }}</x-button>
                <x-button variant="accent">{{ __('common.save_changes') }}</x-button>
            </x-slot:footer>
        </x-card>
    </form>
</x-layouts.admin>
