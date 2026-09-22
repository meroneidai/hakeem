<x-layouts.public :title="__('discover.doctors.heading')">
    <x-catalog-hero :title="__('discover.doctors.heading')" :subtitle="__('discover.doctors.subtitle')">
        <x-slot:crumbs>
            <a href="{{ route('home') }}" class="hover:text-primary-700">{{ __('discover.nav.home') }}</a>
            <span aria-hidden="true">·</span>
            <span class="text-ink-700">{{ __('discover.nav.doctors') }}</span>
        </x-slot:crumbs>
    </x-catalog-hero>

    <div class="mx-auto max-w-6xl px-4 pb-10">
        <form method="GET" class="card relative z-10 -mt-6 mb-8 grid gap-3 p-4 sm:grid-cols-2 lg:grid-cols-7"
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
            <x-select name="gender" :placeholder="__('discover.doctors.any_gender')"
                      :options="['male' => __('discover.doctors.male'), 'female' => __('discover.doctors.female')]"
                      :selected="$filters['gender'] ?? ''"/>
            <x-input name="min_experience" type="number" min="0" :value="$filters['min_experience'] ?? ''"
                     :placeholder="__('discover.doctors.min_experience')"/>
            <x-button variant="accent">{{ __('common.filter') }}</x-button>
        </form>

        <div class="grid gap-4 md:grid-cols-2">
            @forelse ($doctors as $doctor)
                <x-doctor-card :doctor="$doctor"/>
            @empty
                <x-card class="md:col-span-2">
                    <x-empty-state :message="__('discover.doctors.empty')"/>
                </x-card>
            @endforelse
        </div>

        @if ($doctors->hasPages())
            <div class="mt-6">{{ $doctors->links() }}</div>
        @endif
    </div>
</x-layouts.public>
