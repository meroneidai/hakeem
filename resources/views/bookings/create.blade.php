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
            <form method="POST" action="{{ route('book.doctors.store', $doctor) }}" class="space-y-4">
                @csrf

                <x-field :label="__('booking.service')" name="service_type_id" required>
                    <x-select
                        name="service_type_id"
                        :placeholder="__('booking.service')"
                        :options="$serviceTypes->pluck('name', 'id')->all()"
                        :selected="$selectedServiceTypeId ?? null"
                    />
                </x-field>

                <x-field :label="__('booking.branch')" name="clinic_address_id" required>
                    <x-select name="clinic_address_id" :placeholder="__('booking.branch')">
                        @foreach ($addresses as $address)
                            <option value="{{ $address->id }}" @selected((string) old('clinic_address_id') === (string) $address->id)>
                                {{ $address->clinic?->name }} — {{ $address->displayName() }}
                            </option>
                        @endforeach
                    </x-select>
                </x-field>

                <x-field :label="__('booking.when')" name="scheduled_at" required>
                    <x-input
                        name="scheduled_at"
                        type="datetime-local"
                        :value="old('scheduled_at')"
                        :min="now()->addHour()->format('Y-m-d\TH:i')"
                    />
                </x-field>

                @if (($maxSessions ?? 1) > 1 || ($requiresEvaluation ?? false))
                    <x-field :label="__('booking.sessions')" name="session_count" :hint="__('booking.sessions_hint')">
                        <x-input type="number" name="session_count" min="1" max="30" :value="old('session_count', 1)" dir="ltr"/>
                    </x-field>
                @endif

                @if ($requiresEvaluation ?? false)
                    <p class="text-xs text-ink-500">{{ __('booking.evaluation_gate_hint') }}</p>
                @endif

                @include('bookings._payment_modes')

                <x-field :label="__('booking.notes')" name="notes">
                    <x-textarea name="notes" rows="4"/>
                </x-field>

                <x-button variant="accent" class="w-full">{{ __('booking.submit') }}</x-button>
            </form>
        </x-card>
    </div>
</x-layouts.public>
