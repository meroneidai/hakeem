<x-layouts.public :title="__('discover.specialties_page.heading')">
    <x-catalog-hero :title="__('discover.specialties_page.heading')" :subtitle="__('discover.specialties_page.subtitle')">
        <x-slot:crumbs>
            <a href="{{ route('home') }}" class="hover:text-primary-700">{{ __('discover.nav.home') }}</a>
            <span aria-hidden="true">·</span>
            <span class="text-ink-700">{{ __('discover.nav.specialties') }}</span>
        </x-slot:crumbs>
    </x-catalog-hero>

    <div class="mx-auto max-w-6xl px-4 py-8">
        <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
            @forelse ($specialties as $specialty)
                <a href="{{ route('specialties.show', $specialty) }}" class="card flex items-start gap-3 p-4 hover:ring-2 hover:ring-primary-200">
                    <x-media
                        :src="\App\Support\PublicImage::url($specialty->image_path)"
                        :alt="$specialty->name"
                        class="size-12 shrink-0 rounded-xl"
                    />
                    <div>
                        <h2 class="font-semibold text-ink-900">{{ $specialty->name }}</h2>
                        <p class="mt-1 text-sm text-ink-500">{{ __('discover.doctors.count', ['count' => $specialty->doctors_count]) }}</p>
                    </div>
                </a>
            @empty
                <x-card class="sm:col-span-2 lg:col-span-3">
                    <x-empty-state :message="__('discover.specialties_page.empty')"/>
                </x-card>
            @endforelse
        </div>
    </div>
</x-layouts.public>
