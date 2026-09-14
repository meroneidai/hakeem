@props(['tone' => 'neutral'])

@php
    $tones = [
        'neutral' => 'bg-ink-100 text-ink-600',
        'primary' => 'bg-primary-50 text-primary-700',
        'accent' => 'bg-accent-50 text-accent-700',
        'success' => 'bg-success-50 text-success-700',
        'warning' => 'bg-warning-50 text-warning-700',
        'danger' => 'bg-danger-50 text-danger-700',
    ];
@endphp

<span {{ $attributes->merge([
    'class' => 'inline-flex items-center gap-1 rounded-full px-2.5 py-0.5 text-xs font-medium '
        . ($tones[$tone] ?? $tones['neutral']),
]) }}>{{ $slot }}</span>
