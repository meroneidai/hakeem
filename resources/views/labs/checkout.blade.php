<x-layouts.public :title="__('labs.checkout.heading')" robots="noindex,nofollow">
    <x-catalog-hero :title="__('labs.checkout.heading')" :subtitle="__('labs.checkout.subtitle')">
        <x-slot:crumbs>
            <a href="{{ route('home') }}" class="hover:text-primary-700">{{ __('discover.nav.home') }}</a>
            <span aria-hidden="true">·</span>
            <a href="{{ route('labs.cart') }}" class="hover:text-primary-700">{{ __('labs.cart.heading') }}</a>
        </x-slot:crumbs>
    </x-catalog-hero>

    <div class="mx-auto max-w-3xl px-4 py-8">
        @if ($clinics->isEmpty())
            <x-card>
                <x-empty-state :message="__('labs.checkout.no_clinic')"/>
                <div class="mt-4 text-center">
                    <x-button :href="route('labs.cart')" variant="ghost">{{ __('labs.cart.heading') }}</x-button>
                </div>
            </x-card>
        @else
            <form method="POST" action="{{ route('labs.checkout.store') }}" x-data="{
                clinicId: '{{ old('clinic_id', $selectedClinic?->id ?? $clinics->first()?->id) }}',
                mode: '{{ old('collection_mode', 'clinic') }}',
                addressSource: '{{ old('patient_address_id') ? 'saved' : (old('patient_home_address') || old('latitude') ? 'new' : ($addresses->isNotEmpty() ? 'saved' : 'new')) }}',
                clinics: {{ \Illuminate\Support\Js::from($clinicQuotes) }},
                get clinic() {
                    return this.clinics.find(row => String(row.id) === String(this.clinicId)) || this.clinics[0] || {}
                },
                get homeOk() { return Boolean(this.clinic.home) },
                get total() {
                    return this.mode === 'home' ? Number(this.clinic.home_total || 0) : Number(this.clinic.clinic_total || 0)
                },
                format(value) {
                    return new Intl.NumberFormat('ar-EG').format(value)
                },
                revealMap() {
                    this.$nextTick(() => window.dispatchEvent(new Event('hakeem-home-map')))
                },
                dropSavedPin(event) {
                    const option = event.target.selectedOptions[0]
                    if (! option) {
                        return
                    }
                    window.dispatchEvent(new CustomEvent('hakeem-home-pin', {
                        detail: { lat: option.dataset.lat, lng: option.dataset.lng }
                    }))
                }
            }"
            x-init="$watch('mode', value => { if (value === 'home') revealMap() }); if (mode === 'home') { revealMap(); $nextTick(() => { const select = $el.querySelector('[name=patient_address_id]'); if (select && !select.disabled) dropSavedPin({ target: select }) }) }"
            @change="if (mode === 'home' && !homeOk) { const next = clinics.find(row => row.home); if (next) clinicId = String(next.id) }">
                @csrf

                <x-card class="mb-4">
                    <ul class="divide-y divide-ink-100 text-sm">
                        @foreach ($cart->lines() as $line)
                            <li class="flex justify-between gap-3 py-2 first:pt-0 last:pb-0">
                                <span>{{ $line['item']->name }} ×{{ $line['qty'] }}</span>
                            </li>
                        @endforeach
                    </ul>
                    <div class="mt-3 flex justify-between border-t border-ink-100 pt-3 text-sm">
                        <span class="text-ink-500">{{ __('labs.cart.total') }}</span>
                        <span class="font-semibold" x-text="format(total) + ' {{ __('common.currency') }}'"></span>
                    </div>
                    <p class="mt-2 text-xs text-ink-400">{{ __('labs.checkout.price_mode_hint') }}</p>
                </x-card>

                <x-card class="space-y-4">
                    <x-field :label="__('labs.checkout.clinic')" name="clinic_id" required>
                        <select name="clinic_id" x-model="clinicId" class="field-input">
                            @foreach ($clinicQuotes as $quote)
                                <option value="{{ $quote['id'] }}">{{ $quote['name'] }}</option>
                            @endforeach
                        </select>
                    </x-field>

                    <fieldset class="space-y-2">
                        <legend class="text-sm font-medium text-ink-700">{{ __('labs.checkout.collection') }}</legend>
                        <label class="flex items-start gap-3 rounded-lg border border-ink-200 p-3 has-[:checked]:border-primary-400">
                            <input type="radio" name="collection_mode" value="clinic" class="mt-1" x-model="mode">
                            <span>
                                <span class="block text-sm font-medium">{{ __('labs.collection.clinic') }}</span>
                                <span class="block text-xs text-ink-500">{{ __('labs.checkout.collection_clinic_hint') }}</span>
                                <span class="mt-1 block text-xs font-medium text-primary-700" x-text="format(clinic.clinic_total || 0) + ' {{ __('common.currency') }}'"></span>
                            </span>
                        </label>
                        <label class="flex items-start gap-3 rounded-lg border border-ink-200 p-3 has-[:checked]:border-primary-400"
                               :class="homeOk ? '' : 'opacity-60'">
                            <input type="radio" name="collection_mode" value="home" class="mt-1" x-model="mode" :disabled="!homeOk">
                            <span>
                                <span class="block text-sm font-medium">{{ __('labs.collection.home') }}</span>
                                <span class="block text-xs text-ink-500">{{ __('labs.checkout.collection_home_hint') }}</span>
                                <span class="mt-1 block text-xs font-medium text-primary-700" x-show="homeOk" x-text="format(clinic.home_total || 0) + ' {{ __('common.currency') }}'"></span>
                                <span class="mt-1 block text-xs text-warning-700" x-show="!homeOk">{{ __('labs.checkout.home_not_offered') }}</span>
                            </span>
                        </label>
                    </fieldset>

                    <div x-show="mode === 'clinic'" x-cloak>
                        <x-field :label="__('booking.branch')" name="clinic_address_id">
                            <select name="clinic_address_id" class="field-input">
                                <template x-for="address in (clinic.addresses || [])" :key="address.id">
                                    <option :value="address.id" x-text="address.name"></option>
                                </template>
                            </select>
                        </x-field>
                    </div>

                    <div x-show="mode === 'home'" x-cloak class="space-y-3">
                        @if ($addresses->isNotEmpty())
                            <fieldset class="grid gap-2 sm:grid-cols-2">
                                <label class="flex items-center gap-2 rounded-lg border border-ink-200 p-3 text-sm has-[:checked]:border-primary-400">
                                    <input type="radio" value="saved" x-model="addressSource" @change="revealMap()">
                                    {{ __('labs.checkout.saved_address') }}
                                </label>
                                <label class="flex items-center gap-2 rounded-lg border border-ink-200 p-3 text-sm has-[:checked]:border-primary-400">
                                    <input type="radio" value="new" x-model="addressSource" @change="revealMap()">
                                    {{ __('labs.checkout.new_address') }}
                                </label>
                            </fieldset>
                            <div x-show="addressSource === 'saved'">
                                <x-field :label="__('labs.checkout.home_address')" name="patient_address_id">
                                    <select name="patient_address_id" class="field-input" :disabled="mode !== 'home' || addressSource !== 'saved'" @change="dropSavedPin($event)">
                                        @foreach ($addresses as $address)
                                            <option value="{{ $address->id }}"
                                                    data-lat="{{ $address->latitude }}"
                                                    data-lng="{{ $address->longitude }}"
                                                    @selected($address->is_default)>
                                                {{ $address->displayName() }}
                                            </option>
                                        @endforeach
                                    </select>
                                </x-field>
                            </div>
                        @endif

                        <x-map-picker :lat="old('latitude')" :lng="old('longitude')"/>

                        <div x-show="addressSource === 'new' || {{ $addresses->isEmpty() ? 'true' : 'false' }}">
                            <x-field :label="__('labs.checkout.address_label')" name="address_label">
                                <x-input name="address_label" :value="old('address_label')" :placeholder="__('labs.checkout.address_home_label')"/>
                            </x-field>
                            <x-field :label="__('labs.checkout.home_details')" name="patient_home_address" class="mt-3">
                                <x-textarea name="patient_home_address" rows="2" :value="old('patient_home_address')" :placeholder="__('labs.checkout.home_details_hint')"/>
                            </x-field>
                            <div class="mt-2">
                                <x-checkbox name="save_address" :label="__('labs.checkout.save_address')" :checked="old('save_address', true)"/>
                            </div>
                        </div>
                    </div>

                    <div class="grid gap-3 sm:grid-cols-2">
                        <x-field :label="__('labs.checkout.collection_date')" name="collection_date" required>
                            <x-input
                                name="collection_date"
                                type="date"
                                :value="old('collection_date', now()->addDay()->toDateString())"
                                :min="now()->toDateString()"
                            />
                        </x-field>
                        <x-field :label="__('labs.checkout.collection_time')" name="collection_time" required>
                            <select name="collection_time" class="field-input">
                                @foreach ($slots as $slot)
                                    <option value="{{ $slot }}" @selected(old('collection_time', '09:00') === $slot)>{{ $slot }}</option>
                                @endforeach
                            </select>
                        </x-field>
                    </div>
                    <p class="text-xs text-ink-400">{{ __('labs.checkout.slot_hint') }}</p>

                    @include('bookings._payment_modes')

                    <x-field :label="__('booking.notes')" name="notes">
                        <x-textarea name="notes" rows="3"/>
                    </x-field>

                    <x-button variant="accent" class="w-full">{{ __('labs.checkout.submit') }}</x-button>
                </x-card>
            </form>
        @endif
    </div>
</x-layouts.public>
