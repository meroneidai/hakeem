@props(['name' => null, 'type' => 'text', 'value' => null])

<input
    type="{{ $type }}"
    @if ($name) name="{{ $name }}" id="{{ $name }}" @endif
    value="{{ old($name, $value) }}"
    {{ $attributes->merge([
        'class' => 'field-input focus:border-primary-400 focus:ring-2 focus:ring-primary-100 '
            . ($name && $errors->has($name) ? 'border-danger-500' : ''),
    ]) }}
>
