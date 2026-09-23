<x-layouts.public :title="__('booking.heading')" robots="noindex,nofollow">
    <x-catalog-hero :title="__('booking.heading')" :subtitle="__('booking.subtitle')">
        <x-slot:crumbs>
            <a href="{{ route('home') }}" class="hover:text-primary-700">{{ __('discover.nav.home') }}</a>
            <span aria-hidden="true">·</span>
            <a href="{{ route('doctors.show', $doctor) }}" class="hover:text-primary-700">{{ $doctor->name }}</a>
        </x-slot:crumbs>
    </x-catalog-hero>

    <div class="mx-auto max-w-2xl px-4 py-8">

        <x-card class="mb-4">
            <div class="flex items-center gap-3">
                <x-media
                    :src="\App\Support\PublicImage::url($doctor->profile_photo_path)"
                    :alt="$doctor->name"
                    class="size-14 rounded-2xl"
                />
                <div>
                    <p class="font-semibold text-ink-900">{{ $doctor->name }}</p>
                    <p class="text-sm text-ink-500">{{ $doctor->specialty?->name }}</p>
                </div>
            </div>
        </x-card>

        <x-card>
            <form
                method="POST"
                action="{{ route('book.doctors.store', $doctor) }}"
                class="space-y-4"
                @day-changed="selected = ''; load()"
                x-data="{
                    address: @js((string) old('clinic_address_id', $addresses->first()?->id)),
                    service: @js((string) old('service_type_id', $selectedServiceTypeId)),
                    date: @js(old('date', now()->timezone(config('hakeem.display_timezone'))->toDateString())),
                    selected: @js(old('scheduled_at')),
                    flags: @js($serviceFlags ?? []),
                    clinicModes: @js($clinicModes ?? []),
                    addressClinics: @js($addressClinics ?? []),
                    days: @js($dayOptions ?? []),
                    slots: [],
                    loading: false,
                    duration: null,
                    get needsHome() {
                        return Boolean(this.flags[this.service]?.requires_patient_address);
                    },
                    get durationLabel() {
                        const minutes = this.flags[this.service]?.duration_minutes || this.duration;
                        return minutes ? @js(__('booking.duration_minutes', ['minutes' => ':minutes'])).replace(':minutes', minutes) : '';
                    },
                    modeAllowed(value) {
                        const clinicId = this.addressClinics[this.address];
                        const clinic = this.clinicModes[clinicId] || [];
                        const service = this.flags[this.service]?.payment_modes || [];
                        if (service.length && ! service.includes(value)) {
                            return false;
                        }
                        if (! clinic.length) {
                            return true;
                        }
                        if (! service.length) {
                            return clinic.includes(value);
                        }
                        const overlap = clinic.filter((mode) => service.includes(mode));
                        return overlap.length ? overlap.includes(value) : true;
                    },
                    visibleModeCount() {
                        return @js(collect($paymentModes)->pluck('value')->values()).filter((value) => this.modeAllowed(value)).length;
                    },
                    async load() {
                        if (! this.address || ! this.service || ! this.date) {
                            this.slots = [];
                            return;
                        }
                        this.loading = true;
                        const url = new URL(@js(route('doctors.slots', $doctor)), window.location.origin);
                        url.searchParams.set('clinic_address_id', this.address);
                        url.searchParams.set('service_type_id', this.service);
                        url.searchParams.set('date', this.date);
                        const response = await fetch(url, { headers: { 'Accept': 'application/json' } });
                        const payload = await response.json();
                        this.slots = payload.data || [];
                        this.duration = payload.duration_minutes || this.flags[this.service]?.duration_minutes || null;
                        this.loading = false;
                    }
                }"
                x-init="load()"
            >
                @csrf

                <x-field :label="__('booking.service')" name="service_type_id" required>
                    <x-select
                        name="service_type_id"
                        :placeholder="__('booking.service')"
                        :options="$serviceTypes->pluck('name', 'id')->all()"
                        :selected="$selectedServiceTypeId ?? null"
                        x-model="service"
                        @change="duration = null; selected = ''; load()"
                    />
                </x-field>

                <x-field :label="__('booking.branch')" name="clinic_address_id" required>
                    <x-select name="clinic_address_id" :placeholder="__('booking.branch')" x-model="address" @change="duration = null; selected = ''; load()">
                        @foreach ($addresses as $address)
                            <option value="{{ $address->id }}" @selected((string) old('clinic_address_id') === (string) $address->id)>
                                {{ $address->clinic?->name }} — {{ $address->displayName() }}
                            </option>
                        @endforeach
                    </x-select>
                </x-field>

                @include('bookings._day_picker')

                <p class="text-sm font-medium text-primary-700" x-show="durationLabel" x-text="durationLabel"></p>

                <x-field :label="__('booking.when')" name="scheduled_at" required>
                    <input type="hidden" name="scheduled_at" :value="selected">
                    <div class="flex flex-wrap gap-2">
                        <template x-for="slot in slots" :key="slot.starts_at">
                            <button
                                type="button"
                                class="rounded-full px-3 py-1.5 text-sm ring-1"
                                :class="selected === slot.starts_at ? 'bg-primary-600 text-white ring-primary-600' : 'bg-white text-ink-700 ring-ink-200'"
                                @click="selected = slot.starts_at"
                                x-text="slot.label"
                            ></button>
                        </template>
                    </div>
                    <p class="mt-2 text-xs text-ink-500" x-show="loading">{{ __('booking.loading_slots') }}</p>
                    <p class="mt-2 text-xs text-ink-500" x-show="! loading && slots.length === 0">{{ __('booking.no_slots') }}</p>
                    <div class="mt-3">
                        <p class="mb-1 text-xs text-ink-500">{{ __('booking.or_custom_time') }}</p>
                        <input
                            type="datetime-local"
                            value="{{ is_string(old('scheduled_at')) ? old('scheduled_at') : '' }}"
                            min="{{ now()->addHour()->format('Y-m-d\TH:i') }}"
                            dir="ltr"
                            class="field-input focus:border-primary-400 focus:ring-2 focus:ring-primary-100"
                            @change="selected = $event.target.value.replace('T', ' ') + ':00'"
                        >
                    </div>
                </x-field>

                @if (($maxSessions ?? 1) > 1 || ($requiresEvaluation ?? false))
                    <x-field :label="__('booking.sessions')" name="session_count" :hint="__('booking.sessions_hint')">
                        <x-input type="number" name="session_count" min="1" max="30" :value="old('session_count', 1)" dir="ltr"/>
                    </x-field>
                @endif

                @if ($requiresEvaluation ?? false)
                    <p class="text-xs text-ink-500">{{ __('booking.evaluation_gate_hint') }}</p>
                @endif

                @include('bookings._payment_modes', ['filterPaymentsByService' => true])

                <div x-show="needsHome" x-cloak>
                    <x-field :label="__('booking.home_address')" name="patient_home_address" :required="true">
                        <x-input name="patient_home_address" :value="old('patient_home_address')" maxlength="255"/>
                    </x-field>
                </div>

                <x-field :label="__('booking.notes')" name="notes">
                    <x-textarea name="notes" rows="4"/>
                </x-field>

                <x-button variant="accent" class="w-full">{{ __('booking.submit') }}</x-button>
            </form>
        </x-card>
    </div>
</x-layouts.public>
