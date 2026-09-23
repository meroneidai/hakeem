@props(['clinic', 'serviceType'])

@php
    $offering = $clinic->services->first();
    $doctors = $clinic->doctors;
@endphp

<article {{ $attributes->merge(['class' => 'card flex flex-col overflow-hidden p-0']) }}>
    <div class="flex items-start gap-3 p-4 sm:p-5">
        <x-media
            :src="\App\Support\PublicImage::url($clinic->logo_path)"
            :alt="$clinic->name"
            class="size-16 shrink-0 rounded-2xl ring-1 ring-ink-100"
        />
        <div class="min-w-0 flex-1">
            <a href="{{ route('clinics.show', $clinic) }}" class="text-base font-semibold text-ink-900 hover:text-primary-700">{{ $clinic->name }}</a>
            <p class="mt-0.5 text-sm text-ink-500">{{ $clinic->primaryAddress?->city?->name }}</p>
            <div class="mt-2 flex flex-wrap items-center gap-2">
                <x-rating :average="$clinic->ratingAverage()" :count="$clinic->ratingCount()"/>
                @if ($offering && $offering->effectivePrice() > 0)
                    <span class="rounded-full bg-primary-50 px-2 py-0.5 text-xs font-semibold text-primary-800">
                        {{ __('discover.from_price', ['price' => number_format($offering->effectivePrice())]) }}
                    </span>
                @endif
            </div>
        </div>
        <a
            href="{{ route('clinics.show', $clinic) }}"
            class="hidden shrink-0 items-center rounded-full bg-ink-50 px-3 py-1.5 text-xs font-medium text-ink-700 ring-1 ring-ink-200 hover:bg-white hover:text-primary-700 sm:inline-flex"
        >
            {{ __('discover.services_page.browse_clinic') }}
        </a>
    </div>

    @if ($doctors->isNotEmpty())
        <div class="border-t border-ink-100 bg-ink-50/70 px-4 py-3 sm:px-5">
            <p class="mb-2 text-xs font-semibold tracking-wide text-ink-500">{{ __('discover.clinics.doctors') }}</p>
            <ul class="space-y-2">
                @foreach ($doctors as $doctor)
                    <li class="flex items-center gap-3 rounded-2xl bg-white px-3 py-2.5 ring-1 ring-ink-100">
                        <x-media
                            :src="\App\Support\PublicImage::url($doctor->profile_photo_path)"
                            :alt="$doctor->name"
                            class="size-11 shrink-0 rounded-full"
                        />
                        <div class="min-w-0 flex-1">
                            <a href="{{ route('doctors.show', $doctor) }}" class="block truncate text-sm font-semibold text-ink-900 hover:text-primary-700">{{ $doctor->name }}</a>
                            <p class="truncate text-xs text-ink-500">{{ $doctor->specialty?->name }}</p>
                        </div>
                        <div class="flex shrink-0 items-center gap-1.5">
                            <a href="{{ route('doctors.show', $doctor) }}"
                               class="inline-flex h-9 items-center gap-1 rounded-full bg-ink-50 px-2.5 text-xs font-medium text-ink-700 ring-1 ring-ink-200 hover:bg-white hover:text-primary-700"
                               aria-label="{{ __('discover.view_profile') }}">
                                <x-icon name="user" class="size-3.5"/>
                                <span class="hidden sm:inline">{{ __('discover.view_profile') }}</span>
                            </a>
                            <a href="{{ route('book.doctors.create', ['doctor' => $doctor, 'service_type_id' => $serviceType->id]) }}"
                               class="inline-flex h-9 items-center gap-1 rounded-full bg-accent-500 px-2.5 text-xs font-medium text-white hover:bg-accent-600"
                               aria-label="{{ __('discover.book_now') }}">
                                <x-icon name="calendar" class="size-3.5"/>
                                <span class="hidden sm:inline">{{ __('discover.book_now') }}</span>
                            </a>
                        </div>
                    </li>
                @endforeach
            </ul>
            <a href="{{ route('clinics.show', $clinic) }}" class="mt-3 inline-flex sm:hidden text-sm font-medium text-primary-700">
                {{ __('discover.services_page.browse_clinic') }}
            </a>
        </div>
    @endif
</article>
