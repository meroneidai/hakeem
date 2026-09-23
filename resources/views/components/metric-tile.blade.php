@props(['label', 'value', 'href' => null, 'tone' => 'primary', 'icon' => 'chart', 'hint' => null])

@php
    $palettes = [
        'primary' => [
            'card' => 'border-primary-100 bg-gradient-to-br from-primary-50 to-white',
            'icon' => 'bg-primary-600 text-white',
            'value' => 'text-primary-800',
        ],
        'accent' => [
            'card' => 'border-accent-100 bg-gradient-to-br from-accent-50 to-white',
            'icon' => 'bg-accent-500 text-white',
            'value' => 'text-accent-800',
        ],
        'success' => [
            'card' => 'border-success-50 bg-gradient-to-br from-success-50 to-white',
            'icon' => 'bg-success-500 text-white',
            'value' => 'text-success-700',
        ],
        'warning' => [
            'card' => 'border-warning-50 bg-gradient-to-br from-warning-50 to-white',
            'icon' => 'bg-warning-500 text-white',
            'value' => 'text-warning-700',
        ],
        'danger' => [
            'card' => 'border-danger-50 bg-gradient-to-br from-danger-50 to-white',
            'icon' => 'bg-danger-500 text-white',
            'value' => 'text-danger-700',
        ],
        'ink' => [
            'card' => 'border-ink-200 bg-gradient-to-br from-ink-50 to-white',
            'icon' => 'bg-ink-700 text-white',
            'value' => 'text-ink-800',
        ],
    ];
    $palette = $palettes[$tone] ?? $palettes['primary'];
    $tag = $href ? 'a' : 'div';
@endphp

<{{ $tag }}
    @if ($href) href="{{ $href }}" @endif
    {{ $attributes->merge([
        'class' => 'card flex items-start gap-3 p-4 '.$palette['card'].($href ? ' transition hover:-translate-y-0.5 hover:shadow-md' : ''),
    ]) }}
>
    <span class="grid size-11 shrink-0 place-items-center rounded-xl {{ $palette['icon'] }}">
        <x-icon :name="$icon" class="size-5"/>
    </span>
    <span class="min-w-0">
        <span class="block text-2xl font-semibold tabular {{ $palette['value'] }}">{{ $value }}</span>
        <span class="mt-0.5 block text-xs font-medium text-ink-600">{{ $label }}</span>
        @if ($hint)
            <span class="mt-0.5 block text-[11px] text-ink-400">{{ $hint }}</span>
        @endif
    </span>
</{{ $tag }}>
