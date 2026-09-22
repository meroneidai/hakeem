@props(['value'])

<div {{ $attributes->merge(['class' => 'flex gap-2']) }} x-data="{ copied: false }">
    <input x-ref="copyField" type="text" readonly dir="ltr" value="{{ $value }}"
           class="field-input min-w-0 flex-1 bg-ink-50 text-sm">
    <x-button type="button" variant="secondary" size="sm"
              @click="navigator.clipboard.writeText($refs.copyField.value); copied = true; setTimeout(() => copied = false, 1600)">
        <span x-show="!copied">{{ __('common.copy') }}</span>
        <span x-cloak x-show="copied">{{ __('common.copied') }}</span>
    </x-button>
</div>
