<x-layouts.clinic :title="__('clinic.addresses.edit')">
    <x-page-header :title="__('clinic.addresses.edit')" :subtitle="$address->displayName()"/>

    <form method="POST" action="{{ route('clinic.addresses.update', $address) }}">
        @csrf
        @method('PUT')

        <x-card class="max-w-4xl">
            @include('clinic.addresses._form')

            <h3 class="mb-3 mt-8 text-sm font-semibold text-ink-800">{{ __('clinic.addresses.hours') }}</h3>
            <div class="space-y-3">
                @foreach ($days as $day)
                    @php
                        $schedule = $address->schedulesByDay()->get($day->value);
                        $open = old("days.{$day->value}.open", $schedule && ! $schedule->is_closed);
                    @endphp
                    <div class="grid items-end gap-3 rounded-lg border border-ink-200 p-3 sm:grid-cols-[8rem_auto_1fr_1fr]"
                         x-data="{ open: {{ $open ? 'true' : 'false' }} }">
                        <label class="flex items-center gap-2 text-sm font-medium text-ink-800">
                            <input type="hidden" name="days[{{ $day->value }}][open]" value="0">
                            <input type="checkbox" name="days[{{ $day->value }}][open]" value="1" x-model="open"
                                   class="size-4 rounded border-ink-300 text-primary-600">
                            {{ $day->label() }}
                        </label>
                        <span class="text-xs text-ink-400" x-show="!open">{{ __('clinic.addresses.closed') }}</span>
                        <div class="contents" x-show="open">
                            <x-field :label="__('clinic.addresses.open_time')" :name="'days.'.$day->value.'.open_time'">
                                <x-input type="time" :name="'days['.$day->value.'][open_time]'"
                                         :value="old('days.'.$day->value.'.open_time', $schedule?->formatTime($schedule->open_time) ?: '09:00')"/>
                            </x-field>
                            <x-field :label="__('clinic.addresses.close_time')" :name="'days.'.$day->value.'.close_time'">
                                <x-input type="time" :name="'days['.$day->value.'][close_time]'"
                                         :value="old('days.'.$day->value.'.close_time', $schedule?->formatTime($schedule->close_time) ?: '17:00')"/>
                            </x-field>
                        </div>
                    </div>
                @endforeach
            </div>

            <x-slot:footer>
                <x-button :href="route('clinic.addresses.index')" variant="ghost">{{ __('common.cancel') }}</x-button>
                @if ($clinic->addresses->count() > 1)
                    <x-button form="delete-address" variant="danger-ghost">{{ __('common.delete') }}</x-button>
                @endif
                <x-button variant="accent">{{ __('common.save_changes') }}</x-button>
            </x-slot:footer>
        </x-card>
    </form>

    @if ($clinic->addresses->count() > 1)
        <form id="delete-address" method="POST" action="{{ route('clinic.addresses.destroy', $address) }}"
              onsubmit="return confirm(@json(__('common.confirm_delete')))">
            @csrf
            @method('DELETE')
        </form>
    @endif
</x-layouts.clinic>
