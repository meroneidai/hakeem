<div class="grid gap-4 sm:grid-cols-2">
    <x-field :label="__('common.name_ar')" name="name_ar" required>
        <x-input name="name_ar" :value="$package->name_ar"/>
    </x-field>
    <x-field :label="__('common.name_en')" name="name_en" required>
        <x-input name="name_en" :value="$package->name_en" dir="ltr"/>
    </x-field>
    <x-field :label="__('common.slug')" name="slug" :hint="__('common.slug_hint')">
        <x-input name="slug" :value="$package->slug" dir="ltr"/>
    </x-field>
    <x-field :label="__('common.display_order')" name="display_order">
        <x-input name="display_order" type="number" min="0" :value="$package->display_order ?? 0"/>
    </x-field>
    <x-field :label="__('admin.labs.original_price')" name="original_price" required>
        <x-input name="original_price" type="number" step="0.01" min="0" :value="$package->original_price" dir="ltr"/>
    </x-field>
    <x-field :label="__('admin.labs.package_price')" name="package_price" required>
        <x-input name="package_price" type="number" step="0.01" min="0" :value="$package->package_price" dir="ltr"/>
    </x-field>
    <x-field :label="__('common.description_ar')" name="description_ar" class="sm:col-span-2">
        <x-textarea name="description_ar" :value="$package->description_ar"/>
    </x-field>
    <x-field :label="__('common.description_en')" name="description_en" class="sm:col-span-2">
        <x-textarea name="description_en" :value="$package->description_en" dir="ltr"/>
    </x-field>
    <x-field :label="__('admin.labs.includes').' (AR)'" name="includes_ar">
        <x-textarea name="includes_ar" :value="$package->includes_ar" rows="2"/>
    </x-field>
    <x-field :label="__('admin.labs.includes').' (EN)'" name="includes_en">
        <x-textarea name="includes_en" :value="$package->includes_en" rows="2" dir="ltr"/>
    </x-field>
    <x-field :label="__('admin.labs.conditions').' (AR)'" name="conditions_ar">
        <x-textarea name="conditions_ar" :value="$package->conditions_ar" rows="2"/>
    </x-field>
    <x-field :label="__('admin.labs.conditions').' (EN)'" name="conditions_en">
        <x-textarea name="conditions_en" :value="$package->conditions_en" rows="2" dir="ltr"/>
    </x-field>
    <x-field :label="__('admin.labs.preparation').' (AR)'" name="preparation_ar">
        <x-textarea name="preparation_ar" :value="$package->preparation_ar" rows="2"/>
    </x-field>
    <x-field :label="__('admin.labs.preparation').' (EN)'" name="preparation_en">
        <x-textarea name="preparation_en" :value="$package->preparation_en" rows="2" dir="ltr"/>
    </x-field>
</div>

<fieldset class="mt-6">
    <legend class="mb-2 text-sm font-medium text-ink-700">{{ __('admin.labs.select_tests') }}</legend>
    <div class="grid gap-2 sm:grid-cols-2">
        @foreach ($tests as $test)
            <x-checkbox
                name="test_ids[]"
                :value="$test->id"
                :label="$test->name"
                :checked="in_array($test->id, old('test_ids', $selectedTestIds), false)"
                :with-hidden="false"
            />
        @endforeach
    </div>
    @error('test_ids')
        <p class="mt-2 text-xs font-medium text-danger-500">{{ $message }}</p>
    @enderror
</fieldset>

<x-image-field name="image" :path="$package->image_path" class="mt-6"/>

<div class="mt-4 space-y-2.5">
    <x-checkbox name="is_active" :label="__('common.is_active')" :checked="$package->is_active ?? true"/>
    <x-checkbox name="is_featured" :label="__('admin.labs.is_featured')" :checked="$package->is_featured ?? false"/>
</div>
