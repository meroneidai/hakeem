<x-layouts.public :title="__('records.heading')" robots="noindex,nofollow">
    <x-catalog-hero :title="__('records.heading')" :subtitle="__('records.subtitle')">
        <x-slot:crumbs>
            <a href="{{ route('home') }}" class="hover:text-primary-700">{{ __('discover.nav.home') }}</a>
            <span aria-hidden="true">·</span>
            <span class="text-ink-700">{{ __('records.heading') }}</span>
        </x-slot:crumbs>
        <x-slot:actions>
            <x-button :href="route('appointments.index')" variant="secondary">{{ __('booking.my_appointments') }}</x-button>
        </x-slot:actions>
    </x-catalog-hero>

    <div class="mx-auto max-w-4xl px-4 py-8">
        <div class="space-y-3">
            @forelse ($documents as $document)
                <a href="{{ route('records.show', $document) }}" class="card block p-4 hover:ring-2 hover:ring-primary-200">
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div>
                            <x-badge :tone="$document->type->tone()">{{ $document->type->label() }}</x-badge>
                            <p class="mt-2 font-semibold text-ink-900">{{ $document->title }}</p>
                            <p class="mt-1 text-sm text-ink-500">
                                {{ $document->clinic?->name }}
                                @if ($document->doctor)
                                    · {{ $document->doctor->name }}
                                @endif
                            </p>
                        </div>
                        <div class="text-end text-xs text-ink-400">
                            <p>{{ $document->issued_at?->format('Y-m-d H:i') }}</p>
                            <p class="mt-1 font-mono" dir="ltr">{{ $document->verification_code }}</p>
                        </div>
                    </div>
                </a>
            @empty
                <x-card>
                    <x-empty-state :message="__('records.empty')"/>
                </x-card>
            @endforelse
        </div>

        @if ($documents->hasPages())
            <div class="mt-6">{{ $documents->links() }}</div>
        @endif
    </div>
</x-layouts.public>
