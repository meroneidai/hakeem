<x-layouts.admin :title="__('admin.site_pages.heading')">
    <x-page-header :title="__('admin.site_pages.heading')" :subtitle="__('admin.site_pages.subheading')">
        <x-slot:actions>
            <x-button :href="route('admin.site-pages.create')" variant="accent">
                <x-icon name="plus" class="size-4"/>
                {{ __('admin.site_pages.create') }}
            </x-button>
        </x-slot:actions>
    </x-page-header>

    <x-table>
        <x-slot:head>
            <x-th>{{ __('admin.site_pages.heading_label') }}</x-th>
            <x-th>{{ __('admin.seo.path') }}</x-th>
            <x-th>{{ __('common.status') }}</x-th>
            <x-th>{{ __('admin.seo.is_indexable') }}</x-th>
            <x-th class="text-end">{{ __('common.actions') }}</x-th>
        </x-slot:head>
        @forelse ($pages as $page)
            <tr>
                <x-td class="font-medium text-ink-900">
                    {{ $page->heading_ar }}
                    @if ($page->is_system)
                        <x-badge tone="primary">{{ __('admin.site_pages.system') }}</x-badge>
                    @endif
                </x-td>
                <x-td><code dir="ltr">{{ $page->path }}</code></x-td>
                <x-td>
                    <x-badge :tone="$page->is_published ? 'success' : 'neutral'">
                        {{ $page->is_published ? __('admin.articles.published') : __('admin.articles.draft') }}
                    </x-badge>
                </x-td>
                <x-td>{{ $page->is_indexable ? __('common.yes') : __('common.no') }}</x-td>
                <x-td class="text-end">
                    <a href="{{ route('admin.site-pages.edit', $page) }}" class="text-sm font-medium text-primary-700">{{ __('common.edit') }}</a>
                </x-td>
            </tr>
        @empty
            <tr>
                <x-td colspan="5"><x-empty-state :message="__('common.no_results')"/></x-td>
            </tr>
        @endforelse
    </x-table>

    <div class="mt-4">{{ $pages->links() }}</div>
</x-layouts.admin>
