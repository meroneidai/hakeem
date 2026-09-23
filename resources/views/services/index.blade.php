<x-layouts.public :title="__('discover.services_page.heading')">
    <x-catalog-hero :title="__('discover.services_page.heading')" :subtitle="__('discover.services_page.subtitle')">
        <x-slot:crumbs>
            <a href="{{ route('home') }}" class="hover:text-primary-700">{{ __('discover.nav.home') }}</a>
            <span aria-hidden="true">·</span>
            <span class="text-ink-700">{{ __('discover.nav.services') }}</span>
        </x-slot:crumbs>
    </x-catalog-hero>

    <div class="mx-auto max-w-6xl px-4 py-8">
        <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
            @forelse ($serviceTypes as $type)
                <a href="{{ route('services.show', $type) }}" class="card flex items-start gap-3 p-4 hover:ring-2 hover:ring-primary-200">
                    <x-media
                        :src="\App\Support\PublicImage::url($type->image_path)"
                        :alt="$type->name"
                        class="size-12 shrink-0 rounded-xl"
                    />
                    <div>
                        <h2 class="font-semibold text-ink-900">{{ $type->name }}</h2>
                        @if ($type->description)
                            <p class="mt-1 text-sm text-ink-500">{{ \Illuminate\Support\Str::limit($type->description, 110) }}</p>
                        @endif
                    </div>
                </a>
            @empty
                <x-card class="sm:col-span-2 lg:col-span-3">
                    <x-empty-state :message="__('discover.services_page.empty')"/>
                </x-card>
            @endforelse
        </div>
    </div>
</x-layouts.public>
