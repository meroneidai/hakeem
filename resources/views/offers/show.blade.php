<x-layouts.public :title="$offer->title">
    <x-catalog-hero :title="$offer->title" :subtitle="$offer->category?->label()">
        <x-slot:crumbs>
            <a href="{{ route('home') }}" class="hover:text-primary-700">{{ __('discover.nav.home') }}</a>
            <span aria-hidden="true">·</span>
            <a href="{{ route('offers.index') }}" class="hover:text-primary-700">{{ __('discover.nav.offers') }}</a>
        </x-slot:crumbs>
        <x-slot:actions>
            @if ($offer->discountPercent())
                <x-badge tone="accent">{{ $offer->discountPercent() }}%</x-badge>
            @endif
        </x-slot:actions>
    </x-catalog-hero>

    <div class="mx-auto max-w-3xl px-4 py-8">

        <x-card class="space-y-4">
            @if ($offer->banner_image_path)
                <x-media
                    :src="\App\Support\PublicImage::url($offer->banner_image_path)"
                    :alt="$offer->title"
                    class="h-56 w-full rounded-xl"
                />
            @endif
            <p class="text-sm text-ink-600">{{ $offer->description }}</p>

            @if ($offer->includes)
                <section>
                    <h2 class="text-sm font-semibold text-ink-800">{{ __('offers.includes') }}</h2>
                    <p class="mt-1 text-sm text-ink-600">{{ $offer->includes }}</p>
                </section>
            @endif

            @if ($offer->conditions)
                <section>
                    <h2 class="text-sm font-semibold text-ink-800">{{ __('offers.conditions') }}</h2>
                    <p class="mt-1 text-sm text-ink-600">{{ $offer->conditions }}</p>
                </section>
            @endif

            @if ($offer->offer_price || $offer->original_price)
                <p class="text-sm">
                    @if ($offer->original_price)
                        <span class="text-ink-400">{{ __('offers.was') }}</span>
                        <span class="text-ink-400 line-through">{{ number_format((float) $offer->original_price) }}</span>
                    @endif
                    @if ($offer->offer_price)
                        <span class="ms-2 font-semibold text-ink-900">{{ __('offers.now') }} {{ number_format((float) $offer->offer_price) }} {{ __('common.currency') }}</span>
                    @endif
                    @if ($offer->savings() > 0)
                        <span class="ms-2 text-xs text-accent-600">{{ __('offers.save', ['amount' => number_format($offer->savings())]) }}</span>
                    @endif
                </p>
            @endif

            <p class="text-xs text-ink-400">{{ __('offers.until', ['date' => $offer->ends_at->translatedFormat('d M Y')]) }}</p>

            @if ($offer->clinic)
                <section>
                    <h2 class="text-sm font-semibold text-ink-800">{{ __('offers.clinic') }}</h2>
                    <p class="mt-1 text-sm text-ink-700">{{ $offer->clinic->name }}</p>
                    @foreach ($offer->clinic->addresses as $address)
                        <p class="mt-1 text-sm text-ink-500">
                            {{ $address->displayName() }}
                            @if ($address->address_line)
                                — {{ $address->address_line }}
                            @endif
                            @if ($address->hasMapPin())
                                <a href="{{ $address->mapsUrl() }}" target="_blank" rel="noopener" class="ms-1 font-medium text-primary-600">
                                    {{ __('clinic.addresses.map') }}
                                </a>
                            @endif
                        </p>
                    @endforeach
                </section>
            @endif
        </x-card>

        @if ($offer->clinic && $offer->clinic->doctors->isNotEmpty() && $offer->clinic->addresses->isNotEmpty())
            <x-card class="mt-4" :title="__('offers.book')">
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
                            <p class="text-sm font-medium text-primary-700">{{ __('booking.duration_minutes', ['minutes' => $durationMinutes]) }}</p>
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
                        <x-button variant="accent" class="w-full">{{ __('offers.book') }}</x-button>
                    </form>
                @else
                    <p class="text-sm text-ink-500">{{ __('booking.login_first') }}</p>
                    <div class="mt-3">
                        <x-button :href="route('login')" variant="accent">{{ __('auth.login') }}</x-button>
                    </div>
                @endauth
            </x-card>
        @endif
    </div>
</x-layouts.public>
