<x-layouts.admin :title="__('admin.site_pages.edit')">
    <x-page-header :title="__('admin.site_pages.edit')" :subtitle="$page->path">
        <x-slot:actions>
            @if (! $page->is_system)
                <form method="POST" action="{{ route('admin.site-pages.destroy', $page) }}" onsubmit="return confirm(@js(__('common.confirm_delete')))">
                    @csrf
                    @method('DELETE')
                    <x-button variant="danger-ghost">{{ __('common.delete') }}</x-button>
                </form>
            @endif
            <x-button :href="$page->publicUrl()" variant="secondary">{{ __('admin.site_pages.view_public') }}</x-button>
        </x-slot:actions>
    </x-page-header>

    <form method="POST" action="{{ route('admin.site-pages.update', $page) }}">
        @csrf
        @method('PUT')
        <x-card class="max-w-4xl">
            @include('admin.site-pages._form')
            <x-slot:footer>
                <x-button :href="route('admin.site-pages.index')" variant="ghost">{{ __('common.cancel') }}</x-button>
                <x-button variant="accent">{{ __('common.save_changes') }}</x-button>
            </x-slot:footer>
        </x-card>
    </form>
</x-layouts.admin>
