<x-layouts.admin :title="__('admin.bookings.detail')">
    <x-page-header :title="__('admin.bookings.detail')" :subtitle="$booking->clinic?->name">
        <x-slot:actions>
            <x-button :href="route('admin.bookings.index')" variant="secondary">{{ __('common.back') }}</x-button>
            @if ($booking->status->canTransitionTo(\App\Enums\BookingStatus::Cancelled))
                <form method="POST" action="{{ route('admin.bookings.update', $booking) }}" onsubmit="return confirm(@js(__('admin.bookings.confirm_cancel')))">
                    @csrf
                    @method('PUT')
                    <input type="hidden" name="action" value="cancel">
                    <x-button variant="danger">{{ __('clinic.queue.cancel') }}</x-button>
                </form>
            @endif
        </x-slot:actions>
    </x-page-header>

    <div class="grid gap-5 lg:grid-cols-2">
        <x-card :title="__('admin.bookings.patient')">
            <dl class="space-y-2 text-sm">
                <div class="flex justify-between gap-3"><dt class="text-ink-500">{{ __('auth.name') }}</dt><dd class="font-medium">{{ $booking->patient?->name }}</dd></div>
                <div class="flex justify-between gap-3"><dt class="text-ink-500">{{ __('auth.phone') }}</dt><dd dir="ltr">{{ $booking->patient?->phone }}</dd></div>
                <div class="flex justify-between gap-3"><dt class="text-ink-500">{{ __('admin.bookings.when') }}</dt><dd class="tabular">{{ $booking->scheduled_at?->format('Y-m-d H:i') }}</dd></div>
                <div class="flex justify-between gap-3"><dt class="text-ink-500">{{ __('common.status') }}</dt><dd><x-badge :tone="$booking->status->tone()">{{ $booking->status->label() }}</x-badge></dd></div>
                <div class="flex justify-between gap-3"><dt class="text-ink-500">{{ __('clinic.queue.payment') }}</dt>
                    <dd>
                        <x-badge :tone="$booking->isPaid() ? 'success' : 'warning'">{{ __('booking.payment_status.'.$booking->payment_status) }}</x-badge>
                        <span class="ms-1 text-ink-400">{{ $booking->payment_mode->label() }}</span>
                    </dd>
                </div>
            </dl>
        </x-card>

        <x-card :title="__('admin.nav.clinics')">
            <dl class="space-y-2 text-sm">
                <div class="flex justify-between gap-3"><dt class="text-ink-500">{{ __('admin.nav.clinics') }}</dt><dd class="font-medium">{{ $booking->clinic?->name }}</dd></div>
                <div class="flex justify-between gap-3"><dt class="text-ink-500">{{ __('clinic.queue.doctor') }}</dt><dd>{{ $booking->doctor?->name }}</dd></div>
                <div class="flex justify-between gap-3"><dt class="text-ink-500">{{ __('clinic.queue.service') }}</dt><dd>{{ $booking->serviceType?->name }}</dd></div>
                <div class="flex justify-between gap-3"><dt class="text-ink-500">{{ __('clinic.queue.branch') }}</dt><dd>{{ $booking->address?->displayName() }}</dd></div>
                @if ($booking->notes)
                    <div><dt class="text-ink-500">{{ __('clinic.queue.notes') }}</dt><dd class="mt-1">{{ $booking->notes }}</dd></div>
                @endif
            </dl>
        </x-card>
    </div>

    <x-card class="mt-5" :title="__('admin.bookings.history')">
        <ul class="space-y-2 text-sm">
            @forelse ($booking->statusHistory as $history)
                <li class="flex items-center justify-between gap-3">
                    <span>
                        <x-badge :tone="$history->status->tone()">{{ $history->status->label() }}</x-badge>
                        <span class="ms-2 text-ink-400">{{ $history->changedBy?->name }}</span>
                    </span>
                    <span class="tabular text-xs text-ink-400">{{ $history->changed_at?->format('Y-m-d H:i') }}</span>
                </li>
            @empty
                <li class="text-ink-400">{{ __('common.no_results') }}</li>
            @endforelse
        </ul>
    </x-card>
</x-layouts.admin>
