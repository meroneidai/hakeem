<x-layouts.admin :title="__('admin.articles.edit')">
    <x-page-header :title="__('admin.articles.edit')" :subtitle="$article->title_ar"/>

    <form method="POST" action="{{ route('admin.articles.update', $article) }}" enctype="multipart/form-data">
        @csrf
        @method('PUT')
        <x-card class="max-w-4xl">
            @include('admin.articles._form')
            <x-slot:footer>
                <x-button :href="route('admin.articles.index')" variant="ghost">{{ __('common.cancel') }}</x-button>
                <x-button variant="accent">{{ __('common.save_changes') }}</x-button>
            </x-slot:footer>
        </x-card>
    </form>
</x-layouts.admin>
