@php
    $directoryAction = ($place ?? null) instanceof \App\Models\City
        ? route('services.location', [$serviceType, $place->slug])
        : (($place ?? null) instanceof \App\Models\Governorate
            ? route('services.location', [$serviceType, $place->slug])
            : route('services.show', $serviceType));
@endphp

@if (($cities ?? collect())->isNotEmpty())
    <div class="mb-6 flex flex-wrap gap-2">
        @foreach ($cities as $city)
            <a href="{{ route('services.location', [$serviceType, $city->slug]) }}" class="rounded-full bg-white px-3 py-1.5 text-sm text-ink-700 ring-1 ring-ink-200 hover:ring-primary-300">{{ $city->name }}</a>
        @endforeach
    </div>
@endif

<x-provider-filters
    :action="$directoryAction"
    :filters="$filters"
    :specialties="$specialties"
    :governorates="$governorates"
/>

@if ($serviceType->isDoctorLed())
    <div class="mb-4 flex items-end justify-between gap-3">
        <h2 class="text-lg font-semibold text-ink-900">{{ __('discover.services_page.doctors') }}</h2>
        <p class="text-sm text-ink-500">{{ __('discover.search.results', ['count' => $doctors->count()]) }}</p>
    </div>
    <div class="grid gap-4 md:grid-cols-2">
        @forelse ($doctors as $doctor)
            <x-doctor-card :doctor="$doctor" :service-type="$serviceType"/>
        @empty
            <x-card class="md:col-span-2">
                <x-empty-state :message="__('discover.doctors.empty')"/>
            </x-card>
        @endforelse
    </div>
@else
    <div class="mb-4 flex items-end justify-between gap-3">
        <h2 class="text-lg font-semibold text-ink-900">{{ __('discover.services_page.providers') }}</h2>
        <p class="text-sm text-ink-500">{{ __('discover.search.results', ['count' => $clinics->count()]) }}</p>
    </div>
    <div class="grid gap-4 xl:grid-cols-2">
        @forelse ($clinics as $clinic)
            <x-service-clinic-card :clinic="$clinic" :service-type="$serviceType"/>
        @empty
            <x-card class="xl:col-span-2">
                <x-empty-state :message="__('discover.clinics.empty')"/>
            </x-card>
        @endforelse
    </div>
@endif
