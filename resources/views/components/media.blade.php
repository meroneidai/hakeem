@props(['src' => null, 'alt' => '', 'placeholder' => null])

@if ($src)
    <img src="{{ $src }}" alt="{{ $alt }}" {{ $attributes->merge(['class' => 'object-cover']) }}>
@else
    <span {{ $attributes->merge(['class' => 'grid place-items-center bg-primary-50 font-semibold text-primary-700']) }}>
        {{ $placeholder ?? mb_substr($alt, 0, 1) }}
    </span>
@endif
