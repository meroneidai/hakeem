<x-layouts.clinic :title="__('clinic.queue.heading')">
    <x-page-header :title="__('clinic.queue.heading')" :subtitle="__('clinic.queue.subtitle')">
        <x-slot:actions>
            @if ($pendingCount)
                <x-badge tone="warning">{{ __('clinic.queue.pending_count', ['count' => $pendingCount]) }}</x-badge>
            @endif
            <x-button :href="route('clinic.queue.create')" variant="accent">
                <x-icon name="plus" class="size-4"/>
                {{ __('clinic.queue.add') }}
            </x-button>
        </x-slot:actions>
    </x-page-header>

    <x-status-tiles
        class="mb-5"
        :counts="$statusCounts"
        :cases="\App\Enums\BookingStatus::cases()"
        routeName="clinic.queue.index"
        :filters="$filters"
    />

    <form method="GET" class="card mb-5 grid gap-3 p-4 sm:grid-cols-4">
        <x-field :label="__('clinic.queue.date')" name="date">
            <x-input type="date" name="date" :value="$filters['date']"/>
        </x-field>
        <x-field :label="__('common.status')" name="status">
            <x-select
                name="status"
                :placeholder="__('common.all')"
                :selected="$filters['status'] ?? ''"
                :options="collect(\App\Enums\BookingStatus::cases())->mapWithKeys(fn ($status) => [$status->value => $status->label()])->all()"
            />
        </x-field>
        <x-field :label="__('clinic.queue.branch')" name="address">
            <x-select name="address" :placeholder="__('common.all')" :selected="$filters['address'] ?? ''">
                @foreach ($addresses as $address)
                    <option value="{{ $address->id }}" @selected((string) ($filters['address'] ?? '') === (string) $address->id)>
                        {{ $address->displayName() }}
                    </option>
                @endforeach
            </x-select>
        </x-field>
        <div class="flex items-end">
            <x-button variant="secondary" class="w-full">{{ __('common.filter') }}</x-button>
        </div>
    </form>

    <x-table>
        <x-slot:head>
            <x-th>{{ __('clinic.queue.time') }}</x-th>
            <x-th>{{ __('clinic.queue.patient') }}</x-th>
            <x-th>{{ __('clinic.queue.doctor') }}</x-th>
            <x-th>{{ __('clinic.queue.service') }}</x-th>
            <x-th>{{ __('common.status') }}</x-th>
            <x-th>{{ __('clinic.queue.payment') }}</x-th>
            <x-th>{{ __('common.actions') }}</x-th>
        </x-slot:head>

        @forelse ($bookings as $booking)
            <tr>
                <x-td>
                    <span class="font-medium tabular text-ink-900">{{ $booking->scheduled_at->format('H:i') }}</span>
                    <span class="mt-0.5 block text-xs text-ink-400">{{ $booking->address?->displayName() }}</span>
                </x-td>
                <x-td>
                    <span class="font-medium text-ink-900">{{ $booking->patient?->name }}</span>
                    <span class="mt-0.5 block text-xs text-ink-400" dir="ltr">{{ $booking->patient?->phone }}</span>
                </x-td>
                <x-td>{{ $booking->doctor?->name }}</x-td>
                <x-td>
                    {{ $booking->serviceType?->name }}
                    @if ($booking->is_evaluation)
                        <x-badge tone="accent" class="mt-1">{{ __('booking.evaluation') }}</x-badge>
                    @endif
                    @if (($booking->session_count ?? 1) > 1)
                        <span class="mt-0.5 block text-xs text-ink-400">{{ __('booking.session_pack', ['count' => $booking->session_count]) }}</span>
                    @endif
                </x-td>
                <x-td>
                    <x-badge :tone="$booking->status->tone()">{{ $booking->status->label() }}</x-badge>
                </x-td>
                <x-td>
                    <x-badge :tone="$booking->isPaid() ? 'success' : 'warning'">
                        {{ __('booking.payment_status.'.$booking->payment_status) }}
                    </x-badge>
                    <span class="mt-0.5 block text-xs text-ink-400">{{ $booking->payment_mode->label() }}</span>
                </x-td>
                <x-td>
                    @include('clinic.queue.actions', ['booking' => $booking])
                </x-td>
            </tr>
        @empty
            <x-empty-state colspan="7" :message="__('clinic.queue.empty')"/>
        @endforelse
    </x-table>
</x-layouts.clinic>
