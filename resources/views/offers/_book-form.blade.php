@php
    $formId = $formId ?? 'offer-book';
@endphp

<div id="{{ $formId }}" class="scroll-mt-28 overflow-hidden rounded-[1.5rem] bg-white shadow-[0_12px_40px_rgba(15,42,95,0.08)] ring-1 ring-ink-100">
    <div class="border-b border-ink-100 bg-gradient-to-l from-accent-50 to-white px-4 py-3 sm:px-5">
        <h2 class="flex items-center gap-2 text-sm font-semibold text-ink-900">
            <span class="grid size-8 place-items-center rounded-full bg-accent-500 text-white">
                <x-icon name="calendar" class="size-4"/>
            </span>
            {{ __('offers.book') }}
        </h2>
    </div>

    <div class="p-4 sm:p-5">
        @auth
            <form
                method="POST"
                action="{{ route('book.offers.store', $offer) }}"
                class="space-y-4"
                @day-changed="
                    const input = $refs.when;
                    if (input) {
                        const time = (input.value || '').slice(11, 16) || '09:00';
                        input.value = date + 'T' + time;
                    }
                "
                x-data="{
                    date: @js(old('date', now()->timezone(config('hakeem.display_timezone'))->toDateString())),
                    days: @js($dayOptions ?? []),
                }"
            >
                @csrf
                <x-field :label="__('clinic.queue.doctor')" name="doctor_id" required>
                    <x-select
                        name="doctor_id"
                        :placeholder="__('clinic.queue.doctor')"
                        :options="$offer->clinic->doctors->mapWithKeys(fn ($doctor) => [$doctor->id => $doctor->name])->all()"
                    />
                </x-field>
                <x-field :label="__('booking.branch')" name="clinic_address_id" required>
                    <x-select name="clinic_address_id" :placeholder="__('booking.branch')">
                        @foreach ($offer->clinic->addresses as $address)
                            <option value="{{ $address->id }}" @selected((string) old('clinic_address_id') === (string) $address->id)>
                                {{ $address->displayName() }}
                            </option>
                        @endforeach
                    </x-select>
                </x-field>
                @if ($durationMinutes ?? null)
                    <p class="rounded-xl bg-primary-50 px-3 py-2 text-sm font-medium text-primary-700">
                        {{ __('booking.duration_minutes', ['minutes' => $durationMinutes]) }}
                    </p>
                @endif
                @include('bookings._day_picker')
                <x-field :label="__('booking.when')" name="scheduled_at" required>
                    <input
                        type="datetime-local"
                        name="scheduled_at"
                        x-ref="when"
                        value="{{ old('scheduled_at', now()->timezone(config('hakeem.display_timezone'))->addHour()->format('Y-m-d\TH:i')) }}"
                        min="{{ now()->addHour()->format('Y-m-d\TH:i') }}"
                        dir="ltr"
                        class="field-input focus:border-primary-400 focus:ring-2 focus:ring-primary-100"
                    />
                </x-field>
                @if (($offer->session_count ?? 1) > 1)
                    <x-field :label="__('booking.sessions')" name="session_count">
                        <x-input type="number" name="session_count" min="1" max="30" :value="old('session_count', $offer->session_count)" dir="ltr"/>
                    </x-field>
                @endif
                @if ($paymentModes)
                    @include('bookings._payment_modes')
                @endif
                <x-field :label="__('booking.notes')" name="notes">
                    <x-textarea name="notes" rows="3"/>
                </x-field>
                <x-button variant="accent" class="w-full">
                    <x-icon name="calendar" class="size-4"/>
                    {{ __('offers.book') }}
                </x-button>
            </form>
        @else
            <p class="text-sm text-ink-500">{{ __('booking.login_first') }}</p>
            <x-button :href="route('login')" variant="accent" class="mt-3 w-full">{{ __('auth.login') }}</x-button>
        @endauth
    </div>
</div>
