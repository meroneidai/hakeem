@props([
    'average' => null,
    'count' => 0,
    'size' => 'sm',
])

@php
    $score = $average !== null ? max(0, min(5, (float) $average)) : 0.0;
    $hasReviews = (int) $count > 0;
    $display = $hasReviews ? round($score * 2) / 2 : 0.0;
    $starClass = $size === 'lg' ? 'size-4' : 'size-3.5';
    $starPath = 'M10.788 3.21c.448-1.077 1.976-1.077 2.424 0l2.082 5.007 5.404.433c1.164.093 1.636 1.545.749 2.305l-4.117 3.527 1.257 5.273c.271 1.136-.964 2.033-1.96 1.425L12 18.354 7.373 21.18c-.996.608-2.231-.29-1.96-1.425l1.257-5.273-4.117-3.527c-.887-.76-.415-2.212.749-2.305l5.404-.433 2.082-5.006z';
@endphp

<span {{ $attributes->merge(['class' => 'inline-flex items-center gap-1.5 text-ink-600']) }}
      @if ($hasReviews) title="{{ number_format($score, 1) }} / 5" @endif>
    <span class="inline-flex items-center gap-0.5" aria-hidden="true">
        @for ($i = 1; $i <= 5; $i++)
            @php
                $fill = $hasReviews ? max(0, min(1, $display - ($i - 1))) : 0;
            @endphp
            <span class="relative inline-flex shrink-0 {{ $starClass }}">
                {{-- Empty / track --}}
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" class="{{ $starClass }} fill-star-muted" aria-hidden="true">
                    <path fill-rule="evenodd" d="{{ $starPath }}" clip-rule="evenodd"/>
                </svg>
                @if ($fill >= 0.75)
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" class="absolute inset-0 {{ $starClass }} fill-star-500 drop-shadow-[0_1px_0_rgba(245,158,11,0.35)]" aria-hidden="true">
                        <path fill-rule="evenodd" d="{{ $starPath }}" clip-rule="evenodd"/>
                    </svg>
                @elseif ($fill >= 0.25)
                    <span class="absolute inset-0 w-1/2 overflow-hidden" dir="ltr">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" class="{{ $starClass }} fill-star-400 drop-shadow-[0_1px_0_rgba(251,191,36,0.35)]" aria-hidden="true">
                            <path fill-rule="evenodd" d="{{ $starPath }}" clip-rule="evenodd"/>
                        </svg>
                    </span>
                @endif
            </span>
        @endfor
    </span>
    @if ($hasReviews)
        <span class="text-xs font-bold tabular text-ink-800">{{ number_format($score, 1) }}</span>
        <span class="text-xs font-medium text-ink-400">({{ $count }})</span>
    @else
        <span class="text-xs text-ink-400">{{ __('reviews.empty') }}</span>
    @endif
</span>
