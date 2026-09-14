<div class="grid gap-4 sm:grid-cols-2">
    <x-field :label="__('common.name_ar')" name="name_ar" required>
        <x-input name="name_ar" :value="$governorate->name_ar"/>
    </x-field>

    <x-field :label="__('common.name_en')" name="name_en" required>
        <x-input name="name_en" :value="$governorate->name_en" dir="ltr"/>
    </x-field>

    <x-field :label="__('common.slug')" name="slug" :hint="__('common.slug_hint')">
        <x-input name="slug" :value="$governorate->slug" dir="ltr"/>
    </x-field>

    <x-field :label="__('common.display_order')" name="display_order">
        <x-input name="display_order" type="number" min="0" :value="$governorate->display_order ?? 0"/>
    </x-field>
</div>

<div class="mt-4">
    <x-checkbox name="is_active" :label="__('common.is_active')" :checked="$governorate->is_active ?? true"/>
</div>
