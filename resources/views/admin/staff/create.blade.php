<x-layouts.admin :title="__('admin.staff.create')">
    <x-page-header :title="__('admin.staff.create')"/>

    <form method="POST" action="{{ route('admin.staff.store') }}">
        @csrf
        <x-card class="max-w-3xl">
            @include('admin.staff._form')

            <x-slot:footer>
                <x-button :href="route('admin.staff.index')" variant="ghost">{{ __('common.cancel') }}</x-button>
                <x-button variant="accent">{{ __('common.save') }}</x-button>
            </x-slot:footer>
        </x-card>
    </form>
</x-layouts.admin>
