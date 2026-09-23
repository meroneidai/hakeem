<x-layouts.public :title="__('discover.clinics.heading')">
    <x-catalog-hero :title="__('discover.clinics.heading')" :subtitle="__('discover.clinics.subtitle')">
        <x-slot:crumbs>
            <a href="{{ route('home') }}" class="hover:text-primary-700">{{ __('discover.nav.home') }}</a>
            <span aria-hidden="true">·</span>
            <span class="text-ink-700">{{ __('discover.nav.clinics') }}</span>
        </x-slot:crumbs>
    </x-catalog-hero>

    <div class="mx-auto max-w-6xl px-4 pb-10">
        <form method="GET" class="card relative z-10 -mt-6 mb-8 grid gap-3 p-4 sm:grid-cols-2 lg:grid-cols-5"
              x-data="{
                  governorate: '{{ $filters['governorate'] ?? '' }}',
                  city: '{{ $filters['city'] ?? '' }}',
                  cities: {{ \Illuminate\Support\Js::from($governorates->mapWithKeys(fn ($g) => [$g->slug => $g->cities->map(fn ($c) => ['slug' => $c->slug, 'name' => $c->name])])) }},
                  get cityOptions() { return this.cities[this.governorate] || [] }
              }">
            <x-input name="q" :value="$filters['q'] ?? ''" :placeholder="__('discover.search_placeholder')"/>
            <x-select name="specialty" :placeholder="__('admin.nav.specialties')"
                      :options="$specialties->pluck('name', 'slug')->all()" :selected="$filters['specialty'] ?? ''"/>
            <select name="governorate" x-model="governorate" @change="city = ''"
                    class="field-input focus:border-primary-400 focus:ring-2 focus:ring-primary-100">
                <option value="">{{ __('admin.cities.governorate') }}</option>
                @foreach ($governorates as $governorate)
                    <option value="{{ $governorate->slug }}">{{ $governorate->name }}</option>
                @endforeach
            </select>
            <select name="city" x-model="city" class="field-input focus:border-primary-400 focus:ring-2 focus:ring-primary-100">
                <option value="">{{ __('discover.doctors.any_city') }}</option>
                <template x-for="option in cityOptions" :key="option.slug">
                    <option :value="option.slug" x-text="option.name" :selected="option.slug === city"></option>
                </template>
            </select>
            <x-button variant="accent">{{ __('common.filter') }}</x-button>
        </form>

        <div class="grid gap-3 md:grid-cols-2 lg:grid-cols-3">
            @forelse ($clinics as $clinic)
                <x-clinic-card :clinic="$clinic"/>
            @empty
                <x-card class="sm:col-span-2 lg:col-span-3">
                    <x-empty-state :message="__('discover.clinics.empty')"/>
                </x-card>
            @endforelse
        </div>

        @if ($clinics->hasPages())
            <div class="mt-6">{{ $clinics->links() }}</div>
        @endif

        <div class="mt-10">
            <h2 class="mb-3 text-lg font-semibold text-ink-900">{{ __('discover.clinics.map') }}</h2>
            <div id="public-clinics-map" class="h-80 overflow-hidden rounded-2xl border border-ink-200"></div>
        </div>
    </div>

    @push('head')
        <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" crossorigin="">
    @endpush
    @push('scripts')
        <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" crossorigin=""></script>
        <script>
            const pins = @json($mapPins);
            if (pins.length && document.getElementById('public-clinics-map')) {
                const map = L.map('public-clinics-map').setView([pins[0].lat, pins[0].lng], 11);
                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { maxZoom: 18 }).addTo(map);
                const bounds = [];
                pins.forEach((pin) => {
                    L.marker([pin.lat, pin.lng]).addTo(map).bindPopup(pin.name);
                    bounds.push([pin.lat, pin.lng]);
                });
                if (bounds.length) map.fitBounds(bounds, { padding: [20, 20] });
            }
        </script>
    @endpush
</x-layouts.public>
