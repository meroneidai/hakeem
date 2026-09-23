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
    <x-field :label="__('clinic.addresses.lat')" name="latitude">
        <x-input name="latitude" id="latitude" type="number" step="0.0000001" min="-90" max="90"
                 :value="old('latitude', $address->latitude)" dir="ltr"/>
    </x-field>
    <x-field :label="__('clinic.addresses.lng')" name="longitude">
        <x-input name="longitude" id="longitude" type="number" step="0.0000001" min="-180" max="180"
                 :value="old('longitude', $address->longitude)" dir="ltr"/>
    </x-field>
    <div class="sm:col-span-2">
        <p class="mb-2 text-sm font-medium text-ink-700">{{ __('clinic.addresses.map') }}</p>
        <p class="mb-2 text-xs text-ink-500">{{ __('clinic.addresses.map_hint') }}</p>
        <div id="clinic-map" class="h-64 overflow-hidden rounded-xl border border-ink-200"></div>
    </div>
</div>

@push('head')
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" crossorigin="">
@endpush
@push('scripts')
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" crossorigin=""></script>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const latInput = document.getElementById('latitude');
            const lngInput = document.getElementById('longitude');
            const mapEl = document.getElementById('clinic-map');
            if (!latInput || !lngInput || !mapEl || typeof L === 'undefined') {
                return;
            }

            const fallback = [30.0444, 31.2357];
            const start = [
                parseFloat(latInput.value) || fallback[0],
                parseFloat(lngInput.value) || fallback[1],
            ];

            const map = L.map(mapEl).setView(start, latInput.value ? 16 : 12);
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                maxZoom: 19,
                attribution: '&copy; OpenStreetMap',
            }).addTo(map);

            const marker = L.marker(start, { draggable: true }).addTo(map);

            const sync = (latlng) => {
                latInput.value = latlng.lat.toFixed(7);
                lngInput.value = latlng.lng.toFixed(7);
            };

            if (!latInput.value || !lngInput.value) {
                sync(marker.getLatLng());
            }

            marker.on('dragend', () => sync(marker.getLatLng()));
            map.on('click', (event) => {
                marker.setLatLng(event.latlng);
                sync(event.latlng);
            });
        });
    </script>
@endpush

<div class="mt-4 flex flex-wrap gap-4">
    <x-checkbox name="is_primary" :label="__('clinic.addresses.primary')" :checked="$address->is_primary"/>
    <x-checkbox name="is_active" :label="__('common.is_active')" :checked="$address->is_active ?? true"/>
</div>
