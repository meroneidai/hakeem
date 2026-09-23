<x-layouts.public :title="$heading ?? __($copyKey.'.heading')">
    <x-catalog-hero :title="$heading ?? __($copyKey.'.heading')" :subtitle="$lead ?? __($copyKey.'.lead')">
        <x-slot:crumbs>
            <a href="{{ route('home') }}" class="hover:text-primary-700">{{ __('discover.nav.home') }}</a>
            <span aria-hidden="true">·</span>
            <a href="{{ route('services.index') }}" class="hover:text-primary-700">{{ __('discover.nav.services') }}</a>
            <span aria-hidden="true">·</span>
            <span class="text-ink-700">{{ $serviceType->name }}</span>
        </x-slot:crumbs>
    </x-catalog-hero>

    <div class="mx-auto max-w-6xl px-4 pb-10">
        @include('services._directory')
    </div>
</x-layouts.public>
