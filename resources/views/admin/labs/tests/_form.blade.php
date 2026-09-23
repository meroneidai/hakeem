@php
    $categoryOptions = collect(App\Enums\LabTestCategory::cases())
        ->mapWithKeys(fn ($category) => [$category->value => $category->label()])
        ->all();
    $sampleOptions = collect(App\Enums\SampleType::cases())
        ->mapWithKeys(fn ($sample) => [$sample->value => $sample->label()])
        ->all();
@endphp

<div class="grid gap-4 sm:grid-cols-2">
    <x-field :label="__('common.name_ar')" name="name_ar" required>
        <x-input name="name_ar" :value="$test->name_ar"/>
    </x-field>
    <x-field :label="__('common.name_en')" name="name_en" required>
        <x-input name="name_en" :value="$test->name_en" dir="ltr"/>
    </x-field>
    <x-field :label="__('admin.labs.category')" name="category" required>
        <x-select name="category" :options="$categoryOptions" :selected="old('category', $test->category?->value)"/>
    </x-field>
    <x-field :label="__('admin.labs.sample')" name="sample_type" required>
        <x-select name="sample_type" :options="$sampleOptions" :selected="old('sample_type', $test->sample_type?->value)"/>
    </x-field>
    <x-field :label="__('common.slug')" name="slug" :hint="__('common.slug_hint')">
        <x-input name="slug" :value="$test->slug" dir="ltr"/>
    </x-field>
    <x-field :label="__('admin.labs.suggested_price')" name="suggested_price" required>
        <x-input name="suggested_price" type="number" step="0.01" min="0" :value="$test->suggested_price" dir="ltr"/>
    </x-field>
    <x-field :label="__('common.description_ar')" name="description_ar" class="sm:col-span-2">
        <x-textarea name="description_ar" :value="$test->description_ar"/>
    </x-field>
    <x-field :label="__('common.description_en')" name="description_en" class="sm:col-span-2">
        <x-textarea name="description_en" :value="$test->description_en" dir="ltr"/>
    </x-field>
    <x-field :label="__('admin.labs.measures').' (AR)'" name="measures_ar">
        <x-textarea name="measures_ar" :value="$test->measures_ar" rows="2"/>
    </x-field>
    <x-field :label="__('admin.labs.measures').' (EN)'" name="measures_en">
        <x-textarea name="measures_en" :value="$test->measures_en" rows="2" dir="ltr"/>
    </x-field>
    <x-field :label="__('admin.labs.preparation').' (AR)'" name="preparation_ar">
        <x-textarea name="preparation_ar" :value="$test->preparation_ar" rows="2"/>
    </x-field>
    <x-field :label="__('admin.labs.preparation').' (EN)'" name="preparation_en">
        <x-textarea name="preparation_en" :value="$test->preparation_en" rows="2" dir="ltr"/>
    </x-field>
    <x-field :label="__('admin.labs.contains').' (AR)'" name="contains_ar">
        <x-textarea name="contains_ar" :value="$test->contains_ar" rows="2"/>
    </x-field>
    <x-field :label="__('admin.labs.contains').' (EN)'" name="contains_en">
        <x-textarea name="contains_en" :value="$test->contains_en" rows="2" dir="ltr"/>
    </x-field>
    <x-field :label="__('admin.labs.fasting')" name="fasting_hours">
        <x-input name="fasting_hours" type="number" min="0" max="24" :value="$test->fasting_hours" dir="ltr"/>
    </x-field>
    <x-field :label="__('admin.labs.turnaround')" name="turnaround_hours">
        <x-input name="turnaround_hours" type="number" min="1" max="720" :value="$test->turnaround_hours" dir="ltr"/>
    </x-field>
    <x-field :label="__('common.display_order')" name="display_order">
        <x-input name="display_order" type="number" min="0" :value="$test->display_order ?? 0"/>
    </x-field>

    <x-image-field name="image" :path="$test->image_path" :required="! $test->exists || ! $test->image_path" :hint="__('admin.labs.test_image_hint')"/>
</div>

<div class="mt-4">
    <x-checkbox name="is_active" :label="__('common.is_active')" :checked="$test->is_active ?? true"/>
</div>
