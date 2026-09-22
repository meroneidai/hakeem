@props(['title', 'subtitle' => null])

<section {{ $attributes->merge(['class' => 'relative overflow-hidden bg-gradient-to-l from-primary-100 via-primary-50 to-white px-4 py-8 sm:py-10']) }}>
    <div class="pointer-events-none absolute -start-16 top-6 size-56 rounded-full bg-primary-200/50 blur-3xl"></div>
    <div class="pointer-events-none absolute -end-10 bottom-0 size-40 rounded-full bg-accent-200/40 blur-3xl"></div>
    <div class="relative mx-auto max-w-6xl">
        @isset($crumbs)
            <nav class="mb-3 flex flex-wrap items-center gap-1.5 text-sm text-ink-500">{{ $crumbs }}</nav>
        @endisset

        <div class="flex flex-wrap items-end justify-between gap-4">
            <div class="min-w-0">
                <h1 class="text-2xl font-bold tracking-tight text-ink-900 sm:text-3xl">{{ $title }}</h1>
                @if ($subtitle)
                    <p class="mt-2 max-w-2xl text-sm text-ink-600">{{ $subtitle }}</p>
                @endif
            </div>
            @isset($actions)
                <div class="flex flex-wrap items-center gap-2">{{ $actions }}</div>
            @endisset
        </div>

        {{ $slot }}
    </div>
</section>
