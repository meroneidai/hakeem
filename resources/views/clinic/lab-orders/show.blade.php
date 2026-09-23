<x-layouts.clinic :title="__('clinic.lab_orders.results')">
    <x-page-header :title="__('clinic.lab_orders.results')" :subtitle="$order->reference">
        <x-slot:actions>
            <x-button :href="route('clinic.lab-orders.index', ['date' => $order->scheduled_at->toDateString()])" variant="ghost">
                {{ __('common.back') }}
            </x-button>
        </x-slot:actions>
    </x-page-header>

    <div class="grid gap-4 lg:grid-cols-3">
        <x-card class="space-y-3 lg:col-span-1">
            <p class="text-sm text-ink-600">{{ $order->collection_mode->label() }}</p>
            @if ($order->collection_mode->value === 'home')
                @include('labs._visit-destination', ['order' => $order])
            @else
                <p class="text-sm font-semibold text-ink-900">{{ $order->patient?->name }}</p>
                <p class="text-xs text-ink-400" dir="ltr">{{ $order->patient?->phone }}</p>
                <p class="text-sm text-ink-700">{{ $order->address?->displayName() }}</p>
            @endif
            <p class="text-sm text-ink-600">{{ $order->scheduled_at->format('Y-m-d H:i') }}</p>
            <x-badge :tone="$order->status->tone()">{{ $order->status->label() }}</x-badge>
        </x-card>

        <x-card class="lg:col-span-2">
            <form method="POST" action="{{ route('clinic.lab-orders.update', $order) }}" class="space-y-4">
                @csrf
                @method('PUT')
                <input type="hidden" name="action" value="complete">

                <h2 class="text-sm font-semibold text-ink-900">{{ __('clinic.lab_orders.enter_results') }}</h2>

                @foreach ($order->items as $item)
                    <div class="rounded-xl border border-ink-100 p-3">
                        <p class="font-medium text-ink-900">{{ $item->catalogItem()?->name }}</p>
                        <div class="mt-3 grid gap-3 sm:grid-cols-3">
                            <x-field :label="__('clinic.lab_orders.result_value')" :name="'results.'.$item->id.'.value'">
                                <x-input :name="'results['.$item->id.'][value]'" :value="$item->result_value"/>
                            </x-field>
                            <x-field :label="__('clinic.lab_orders.result_unit')" :name="'results.'.$item->id.'.unit'">
                                <x-input :name="'results['.$item->id.'][unit]'" :value="$item->result_unit"/>
                            </x-field>
                            <x-field :label="__('clinic.lab_orders.result_flag')" :name="'results.'.$item->id.'.flag'">
                                <x-input :name="'results['.$item->id.'][flag]'" :value="$item->result_flag" :placeholder="__('clinic.lab_orders.result_flag_hint')"/>
                            </x-field>
                        </div>
                        <x-field class="mt-3" :label="__('clinic.lab_orders.result_note')" :name="'results.'.$item->id.'.note'">
                            <x-input :name="'results['.$item->id.'][note]'" :value="$item->result_note"/>
                        </x-field>
                    </div>
                @endforeach

                @if ($order->status->canTransitionTo(\App\Enums\LabOrderStatus::Completed))
                    <x-button variant="accent">{{ __('clinic.lab_orders.complete_with_results') }}</x-button>
                @endif
            </form>
        </x-card>
    </div>
</x-layouts.clinic>
