<x-layouts.public :title="$heading">
    <x-catalog-hero :title="$heading" :subtitle="$intro">
        <x-slot:crumbs>
            <a href="{{ route('home') }}" class="hover:text-primary-700">{{ __('discover.nav.home') }}</a>
            <span aria-hidden="true">·</span>
            <span class="text-ink-700">{{ $heading }}</span>
        </x-slot:crumbs>
    </x-catalog-hero>

    <div class="mx-auto max-w-3xl px-4 py-10">
        <x-card>
            @if (filled($bodyHtml))
                <div class="rich-content text-sm leading-8 text-ink-700">{!! $bodyHtml !!}</div>
            @endif
        </x-card>
    </div>
</x-layouts.public>
