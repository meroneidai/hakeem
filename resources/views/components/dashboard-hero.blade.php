@props(['eyebrow' => null, 'title', 'subtitle' => null, 'tone' => 'admin'])

@php
    $gradient = $tone === 'clinic'
        ? 'from-accent-500 via-primary-600 to-success-700'
        : 'from-primary-800 via-primary-600 to-accent-500';
@endphp

<section {{ $attributes->merge(['class' => "relative overflow-hidden rounded-2xl bg-gradient-to-br {$gradient} p-6 text-white shadow-card sm:p-8"]) }}>
    <div class="pointer-events-none absolute -top-20 -end-12 size-52 rounded-full bg-white/20 blur-2xl"></div>
    <div class="pointer-events-none absolute -bottom-24 -start-10 size-64 rounded-full bg-accent-400/40 blur-3xl"></div>
    <div class="pointer-events-none absolute top-10 start-1/3 size-24 rounded-full bg-success-500/20 blur-xl"></div>

    <div class="relative">
        @if ($eyebrow)
            <p class="text-xs font-medium uppercase tracking-wider text-white/80">{{ $eyebrow }}</p>
        @endif
        <h1 class="mt-1 text-2xl font-bold sm:text-3xl">{{ $title }}</h1>
        @if ($subtitle)
            <p class="mt-2 max-w-2xl text-sm text-white/85">{{ $subtitle }}</p>
        @endif

        @isset($stats)
            <div class="mt-6 grid gap-3 sm:grid-cols-3">{{ $stats }}</div>
        @endisset

        @isset($actions)
            <div class="mt-5 flex flex-wrap gap-2">{{ $actions }}</div>
        @endisset
    </div>
</section>
