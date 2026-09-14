<div class="grid gap-4 sm:grid-cols-2">
    <x-field :label="__('admin.cities.governorate')" name="governorate_id" required class="sm:col-span-2">
        <x-select name="governorate_id" :placeholder="__('admin.cities.filter_governorate')"
                  :options="$governorates->pluck('name', 'id')->all()"
                  :selected="$city->governorate_id"/>
    </x-field>

    <x-field :label="__('common.name_ar')" name="name_ar" required>
        <x-input name="name_ar" :value="$city->name_ar"/>
    </x-field>

    <x-field :label="__('common.name_en')" name="name_en" required>
        <x-input name="name_en" :value="$city->name_en" dir="ltr"/>
    </x-field>

    <x-field :label="__('common.slug')" name="slug" :hint="__('common.slug_hint')">
        <x-input name="slug" :value="$city->slug" dir="ltr"/>
    </x-field>

    <x-field :label="__('common.display_order')" name="display_order">
        <x-input name="display_order" type="number" min="0" :value="$city->display_order ?? 0"/>
    </x-field>
</div>

<div class="mt-4">
    <x-checkbox name="is_active" :label="__('common.is_active')" :checked="$city->is_active ?? true"/>
</div>
