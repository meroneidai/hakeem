@props(['name' => null, 'value' => null, 'rows' => 3])

<textarea
    @if ($name) name="{{ $name }}" id="{{ $name }}" @endif
    rows="{{ $rows }}"
    {{ $attributes->merge([
        'class' => 'field-input focus:border-primary-400 focus:ring-2 focus:ring-primary-100 '
            . ($name && $errors->has($name) ? 'border-danger-500' : ''),
    ]) }}
>{{ old($name, $value) }}</textarea>
