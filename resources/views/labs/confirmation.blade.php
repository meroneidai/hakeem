<x-layouts.public :title="__('labs.checkout.confirmed')" robots="noindex,nofollow">
    <div class="relative overflow-hidden bg-gradient-to-b from-success-50 via-primary-50/40 to-white px-3 pb-6 pt-10 min-[390px]:px-4 sm:pb-8 sm:pt-14">
        <div class="pointer-events-none absolute -start-10 top-0 size-48 rounded-full bg-success-200/40 blur-3xl"></div>
        <div class="relative mx-auto max-w-3xl text-center">
            <span class="mx-auto grid size-16 place-items-center rounded-full bg-success-500 text-white shadow-lg shadow-success-500/30">
                <x-icon name="check" class="size-8"/>
            </span>
            <h1 class="mt-4 text-2xl font-bold text-ink-900 sm:text-3xl">{{ __('labs.checkout.confirmed') }}</h1>
            <p class="mt-2 font-mono text-sm text-ink-500" dir="ltr">{{ $order->reference }}</p>
            <div class="mt-4 flex flex-wrap items-center justify-center gap-2">
                <x-badge :tone="$order->status->tone()">{{ $order->status->label() }}</x-badge>
                <x-badge :tone="$order->isPaid() ? 'success' : 'warning'">
                    {{ __('booking.payment_status.'.$order->payment_status) }}
                </x-badge>
            </div>
        </div>
    </div>

    <div class="mx-auto max-w-3xl space-y-4 px-3 py-6 min-[390px]:px-4 sm:py-8">
        <div class="overflow-hidden rounded-[1.5rem] bg-white shadow-sm ring-1 ring-ink-100">
            <div class="flex flex-wrap items-start justify-between gap-3 border-b border-ink-50 bg-gradient-to-l from-primary-50 to-white px-5 py-4">
                <div class="text-start">
                    <p class="text-xs font-medium text-ink-400">{{ __('labs.order.reference') }}</p>
                    <p class="mt-1 font-semibold text-ink-900" dir="ltr">{{ $order->reference }}</p>
                </div>
                <p class="text-2xl font-bold tabular text-primary-800">
                    {{ number_format((float) $order->total) }}
                    <span class="text-sm font-medium text-ink-500">{{ __('common.currency') }}</span>
                </p>
            </div>

            <dl class="grid gap-4 p-5 text-start text-sm sm:grid-cols-2">
                <div class="rounded-2xl bg-ink-50 p-3.5">
                    <dt class="text-xs font-medium text-ink-400">{{ __('labs.order.lab') }}</dt>
                    <dd class="mt-1 font-semibold text-ink-800">{{ $order->clinic?->name ?: '—' }}</dd>
                </div>
                <div class="rounded-2xl bg-ink-50 p-3.5">
                    <dt class="text-xs font-medium text-ink-400">{{ __('labs.checkout.collection') }}</dt>
                    <dd class="mt-1 font-semibold text-ink-800">{{ $order->collection_mode->label() }}</dd>
                    <dd class="mt-0.5 text-xs text-ink-500">
                        @if ($order->collection_mode->value === 'home')
                            {{ $order->patient_home_address ?: __('labs.collection.home') }}
                            @if ($order->hasMapPin())
                                <a href="{{ $order->mapsUrl() }}" target="_blank" rel="noopener" class="mt-1 inline-flex items-center gap-1 font-medium text-primary-700">
                                    {{ __('discover.clinics.map') }}
                                </a>
                            @endif
                        @else
                            {{ $order->address?->displayName() ?: __('labs.collection.clinic') }}
                        @endif
                    </dd>
                </div>
                <div class="rounded-2xl bg-ink-50 p-3.5">
                    <dt class="text-xs font-medium text-ink-400">{{ __('labs.order.schedule') }}</dt>
                    <dd class="mt-1 font-semibold text-ink-800">{{ optional($order->scheduled_at)->format('Y-m-d H:i') }}</dd>
                </div>
                <div class="rounded-2xl bg-ink-50 p-3.5">
                    <dt class="text-xs font-medium text-ink-400">{{ __('labs.order.payment') }}</dt>
                    <dd class="mt-1 font-semibold text-ink-800">{{ $order->payment_mode?->label() }}</dd>
                    @if ($order->payment_mode)
                        <dd class="mt-0.5 text-xs text-ink-500">{{ $order->payment_mode->hint() }}</dd>
                    @endif
                </div>
            </dl>
        </div>

        <div class="overflow-hidden rounded-[1.5rem] bg-white p-5 shadow-sm ring-1 ring-ink-100">
            <h2 class="text-start text-sm font-semibold text-ink-900">{{ __('labs.order.items') }}</h2>
            <ul class="mt-3 divide-y divide-ink-100 text-sm">
                @foreach ($order->items as $item)
                    <li class="flex items-start justify-between gap-3 py-3 first:pt-0 last:pb-0">
                        <div class="text-start">
                            <p class="font-medium text-ink-800">{{ $item->catalogItem()?->name }}</p>
                            <p class="text-xs text-ink-400">
                                {{ $item->item_type === 'package' ? __('labs.packages') : __('labs.tests') }}
                                · ×{{ $item->qty }}
                            </p>
                            @if ($order->status === \App\Enums\LabOrderStatus::Completed && ($item->result_value || $item->result_note))
                                <p class="mt-1 text-sm text-ink-700">
                                    {{ $item->result_value }} {{ $item->result_unit }}
                                    @if ($item->result_flag)
                                        <span class="text-xs text-warning-700">{{ $item->result_flag }}</span>
                                    @endif
                                </p>
                                @if ($item->result_note)
                                    <p class="text-xs text-ink-400">{{ $item->result_note }}</p>
                                @endif
                            @endif
                        </div>
                        <p class="shrink-0 font-bold tabular text-ink-900">{{ number_format((float) $item->line_total) }} {{ __('common.currency') }}</p>
                    </li>
                @endforeach
            </ul>
            <div class="mt-3 flex justify-between border-t border-ink-100 pt-3 text-sm font-bold">
                <span>{{ __('labs.cart.total') }}</span>
                <span class="tabular">{{ number_format((float) $order->total) }} {{ __('common.currency') }}</span>
            </div>
        </div>

        <div class="rounded-[1.5rem] bg-white p-5 text-start shadow-sm ring-1 ring-ink-100">
            <h2 class="text-sm font-semibold text-ink-900">{{ __('labs.order.payment') }}</h2>
            <div class="mt-3 rounded-2xl bg-ink-50 px-4 py-3">
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
        </div>

        @if ($order->notes)
            <div class="rounded-[1.5rem] bg-white p-5 text-start shadow-sm ring-1 ring-ink-100">
                <h2 class="text-sm font-semibold text-ink-900">{{ __('labs.order.notes') }}</h2>
                <p class="mt-2 text-sm text-ink-600">{{ $order->notes }}</p>
            </div>
        @endif

        <div class="rounded-[1.5rem] bg-gradient-to-br from-primary-50 to-white p-5 text-start ring-1 ring-primary-100">
            <h2 class="text-sm font-semibold text-ink-900">{{ __('labs.order.next') }}</h2>
            <p class="mt-2 text-sm text-ink-500">{{ __('labs.checkout.pending_hint') }}</p>
            <div class="mt-4 flex flex-wrap gap-2">
                <x-button :href="route('appointments.index')" variant="accent">{{ __('booking.my_appointments') }}</x-button>
                @if ($order->careDocuments->isNotEmpty())
                    <x-button :href="route('records.show', $order->careDocuments->first())" variant="secondary">{{ __('records.view_results') }}</x-button>
                @else
                    <x-button :href="route('records.index')" variant="ghost">{{ __('records.heading') }}</x-button>
                @endif
                <x-button :href="route('labs.index')" variant="ghost">{{ __('labs.tests') }}</x-button>
            </div>
        </div>
    </div>
</x-layouts.public>
