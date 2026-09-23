<x-layouts.public :title="__('records.verify_heading')">
    <x-catalog-hero :title="__('records.verify_heading')" :subtitle="$document->verification_code">
        <x-slot:crumbs>
            <a href="{{ route('home') }}" class="hover:text-primary-700">{{ __('discover.nav.home') }}</a>
            <span aria-hidden="true">·</span>
            <span class="text-ink-700">{{ __('records.verify_heading') }}</span>
        </x-slot:crumbs>
    </x-catalog-hero>

    <div class="mx-auto max-w-3xl px-4 py-8">
        <x-card class="space-y-3">
            <x-badge tone="success">{{ __('records.verified') }}</x-badge>
            <p class="text-lg font-semibold text-ink-900">{{ $document->type->label() }}</p>
            <p class="text-sm text-ink-600">{{ $document->title }}</p>
            <p class="text-sm text-ink-500">{{ $document->clinic?->name }} · {{ $document->patient?->name }}</p>
            <p class="font-mono text-xl tracking-widest" dir="ltr">{{ $document->verification_code }}</p>
            <p class="text-xs text-ink-400">{{ $document->issued_at?->format('Y-m-d H:i') }}</p>
        </x-card>
    </div>
</x-layouts.public>
