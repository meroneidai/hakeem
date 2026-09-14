@props(['name' => null, 'options' => [], 'selected' => null, 'placeholder' => null])

<select
    @if ($name) name="{{ $name }}" id="{{ $name }}" @endif
    {{ $attributes->merge([
        'class' => 'field-input focus:border-primary-400 focus:ring-2 focus:ring-primary-100 '
            . ($name && $errors->has($name) ? 'border-danger-500' : ''),
    ]) }}
>
    @if ($placeholder !== null)
        <option value="">{{ $placeholder }}</option>
    @endif

    @if (count($options))
        @php $current = old($name, $selected); @endphp
        @foreach ($options as $optionValue => $optionLabel)
            <option value="{{ $optionValue }}" @selected((string) $current === (string) $optionValue)>
                {{ $optionLabel }}
            </option>
        @endforeach
    @else
        {{ $slot }}
    @endif
</select>
