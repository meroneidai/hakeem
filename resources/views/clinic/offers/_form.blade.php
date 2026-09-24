@php
    $discountTypes = [
        'percentage' => __('admin.discount_codes.percentage'),
        'fixed' => __('admin.discount_codes.fixed'),
        'custom' => __('admin.promotions.custom'),
    ];
    $categoryOptions = collect(App\Enums\OfferCategory::cases())
        ->mapWithKeys(fn ($category) => [$category->value => $category->label()])
        ->all();
@endphp

<div class="grid gap-4 sm:grid-cols-2">
    <x-field :label="__('common.title_ar')" name="title_ar" required>
        <x-input name="title_ar" :value="$offer->title_ar"/>
    </x-field>
    <x-field :label="__('common.title_en')" name="title_en" required>
        <x-input name="title_en" :value="$offer->title_en" dir="ltr"/>
    </x-field>
    <x-field :label="__('admin.promotions.category')" name="category" required>
        <x-select name="category" :options="$categoryOptions" :selected="old('category', $offer->category?->value)"/>
    </x-field>
    <x-field :label="__('admin.promotions.discount_type')" name="discount_type" required>
        <x-select name="discount_type" :options="$discountTypes" :selected="$offer->discount_type"/>
    </x-field>
    <x-field :label="__('admin.promotions.discount_value')" name="discount_value">
        <x-input name="discount_value" type="number" step="0.01" min="0" :value="$offer->discount_value"/>
    </x-field>
    <x-field :label="__('admin.promotions.original_price')" name="original_price">
        <x-input name="original_price" type="number" step="0.01" min="0" :value="$offer->original_price" dir="ltr"/>
    </x-field>
    <x-field :label="__('admin.promotions.offer_price')" name="offer_price">
        <x-input name="offer_price" type="number" step="0.01" min="0" :value="$offer->offer_price" dir="ltr"/>
    </x-field>
    <x-field :label="__('booking.sessions')" name="session_count">
        <x-input name="session_count" type="number" min="1" max="30" :value="$offer->session_count ?? 1" dir="ltr"/>
    </x-field>
    <x-field :label="__('common.description_ar')" name="description_ar" class="sm:col-span-2">
        <x-textarea name="description_ar" :value="$offer->description_ar" rows="2"/>
    </x-field>
    <x-field :label="__('common.description_en')" name="description_en" class="sm:col-span-2">
        <x-textarea name="description_en" :value="$offer->description_en" rows="2" dir="ltr"/>
    </x-field>
    <x-field :label="__('admin.promotions.includes').' (AR)'" name="includes_ar">
        <x-textarea name="includes_ar" :value="$offer->includes_ar" rows="2"/>
    </x-field>
    <x-field :label="__('admin.promotions.includes').' (EN)'" name="includes_en">
        <x-textarea name="includes_en" :value="$offer->includes_en" rows="2" dir="ltr"/>
    </x-field>
    <x-field :label="__('admin.promotions.conditions').' (AR)'" name="conditions_ar">
        <x-textarea name="conditions_ar" :value="$offer->conditions_ar" rows="2"/>
    </x-field>
    <x-field :label="__('admin.promotions.conditions').' (EN)'" name="conditions_en">
        <x-textarea name="conditions_en" :value="$offer->conditions_en" rows="2" dir="ltr"/>
    </x-field>
    <x-field :label="__('admin.promotions.starts_at')" name="starts_at" required>
        <x-input name="starts_at" type="datetime-local" :value="old('starts_at', $offer->starts_at?->format('Y-m-d\TH:i'))"/>
    </x-field>
    <x-field :label="__('admin.promotions.ends_at')" name="ends_at" required>
        <x-input name="ends_at" type="datetime-local" :value="old('ends_at', $offer->ends_at?->format('Y-m-d\TH:i'))"/>
    </x-field>

    <x-image-field name="banner" :path="$offer->banner_image_path" :label="__('common.banner')"/>
</div>

<div class="mt-4 space-y-2.5">
    <x-alert tone="info">{{ __('clinic.offers.approval_hint') }}</x-alert>
    @if ($offer->exists)
        <x-badge :tone="$offer->approval_status?->tone() ?? 'warning'">
            {{ $offer->approval_status?->label() ?? __('admin.promotions.approval.pending') }}
        </x-badge>
        @if ($offer->rejection_reason)
            <p class="text-sm text-danger-600">{{ $offer->rejection_reason }}</p>
        @endif
    @endif
    <x-checkbox name="is_featured" :label="__('admin.promotions.is_featured')" :checked="$offer->is_featured ?? false"/>
</div>
