<x-layouts.clinic :title="__('clinic.lab_orders.heading')">
    <x-page-header :title="__('clinic.lab_orders.heading')" :subtitle="__('clinic.lab_orders.subtitle')">
        <x-slot:actions>
            @if ($pendingCount)
                <x-badge tone="warning">{{ __('clinic.queue.pending_count', ['count' => $pendingCount]) }}</x-badge>
            @endif
        </x-slot:actions>
    </x-page-header>

    <x-status-tiles
        class="mb-5"
        :counts="$statusCounts"
        :cases="\App\Enums\LabOrderStatus::cases()"
        routeName="clinic.lab-orders.index"
        :filters="$filters"
    />

    <form method="GET" class="card mb-5 grid gap-3 p-4 sm:grid-cols-3">
        <x-field :label="__('clinic.queue.date')" name="date">
            <x-input type="date" name="date" :value="$filters['date']"/>
        </x-field>
        <x-field :label="__('common.status')" name="status">
            <x-select
                name="status"
                :placeholder="__('common.all')"
                :selected="$filters['status'] ?? ''"
                :options="collect(\App\Enums\LabOrderStatus::cases())->mapWithKeys(fn ($status) => [$status->value => $status->label()])->all()"
            />
        </x-field>
        <div class="flex items-end">
            <x-button variant="secondary" class="w-full">{{ __('common.filter') }}</x-button>
        </div>
    </form>

    <x-table>
        <x-slot:head>
            <x-th>{{ __('clinic.queue.time') }}</x-th>
            <x-th>{{ __('clinic.queue.patient') }}</x-th>
            <x-th>{{ __('labs.checkout.items') }}</x-th>
            <x-th>{{ __('common.status') }}</x-th>
            <x-th>{{ __('clinic.queue.payment') }}</x-th>
            <x-th>{{ __('common.actions') }}</x-th>
        </x-slot:head>

        @forelse ($orders as $order)
            <tr>
                <x-td>
                    <span class="font-medium tabular">{{ $order->scheduled_at->format('H:i') }}</span>
                    <span class="mt-0.5 block text-xs text-ink-400">{{ $order->reference }}</span>
                </x-td>
                <x-td>
                    <span class="font-medium">{{ $order->patient?->name }}</span>
                    <span class="mt-0.5 block text-xs text-ink-400" dir="ltr">{{ $order->patient?->phone }}</span>
                </x-td>
                <x-td>
                    {{ $order->items->map(fn ($item) => $item->catalogItem()?->name)->filter()->join('، ') }}
                    <span class="mt-0.5 block text-xs text-ink-400">{{ number_format((float) $order->total) }} {{ __('common.currency') }}</span>
                </x-td>
                <x-td>
                    <x-badge :tone="$order->status->tone()">{{ $order->status->label() }}</x-badge>
                    <span class="mt-0.5 block text-xs text-ink-400">{{ $order->collection_mode->label() }}</span>
                </x-td>
                <x-td>
                    <x-badge :tone="$order->isPaid() ? 'success' : 'warning'">
                        {{ __('booking.payment_status.'.$order->payment_status) }}
                    </x-badge>
                </x-td>
                <x-td>
                    <div class="flex flex-wrap gap-1.5">
                        @if ($order->status->canTransitionTo(\App\Enums\LabOrderStatus::Confirmed))
                            <form method="POST" action="{{ route('clinic.lab-orders.update', $order) }}">
                                @csrf
                                @method('PUT')
                                <input type="hidden" name="action" value="confirm">
                                <x-button size="sm" variant="accent">{{ __('clinic.queue.confirm') }}</x-button>
                            </form>
                        @endif
                        @if ($order->status->canTransitionTo(\App\Enums\LabOrderStatus::Completed))
                            <form method="POST" action="{{ route('clinic.lab-orders.update', $order) }}">
                                @csrf
                                @method('PUT')
                                <input type="hidden" name="action" value="complete">
                                <x-button size="sm">{{ __('clinic.queue.complete') }}</x-button>
                            </form>
                        @endif
                        @if ($order->status->canTransitionTo(\App\Enums\LabOrderStatus::Cancelled))
                            <form method="POST" action="{{ route('clinic.lab-orders.update', $order) }}">
                                @csrf
                                @method('PUT')
                                <input type="hidden" name="action" value="cancel">
                                <x-button size="sm" variant="danger-ghost">{{ __('clinic.queue.cancel') }}</x-button>
                            </form>
                        @endif
                        <form method="POST" action="{{ route('clinic.lab-orders.update', $order) }}">
                            @csrf
                            @method('PUT')
                            <input type="hidden" name="action" value="{{ $order->isPaid() ? 'mark_unpaid' : 'mark_paid' }}">
                            <x-button size="sm" variant="ghost">
                                {{ $order->isPaid() ? __('clinic.queue.mark_unpaid') : __('clinic.queue.mark_paid') }}
                            </x-button>
                        </form>
                    </div>
                </x-td>
            </tr>
        @empty
            <tr>
                <x-td colspan="6">{{ __('clinic.lab_orders.empty') }}</x-td>
            </tr>
        @endforelse
    </x-table>
</x-layouts.clinic>
