@props(['variant' => 'primary', 'href' => null, 'size' => 'md'])

@php
    $base = 'inline-flex items-center justify-center gap-2 rounded-lg font-medium transition focus:outline-none focus-visible:ring-2 focus-visible:ring-primary-300 disabled:opacity-50';

    $sizes = [
        'sm' => 'px-2.5 py-1.5 text-xs',
        'md' => 'px-4 py-2 text-sm',
        'lg' => 'px-5 py-2.5 text-base',
    ];

    // Warm accent is reserved for primary calls to action; red only for destructive.
    $variants = [
        'primary' => 'bg-primary-600 text-white hover:bg-primary-700',
        'accent' => 'bg-accent-500 text-white hover:bg-accent-600 shadow-sm',
        'secondary' => 'border border-ink-300 bg-white text-ink-700 hover:bg-ink-50',
        'ghost' => 'text-ink-600 hover:bg-ink-100',
        'danger' => 'bg-danger-500 text-white hover:bg-danger-700',
        'danger-ghost' => 'text-danger-500 hover:bg-danger-50',
    ];

    $classes = implode(' ', [$base, $sizes[$size] ?? $sizes['md'], $variants[$variant] ?? $variants['primary']]);
@endphp

@if ($href)
    <a href="{{ $href }}" {{ $attributes->merge(['class' => $classes]) }}>{{ $slot }}</a>
@else
    <button {{ $attributes->merge(['class' => $classes, 'type' => 'submit']) }}>{{ $slot }}</button>
@endif
