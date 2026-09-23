@props([
    'action',
    'filters' => [],
    'specialties',
    'governorates',
    'showType' => false,
    'live' => false,
    'type' => 'all',
])

@php
    $searchValue = $filters['q'] ?? '';
    $searchPlaceholder = __('discover.search_placeholder');
@endphp

<form method="GET" action="{{ $action }}" {{ $attributes->merge(['class' => 'card relative z-20 -mt-6 mb-8 p-3 shadow-sm sm:p-4']) }}>
    <div class="flex flex-col gap-3 lg:flex-row lg:items-center">
        <div class="min-w-0 flex-1">
            @if ($live)
                <x-input
                    name="q"
                    type="search"
                    enterkeyhint="search"
                    autocomplete="off"
                    :value="$searchValue"
                    :placeholder="$searchPlaceholder"
                    :aria-label="$searchPlaceholder"
                    class="min-h-12 text-base"
                    x-model="q"
                    @input="schedule()"
                />
            @else
                <x-input
                    name="q"
                    type="search"
                    enterkeyhint="search"
                    autocomplete="off"
                    :value="$searchValue"
                    :placeholder="$searchPlaceholder"
                    :aria-label="$searchPlaceholder"
                    class="min-h-12 text-base"
                />
            @endif
        </div>

        <div class="flex flex-wrap items-center gap-2">
            <select name="specialty" @if ($live) x-model="specialty" @change="schedule()" @endif class="field-input min-h-11 min-w-[9.5rem] flex-1 lg:w-44 lg:flex-none focus:border-primary-400 focus:ring-2 focus:ring-primary-100">
                <option value="">{{ __('discover.mega.all_specialties') }}</option>
                @foreach ($specialties as $specialty)
                    <option value="{{ $specialty->slug }}" @selected(($filters['specialty'] ?? '') === $specialty->slug)>{{ $specialty->name }}</option>
                @endforeach
            </select>

            <select name="city" @if ($live) x-model="city" @change="schedule()" @endif class="field-input min-h-11 min-w-[9.5rem] flex-1 lg:w-40 lg:flex-none focus:border-primary-400 focus:ring-2 focus:ring-primary-100">
                <option value="">{{ __('discover.doctors.any_city') }}</option>
                @foreach ($governorates as $governorate)
                    <optgroup label="{{ $governorate->name }}">
                        @foreach ($governorate->cities as $city)
                            <option value="{{ $city->slug }}" @selected(($filters['city'] ?? '') === $city->slug)>{{ $city->name }}</option>
                        @endforeach
                    </optgroup>
                @endforeach
            </select>

            <select name="gender" @if ($live) x-model="gender" @change="schedule()" @endif class="field-input min-h-11 min-w-[7.5rem] flex-1 lg:w-32 lg:flex-none focus:border-primary-400 focus:ring-2 focus:ring-primary-100">
                <option value="">{{ __('discover.doctors.any_gender') }}</option>
                <option value="male" @selected(($filters['gender'] ?? '') === 'male')>{{ __('discover.doctors.male') }}</option>
                <option value="female" @selected(($filters['gender'] ?? '') === 'female')>{{ __('discover.doctors.female') }}</option>
            </select>

            @if ($showType)
                <select name="type" @if ($live) x-model="type" @change="schedule()" @endif class="field-input min-h-11 min-w-[7.5rem] flex-1 lg:w-36 lg:flex-none focus:border-primary-400 focus:ring-2 focus:ring-primary-100">
                    <option value="all" @selected($type === 'all')>{{ __('discover.search.all') }}</option>
                    <option value="doctors" @selected($type === 'doctors')>{{ __('discover.nav.doctors') }}</option>
                    <option value="clinics" @selected($type === 'clinics')>{{ __('discover.nav.clinics') }}</option>
                    <option value="services" @selected($type === 'services')>{{ __('discover.nav.services') }}</option>
                    <option value="offers" @selected($type === 'offers')>{{ __('discover.nav.offers') }}</option>
                    <option value="labs" @selected($type === 'labs')>{{ __('discover.nav.labs') }}</option>
                </select>
            @endif

            <x-button variant="accent" class="min-h-11 min-w-[7.5rem] flex-1 lg:flex-none lg:px-5">{{ __('discover.search.filters') }}</x-button>
        </div>
    </div>

    {{ $slot }}
</form>
