<x-layouts.public :title="$heading ?? __('pages.help.heading')">
    <x-catalog-hero :title="$heading ?? __('pages.help.heading')" :subtitle="$lead ?? __('pages.help.lead')">
        <x-slot:crumbs>
            <a href="{{ route('home') }}" class="hover:text-primary-700">{{ __('discover.nav.home') }}</a>
            <span aria-hidden="true">·</span>
            <span class="text-ink-700">{{ $heading ?? __('pages.help.heading') }}</span>
        </x-slot:crumbs>
        <x-slot:actions>
            <x-button :href="route('contact')" variant="secondary">{{ __('pages.contact.heading') }}</x-button>
        </x-slot:actions>
    </x-catalog-hero>

    <div class="mx-auto max-w-3xl px-4 py-10">
        <div class="grid gap-3">
            @foreach (__('pages.help.items') as $topic => $item)
                <a href="{{ route('help.topic', $topic) }}" class="card p-4 hover:ring-2 hover:ring-primary-200">
                    <h2 class="font-semibold text-ink-900">{{ $item['title'] }}</h2>
                    <p class="mt-2 text-sm leading-7 text-ink-600">{{ $item['body'] }}</p>
                </a>
            @endforeach
        </div>
    </div>
</x-layouts.public>
