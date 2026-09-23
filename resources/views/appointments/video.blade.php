<x-layouts.public :title="__('booking.video.heading')" robots="noindex,nofollow">
    <x-catalog-hero :title="__('booking.video.heading')" :subtitle="$booking->doctor?->name">
        <x-slot:crumbs>
            <a href="{{ route('home') }}" class="hover:text-primary-700">{{ __('discover.nav.home') }}</a>
            <span aria-hidden="true">·</span>
            <a href="{{ route('appointments.index') }}" class="hover:text-primary-700">{{ __('booking.my_appointments') }}</a>
            <span aria-hidden="true">·</span>
            <span class="text-ink-700">{{ __('booking.video.heading') }}</span>
        </x-slot:crumbs>
    </x-catalog-hero>

    <div class="mx-auto max-w-5xl space-y-4 px-4 py-8">
        <x-card class="p-4">
            <p class="font-semibold text-ink-900">{{ $booking->doctor?->name }}</p>
            <p class="mt-0.5 text-sm text-ink-500">{{ $booking->clinic?->name }} · {{ $booking->serviceType?->name }}</p>
            <p class="mt-2 text-sm text-ink-600">
                {{ $booking->localScheduledAt()?->format('Y-m-d H:i') }}
                <span class="text-ink-400">{{ __('booking.egypt_time') }}</span>
            </p>
        </x-card>

        @if ($booking->canJoinVideo(auth()->user()))
            <iframe
                src="{{ $booking->videoEmbedUrl() }}"
                allow="camera; microphone; fullscreen; display-capture"
                class="h-[70vh] w-full rounded-[1.5rem] bg-ink-900"
                title="{{ __('booking.video.heading') }}"
            ></iframe>
        @else
            <x-card>
                <p class="text-sm text-ink-600">{{ __('booking.video.waiting') }}</p>
                <div class="mt-4">
                    <x-button :href="route('appointments.index')" variant="secondary">{{ __('booking.my_appointments') }}</x-button>
                </div>
            </x-card>
        @endif
    </div>
</x-layouts.public>
