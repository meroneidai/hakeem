@php
    $updateUrl = $updateUrl ?? route('clinic.queue.update', $booking);
@endphp

<div class="flex flex-wrap items-center gap-1.5">
    @if ($booking->status->canTransitionTo(\App\Enums\BookingStatus::Confirmed))
        <form method="POST" action="{{ $updateUrl }}">
            @csrf
            @method('PUT')
            <input type="hidden" name="action" value="confirm">
            <x-button size="sm">{{ __('clinic.queue.confirm') }}</x-button>
        </form>
    @endif

    @if ($booking->status->canTransitionTo(\App\Enums\BookingStatus::InProgress))
        <form method="POST" action="{{ $updateUrl }}">
            @csrf
            @method('PUT')
            <input type="hidden" name="action" value="check_in">
            <x-button size="sm" variant="accent">{{ __('clinic.queue.check_in') }}</x-button>
        </form>
    @endif

    @if ($booking->status->canTransitionTo(\App\Enums\BookingStatus::Completed))
        <form method="POST" action="{{ $updateUrl }}">
            @csrf
            @method('PUT')
            <input type="hidden" name="action" value="complete">
            <x-button size="sm">{{ __('clinic.queue.complete') }}</x-button>
        </form>
    @endif

    @if ($booking->status->canTransitionTo(\App\Enums\BookingStatus::NoShow))
        <form method="POST" action="{{ $updateUrl }}">
            @csrf
            @method('PUT')
            <input type="hidden" name="action" value="no_show">
            <x-button size="sm" variant="ghost">{{ __('clinic.queue.no_show') }}</x-button>
        </form>
    @endif

    @if ($booking->status->canTransitionTo(\App\Enums\BookingStatus::Cancelled))
        <form method="POST" action="{{ $updateUrl }}" onsubmit="return confirm(@js(__('clinic.bookings.confirm_cancel')))">
            @csrf
            @method('PUT')
            <input type="hidden" name="action" value="cancel">
            <x-button size="sm" variant="danger-ghost">{{ __('clinic.queue.cancel') }}</x-button>
        </form>
    @endif

    <form method="POST" action="{{ $updateUrl }}">
        @csrf
        @method('PUT')
        <input type="hidden" name="action" value="{{ $booking->isPaid() ? 'mark_unpaid' : 'mark_paid' }}">
        <x-button size="sm" variant="secondary">
            {{ $booking->isPaid() ? __('clinic.queue.mark_unpaid') : __('clinic.queue.mark_paid') }}
        </x-button>
    </form>
</div>

@if ($booking->status->isOpen())
    <form method="POST" action="{{ $updateUrl }}" class="mt-2 flex items-end gap-1.5">
        @csrf
        @method('PUT')
        <input type="hidden" name="action" value="reschedule">
        <x-input
            type="datetime-local"
            name="scheduled_at"
            class="!py-1.5 text-xs"
            :min="now()->addMinute()->format('Y-m-d\TH:i')"
            :value="$booking->scheduled_at->format('Y-m-d\TH:i')"
        />
        <x-button size="sm" variant="ghost">{{ __('clinic.queue.reschedule') }}</x-button>
    </form>
@endif

@if ($booking->is_evaluation)
    <form method="POST" action="{{ $updateUrl }}" class="mt-2 space-y-1.5">
        @csrf
        @method('PUT')
        <input type="hidden" name="action" value="evaluation_note">
        <x-input name="evaluation_notes" :value="$booking->evaluation_notes" :placeholder="__('clinic.queue.evaluation_notes')"/>
        <x-button size="sm" variant="secondary">{{ __('clinic.queue.save_evaluation') }}</x-button>
    </form>
@endif
