<x-layouts.clinic :title="__('clinic.queue.add')">
    <x-page-header :title="__('clinic.queue.add')" :subtitle="__('clinic.queue.add_subtitle')"/>

    @if ($clinic->doctors->isEmpty() || $clinic->addresses->isEmpty())
        <x-card>
            <p class="text-sm text-ink-600">{{ __('clinic.queue.setup_required') }}</p>
            <div class="mt-4">
                <x-button :href="route('clinic.queue.index')" variant="ghost">{{ __('common.back') }}</x-button>
            </div>
        </x-card>
    @else
        <form
            method="POST"
            action="{{ route('clinic.queue.store') }}"
            @day-changed="
                const input = $refs.when;
                if (input) {
                    const time = (input.value || '').slice(11, 16) || '09:00';
                    input.value = date + 'T' + time;
                }
            "
            x-data="{
                service: @js((string) old('service_type_id', $serviceTypes->first()?->id)),
                date: @js(old('date', now()->timezone(config('hakeem.display_timezone'))->toDateString())),
                days: @js($dayOptions ?? []),
                flags: @js($serviceFlags ?? []),
                modeAllowed(value) {
                    const service = this.flags[this.service]?.payment_modes || [];
                    return service.length === 0 || service.includes(value);
                },
                visibleModeCount() {
                    return @js(collect($paymentModes)->map(fn ($mode) => $mode->value)->values()).filter((value) => this.modeAllowed(value)).length;
                },
                get durationLabel() {
                    const minutes = this.flags[this.service]?.duration_minutes;
                    return minutes ? @js(__('booking.duration_minutes', ['minutes' => ':minutes'])).replace(':minutes', minutes) : '';
                }
            }"
        >
            @csrf
            <x-card class="max-w-xl">
                <div class="space-y-4">
                    <x-field :label="__('clinic.queue.patient_name')" name="patient_name" required>
                        <x-input name="patient_name" autocomplete="name"/>
                    </x-field>
                    <x-field :label="__('clinic.queue.patient_phone')" name="patient_phone" required :hint="__('auth.phone_hint')">
                        <x-input name="patient_phone" type="tel" dir="ltr" :placeholder="__('auth.phone_placeholder')"/>
                    </x-field>
                    <x-field :label="__('clinic.queue.doctor')" name="doctor_id" required>
                        <x-select
                            name="doctor_id"
                            :placeholder="__('clinic.queue.doctor')"
                            :options="$clinic->doctors->mapWithKeys(fn ($doctor) => [$doctor->id => $doctor->name])->all()"
                        />
                    </x-field>
                    <x-field :label="__('clinic.queue.branch')" name="clinic_address_id" required>
                        <x-select name="clinic_address_id" :placeholder="__('clinic.queue.branch')">
                            @foreach ($clinic->addresses as $address)
                                <option value="{{ $address->id }}" @selected((string) old('clinic_address_id') === (string) $address->id)>
                                    {{ $address->displayName() }}
                                </option>
                            @endforeach
                        </x-select>
                    </x-field>
                    <x-field :label="__('clinic.queue.service')" name="service_type_id" required>
                        <x-select
                            name="service_type_id"
                            :placeholder="__('clinic.queue.service')"
                            :options="$serviceTypes->pluck('name', 'id')->all()"
                            x-model="service"
                        />
                    </x-field>
                    <p class="text-sm font-medium text-primary-700" x-show="durationLabel" x-text="durationLabel"></p>
                    @include('bookings._day_picker')
                    <x-field :label="__('clinic.queue.when')" name="scheduled_at" required>
                        <input
                            type="datetime-local"
                            name="scheduled_at"
                            x-ref="when"
                            dir="ltr"
                            value="{{ old('scheduled_at', now()->timezone(config('hakeem.display_timezone'))->format('Y-m-d\TH:i')) }}"
                            class="field-input focus:border-primary-400 focus:ring-2 focus:ring-primary-100"
                        />
                    </x-field>
                    <x-field :label="__('booking.sessions')" name="session_count" :hint="__('booking.sessions_hint')">
                        <x-input type="number" name="session_count" min="1" max="30" :value="old('session_count', 1)" dir="ltr"/>
                    </x-field>
                    @include('bookings._payment_modes', ['filterPaymentsByService' => true])
                    <x-field :label="__('clinic.queue.notes')" name="notes">
                        <x-textarea name="notes" rows="3"/>
                    </x-field>
                </div>
                <x-slot:footer>
                    <x-button :href="route('clinic.queue.index')" variant="ghost">{{ __('common.cancel') }}</x-button>
                    <x-button variant="accent">{{ __('clinic.queue.submit') }}</x-button>
                </x-slot:footer>
            </x-card>
        </form>
    @endif
</x-layouts.clinic>
