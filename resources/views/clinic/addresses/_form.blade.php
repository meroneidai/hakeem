@php
    $selectedGovernorate = old('governorate_id', $address->city?->governorate_id);
@endphp

<div class="grid gap-4 sm:grid-cols-2"
     x-data="{
         governorateId: '{{ $selectedGovernorate }}',
         cityId: '{{ old('city_id', $address->city_id) }}',
         cities: {{ \Illuminate\Support\Js::from($citiesByGovernorate) }},
         get cityOptions() { return this.cities[this.governorateId] || [] }
     }">
    <x-field :label="__('clinic.addresses.governorate')" name="governorate_id" required>
        <select name="governorate_id" id="governorate_id" x-model="governorateId" @change="cityId = ''"
                class="field-input focus:border-primary-400 focus:ring-2 focus:ring-primary-100">
            <option value="">{{ __('clinic.addresses.governorate') }}</option>
            @foreach ($governorates as $governorate)
                <option value="{{ $governorate->id }}" @selected((string) $selectedGovernorate === (string) $governorate->id)>
                    {{ $governorate->name }}
                </option>
            @endforeach
        </select>
        @error('governorate_id')
            <p class="text-xs font-medium text-danger-500">{{ $message }}</p>
        @enderror
    </x-field>

    <x-field :label="__('clinic.addresses.city')" name="city_id" required>
        <select name="city_id" id="city_id" x-model="cityId"
                class="field-input focus:border-primary-400 focus:ring-2 focus:ring-primary-100">
            <option value="">{{ __('clinic.addresses.city') }}</option>
            <template x-for="city in cityOptions" :key="city.id">
                <option :value="city.id" x-text="city.name" :selected="String(city.id) === String(cityId)"></option>
            </template>
        </select>
        @error('city_id')
            <p class="text-xs font-medium text-danger-500">{{ $message }}</p>
        @enderror
    </x-field>

    <x-field :label="__('clinic.addresses.label_ar')" name="label_ar">
        <x-input name="label_ar" :value="$address->label_ar"/>
    </x-field>
    <x-field :label="__('clinic.addresses.label_en')" name="label_en">
        <x-input name="label_en" :value="$address->label_en" dir="ltr"/>
    </x-field>
    <x-field :label="__('clinic.addresses.address_line')" name="address_line" required class="sm:col-span-2">
        <x-input name="address_line" :value="$address->address_line"/>
    </x-field>
    <x-field :label="__('clinic.addresses.landmark')" name="landmark">
        <x-input name="landmark" :value="$address->landmark"/>
    </x-field>
    <x-field :label="__('clinic.addresses.phone')" name="phone">
        <x-input name="phone" type="tel" :value="$address->phone" dir="ltr"/>
    </x-field>
</div>

<div class="mt-4 flex flex-wrap gap-4">
    <x-checkbox name="is_primary" :label="__('clinic.addresses.primary')" :checked="$address->is_primary"/>
    <x-checkbox name="is_active" :label="__('common.is_active')" :checked="$address->is_active ?? true"/>
</div>
