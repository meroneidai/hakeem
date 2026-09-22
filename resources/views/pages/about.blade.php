<x-layouts.public :title="__('pages.about.heading')">
    <x-catalog-hero :title="__('pages.about.heading')" :subtitle="__('pages.about.lead')">
        <x-slot:crumbs>
            <a href="{{ route('home') }}" class="hover:text-primary-700">{{ __('discover.nav.home') }}</a>
            <span aria-hidden="true">·</span>
            <span class="text-ink-700">{{ __('pages.about.heading') }}</span>
        </x-slot:crumbs>
        <x-slot:actions>
            <x-button :href="route('contact')" variant="accent">{{ __('pages.contact.heading') }}</x-button>
        </x-slot:actions>
    </x-catalog-hero>

    <div class="mx-auto max-w-3xl px-4 py-10">
        <div class="grid gap-4">
            <x-card>
                <h2 class="font-semibold text-ink-900">{{ __('pages.about.mission') }}</h2>
                <p class="mt-2 text-sm leading-7 text-ink-600">{{ __('pages.about.mission_body') }}</p>
            </x-card>
            <x-card>
                <h2 class="font-semibold text-ink-900">{{ __('pages.about.vision') }}</h2>
                <p class="mt-2 text-sm leading-7 text-ink-600">{{ __('pages.about.vision_body') }}</p>
            </x-card>
        </div>
    </div>
</x-layouts.public>
