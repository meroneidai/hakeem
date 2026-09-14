<div class="grid gap-4 sm:grid-cols-2">
    <x-field :label="__('common.name_ar')" name="name_ar" required>
        <x-input name="name_ar" :value="$plan->name_ar"/>
    </x-field>

    <x-field :label="__('common.name_en')" name="name_en" required>
        <x-input name="name_en" :value="$plan->name_en" dir="ltr"/>
    </x-field>

    <x-field :label="__('common.description_ar')" name="description_ar">
        <x-textarea name="description_ar" :value="$plan->description_ar" rows="2"/>
    </x-field>

    <x-field :label="__('common.description_en')" name="description_en">
        <x-textarea name="description_en" :value="$plan->description_en" rows="2" dir="ltr"/>
    </x-field>

    <x-field :label="__('admin.plans.monthly_price').' ('.__('common.currency').')'" name="monthly_price" required>
        <x-input name="monthly_price" type="number" step="0.01" min="0" :value="$plan->monthly_price ?? 0"/>
    </x-field>

    <x-field :label="__('admin.plans.yearly_discount_pct')" name="yearly_discount_pct">
        <x-input name="yearly_discount_pct" type="number" min="0" max="100" :value="$plan->yearly_discount_pct ?? 0"/>
    </x-field>

    <x-field :label="__('admin.plans.yearly_price').' ('.__('common.currency').')'" name="yearly_price"
             :hint="__('admin.plans.yearly_price_hint')">
        <x-input name="yearly_price" type="number" step="0.01" min="0" :value="$plan->yearly_price ?? 0"/>
    </x-field>

    <x-field :label="__('common.display_order')" name="display_order">
        <x-input name="display_order" type="number" min="0" :value="$plan->display_order ?? 0"/>
    </x-field>

    <x-field :label="__('admin.plans.booking_cap')" name="booking_cap" :hint="__('admin.plans.cap_hint')">
        <x-input name="booking_cap" type="number" min="0" :value="$plan->booking_cap"/>
    </x-field>

    <x-field :label="__('admin.plans.doctor_cap')" name="doctor_cap" :hint="__('admin.plans.cap_hint')">
        <x-input name="doctor_cap" type="number" min="0" :value="$plan->doctor_cap"/>
    </x-field>

    <x-field :label="__('admin.plans.address_cap')" name="address_cap" :hint="__('admin.plans.cap_hint')">
        <x-input name="address_cap" type="number" min="0" :value="$plan->address_cap"/>
    </x-field>

    <x-field :label="__('common.slug')" name="slug" :hint="__('common.slug_hint')">
        <x-input name="slug" :value="$plan->slug" dir="ltr"/>
    </x-field>
</div>

<div class="mt-4 space-y-2.5">
    <x-checkbox name="is_active" :label="__('common.is_active')" :checked="$plan->is_active ?? true"/>
    <x-checkbox name="is_default_free" :label="__('admin.plans.is_default_free')"
                :hint="__('admin.plans.is_default_free_hint')" :checked="$plan->is_default_free ?? false"/>
</div>

<fieldset class="mt-6 space-y-4 rounded-lg border border-ink-200 p-4">
    <legend class="px-1 text-sm font-medium text-ink-700">{{ __('admin.plans.features') }}</legend>
    <p class="text-xs text-ink-500">{{ __('admin.plans.features_hint') }}</p>

    @foreach ($features as $group => $groupFeatures)
        <div>
            <p class="mb-2 text-xs font-semibold uppercase tracking-wide text-ink-400">
                {{ __('admin.plans.feature_groups.'.$group) }}
            </p>
            <div class="grid gap-2.5 sm:grid-cols-2">
                @foreach ($groupFeatures as $feature)
                    <x-checkbox :name="'features['.$feature->value.']'" :label="$feature->label()"
                                :checked="in_array($feature->value, old('features') ? array_keys(old('features')) : $enabled, true)"/>
                @endforeach
            </div>
        </div>
    @endforeach
</fieldset>
