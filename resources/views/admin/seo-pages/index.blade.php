@php
    $typeOptions = collect(App\Models\SeoPage::PAGE_TYPES)
        ->mapWithKeys(fn ($type) => [$type => __('admin.seo.page_types.'.$type)])
        ->all();
@endphp

<x-layouts.admin :title="__('admin.seo.heading')">
    <x-page-header :title="__('admin.seo.heading')" :subtitle="__('admin.seo.subheading')">
        <x-slot:actions>
            <x-button :href="route('admin.seo.site.edit')" variant="secondary" size="sm">{{ __('admin.seo.site') }}</x-button>
            <form method="GET" class="flex items-center gap-2">
                <x-select name="page_type" :placeholder="__('common.all')" :options="$typeOptions"
                          :selected="request('page_type')" class="w-44"/>
                <x-input name="q" :value="request('q')" :placeholder="__('common.search')" class="w-40" dir="ltr"/>
                <x-button variant="secondary" size="sm">{{ __('common.filter') }}</x-button>
            </form>

            <form method="POST" action="{{ route('admin.seo-pages.generate') }}">
                @csrf
                <x-button variant="accent">{{ __('admin.seo.generate') }}</x-button>
            </form>
        </x-slot:actions>
    </x-page-header>

    <div class="mb-5 flex flex-wrap gap-2">
        @foreach ($typeOptions as $type => $label)
            <a href="{{ route('admin.seo-pages.index', ['page_type' => $type]) }}"
               class="card px-3 py-2 text-xs transition hover:border-primary-300">
                <span class="font-medium text-ink-700">{{ $label }}</span>
                <span class="tabular ms-1.5 font-semibold text-primary-600">{{ $counts[$type] ?? 0 }}</span>
            </a>
        @endforeach
    </div>

    <x-table>
        <x-slot:head>
            <x-th>{{ __('admin.seo.path') }}</x-th>
            <x-th>{{ __('admin.seo.page_type') }}</x-th>
            <x-th>{{ __('admin.seo.meta_title') }}</x-th>
            <x-th>{{ __('admin.seo.sitemap_priority') }}</x-th>
            <x-th>{{ __('admin.seo.is_indexable') }}</x-th>
            <x-th class="text-end">{{ __('common.actions') }}</x-th>
        </x-slot:head>

        @forelse ($pages as $page)
            <tr>
                <x-td><code class="text-xs text-primary-700" dir="ltr">{{ $page->path }}</code></x-td>
                <x-td><x-badge tone="neutral">{{ __('admin.seo.page_types.'.$page->page_type) }}</x-badge></x-td>
                <x-td class="max-w-xs truncate text-sm">
                    {{ $page->meta_title ?? '—' }}
                    @unless ($page->meta_title)
                        <span class="text-xs text-ink-400">({{ __('admin.seo.auto_hint') }})</span>
                    @endunless
                </x-td>
                <x-td class="tabular">{{ $page->sitemap_priority }}</x-td>
                <x-td>
                    <x-badge :tone="$page->is_indexable ? 'success' : 'neutral'">
                        {{ $page->is_indexable ? __('common.yes') : __('common.no') }}
                    </x-badge>
                </x-td>
                <x-td>
                    <x-row-actions :edit="route('admin.seo-pages.edit', $page)"
                                   :destroy="route('admin.seo-pages.destroy', $page)"/>
                </x-td>
            </tr>
        @empty
            <x-empty-state colspan="6"/>
        @endforelse

        @if ($pages->hasPages())
            <x-slot:footer>{{ $pages->links() }}</x-slot:footer>
        @endif
    </x-table>
</x-layouts.admin>
