<x-provider-filters
    :action="route('services.show', $serviceType)"
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
