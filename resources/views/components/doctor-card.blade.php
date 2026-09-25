@props(['doctor', 'serviceType' => null])

@php
    $bookUrl = $serviceType
        ? route('book.doctors.create', ['doctor' => $doctor, 'service_type_id' => $serviceType->id])
        : route('book.doctors.create', $doctor);
    $clinics = $doctor->clinics;
    $visits = $doctor->getAttribute('completed_bookings_count');
@endphp

<article {{ $attributes->merge(['class' => '@container/card card flex h-full flex-col gap-3 p-3 text-start sm:gap-4 sm:p-5']) }}>
    <div class="flex min-w-0 items-start gap-2.5 sm:gap-4">
        <x-media
            :src="\App\Support\PublicImage::url($doctor->profile_photo_path)"
            :alt="$doctor->name"
            class="size-12 shrink-0 rounded-2xl ring-1 ring-ink-100 sm:size-20"
        />
        <div class="min-w-0 flex-1 text-start">
            <a href="{{ route('doctors.show', $doctor) }}" class="line-clamp-2 text-sm font-semibold text-ink-900 hover:text-primary-700 sm:text-base">{{ $doctor->name }}</a>
            <p class="mt-0.5 truncate text-xs text-ink-500 sm:text-sm">{{ $doctor->specialty?->name }}</p>
            <x-rating class="mt-1" :average="$doctor->ratingAverage()" :count="$doctor->ratingCount()"/>
            <p class="mt-1.5 text-sm font-semibold tabular text-primary-700 sm:hidden">
                @if ($doctor->consultation_fee)
                    {{ number_format((float) $doctor->consultation_fee) }}
                    <span class="text-xs font-medium text-ink-500">{{ __('common.currency') }}</span>
                @else
                    <span class="text-xs font-medium text-ink-400">{{ __('common.none') }}</span>
                @endif
            </p>
            <div class="mt-2 hidden flex-wrap justify-start gap-1.5 text-xs text-ink-600 @[20rem]/card:flex sm:flex">
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
                <p class="mt-2 hidden items-center justify-start gap-1 text-xs text-ink-500 @[20rem]/card:flex sm:flex">
                    <x-icon name="building" class="size-3.5 shrink-0"/>
                    <span class="line-clamp-1">{{ $clinics->pluck('name')->filter()->join(' · ') }}</span>
                </p>
            @endif
            @if ($doctor->credentials)
                <p class="mt-1 hidden line-clamp-1 text-xs text-ink-400 @[20rem]/card:block sm:block">{{ $doctor->credentials }}</p>
            @endif
        </div>
        <div class="hidden shrink-0 text-end sm:block">
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
    <div class="mt-auto flex flex-col gap-1.5 @[17rem]/card:flex-row sm:flex-row sm:gap-2">
        <x-button :href="route('doctors.show', $doctor)" variant="secondary" size="sm" class="w-full flex-1 px-2 text-xs sm:px-3 sm:text-sm">
            <x-icon name="user" class="size-4 shrink-0"/>
            <span class="truncate">{{ __('discover.view_profile') }}</span>
        </x-button>
        <x-button :href="$bookUrl" variant="accent" size="sm" class="w-full flex-1 px-2 text-xs sm:px-3 sm:text-sm">
            <x-icon name="calendar" class="size-4 shrink-0"/>
            <span class="truncate">{{ __('discover.book_now') }}</span>
        </x-button>
    </div>
</article>
