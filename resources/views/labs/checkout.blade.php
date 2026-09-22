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
            <x-card class="mb-4">
                <ul class="divide-y divide-ink-100 text-sm">
                    @foreach ($cart->lines() as $line)
                        <li class="flex justify-between gap-3 py-2 first:pt-0 last:pb-0">
                            <span>{{ $line['item']->name }} ×{{ $line['qty'] }}</span>
                            <span class="font-medium">{{ number_format($line['line_total']) }} {{ __('common.currency') }}</span>
                        </li>
                    @endforeach
                </ul>
                <div class="mt-3 flex justify-between border-t border-ink-100 pt-3 text-sm">
                    <span class="text-ink-500">{{ __('labs.cart.total') }}</span>
                    <span class="font-semibold">{{ number_format($cart->total()) }} {{ __('common.currency') }}</span>
                </div>
                <p class="mt-2 text-xs text-ink-400">{{ __('labs.checkout.price_hint') }}</p>
            </x-card>

            <form method="POST" action="{{ route('labs.checkout.store') }}" x-data="{
                clinicId: '{{ old('clinic_id', $clinics->first()?->id) }}',
                mode: '{{ old('collection_mode', 'clinic') }}',
                clinics: {{ \Illuminate\Support\Js::from($clinics->mapWithKeys(fn ($clinic) => [$clinic->id => [
                    'name' => $clinic->name,
                    'addresses' => $clinic->addresses->map(fn ($address) => [
                        'id' => $address->id,
                        'name' => $address->displayName(),
                    ])->values(),
                ]])) }}
            }">
                @csrf
                <x-card class="space-y-4">
                    <x-field :label="__('labs.checkout.clinic')" name="clinic_id" required>
                        <select name="clinic_id" x-model="clinicId" class="field-input">
                            @foreach ($clinics as $clinic)
                                <option value="{{ $clinic->id }}">{{ $clinic->name }}</option>
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
                            </span>
                        </label>
                        <label class="flex items-start gap-3 rounded-lg border border-ink-200 p-3 has-[:checked]:border-primary-400">
                            <input type="radio" name="collection_mode" value="home" class="mt-1" x-model="mode">
                            <span>
                                <span class="block text-sm font-medium">{{ __('labs.collection.home') }}</span>
                                <span class="block text-xs text-ink-500">{{ __('labs.checkout.collection_home_hint') }}</span>
                            </span>
                        </label>
                    </fieldset>

                    <div x-show="mode === 'clinic'">
                        <x-field :label="__('booking.branch')" name="clinic_address_id">
                            <select name="clinic_address_id" class="field-input">
                                <template x-for="address in (clinics[clinicId]?.addresses || [])" :key="address.id">
                                    <option :value="address.id" x-text="address.name"></option>
                                </template>
                            </select>
                        </x-field>
                    </div>

                    <div x-show="mode === 'home'">
                        <x-field :label="__('labs.checkout.home_address')" name="patient_home_address">
                            <x-input name="patient_home_address" :value="old('patient_home_address')"/>
                        </x-field>
                    </div>

                    <x-field :label="__('booking.when')" name="scheduled_at" required>
                        <x-input
                            name="scheduled_at"
                            type="datetime-local"
                            :value="old('scheduled_at')"
                            :min="now()->addHour()->format('Y-m-d\TH:i')"
                        />
                    </x-field>

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
