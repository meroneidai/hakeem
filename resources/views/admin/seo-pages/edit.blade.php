<x-layouts.admin :title="__('admin.seo.edit')">
    <x-page-header :title="__('admin.seo.edit')" :subtitle="$page->path"/>

    <form method="POST" action="{{ route('admin.seo-pages.update', $page) }}">
        @csrf
        @method('PUT')

        <x-card class="max-w-4xl">
            <div class="mb-5 flex flex-wrap items-center gap-2 rounded-lg bg-ink-50 px-3 py-2 text-xs">
                <x-badge tone="primary">{{ __('admin.seo.page_types.'.$page->page_type) }}</x-badge>
                @foreach ([$page->governorate, $page->city, $page->specialty, $page->serviceType] as $relation)
                    @if ($relation)
                        <x-badge>{{ $relation->name }}</x-badge>
                    @endif
                @endforeach
                <code class="ms-auto text-primary-700" dir="ltr">{{ $page->path }}</code>
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                <x-field :label="__('admin.seo.meta_title').' — AR'" name="meta_title_ar" :hint="__('admin.seo.auto_hint')">
                    <x-input name="meta_title_ar" :value="$page->meta_title_ar" maxlength="255"/>
                </x-field>

                <x-field :label="__('admin.seo.meta_title').' — EN'" name="meta_title_en">
                    <x-input name="meta_title_en" :value="$page->meta_title_en" dir="ltr" maxlength="255"/>
                </x-field>

                <x-field :label="__('admin.seo.meta_description').' — AR'" name="meta_description_ar">
                    <x-textarea name="meta_description_ar" :value="$page->meta_description_ar" rows="2" maxlength="320"/>
                </x-field>

                <x-field :label="__('admin.seo.meta_description').' — EN'" name="meta_description_en">
                    <x-textarea name="meta_description_en" :value="$page->meta_description_en" rows="2" dir="ltr" maxlength="320"/>
                </x-field>

                <x-field :label="__('admin.seo.h1').' — AR'" name="h1_ar">
                    <x-input name="h1_ar" :value="$page->h1_ar"/>
                </x-field>

                <x-field :label="__('admin.seo.h1').' — EN'" name="h1_en">
                    <x-input name="h1_en" :value="$page->h1_en" dir="ltr"/>
                </x-field>

                <x-field :label="__('admin.seo.intro_content').' — AR'" name="intro_content_ar">
                    <x-textarea name="intro_content_ar" :value="$page->intro_content_ar" rows="5"/>
                </x-field>

                <x-field :label="__('admin.seo.intro_content').' — EN'" name="intro_content_en">
                    <x-textarea name="intro_content_en" :value="$page->intro_content_en" rows="5" dir="ltr"/>
                </x-field>

                <x-field :label="__('admin.seo.sitemap_priority')" name="sitemap_priority" required>
                    <x-input name="sitemap_priority" type="number" min="1" max="10" :value="$page->sitemap_priority"/>
                </x-field>
            </div>

            <div class="mt-4">
                <x-checkbox name="is_indexable" :label="__('admin.seo.is_indexable')" :checked="$page->is_indexable"/>
            </div>

            <x-slot:footer>
                <x-button :href="route('admin.seo-pages.index')" variant="ghost">{{ __('common.cancel') }}</x-button>
                <x-button variant="accent">{{ __('common.save_changes') }}</x-button>
            </x-slot:footer>
        </x-card>
    </form>
</x-layouts.admin>
