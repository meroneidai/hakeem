<x-layouts.admin :title="__('admin.specialties.edit')">
    <x-page-header :title="__('admin.specialties.edit')" :subtitle="$specialty->name_ar"/>

    <form method="POST" action="{{ route('admin.specialties.update', $specialty) }}">
        @csrf
        @method('PUT')
        <x-card class="max-w-3xl">
            @include('admin.specialties._form')

            <x-slot:footer>
                <x-button :href="route('admin.specialties.index')" variant="ghost">{{ __('common.cancel') }}</x-button>
                <x-button variant="accent">{{ __('common.save_changes') }}</x-button>
            </x-slot:footer>
        </x-card>
    </form>
</x-layouts.admin>
