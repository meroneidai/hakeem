@props(['title', 'subtitle' => null])

<div {{ $attributes->merge(['class' => 'mb-6 flex flex-wrap items-end justify-between gap-4']) }}>
    <div>
        <h1 class="text-xl font-semibold text-ink-900 sm:text-2xl">{{ $title }}</h1>
        @if ($subtitle)
            <p class="mt-1 max-w-2xl text-sm text-ink-500">{{ $subtitle }}</p>
        @endif
    </div>

    @isset($actions)
        <div class="flex flex-wrap items-center gap-2">{{ $actions }}</div>
    @endisset
</div>
