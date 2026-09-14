<x-layouts.clinic :title="__('clinic.addresses.heading')">
    <x-page-header :title="__('clinic.addresses.heading')" :subtitle="__('clinic.addresses.subtitle')">
        <x-slot:actions>
            @if ($access->canManage() && $clinic->canAddAddress())
                <x-button :href="route('clinic.addresses.create')" variant="accent">
                    <x-icon name="plus" class="size-4"/>
                    {{ __('clinic.addresses.add') }}
                </x-button>
            @endif
        </x-slot:actions>
    </x-page-header>

    @if ($addresses->isEmpty())
        <x-card>
            <x-empty-state :message="__('common.no_results')"/>
        </x-card>
    @else
        <div class="space-y-4">
            @foreach ($addresses as $address)
                <x-card>
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div>
                            <h3 class="font-semibold text-ink-900">{{ $address->displayName() }}</h3>
                            <p class="mt-1 text-sm text-ink-500">
                                {{ $address->city?->governorate?->name }} · {{ $address->city?->name }}
                            </p>
                            <p class="mt-1 text-sm text-ink-600">{{ $address->address_line }}</p>
                            @if ($address->is_primary)
                                <x-badge tone="primary" class="mt-2">{{ __('clinic.addresses.primary') }}</x-badge>
                            @endif
                        </div>
                        @if ($access->canManage())
                            <x-button :href="route('clinic.addresses.edit', $address)" variant="secondary" size="sm">
                                {{ __('common.edit') }}
                            </x-button>
                        @endif
                    </div>
                    <div class="mt-4 grid gap-2 sm:grid-cols-2 lg:grid-cols-4">
                        @foreach ($address->schedulesByDay() as $schedule)
                            <div class="rounded-lg bg-ink-50 px-3 py-2 text-xs">
                                <span class="font-medium text-ink-700">{{ $schedule->day_of_week->label() }}</span>
                                <span class="mt-0.5 block text-ink-500">
                                    {{ $schedule->is_closed ? __('clinic.addresses.closed') : $schedule->timeRange() }}
                                </span>
                            </div>
                        @endforeach
                    </div>
                </x-card>
            @endforeach
        </div>
    @endif
</x-layouts.clinic>
