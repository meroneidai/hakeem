<div class="grid gap-4 sm:grid-cols-2">
    <x-field :label="__('admin.service_types.code')" name="code" required :hint="__('admin.service_types.code_hint')">
        <x-input name="code" :value="$serviceType->code" dir="ltr" placeholder="clinic_appointment"/>
    </x-field>

    <x-field :label="__('common.slug')" name="slug" :hint="__('common.slug_hint')">
        <x-input name="slug" :value="$serviceType->slug" dir="ltr"/>
    </x-field>

    <x-field :label="__('common.name_ar')" name="name_ar" required>
        <x-input name="name_ar" :value="$serviceType->name_ar"/>
    </x-field>

    <x-field :label="__('common.name_en')" name="name_en" required>
        <x-input name="name_en" :value="$serviceType->name_en" dir="ltr"/>
    </x-field>

    <x-field :label="__('common.description_ar')" name="description_ar">
        <x-textarea name="description_ar" :value="$serviceType->description_ar"/>
    </x-field>

    <x-field :label="__('common.description_en')" name="description_en">
        <x-textarea name="description_en" :value="$serviceType->description_en" dir="ltr"/>
    </x-field>

    <x-field :label="__('admin.specialties.icon')" name="icon">
        <x-input name="icon" :value="$serviceType->icon" dir="ltr"/>
    </x-field>

    <x-field :label="__('common.display_order')" name="display_order">
        <x-input name="display_order" type="number" min="0" :value="$serviceType->display_order ?? 0"/>
    </x-field>
</div>

<fieldset class="mt-5 rounded-lg border border-ink-200 p-4">
    <legend class="px-1 text-sm font-medium text-ink-700">{{ __('admin.service_types.rules') }}</legend>

    <div class="grid gap-2.5 sm:grid-cols-2">
        <x-checkbox name="requires_clinic_address" :label="__('admin.service_types.requires_clinic_address')"
                    :checked="$serviceType->requires_clinic_address ?? false"/>
        <x-checkbox name="requires_patient_address" :label="__('admin.service_types.requires_patient_address')"
                    :checked="$serviceType->requires_patient_address ?? false"/>
        <x-checkbox name="requires_time_slot" :label="__('admin.service_types.requires_time_slot')"
                    :checked="$serviceType->requires_time_slot ?? false"/>
        <x-checkbox name="is_online" :label="__('admin.service_types.is_online')"
                    :checked="$serviceType->is_online ?? false"/>
        <x-checkbox name="is_sensitive" :label="__('admin.service_types.is_sensitive')"
                    :checked="$serviceType->is_sensitive ?? false"/>
        <x-checkbox name="is_active" :label="__('common.is_active')"
                    :checked="$serviceType->is_active ?? true"/>
    </div>
</fieldset>
