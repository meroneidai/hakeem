<x-layouts.public :title="__('pages.how.heading')">
    <x-catalog-hero :title="__('pages.how.heading')" :subtitle="__('pages.how.lead')">
        <x-slot:crumbs>
            <a href="{{ route('home') }}" class="hover:text-primary-700">{{ __('discover.nav.home') }}</a>
            <span aria-hidden="true">·</span>
            <span class="text-ink-700">{{ __('pages.how.heading') }}</span>
        </x-slot:crumbs>
        <x-slot:actions>
            <x-button :href="route('doctors.index')" variant="accent">{{ __('discover.nav.book') }}</x-button>
        </x-slot:actions>
    </x-catalog-hero>

    <div class="mx-auto max-w-3xl px-4 py-10">
        <ol class="grid gap-3">
            @foreach (__('pages.how.steps') as $index => $step)
                <li class="card p-4">
                    <p class="text-sm font-medium text-ink-800">{{ $index + 1 }}. {{ $step }}</p>
                </li>
            @endforeach
        </ol>
    </div>
</x-layouts.public>
