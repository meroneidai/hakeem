@props(['label', 'value', 'href' => null, 'tone' => 'primary'])

@php
    $tones = [
        'primary' => 'text-primary-600',
        'accent' => 'text-accent-600',
        'success' => 'text-success-500',
        'warning' => 'text-warning-500',
        'ink' => 'text-ink-700',
    ];
    $tag = $href ? 'a' : 'div';
@endphp

<{{ $tag }}
    @if ($href) href="{{ $href }}" @endif
    class="card flex flex-col gap-1 p-4 {{ $href ? 'transition hover:border-primary-300 hover:shadow-md' : '' }}"
>
    <span class="text-2xl font-semibold tabular {{ $tones[$tone] ?? $tones['primary'] }}">{{ $value }}</span>
    <span class="text-xs text-ink-500">{{ $label }}</span>
</{{ $tag }}>
