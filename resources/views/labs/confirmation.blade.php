<x-layouts.public :title="__('labs.checkout.confirmed')" robots="noindex,nofollow">
    <x-catalog-hero :title="__('labs.checkout.confirmed')" :subtitle="$order->reference">
        <x-slot:crumbs>
            <a href="{{ route('home') }}" class="hover:text-primary-700">{{ __('discover.nav.home') }}</a>
            <span aria-hidden="true">·</span>
            <a href="{{ route('appointments.index') }}" class="hover:text-primary-700">{{ __('booking.my_appointments') }}</a>
        </x-slot:crumbs>
    </x-catalog-hero>

    <div class="mx-auto max-w-3xl space-y-4 px-4 py-8">
        <div class="flex flex-wrap items-center gap-2">
            <x-badge :tone="$order->status->tone()">{{ $order->status->label() }}</x-badge>
            <x-badge :tone="$order->isPaid() ? 'success' : 'warning'">
                {{ __('booking.payment_status.'.$order->payment_status) }}
            </x-badge>
        </div>

        <x-card class="space-y-4">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <p class="text-xs font-medium text-ink-400">{{ __('labs.order.reference') }}</p>
                    <p class="mt-1 font-semibold text-ink-900" dir="ltr">{{ $order->reference }}</p>
                </div>
                <p class="text-2xl font-semibold tabular text-ink-900">
                    {{ number_format((float) $order->total) }}
                    <span class="text-sm font-medium text-ink-500">{{ __('common.currency') }}</span>
                </p>
            </div>

            <dl class="grid gap-4 text-sm sm:grid-cols-2">
                <div>
                    <dt class="text-xs font-medium text-ink-400">{{ __('labs.order.lab') }}</dt>
                    <dd class="mt-1 font-medium text-ink-800">{{ $order->clinic?->name ?: '—' }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-medium text-ink-400">{{ __('labs.checkout.collection') }}</dt>
                    <dd class="mt-1 font-medium text-ink-800">{{ $order->collection_mode->label() }}</dd>
                    <dd class="mt-0.5 text-xs text-ink-500">
                        @if ($order->collection_mode->value === 'home')
                            {{ $order->patient_home_address ?: __('labs.collection.home') }}
                        @else
                            {{ $order->address?->displayName() ?: __('labs.collection.clinic') }}
                        @endif
                    </dd>
                </div>
                <div>
                    <dt class="text-xs font-medium text-ink-400">{{ __('labs.order.schedule') }}</dt>
                    <dd class="mt-1 font-medium text-ink-800">{{ optional($order->scheduled_at)->format('Y-m-d H:i') }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-medium text-ink-400">{{ __('labs.order.payment') }}</dt>
                    <dd class="mt-1 font-medium text-ink-800">{{ $order->payment_mode?->label() }}</dd>
                    @if ($order->payment_mode)
                        <dd class="mt-0.5 text-xs text-ink-500">{{ $order->payment_mode->hint() }}</dd>
                    @endif
                </div>
            </dl>
        </x-card>

        <x-card class="space-y-3">
            <h2 class="text-sm font-semibold text-ink-900">{{ __('labs.order.items') }}</h2>
            <ul class="divide-y divide-ink-100 text-sm">
                @foreach ($order->items as $item)
                    <li class="flex items-start justify-between gap-3 py-3 first:pt-0 last:pb-0">
                        <div>
                            <p class="font-medium text-ink-800">{{ $item->catalogItem()?->name }}</p>
                            <p class="text-xs text-ink-400">
                                {{ $item->item_type === 'package' ? __('labs.packages') : __('labs.tests') }}
                                · ×{{ $item->qty }}
                            </p>
                        </div>
                        <p class="shrink-0 font-semibold text-ink-900">{{ number_format((float) $item->line_total) }} {{ __('common.currency') }}</p>
                    </li>
                @endforeach
            </ul>
            <div class="flex justify-between border-t border-ink-100 pt-3 text-sm font-semibold">
                <span>{{ __('labs.cart.total') }}</span>
                <span>{{ number_format((float) $order->total) }} {{ __('common.currency') }}</span>
            </div>
        </x-card>

        <x-card class="space-y-3">
            <h2 class="text-sm font-semibold text-ink-900">{{ __('labs.order.payment') }}</h2>
            <div class="rounded-2xl bg-ink-50 px-4 py-3">
                <div class="flex flex-wrap items-center justify-between gap-2">
                    <p class="font-medium text-ink-800">{{ $order->payment_mode?->label() }}</p>
                    <x-badge :tone="$order->isPaid() ? 'success' : 'warning'">
                        {{ __('booking.payment_status.'.$order->payment_status) }}
                    </x-badge>
                </div>
                @if ($order->payment_mode)
                    <p class="mt-1 text-sm text-ink-500">{{ $order->payment_mode->hint() }}</p>
                @endif
                @if ($order->payment_mode?->value === 'online' && ! $order->isPaid())
                    <p class="mt-2 text-xs text-warning-700">{{ __('labs.checkout.pending_hint') }}</p>
                @endif
            </div>
        </x-card>

        @if ($order->notes)
            <x-card>
                <h2 class="text-sm font-semibold text-ink-900">{{ __('labs.order.notes') }}</h2>
                <p class="mt-2 text-sm text-ink-600">{{ $order->notes }}</p>
            </x-card>
        @endif

        <x-card class="space-y-3">
            <h2 class="text-sm font-semibold text-ink-900">{{ __('labs.order.next') }}</h2>
            <p class="text-sm text-ink-500">{{ __('labs.checkout.pending_hint') }}</p>
            <div class="flex flex-wrap gap-2">
                <x-button :href="route('appointments.index')" variant="accent">{{ __('booking.my_appointments') }}</x-button>
                <x-button :href="route('labs.index')" variant="ghost">{{ __('labs.tests') }}</x-button>
            </div>
        </x-card>
    </div>
</x-layouts.public>
