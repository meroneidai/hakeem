<x-layouts.admin :title="__('admin.bookings.create')">
    <x-page-header :title="__('admin.bookings.create')" :subtitle="__('admin.bookings.create_sub')">
        <x-slot:actions>
            <x-button :href="route('admin.bookings.index')" variant="secondary">{{ __('common.back') }}</x-button>
        </x-slot:actions>
    </x-page-header>

    <form method="GET" action="{{ route('admin.bookings.create') }}" class="card mb-5 max-w-2xl p-4">
        <x-field :label="__('admin.nav.clinics')" name="clinic" required>
            <x-select name="clinic" :placeholder="__('admin.bookings.pick_clinic')" onchange="this.form.submit()">
                @foreach ($clinics as $option)
                    <option value="{{ $option->id }}" @selected((string) old('clinic', $clinic?->id) === (string) $option->id)>
                        {{ $option->name }}
                    </option>
                @endforeach
            </x-select>
        </x-field>
    </form>

    @if (! $clinic)
        <x-card class="max-w-2xl">
            <p class="text-sm text-ink-600">{{ __('admin.bookings.pick_clinic_hint') }}</p>
        </x-card>
    @elseif ($clinic->doctors->isEmpty() || $clinic->addresses->isEmpty())
        <x-card class="max-w-2xl">
            <p class="text-sm text-ink-600">{{ __('admin.bookings.setup_required') }}</p>
        </x-card>
    @else
        <form
            method="POST"
            action="{{ route('admin.bookings.store') }}"
            class="max-w-2xl space-y-6"
            @day-changed="selected = ''; load()"
            x-data="{
                doctor: @js((string) old('doctor_id', $clinic->doctors->first()?->id)),
                address: @js((string) old('clinic_address_id', $clinic->addresses->first()?->id)),
                service: @js((string) old('service_type_id', $serviceTypes->first()?->id)),
                date: @js(old('date', now()->timezone(config('hakeem.display_timezone'))->toDateString())),
                selected: @js(old('scheduled_at')),
                days: @js($dayOptions ?? []),
                flags: @js($serviceFlags ?? []),
                doctorSlugs: @js($doctorSlugs ?? []),
                phone: @js(old('patient_phone', '')),
                patientName: @js(old('patient_name', '')),
                patientStatus: @js(old('patient_name') ? 'ready' : null),
                lookingUp: false,
                lookupError: '',
                slots: [],
                loading: false,
                duration: null,
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
                    const service = this.flags[this.service]?.payment_modes || [];
                    return service.length === 0 || service.includes(value);
                },
                visibleModeCount() {
                    return @js(collect($paymentModes)->map(fn ($mode) => $mode->value)->values()).filter((value) => this.modeAllowed(value)).length;
                },
                clearPatient() {
                    this.patientStatus = null;
                    this.patientName = '';
                    this.lookupError = '';
                },
                async lookupPatient() {
                    this.lookupError = '';
                    const phone = (this.phone || '').trim();
                    if (! phone) {
                        this.lookupError = @js(__('auth.identifier_required'));
                        return;
                    }
                    this.lookingUp = true;
                    try {
                        const url = new URL(@js(route('admin.bookings.patients.lookup')), window.location.origin);
                        url.searchParams.set('phone', phone);
                        const response = await fetch(url, {
                            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                        });
                        const payload = await response.json();
                        if (! response.ok) {
                            this.lookupError = payload.message || Object.values(payload.errors || {}).flat()[0] || @js(__('auth.invalid_phone'));
                            this.patientStatus = null;
                            return;
                        }
                        if (payload.found) {
                            this.phone = payload.patient.phone || phone;
                            this.patientName = payload.patient.name || '';
                            this.patientStatus = 'found';
                        } else {
                            this.patientName = '';
                            this.patientStatus = 'missing';
                        }
                    } catch (e) {
                        this.lookupError = @js(__('auth.invalid_phone'));
                        this.patientStatus = null;
                    } finally {
                        this.lookingUp = false;
                    }
                },
                async load() {
                    const slug = this.doctorSlugs[this.doctor];
                    if (! slug || ! this.address || ! this.service || ! this.date) {
                        this.slots = [];
                        return;
                    }
                    this.loading = true;
                    const url = new URL('/doctors/' + slug + '/slots', window.location.origin);
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
            <input type="hidden" name="clinic_id" value="{{ $clinic->id }}">

            <x-card :title="$clinic->name">
                <div class="space-y-6">
                    <section class="space-y-3">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wide text-ink-400">{{ __('admin.bookings.patient_step') }}</p>
                            <p class="mt-1 text-sm text-ink-500">{{ __('admin.bookings.search_phone_hint') }}</p>
                        </div>

                        <div class="flex flex-col gap-3 sm:flex-row sm:items-end">
                            <div class="min-w-0 flex-1">
                                <x-field :label="__('admin.bookings.search_phone')" name="patient_phone" required :hint="__('auth.phone_hint')">
                                    <input
                                        type="tel"
                                        name="patient_phone"
                                        id="patient_phone"
                                        dir="ltr"
                                        placeholder="{{ __('auth.phone_placeholder') }}"
                                        class="field-input focus:border-primary-400 focus:ring-2 focus:ring-primary-100"
                                        x-model="phone"
                                        @keydown.enter.prevent="lookupPatient()"
                                        :readonly="patientStatus === 'found'"
                                    >
                                </x-field>
                            </div>
                            <div class="flex gap-2 sm:pb-1">
                                <x-button
                                    type="button"
                                    variant="secondary"
                                    x-show="patientStatus !== 'found'"
                                    @click="lookupPatient()"
                                    x-bind:disabled="lookingUp"
                                >
                                    <span x-show="! lookingUp">{{ __('admin.bookings.lookup') }}</span>
                                    <span x-cloak x-show="lookingUp">{{ __('admin.bookings.looking_up') }}</span>
                                </x-button>
                                <x-button
                                    type="button"
                                    variant="ghost"
                                    x-cloak
                                    x-show="patientStatus"
                                    @click="clearPatient()"
                                >
                                    {{ __('admin.bookings.clear_patient') }}
                                </x-button>
                            </div>
                        </div>

                        <p class="text-sm text-danger-600" x-cloak x-show="lookupError" x-text="lookupError"></p>

                        <div
                            x-cloak
                            x-show="patientStatus === 'found'"
                            class="rounded-2xl border border-success-200 bg-success-50 px-3 py-3 text-sm text-success-900"
                        >
                            <p class="font-semibold">{{ __('admin.bookings.patient_found') }}</p>
                            <p class="mt-1">
                                <span x-text="patientName"></span>
                                <span class="text-success-700" dir="ltr"> · <span x-text="phone"></span></span>
                            </p>
                        </div>

                        <div
                            x-cloak
                            x-show="patientStatus === 'missing'"
                            class="rounded-2xl border border-warning-200 bg-warning-50 px-3 py-3 text-sm text-warning-900"
                        >
                            {{ __('admin.bookings.patient_missing') }}
                        </div>

                        <div x-show="patientStatus === 'found' || patientStatus === 'missing' || patientStatus === 'ready'">
                            <x-field :label="__('clinic.queue.patient_name')" name="patient_name" required>
                                <input
                                    type="text"
                                    name="patient_name"
                                    id="patient_name"
                                    autocomplete="name"
                                    class="field-input focus:border-primary-400 focus:ring-2 focus:ring-primary-100"
                                    x-model="patientName"
                                    :readonly="patientStatus === 'found'"
                                >
                            </x-field>
                        </div>
                    </section>

                    <section class="space-y-3" x-show="patientStatus">
                        <p class="text-xs font-semibold uppercase tracking-wide text-ink-400">{{ __('admin.bookings.details_step') }}</p>

                        <x-field :label="__('clinic.queue.doctor')" name="doctor_id" required>
                            <x-select
                                name="doctor_id"
                                :placeholder="__('clinic.queue.doctor')"
                                :options="$clinic->doctors->mapWithKeys(fn ($doctor) => [$doctor->id => $doctor->name])->all()"
                                x-model="doctor"
                                @change="selected = ''; load()"
                            />
                        </x-field>

                        <div>
                            <p class="mb-2 text-sm font-medium text-ink-700">{{ __('clinic.queue.branch') }}</p>
                            <p class="mb-3 text-sm text-ink-500">{{ __('admin.bookings.branch_hint') }}</p>
                            <div class="space-y-2" role="radiogroup" aria-label="{{ __('clinic.queue.branch') }}">
                                @foreach ($clinic->addresses as $address)
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
                                            @change="selected = ''; load()"
                                            @checked((string) old('clinic_address_id', $clinic->addresses->first()?->id) === (string) $address->id)
                                        >
                                        <span class="min-w-0 flex-1">
                                            <span class="block font-semibold text-ink-900">{{ $address->displayName() }}</span>
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
                                <p class="mt-2 text-sm text-danger-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <x-field :label="__('clinic.queue.service')" name="service_type_id" required>
                            <x-select
                                name="service_type_id"
                                :placeholder="__('clinic.queue.service')"
                                :options="$serviceTypes->pluck('name', 'id')->all()"
                                x-model="service"
                                @change="selected = ''; load()"
                            />
                        </x-field>
                        <p class="text-sm font-medium text-primary-700" x-show="durationLabel" x-text="durationLabel"></p>
                    </section>

                    <section class="space-y-3" x-show="patientStatus">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wide text-ink-400">{{ __('admin.bookings.when_step') }}</p>
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
                            <p
                                x-cloak
                                x-show="selectedLabel"
                                class="mt-2 text-sm font-medium text-primary-800"
                            >
                                {{ __('booking.selected_slot') }}:
                                <span dir="ltr" x-text="selectedLabel"></span>
                            </p>
                            @error('scheduled_at')
                                <p class="mt-2 text-sm text-danger-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <x-field :label="__('booking.sessions')" name="session_count" :hint="__('booking.sessions_hint')">
                            <x-input type="number" name="session_count" min="1" max="30" :value="old('session_count', 1)" dir="ltr"/>
                        </x-field>
                        @include('bookings._payment_modes', ['filterPaymentsByService' => true])
                        <x-field :label="__('clinic.queue.notes')" name="notes">
                            <x-textarea name="notes" rows="3"/>
                        </x-field>
                    </section>
                </div>

                <x-slot:footer>
                    <x-button :href="route('admin.bookings.index')" variant="ghost">{{ __('common.cancel') }}</x-button>
                    <x-button variant="accent" x-bind:disabled="! patientStatus || ! selected">{{ __('admin.bookings.submit') }}</x-button>
                </x-slot:footer>
            </x-card>
        </form>
    @endif
</x-layouts.admin>
