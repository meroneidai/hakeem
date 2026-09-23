@php
    $categoryOptions = collect(App\Models\Specialty::CATEGORIES)
        ->mapWithKeys(fn ($category) => [$category => __('admin.specialties.categories.'.$category)])
        ->all();
@endphp

<div class="grid gap-4 sm:grid-cols-2">
    <x-field :label="__('common.name_ar')" name="name_ar" required>
        <x-input name="name_ar" :value="$specialty->name_ar"/>
    </x-field>

    <x-field :label="__('common.name_en')" name="name_en" required>
        <x-input name="name_en" :value="$specialty->name_en" dir="ltr"/>
    </x-field>

    <x-field :label="__('admin.specialties.category')" name="category" required>
        <x-select name="category" :options="$categoryOptions" :selected="$specialty->category"/>
    </x-field>

    <x-field :label="__('common.slug')" name="slug" :hint="__('common.slug_hint')">
        <x-input name="slug" :value="$specialty->slug" dir="ltr"/>
    </x-field>

    <x-field :label="__('common.description_ar')" name="description_ar">
        <x-textarea name="description_ar" :value="$specialty->description_ar"/>
    </x-field>

    <x-field :label="__('common.description_en')" name="description_en">
        <x-textarea name="description_en" :value="$specialty->description_en" dir="ltr"/>
    </x-field>

    <x-field :label="__('admin.specialties.icon')" name="icon">
        <x-input name="icon" :value="$specialty->icon" dir="ltr" placeholder="stethoscope"/>
    </x-field>

    <x-field :label="__('common.display_order')" name="display_order">
        <x-input name="display_order" type="number" min="0" :value="$specialty->display_order ?? 0"/>
    </x-field>

    <x-image-field name="image" :path="$specialty->image_path"/>
</div>

<div class="mt-4 space-y-2.5">
    <x-checkbox name="is_active" :label="__('common.is_active')" :checked="$specialty->is_active ?? true"/>
    <x-checkbox name="is_featured" :label="__('admin.specialties.is_featured')" :checked="$specialty->is_featured ?? false"/>
</div>
