@props(['doctor', 'serviceType' => null])

@php
    $bookUrl = $serviceType
        ? route('book.doctors.create', ['doctor' => $doctor, 'service_type_id' => $serviceType->id])
        : route('book.doctors.create', $doctor);
    $clinics = $doctor->clinics;
    $visits = $doctor->getAttribute('completed_bookings_count');
@endphp

<article {{ $attributes->merge(['class' => 'card flex flex-col gap-4 p-4 sm:p-5']) }}>
    <div class="flex items-start gap-3 sm:gap-4">
        <x-media
            :src="\App\Support\PublicImage::url($doctor->profile_photo_path)"
            :alt="$doctor->name"
            class="size-16 shrink-0 rounded-2xl ring-1 ring-ink-100 sm:size-20"
        />
        <div class="min-w-0 flex-1">
            <a href="{{ route('doctors.show', $doctor) }}" class="text-base font-semibold text-ink-900 hover:text-primary-700">{{ $doctor->name }}</a>
            <p class="mt-0.5 text-sm text-ink-500">{{ $doctor->specialty?->name }}</p>
            <x-rating class="mt-1.5" :average="$doctor->ratingAverage()" :count="$doctor->ratingCount()"/>
            <div class="mt-2 flex flex-wrap gap-1.5 text-xs text-ink-600">
                @if ($doctor->years_of_experience)
                    <span class="inline-flex items-center gap-1 rounded-full bg-ink-50 px-2 py-0.5">
                        <x-icon name="clock" class="size-3"/>
                        {{ __('discover.doctors.years', ['count' => $doctor->years_of_experience]) }}
                    </span>
                @endif
                @if ($doctor->gender)
                    <span class="rounded-full bg-ink-50 px-2 py-0.5">{{ __('discover.doctors.'.$doctor->gender) }}</span>
                @endif
                @if ($visits !== null)
                    <span class="inline-flex items-center gap-1 rounded-full bg-ink-50 px-2 py-0.5">
                        <x-icon name="calendar" class="size-3"/>
                        {{ __('discover.doctors.visits', ['count' => $visits]) }}
                    </span>
                @endif
                @php
                    $cities = $clinics->flatMap->addresses->pluck('city.name')->unique()->filter()->take(2);
                @endphp
                @if ($cities->isNotEmpty())
                    <span class="inline-flex items-center gap-1 rounded-full bg-ink-50 px-2 py-0.5">
                        <x-icon name="map-pin" class="size-3"/>
                        {{ $cities->join(' · ') }}
                    </span>
                @endif
            </div>
            @if ($clinics->isNotEmpty())
                <p class="mt-2 flex items-center gap-1 text-xs text-ink-500">
                    <x-icon name="building" class="size-3.5 shrink-0"/>
                    <span class="line-clamp-1">{{ $clinics->pluck('name')->filter()->join(' · ') }}</span>
                </p>
            @endif
            @if ($doctor->credentials)
                <p class="mt-1 line-clamp-1 text-xs text-ink-400">{{ $doctor->credentials }}</p>
            @endif
        </div>
        <div class="shrink-0 text-end">
            <p class="text-[11px] text-ink-400">{{ __('discover.doctors.fee') }}</p>
            <p class="text-sm font-semibold tabular text-primary-700 sm:text-base">
                @if ($doctor->consultation_fee)
                    {{ number_format((float) $doctor->consultation_fee) }}
                    <span class="text-xs font-medium text-ink-500">{{ __('common.currency') }}</span>
                @else
                    {{ __('common.none') }}
                @endif
            </p>
        </div>
    </div>
    <div class="mt-auto flex gap-2">
        <x-button :href="route('doctors.show', $doctor)" variant="secondary" size="sm" class="flex-1">
            <x-icon name="user" class="size-4"/>
            {{ __('discover.view_profile') }}
        </x-button>
        <x-button :href="$bookUrl" variant="accent" size="sm" class="flex-1">
            <x-icon name="calendar" class="size-4"/>
            {{ __('discover.book_now') }}
        </x-button>
    </div>
</article>
