<x-layouts.public :title="$specialty->name">
    <x-catalog-hero :title="$specialty->name" :subtitle="$specialty->description">
        <x-slot:crumbs>
            <a href="{{ route('home') }}" class="hover:text-primary-700">{{ __('discover.nav.home') }}</a>
            <span aria-hidden="true">·</span>
            <a href="{{ route('specialties.index') }}" class="hover:text-primary-700">{{ __('discover.nav.specialties') }}</a>
        </x-slot:crumbs>
    </x-catalog-hero>

    <div class="mx-auto max-w-6xl px-4 py-8">
        <div class="grid gap-4 md:grid-cols-2">
            @forelse ($specialty->doctors as $doctor)
                <x-doctor-card :doctor="$doctor"/>
            @empty
                <x-card class="sm:col-span-2">
                    <x-empty-state :message="__('discover.doctors.empty')"/>
                </x-card>
            @endforelse
        </div>
    </div>
</x-layouts.public>
