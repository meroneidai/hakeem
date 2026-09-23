<div class="grid gap-4 sm:grid-cols-2">
    <x-field :label="__('admin.site_pages.heading_ar')" name="heading_ar" required>
        <x-input name="heading_ar" :value="$page->heading_ar"/>
    </x-field>
    <x-field :label="__('admin.site_pages.heading_en')" name="heading_en" required>
        <x-input name="heading_en" :value="$page->heading_en" dir="ltr"/>
    </x-field>
    <x-field :label="__('common.slug')" name="slug" :hint="$page->is_system ? __('admin.site_pages.system_slug') : __('common.slug_hint')">
        <x-input name="slug" :value="$page->slug" dir="ltr" :disabled="$page->is_system"/>
    </x-field>
    <x-field :label="__('admin.seo.sitemap_priority')" name="sitemap_priority" required>
        <x-input name="sitemap_priority" type="number" min="1" max="10" :value="old('sitemap_priority', $page->sitemap_priority ?: 5)"/>
    </x-field>
    <x-field :label="__('admin.site_pages.intro_ar')" name="intro_ar" class="sm:col-span-2">
        <x-textarea name="intro_ar" :value="$page->intro_ar" rows="2"/>
    </x-field>
    <x-field :label="__('admin.site_pages.intro_en')" name="intro_en" class="sm:col-span-2">
        <x-textarea name="intro_en" :value="$page->intro_en" rows="2" dir="ltr"/>
    </x-field>
    <x-field :label="__('admin.seo.meta_title').' — AR'" name="meta_title_ar">
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
    <x-field :label="__('admin.site_pages.body_ar')" name="body_ar" class="sm:col-span-2">
        <x-rich-editor name="body_ar" :value="$page->body_ar" dir="rtl"/>
    </x-field>
    <x-field :label="__('admin.site_pages.body_en')" name="body_en" class="sm:col-span-2">
        <x-rich-editor name="body_en" :value="$page->body_en" dir="ltr"/>
    </x-field>
    <label class="flex items-center gap-2 text-sm text-ink-700">
        <input type="checkbox" name="is_published" value="1" @checked(old('is_published', $page->is_published))>
        {{ __('admin.articles.published') }}
    </label>
    <label class="flex items-center gap-2 text-sm text-ink-700">
        <input type="checkbox" name="is_indexable" value="1" @checked(old('is_indexable', $page->is_indexable))>
        {{ __('admin.seo.is_indexable') }}
    </label>
</div>
