@php
    $discountTypes = [
        'percentage' => __('admin.discount_codes.percentage'),
        'fixed' => __('admin.discount_codes.fixed'),
        'custom' => __('admin.promotions.custom'),
    ];
@endphp

<div class="grid gap-4 sm:grid-cols-2">
    <x-field :label="__('common.title_ar')" name="title_ar" required>
        <x-input name="title_ar" :value="$promotion->title_ar"/>
    </x-field>

    <x-field :label="__('common.title_en')" name="title_en" required>
        <x-input name="title_en" :value="$promotion->title_en" dir="ltr"/>
    </x-field>

    <x-field :label="__('common.description_ar')" name="description_ar">
        <x-textarea name="description_ar" :value="$promotion->description_ar" rows="2"/>
    </x-field>

    <x-field :label="__('common.description_en')" name="description_en">
        <x-textarea name="description_en" :value="$promotion->description_en" rows="2" dir="ltr"/>
    </x-field>

    <x-field :label="__('admin.promotions.discount_type')" name="discount_type" required>
        <x-select name="discount_type" :options="$discountTypes" :selected="$promotion->discount_type"/>
    </x-field>

    <x-field :label="__('admin.promotions.discount_value')" name="discount_value">
        <x-input name="discount_value" type="number" step="0.01" min="0" :value="$promotion->discount_value"/>
    </x-field>

    <x-field :label="__('admin.promotions.discount_details')" name="discount_details"
             :hint="__('admin.promotions.discount_details_hint')" class="sm:col-span-2">
        <x-input name="discount_details" :value="$promotion->discount_details"/>
    </x-field>

    <x-field :label="__('admin.promotions.category')" name="category" required>
        <x-select name="category"
                  :options="collect(App\Enums\OfferCategory::cases())->mapWithKeys(fn ($category) => [$category->value => $category->label()])->all()"
                  :selected="old('category', $promotion->category?->value)"/>
    </x-field>

    <x-field :label="__('admin.promotions.clinic')" name="clinic_id">
        <x-select name="clinic_id" :placeholder="__('admin.promotions.platform_wide')"
                  :options="$clinics->pluck('name', 'id')->all()" :selected="$promotion->clinic_id"/>
    </x-field>

    <x-field :label="__('admin.promotions.original_price')" name="original_price">
        <x-input name="original_price" type="number" step="0.01" min="0" :value="$promotion->original_price" dir="ltr"/>
    </x-field>

    <x-field :label="__('admin.promotions.offer_price')" name="offer_price">
        <x-input name="offer_price" type="number" step="0.01" min="0" :value="$promotion->offer_price" dir="ltr"/>
    </x-field>

    <x-field :label="__('admin.promotions.includes').' (AR)'" name="includes_ar" class="sm:col-span-2">
        <x-textarea name="includes_ar" :value="$promotion->includes_ar" rows="2"/>
    </x-field>
    <x-field :label="__('admin.promotions.includes').' (EN)'" name="includes_en" class="sm:col-span-2">
        <x-textarea name="includes_en" :value="$promotion->includes_en" rows="2" dir="ltr"/>
    </x-field>
    <x-field :label="__('admin.promotions.conditions').' (AR)'" name="conditions_ar" class="sm:col-span-2">
        <x-textarea name="conditions_ar" :value="$promotion->conditions_ar" rows="2"/>
    </x-field>
    <x-field :label="__('admin.promotions.conditions').' (EN)'" name="conditions_en" class="sm:col-span-2">
        <x-textarea name="conditions_en" :value="$promotion->conditions_en" rows="2" dir="ltr"/>
    </x-field>

    <x-field :label="__('admin.promotions.specialty')" name="specialty_id">
        <x-select name="specialty_id" :placeholder="__('common.none')"
                  :options="$specialties->pluck('name', 'id')->all()" :selected="$promotion->specialty_id"/>
    </x-field>

    <x-field :label="__('admin.promotions.service_type')" name="service_type_id">
        <x-select name="service_type_id" :placeholder="__('common.none')"
                  :options="$serviceTypes->pluck('name', 'id')->all()" :selected="$promotion->service_type_id"/>
    </x-field>

    <x-field :label="__('admin.promotions.starts_at')" name="starts_at" required>
        <x-input name="starts_at" type="datetime-local" :value="$promotion->starts_at?->format('Y-m-d\TH:i')"/>
    </x-field>

    <x-field :label="__('admin.promotions.ends_at')" name="ends_at" required>
        <x-input name="ends_at" type="datetime-local" :value="$promotion->ends_at?->format('Y-m-d\TH:i')"/>
    </x-field>

    <x-field :label="__('common.slug')" name="slug" :hint="__('common.slug_hint')" class="sm:col-span-2">
        <x-input name="slug" :value="$promotion->slug" dir="ltr"/>
    </x-field>

    <x-image-field name="banner" :path="$promotion->banner_image_path" :label="__('common.banner')"/>
</div>

<div class="mt-4 space-y-2.5">
    <x-checkbox name="is_active" :label="__('common.is_active')" :checked="$promotion->is_active ?? true"/>
    <x-checkbox name="is_featured" :label="__('admin.promotions.is_featured')" :checked="$promotion->is_featured ?? false"/>
</div>
