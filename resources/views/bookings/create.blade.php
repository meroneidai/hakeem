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
                class="space-y-6"
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
                    get selectedLabel() {
                        if (! this.selected) {
                            return '';
                        }
                        const slot = this.slots.find((item) => item.starts_at === this.selected);
                        return slot?.label || this.selected;
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
                        try {
                            const response = await fetch(url, { headers: { 'Accept': 'application/json' } });
                            const payload = await response.json();
                            this.slots = payload.data || [];
                            this.duration = payload.duration_minutes || this.flags[this.service]?.duration_minutes || null;
                            if (this.selected && ! this.slots.some((slot) => slot.starts_at === this.selected)) {
                                this.selected = '';
                            }
                        } catch (e) {
                            this.slots = [];
                        } finally {
                            this.loading = false;
                        }
                    }
                }"
                x-init="load()"
            >
                @csrf

                <section class="space-y-3">
                    <p class="text-xs font-semibold uppercase tracking-wide text-ink-400">{{ __('booking.steps.service') }}</p>
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
                    <p class="text-sm font-medium text-primary-700" x-show="durationLabel" x-text="durationLabel"></p>
                </section>

                <section class="space-y-3">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wide text-ink-400">{{ __('booking.steps.location') }}</p>
                        <p class="mt-1 text-sm text-ink-500">{{ __('booking.branch_hint') }}</p>
                    </div>

                    @if ($addresses->isEmpty())
                        <x-alert tone="warning">{{ __('booking.no_branches') }}</x-alert>
                    @else
                        <div class="space-y-2" role="radiogroup" aria-label="{{ __('booking.branch') }}">
                            @foreach ($addresses as $address)
                                <label
                                    class="flex cursor-pointer gap-3 rounded-2xl border p-3 transition"
                                    :class="address === @js((string) $address->id)
                                        ? 'border-primary-400 bg-primary-50 ring-1 ring-primary-200'
                                        : 'border-ink-200 bg-white hover:border-primary-200'"
                                >
                                    <input
                                        type="radio"
                                        class="mt-1 size-4 border-ink-300 text-primary-600 focus:ring-primary-300"
                                        name="clinic_address_id"
                                        value="{{ $address->id }}"
                                        x-model="address"
                                        @change="duration = null; selected = ''; load()"
                                        @checked((string) old('clinic_address_id', $addresses->first()?->id) === (string) $address->id)
                                    >
                                    <span class="min-w-0 flex-1">
                                        <span class="block font-semibold text-ink-900">
                                            {{ $address->clinic?->name }}
                                            <span class="font-normal text-ink-500">· {{ $address->displayName() }}</span>
                                        </span>
                                        <span class="mt-0.5 block text-sm text-ink-500">
                                            {{ collect([$address->city?->name, $address->address_line, $address->landmark])->filter()->join(' — ') }}
                                        </span>
                                        @if ($address->phone)
                                            <span class="mt-1 block text-xs text-ink-400" dir="ltr">{{ $address->phone }}</span>
                                        @endif
                                    </span>
                                </label>
                            @endforeach
                        </div>
                        @error('clinic_address_id')
                            <p class="text-sm text-danger-600">{{ $message }}</p>
                        @enderror
                    @endif
                </section>

                <section class="space-y-3">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wide text-ink-400">{{ __('booking.steps.when') }}</p>
                        <p class="mt-1 text-sm text-ink-500">{{ __('booking.when_hint') }}</p>
                    </div>

                    @include('bookings._day_picker')

                    <div>
                        <p class="mb-2 text-sm font-medium text-ink-700">{{ __('booking.pick_slot') }}</p>
                        <input type="hidden" name="scheduled_at" :value="selected">
                        <div class="flex flex-wrap gap-2" x-show="! loading && slots.length > 0">
                            <template x-for="slot in slots" :key="slot.starts_at">
                                <button
                                    type="button"
                                    class="rounded-full px-3 py-1.5 text-sm ring-1 transition"
                                    :class="selected === slot.starts_at ? 'bg-primary-600 text-white ring-primary-600' : 'bg-white text-ink-700 ring-ink-200 hover:ring-primary-300'"
                                    @click="selected = slot.starts_at"
                                    x-text="slot.label"
                                ></button>
                            </template>
                        </div>
                        <p class="text-sm text-ink-500" x-show="loading">{{ __('booking.loading_slots') }}</p>
                        <div
                            x-cloak
                            x-show="! loading && slots.length === 0"
                            class="rounded-2xl border border-warning-200 bg-warning-50 px-3 py-3 text-sm text-warning-900"
                        >
                            {{ __('booking.no_slots') }}
                        </div>
                        @error('scheduled_at')
                            <p class="mt-2 text-sm text-danger-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div
                        x-cloak
                        x-show="selected"
                        class="rounded-2xl border border-success-200 bg-success-50 px-3 py-2 text-sm text-success-900"
                    >
                        <span class="font-medium">{{ __('booking.selected_slot') }}:</span>
                        <span class="tabular" x-text="selectedLabel"></span>
                        <span class="text-success-700"> · {{ __('booking.egypt_time') }}</span>
                    </div>
                </section>

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
                    <x-textarea name="notes" rows="3"/>
                </x-field>

                <x-button
                    variant="accent"
                    class="w-full"
                    x-bind:disabled="! selected || ! address || ! service"
                >
                    {{ __('booking.submit') }}
                </x-button>
                <p class="text-center text-xs text-ink-400" x-show="! selected">{{ __('booking.select_slot_first') }}</p>
            </form>
        </x-card>
    </div>
</x-layouts.public>
