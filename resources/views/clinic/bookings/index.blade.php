<x-layouts.clinic :title="__('clinic.bookings.heading')">
    <x-page-header :title="__('clinic.bookings.heading')" :subtitle="__('clinic.bookings.subtitle')">
        <x-slot:actions>
            <x-button :href="route('clinic.queue.index')" variant="secondary">{{ __('clinic.nav.queue') }}</x-button>
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
        routeName="clinic.bookings.index"
        :filters="$filters"
    />

    <form method="GET" class="card mb-5 grid gap-3 p-4 sm:grid-cols-2 lg:grid-cols-6">
        <x-input name="q" :value="$filters['q'] ?? ''" :placeholder="__('common.search')"/>
        <x-select
            name="status"
            :placeholder="__('common.all')"
            :selected="$filters['status'] ?? ''"
            :options="collect(\App\Enums\BookingStatus::cases())->mapWithKeys(fn ($status) => [$status->value => $status->label()])->all()"
        />
        <x-select name="doctor" :placeholder="__('clinic.queue.doctor')" :selected="$filters['doctor'] ?? ''">
            @foreach ($doctors as $doctor)
                <option value="{{ $doctor->id }}" @selected((string) ($filters['doctor'] ?? '') === (string) $doctor->id)>
                    {{ $doctor->name }}
                </option>
            @endforeach
        </x-select>
        <x-input type="date" name="from" :value="$filters['from'] ?? ''"/>
        <x-input type="date" name="to" :value="$filters['to'] ?? ''"/>
        <x-button variant="secondary" class="self-end">{{ __('common.filter') }}</x-button>
    </form>

    <x-table>
        <x-slot:head>
            <x-th>{{ __('clinic.bookings.when') }}</x-th>
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
                    <span class="font-medium tabular text-ink-900">{{ $booking->scheduled_at?->format('Y-m-d H:i') }}</span>
                    <span class="mt-0.5 block text-xs text-ink-400">{{ $booking->address?->displayName() }}</span>
                </x-td>
                <x-td>
                    <span class="font-medium text-ink-900">{{ $booking->patient?->name }}</span>
                    <span class="mt-0.5 block text-xs text-ink-400" dir="ltr">{{ $booking->patient?->phone }}</span>
                </x-td>
                <x-td>{{ $booking->doctor?->name }}</x-td>
                <x-td>
                    {{ $booking->serviceType?->name }}
                    <span class="mt-0.5 block text-xs text-ink-400">{{ __('booking.duration_minutes', ['minutes' => $booking->durationMinutes()]) }}</span>
                    @if ($booking->is_evaluation)
                        <x-badge tone="accent" class="mt-1">{{ __('booking.evaluation') }}</x-badge>
                    @endif
                </x-td>
                <x-td><x-badge :tone="$booking->status->tone()">{{ $booking->status->label() }}</x-badge></x-td>
                <x-td>
                    <x-badge :tone="$booking->isPaid() ? 'success' : 'warning'">
                        {{ __('booking.payment_status.'.$booking->payment_status) }}
                    </x-badge>
                </x-td>
                <x-td>
                    @include('clinic.queue.actions', ['booking' => $booking])
                    @if (in_array($booking->status, [\App\Enums\BookingStatus::InProgress, \App\Enums\BookingStatus::Completed], true))
                        <div class="mt-2">
                            <x-button size="sm" variant="secondary" :href="route('clinic.care.create', ['booking' => $booking->id])">
                                {{ __('clinic.care.issue') }}
                            </x-button>
                        </div>
                    @endif
                </x-td>
            </tr>
        @empty
            <x-empty-state colspan="7" :message="__('clinic.bookings.empty')"/>
        @endforelse
        @if ($bookings->hasPages())
            <x-slot:footer>{{ $bookings->links() }}</x-slot:footer>
        @endif
    </x-table>
</x-layouts.clinic>
