<x-layouts.admin :title="__('admin.articles.create')">
    <x-page-header :title="__('admin.articles.create')"/>

    <form method="POST" action="{{ route('admin.articles.store') }}" enctype="multipart/form-data">
        @csrf
        <x-card class="max-w-4xl">
            @include('admin.articles._form')
            <x-slot:footer>
                <x-button :href="route('admin.articles.index')" variant="ghost">{{ __('common.cancel') }}</x-button>
                <x-button variant="accent">{{ __('common.save') }}</x-button>
            </x-slot:footer>
        </x-card>
    </form>
</x-layouts.admin>
