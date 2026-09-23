<x-layouts.admin :title="__('admin.site_pages.create')">
    <x-page-header :title="__('admin.site_pages.create')"/>

    <form method="POST" action="{{ route('admin.site-pages.store') }}">
        @csrf
        <x-card class="max-w-4xl">
            @include('admin.site-pages._form')
            <x-slot:footer>
                <x-button :href="route('admin.site-pages.index')" variant="ghost">{{ __('common.cancel') }}</x-button>
                <x-button variant="accent">{{ __('common.save') }}</x-button>
            </x-slot:footer>
        </x-card>
    </form>
</x-layouts.admin>
