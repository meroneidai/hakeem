@props(['tone' => 'success'])

@php
    $tones = [
        'success' => 'border-success-500/30 bg-success-50 text-success-700',
        'warning' => 'border-warning-500/30 bg-warning-50 text-warning-700',
        'danger' => 'border-danger-500/30 bg-danger-50 text-danger-700',
        'info' => 'border-primary-300 bg-primary-50 text-primary-700',
    ];
@endphp

<div {{ $attributes->merge([
    'class' => 'rounded-lg border px-4 py-3 text-sm ' . ($tones[$tone] ?? $tones['success']),
]) }}>
    {{ $slot }}
</div>
