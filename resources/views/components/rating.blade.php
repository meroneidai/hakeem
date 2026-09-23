@props([
    'average' => null,
    'count' => 0,
    'size' => 'sm',
])

@php
    $score = $average !== null ? (float) $average : 0;
    $filled = $count > 0 ? (int) round($score) : 0;
    $starClass = $size === 'lg' ? 'size-4' : 'size-3.5';
@endphp

<span {{ $attributes->merge(['class' => 'inline-flex items-center gap-1 text-ink-600']) }}>
    <span class="inline-flex items-center gap-px text-accent-500" aria-hidden="true">
        @for ($i = 1; $i <= 5; $i++)
            <x-icon name="star" @class([$starClass, 'text-ink-200' => $i > $filled]) />
        @endfor
    </span>
    @if ($count > 0)
        <span class="text-xs font-semibold tabular text-ink-800">{{ number_format($score, 1) }}</span>
        <span class="text-xs text-ink-400">({{ $count }})</span>
    @else
        <span class="text-xs text-ink-400">{{ __('reviews.empty') }}</span>
    @endif
</span>
