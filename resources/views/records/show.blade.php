@php
    $clinicView = $clinicView ?? false;
@endphp

<x-layouts.public :title="$document->title" robots="noindex,nofollow">
    <x-catalog-hero :title="$document->title" :subtitle="$document->type->label()">
        <x-slot:crumbs>
            @if ($clinicView)
                <a href="{{ route('clinic.queue.index') }}" class="hover:text-primary-700">{{ __('clinic.nav.queue') }}</a>
            @else
                <a href="{{ route('records.index') }}" class="hover:text-primary-700">{{ __('records.heading') }}</a>
            @endif
            <span aria-hidden="true">·</span>
            <span class="text-ink-700">{{ $document->verification_code }}</span>
        </x-slot:crumbs>
        <x-slot:actions>
            <x-button :href="route('records.pdf', $document)" variant="accent">
                <x-icon name="download" class="size-4"/>
                {{ __('records.download_pdf') }}
            </x-button>
        </x-slot:actions>
    </x-catalog-hero>

    <div class="mx-auto max-w-3xl space-y-4 px-4 py-8">
        @include('records._body', ['document' => $document])
    </div>
</x-layouts.public>
