<x-layouts.admin :title="__('admin.articles.heading')">
    <x-page-header :title="__('admin.articles.heading')" :subtitle="__('admin.articles.subheading')">
        <x-slot:actions>
            <x-button :href="route('admin.articles.create')" variant="accent">
                <x-icon name="plus" class="size-4"/>
                {{ __('admin.articles.create') }}
            </x-button>
        </x-slot:actions>
    </x-page-header>

    <x-table>
        <x-slot:head>
            <x-th>{{ __('common.title_ar') }}</x-th>
            <x-th>{{ __('admin.articles.category') }}</x-th>
            <x-th>{{ __('common.status') }}</x-th>
            <x-th class="text-end">{{ __('common.actions') }}</x-th>
        </x-slot:head>
        @forelse ($articles as $article)
            <tr>
                <x-td class="font-medium text-ink-900">{{ $article->title_ar }}</x-td>
                <x-td>{{ $article->category->label() }}</x-td>
                <x-td>
                    <x-badge :tone="$article->is_published ? 'success' : 'neutral'">
                        {{ $article->is_published ? __('admin.articles.published') : __('admin.articles.draft') }}
                    </x-badge>
                </x-td>
                <x-td class="text-end">
                    <a href="{{ route('admin.articles.edit', $article) }}" class="text-sm font-medium text-primary-700">{{ __('common.edit') }}</a>
                </x-td>
            </tr>
        @empty
            <tr>
                <x-td colspan="4"><x-empty-state :message="__('common.no_results')"/></x-td>
            </tr>
        @endforelse
    </x-table>

    <div class="mt-4">{{ $articles->links() }}</div>
</x-layouts.admin>
