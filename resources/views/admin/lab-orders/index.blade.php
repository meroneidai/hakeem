<x-layouts.admin :title="__('admin.lab_orders.heading')">
    <x-page-header :title="__('admin.lab_orders.heading')" :subtitle="__('admin.lab_orders.subheading')">
        <x-slot:actions>
            <x-button :href="route('admin.bookings.index')" variant="secondary">{{ __('admin.nav.bookings') }}</x-button>
        </x-slot:actions>
    </x-page-header>

    <x-status-tiles
        class="mb-5"
        :counts="$statusCounts"
        :cases="\App\Enums\LabOrderStatus::cases()"
        routeName="admin.lab-orders.index"
        :filters="$filters"
    />

    <form method="GET" class="card mb-5 grid gap-3 p-4 sm:grid-cols-2 lg:grid-cols-6">
        <x-input name="q" :value="$filters['q'] ?? ''" :placeholder="__('common.search')"/>
        <x-select
            name="status"
            :placeholder="__('common.all')"
            :selected="$filters['status'] ?? ''"
            :options="collect(\App\Enums\LabOrderStatus::cases())->mapWithKeys(fn ($status) => [$status->value => $status->label()])->all()"
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
            <x-th>{{ __('labs.checkout.items') }}</x-th>
            <x-th>{{ __('common.status') }}</x-th>
            <x-th>{{ __('clinic.queue.payment') }}</x-th>
            <x-th>{{ __('common.actions') }}</x-th>
        </x-slot:head>
        @forelse ($orders as $order)
            <tr>
                <x-td>
                    <span class="font-medium tabular">{{ $order->scheduled_at?->format('Y-m-d H:i') }}</span>
                    <span class="mt-0.5 block text-xs text-ink-400">{{ $order->reference }}</span>
                </x-td>
                <x-td>
                    <span class="font-medium">{{ $order->patient?->name }}</span>
                    <span class="mt-0.5 block text-xs text-ink-400" dir="ltr">{{ $order->patient?->phone }}</span>
                </x-td>
                <x-td>{{ $order->clinic?->name }}</x-td>
                <x-td>
                    {{ $order->items->map(fn ($item) => $item->catalogItem()?->name)->filter()->join('، ') }}
                    <span class="mt-0.5 block text-xs text-ink-400">{{ number_format((float) $order->total) }} {{ __('common.currency') }}</span>
                </x-td>
                <x-td><x-badge :tone="$order->status->tone()">{{ $order->status->label() }}</x-badge></x-td>
                <x-td>
                    <x-badge :tone="$order->isPaid() ? 'success' : 'warning'">
                        {{ __('booking.payment_status.'.$order->payment_status) }}
                    </x-badge>
                </x-td>
                <x-td>
                    <a href="{{ route('admin.lab-orders.show', $order) }}" class="text-sm font-medium text-primary-700 hover:underline">{{ __('common.view') }}</a>
                </x-td>
            </tr>
        @empty
            <x-empty-state colspan="7"/>
        @endforelse
        @if ($orders->hasPages())
            <x-slot:footer>{{ $orders->links() }}</x-slot:footer>
        @endif
    </x-table>
</x-layouts.admin>
