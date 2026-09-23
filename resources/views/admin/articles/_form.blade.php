@php
    $specialtyOptions = $specialties->mapWithKeys(fn ($specialty) => [$specialty->id => $specialty->name])->all();
@endphp

<div class="grid gap-4 sm:grid-cols-2">
    <x-field :label="__('common.title_ar')" name="title_ar" required>
        <x-input name="title_ar" :value="$article->title_ar"/>
    </x-field>
    <x-field :label="__('common.title_en')" name="title_en" required>
        <x-input name="title_en" :value="$article->title_en" dir="ltr"/>
    </x-field>
    <x-field :label="__('common.slug')" name="slug" :hint="__('common.slug_hint')">
        <x-input name="slug" :value="$article->slug" dir="ltr"/>
    </x-field>
    <x-field :label="__('admin.articles.category')" name="category" required>
        <x-select name="category" :options="$categories" :selected="old('category', $article->category?->value)"/>
    </x-field>
    <x-field :label="__('admin.nav.specialties')" name="specialty_id">
        <x-select name="specialty_id" :placeholder="__('common.optional')" :options="$specialtyOptions" :selected="old('specialty_id', $article->specialty_id)"/>
    </x-field>
    <x-field :label="__('common.display_order')" name="display_order">
        <x-input name="display_order" type="number" min="0" :value="old('display_order', $article->display_order)"/>
    </x-field>
    <x-field :label="__('admin.articles.excerpt_ar')" name="excerpt_ar" class="sm:col-span-2">
        <x-textarea name="excerpt_ar" :value="$article->excerpt_ar" rows="2"/>
    </x-field>
    <x-field :label="__('admin.articles.excerpt_en')" name="excerpt_en" class="sm:col-span-2">
        <x-textarea name="excerpt_en" :value="$article->excerpt_en" rows="2" dir="ltr"/>
    </x-field>
    <x-field :label="__('admin.articles.body_ar')" name="body_ar" required class="sm:col-span-2">
        <x-rich-editor name="body_ar" :value="$article->body_ar" dir="rtl"/>
    </x-field>
    <x-field :label="__('admin.articles.body_en')" name="body_en" required class="sm:col-span-2">
        <x-rich-editor name="body_en" :value="$article->body_en" dir="ltr"/>
    </x-field>
    <x-image-field :path="$article->image_path"/>
    <label class="flex items-center gap-2 text-sm text-ink-700 sm:col-span-2">
        <input type="checkbox" name="is_published" value="1" @checked(old('is_published', $article->is_published))>
        {{ __('admin.articles.published') }}
    </label>
</div>
