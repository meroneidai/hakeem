<x-layouts.admin :title="__('admin.lab_orders.detail')">
    <x-page-header :title="$order->reference" :subtitle="$order->clinic?->name">
        <x-slot:actions>
            <x-button :href="route('admin.lab-orders.index')" variant="secondary">{{ __('common.back') }}</x-button>
            @if ($order->status->canTransitionTo(\App\Enums\LabOrderStatus::Cancelled))
                <form method="POST" action="{{ route('admin.lab-orders.update', $order) }}" onsubmit="return confirm(@js(__('admin.lab_orders.confirm_cancel')))">
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
                <div class="flex justify-between gap-3"><dt class="text-ink-500">{{ __('auth.name') }}</dt><dd class="font-medium">{{ $order->patient?->name }}</dd></div>
                <div class="flex justify-between gap-3"><dt class="text-ink-500">{{ __('auth.phone') }}</dt><dd dir="ltr">{{ $order->patient?->phone }}</dd></div>
                <div class="flex justify-between gap-3"><dt class="text-ink-500">{{ __('admin.bookings.when') }}</dt><dd class="tabular">{{ $order->scheduled_at?->format('Y-m-d H:i') }}</dd></div>
                <div class="flex justify-between gap-3"><dt class="text-ink-500">{{ __('common.status') }}</dt><dd><x-badge :tone="$order->status->tone()">{{ $order->status->label() }}</x-badge></dd></div>
                <div class="flex justify-between gap-3"><dt class="text-ink-500">{{ __('clinic.queue.payment') }}</dt>
                    <dd>
                        <x-badge :tone="$order->isPaid() ? 'success' : 'warning'">{{ __('booking.payment_status.'.$order->payment_status) }}</x-badge>
                    </dd>
                </div>
                <div class="flex justify-between gap-3"><dt class="text-ink-500">{{ __('labs.checkout.collection') }}</dt><dd>{{ $order->collection_mode->label() }}</dd></div>
            </dl>
            @if ($order->collection_mode->value === 'home')
                <div class="mt-4 border-t border-ink-100 pt-4">
                    @include('labs._visit-destination', ['order' => $order])
                </div>
            @endif
        </x-card>

        <x-card :title="__('labs.checkout.items')">
            <ul class="space-y-2 text-sm">
                @foreach ($order->items as $item)
                    <li class="flex justify-between gap-3">
                        <span>{{ $item->catalogItem()?->name }} × {{ $item->qty }}</span>
                        <span class="tabular">{{ number_format((float) $item->line_total) }} {{ __('common.currency') }}</span>
                    </li>
                @endforeach
            </ul>
            <p class="mt-4 text-sm font-semibold">{{ number_format((float) $order->total) }} {{ __('common.currency') }}</p>
        </x-card>
    </div>
</x-layouts.admin>
