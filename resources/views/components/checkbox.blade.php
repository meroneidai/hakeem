@props(['name', 'label' => null, 'checked' => false, 'value' => 1, 'hint' => null, 'withHidden' => true])

@php
    // Array-style names (features[...], allowed_modes[]) can't be resolved through
    // old() reliably, so those rely on the caller-provided checked state.
    $isArrayName = str_contains($name, '[');
    $id = $isArrayName ? 'cb_'.md5($name.$value) : $name;
    $isChecked = $isArrayName ? (bool) $checked : (bool) old($name, $checked);
@endphp

<label for="{{ $id }}" class="flex items-start gap-2.5 text-sm text-ink-700">
    @if ($withHidden && ! $isArrayName)
        <input type="hidden" name="{{ $name }}" value="0">
    @endif

    <input
        type="checkbox"
        name="{{ $name }}"
        id="{{ $id }}"
        value="{{ $value }}"
        @checked($isChecked)
        {{ $attributes->merge(['class' => 'mt-0.5 size-4 rounded border-ink-300 text-primary-600 focus:ring-primary-300']) }}
    >

    <span>
        <span class="font-medium">{{ $label }}</span>
        @if ($hint)
            <span class="mt-0.5 block text-xs font-normal text-ink-500">{{ $hint }}</span>
        @endif
    </span>
</label>
