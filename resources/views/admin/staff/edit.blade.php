<x-layouts.admin :title="__('admin.staff.edit')">
    <x-page-header :title="__('admin.staff.edit')" :subtitle="$member->name"/>

    <form method="POST" action="{{ route('admin.staff.update', $member) }}">
        @csrf
        @method('PUT')
        <x-card class="max-w-3xl">
            @include('admin.staff._form')

            <x-slot:footer>
                <x-button :href="route('admin.staff.index')" variant="ghost">{{ __('common.cancel') }}</x-button>
                <x-button variant="accent">{{ __('common.save_changes') }}</x-button>
            </x-slot:footer>
        </x-card>
    </form>
</x-layouts.admin>
