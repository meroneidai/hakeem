@props(['label' => null, 'name' => null, 'hint' => null, 'required' => false])

<div {{ $attributes->merge(['class' => 'space-y-1.5']) }}>
    @if ($label)
        <label @if ($name) for="{{ $name }}" @endif class="block text-sm font-medium text-ink-700">
            {{ $label }}
            @if ($required)
                <span class="text-danger-500">*</span>
            @else
                <span class="text-xs font-normal text-ink-400">({{ __('common.optional') }})</span>
            @endif
        </label>
    @endif

    {{ $slot }}

    @if ($hint)
        <p class="text-xs text-ink-500">{{ $hint }}</p>
    @endif

    @if ($name)
        @error($name)
            <p class="text-xs font-medium text-danger-500">{{ $message }}</p>
        @enderror
    @endif
</div>
