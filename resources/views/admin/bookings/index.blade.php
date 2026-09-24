<x-layouts.admin :title="__('admin.bookings.heading')">
    <x-page-header :title="__('admin.bookings.heading')" :subtitle="__('admin.bookings.subheading')">
        <x-slot:actions>
            <x-button :href="route('admin.lab-orders.index')" variant="secondary">{{ __('admin.nav.lab_orders') }}</x-button>
            <x-button :href="route('admin.bookings.create')" variant="accent">
                <x-icon name="plus" class="size-4"/>
                {{ __('admin.bookings.create') }}
            </x-button>
        </x-slot:actions>
    </x-page-header>

    <x-status-tiles
        class="mb-5"
        :counts="$statusCounts"
        :cases="\App\Enums\BookingStatus::cases()"
        routeName="admin.bookings.index"
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
        <x-select name="clinic" :placeholder="__('admin.nav.clinics')" :selected="$filters['clinic'] ?? ''">
            @foreach ($clinics as $clinic)
                <option value="{{ $clinic->id }}" @selected((string) ($filters['clinic'] ?? '') === (string) $clinic->id)>
                    {{ $clinic->name }}
                </option>
            @endforeach
        </x-select>
        <x-input type="date" name="from" :value="$filters['from'] ?? ''"/>
        <x-input type="date" name="to" :value="$filters['to'] ?? ''"/>
        <x-button variant="secondary" class="self-end">{{ __('common.filter') }}</x-button>
    </form>

    <x-table>
        <x-slot:head>
            <x-th>{{ __('admin.bookings.when') }}</x-th>
            <x-th>{{ __('admin.bookings.patient') }}</x-th>
            <x-th>{{ __('admin.nav.clinics') }}</x-th>
            <x-th>{{ __('clinic.queue.doctor') }}</x-th>
            <x-th>{{ __('common.status') }}</x-th>
            <x-th>{{ __('clinic.queue.payment') }}</x-th>
            <x-th>{{ __('common.actions') }}</x-th>
        </x-slot:head>
        @forelse ($bookings as $booking)
            <tr>
                <x-td>
                    <span class="font-medium tabular text-ink-900">{{ $booking->scheduled_at?->format('Y-m-d H:i') }}</span>
                    <span class="mt-0.5 block text-xs text-ink-400">#{{ $booking->id }}</span>
                </x-td>
                <x-td>
                    <span class="font-medium text-ink-900">{{ $booking->patient?->name }}</span>
                    <span class="mt-0.5 block text-xs text-ink-400" dir="ltr">{{ $booking->patient?->phone }}</span>
                </x-td>
                <x-td>{{ $booking->clinic?->name }}</x-td>
                <x-td>{{ $booking->doctor?->name }}</x-td>
                <x-td><x-badge :tone="$booking->status->tone()">{{ $booking->status->label() }}</x-badge></x-td>
                <x-td>
                    <x-badge :tone="$booking->isPaid() ? 'success' : 'warning'">
                        {{ __('booking.payment_status.'.$booking->payment_status) }}
                    </x-badge>
                </x-td>
                <x-td>
                    <a href="{{ route('admin.bookings.show', $booking) }}" class="text-sm font-medium text-primary-700 hover:underline">{{ __('common.view') }}</a>
                </x-td>
            </tr>
        @empty
            <x-empty-state colspan="7"/>
        @endforelse
        @if ($bookings->hasPages())
            <x-slot:footer>{{ $bookings->links() }}</x-slot:footer>
        @endif
    </x-table>
</x-layouts.admin>
