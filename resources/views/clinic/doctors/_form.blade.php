<div class="grid gap-4 sm:grid-cols-2">
    <x-field :label="__('common.name_ar')" name="name_ar" required>
        <x-input name="name_ar" :value="$doctor->name_ar"/>
    </x-field>
    <x-field :label="__('common.name_en')" name="name_en" required>
        <x-input name="name_en" :value="$doctor->name_en" dir="ltr"/>
    </x-field>
    <x-field :label="__('clinic.doctors.specialty')" name="specialty_id" required class="sm:col-span-2">
        <x-select name="specialty_id" :placeholder="__('clinic.doctors.specialty')"
                  :options="$specialties->pluck('name', 'id')->all()"
                  :selected="$doctor->specialty_id"/>
    </x-field>
    <x-field :label="__('clinic.doctors.credentials')" name="credentials">
        <x-input name="credentials" :value="$doctor->credentials"/>
    </x-field>
    <x-field :label="__('clinic.doctors.experience')" name="years_of_experience">
        <x-input name="years_of_experience" type="number" min="0" max="70" :value="$doctor->years_of_experience"/>
    </x-field>
    <x-field :label="__('common.description_ar')" name="bio_ar" class="sm:col-span-2">
        <x-textarea name="bio_ar" :value="$doctor->bio_ar"/>
    </x-field>
    <x-field :label="__('common.description_en')" name="bio_en" class="sm:col-span-2">
        <x-textarea name="bio_en" :value="$doctor->bio_en"/>
    </x-field>
</div>

@if ($addresses->isNotEmpty())
    <fieldset class="mt-6">
        <legend class="mb-2 text-sm font-medium text-ink-700">{{ __('clinic.doctors.addresses') }}</legend>
        <p class="mb-3 text-xs text-ink-500">{{ __('clinic.doctors.addresses_hint') }}</p>
        <div class="space-y-2">
            @foreach ($addresses as $address)
                <x-checkbox
                    name="address_ids[]"
                    :value="$address->id"
                    :label="$address->displayName()"
                    :checked="in_array($address->id, old('address_ids', $selectedAddressIds), false)"
                    :with-hidden="false"
                />
            @endforeach
        </div>
    </fieldset>
@endif

<div class="mt-4">
    <x-checkbox name="is_active" :label="__('common.is_active')" :checked="$doctor->is_active ?? true"/>
</div>
