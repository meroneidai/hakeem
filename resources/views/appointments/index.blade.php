<x-layouts.public :title="__('booking.my_appointments')" robots="noindex,nofollow">
    <x-catalog-hero :title="__('booking.my_appointments')" :subtitle="__('booking.my_appointments_subtitle')">
        <x-slot:crumbs>
            <a href="{{ route('home') }}" class="hover:text-primary-700">{{ __('discover.nav.home') }}</a>
            <span aria-hidden="true">·</span>
            <span class="text-ink-700">{{ __('booking.my_appointments') }}</span>
        </x-slot:crumbs>
        <x-slot:actions>
            <x-button :href="route('doctors.index')" variant="accent">{{ __('discover.nav.book') }}</x-button>
        </x-slot:actions>
    </x-catalog-hero>

    <div class="mx-auto max-w-4xl px-4 py-8">
        <div class="space-y-3">
            @forelse ($bookings as $booking)
                <x-card class="p-4">
                    <div class="flex flex-wrap items-start gap-4">
                        <x-media
                            :src="\App\Support\PublicImage::url($booking->doctor?->profile_photo_path)"
                            :alt="$booking->doctor?->name ?? ''"
                            class="size-16 shrink-0 rounded-2xl"
                        />
                        <div class="min-w-0 flex-1">
                            <p class="font-semibold text-ink-900">{{ $booking->doctor?->name }}</p>
                            <p class="mt-0.5 text-sm text-ink-500">
                                {{ $booking->clinic?->name }}
                                @if ($booking->address)
                                    · {{ $booking->address->displayName() }}
                                @endif
                            </p>
                            <p class="mt-1 flex items-center gap-1.5 text-sm text-ink-600">
                                <x-icon name="calendar" class="size-4 text-primary-600"/>
                                {{ $booking->localScheduledAt()?->format('Y-m-d H:i') }}
                                <span class="text-xs text-ink-400">{{ __('booking.egypt_time') }}</span>
                                @if ($booking->serviceType)
                                    · {{ $booking->serviceType->name }}
                                    · {{ __('booking.duration_minutes', ['minutes' => $booking->durationMinutes()]) }}
                                @endif
                            </p>
                            @if ($booking->patient_home_address)
                                <p class="mt-1 text-sm text-ink-500">{{ $booking->patient_home_address }}</p>
                            @endif
                            @if ($booking->is_evaluation)
                                <x-badge tone="accent" class="mt-2">{{ __('booking.evaluation') }}</x-badge>
                            @endif
                            @if (($booking->session_count ?? 1) > 1)
                                <x-badge class="mt-2">{{ __('booking.session_pack', ['count' => $booking->session_count]) }}</x-badge>
                            @endif
                            @if ($booking->promotion)
                                <p class="mt-1 text-xs text-accent-700">{{ $booking->promotion->title }}</p>
                            @endif
                        </div>
                        <div class="flex flex-col items-end gap-1.5">
                            <x-badge :tone="$booking->status->tone()">{{ $booking->status->label() }}</x-badge>
                            <x-badge :tone="$booking->isPaid() ? 'success' : 'warning'">
                                {{ __('booking.payment_status.'.$booking->payment_status) }}
                            </x-badge>
                            <span class="text-xs text-ink-400">{{ $booking->payment_mode->label() }}</span>
                            @if ($booking->isVideoVisit() && $booking->canAccessVideo(auth()->user()))
                                <x-button :href="route('appointments.video', $booking)" variant="accent" size="sm">{{ __('booking.video.join') }}</x-button>
                            @endif
                            @if ($booking->status === \App\Enums\BookingStatus::Completed)
                                <x-button :href="route('records.index')" variant="ghost" size="sm">{{ __('records.heading') }}</x-button>
                            @endif
                            @if ($booking->status->canTransitionTo(\App\Enums\BookingStatus::Cancelled))
                                <form method="POST" action="{{ route('appointments.destroy', $booking) }}" onsubmit="return confirm(@js(__('booking.confirm_cancel')))">
                                    @csrf
                                    @method('DELETE')
                                    <x-button variant="ghost" size="sm">{{ __('booking.cancel') }}</x-button>
                                </form>
                            @endif
                        </div>
                    </div>
                    @if ($booking->canBeReviewedBy(auth()->user()))
                        <form method="POST" action="{{ route('appointments.review', $booking) }}" class="mt-4 grid gap-3 border-t border-ink-100 pt-4 sm:grid-cols-2">
                            @csrf
                            <x-field :label="__('reviews.overall')" name="overall" required>
                                <x-select name="overall" :options="[5 => '5', 4 => '4', 3 => '3', 2 => '2', 1 => '1']" :selected="5"/>
                            </x-field>
                            <x-field :label="__('reviews.body')" name="body">
                                <x-input name="body" maxlength="1000"/>
                            </x-field>
                            <div class="sm:col-span-2">
                                <x-button variant="accent" size="sm">{{ __('reviews.submit') }}</x-button>
                            </div>
                        </form>
                    @elseif ($booking->review)
                        <div class="mt-4 border-t border-ink-100 pt-4">
                            <x-rating :average="$booking->review->overall" :count="1"/>
                            @if ($booking->review->body)
                                <p class="mt-1 text-sm text-ink-500">{{ $booking->review->body }}</p>
                            @endif
                        </div>
                    @endif
                </x-card>
            @empty
                <x-card>
                    <p class="text-sm text-ink-500">{{ __('booking.empty') }}</p>
                    <div class="mt-4">
                        <x-button :href="route('doctors.index')" variant="accent">{{ __('discover.nav.book') }}</x-button>
                    </div>
                </x-card>
            @endforelse
        </div>

        @if ($bookings->hasPages())
            <div class="mt-6">{{ $bookings->links() }}</div>
        @endif

        @if (($labOrders ?? collect())->isNotEmpty())
            <h2 class="mt-10 mb-3 text-lg font-semibold text-ink-900">{{ __('labs.checkout.my_orders') }}</h2>
            <div class="space-y-3">
                @foreach ($labOrders as $order)
                    <x-card class="p-4">
                        <div class="flex flex-wrap items-start justify-between gap-3">
                            <div>
                                <p class="font-semibold text-ink-900">{{ $order->clinic?->name }}</p>
                                <p class="mt-0.5 text-sm text-ink-500">{{ $order->reference }} · {{ $order->scheduled_at->format('Y-m-d H:i') }}</p>
                                <p class="mt-1 text-sm text-ink-600">
                                    {{ $order->items->map(fn ($item) => $item->catalogItem()?->name)->filter()->join('، ') }}
                                </p>
                                <a href="{{ route('labs.orders.show', $order) }}" class="mt-2 inline-block text-sm font-medium text-primary-700">{{ __('labs.order.details') }}</a>
                            </div>
                            <div class="flex flex-col items-end gap-1.5">
                                <x-badge :tone="$order->status->tone()">{{ $order->status->label() }}</x-badge>
                                <span class="text-sm font-medium">{{ number_format((float) $order->total) }} {{ __('common.currency') }}</span>
                                @if ($order->status->canTransitionTo(\App\Enums\LabOrderStatus::Cancelled))
                                    <form method="POST" action="{{ route('appointments.lab-orders.destroy', $order) }}" onsubmit="return confirm(@js(__('booking.confirm_cancel')))">
                                        @csrf
                                        @method('DELETE')
                                        <x-button variant="ghost" size="sm">{{ __('booking.cancel') }}</x-button>
                                    </form>
                                @endif
                            </div>
                        </div>
                    </x-card>
                @endforeach
            </div>
        @endif
    </div>
</x-layouts.public>
