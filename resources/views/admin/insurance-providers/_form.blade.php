<div class="grid gap-4 sm:grid-cols-2">
    <x-field :label="__('common.name_ar')" name="name_ar" required>
        <x-input name="name_ar" :value="$provider->name_ar"/>
    </x-field>

    <x-field :label="__('common.name_en')" name="name_en" required>
        <x-input name="name_en" :value="$provider->name_en" dir="ltr"/>
    </x-field>

    <x-field :label="__('admin.insurance_providers.hotline')" name="hotline">
        <x-input name="hotline" :value="$provider->hotline" dir="ltr" placeholder="16000"/>
    </x-field>

    <x-field :label="__('common.slug')" name="slug" :hint="__('common.slug_hint')">
        <x-input name="slug" :value="$provider->slug" dir="ltr"/>
    </x-field>

    <x-field :label="__('admin.insurance_providers.notes_ar')" name="notes_ar">
        <x-textarea name="notes_ar" :value="$provider->notes_ar"/>
    </x-field>

    <x-field :label="__('admin.insurance_providers.notes_en')" name="notes_en">
        <x-textarea name="notes_en" :value="$provider->notes_en" dir="ltr"/>
    </x-field>

    <x-field :label="__('common.display_order')" name="display_order">
        <x-input name="display_order" type="number" min="0" :value="$provider->display_order ?? 0"/>
    </x-field>

    <x-image-field name="image" :path="$provider->image_path"/>
</div>

<div class="mt-4">
    <x-checkbox name="is_active" :label="__('common.is_active')" :checked="$provider->is_active ?? true"/>
</div>
