@props(['name' => 'identifier', 'value' => '', 'autofocus' => false])

@php
    $current = (string) old($name, $value);
@endphp

<div x-data="{
        value: @js($current),
        first() { return (this.value || '').trim().charAt(0); },
        isPhone() {
            const c = this.first();
            return c === '+' || (c >= '0' && c <= '9') || '٠١٢٣٤٥٦٧٨٩۰۱۲۳۴۵۶۷۸۹'.includes(c);
        },
        isEmail() {
            const c = this.first();
            return c !== '' && ! this.isPhone();
        }
    }" class="space-y-1.5">
    <label for="{{ $name }}" class="block text-sm font-medium text-ink-700">
        <span x-show="! isEmail() && ! isPhone()">{{ __('auth.email_or_phone') }}</span>
        <span x-cloak x-show="isPhone()">{{ __('auth.phone') }}</span>
        <span x-cloak x-show="isEmail()">{{ __('auth.email') }}</span>
        <span class="text-danger-500">*</span>
    </label>

    <div @class([
        'flex overflow-hidden rounded-lg border bg-white focus-within:border-primary-400 focus-within:ring-2 focus-within:ring-primary-100',
        'border-danger-500' => $errors->has($name),
        'border-ink-200' => ! $errors->has($name),
    ])>
        <span x-cloak x-show="isPhone()" class="inline-flex items-center gap-1.5 border-e border-ink-200 bg-ink-50 px-3 text-sm text-ink-700">
            <span aria-hidden="true">🇪🇬</span>
            <span dir="ltr">+20</span>
        </span>
        <input name="{{ $name }}" id="{{ $name }}" x-model="value"
               :type="isEmail() ? 'email' : 'tel'"
               :inputmode="isEmail() ? 'email' : 'tel'"
               autocomplete="username" dir="ltr"
               @if ($autofocus) autofocus @endif
               :placeholder="isEmail() ? @js(__('auth.email_placeholder')) : @js(__('auth.phone_placeholder'))"
               class="min-w-0 flex-1 border-0 bg-transparent px-3 py-2 text-sm text-ink-900 outline-none">
    </div>

    <p class="text-xs text-ink-500" x-show="! isEmail()">{{ __('auth.identifier_hint') }}</p>
    <p class="text-xs text-ink-500" x-cloak x-show="isEmail()">{{ __('auth.email_hint') }}</p>

    @error($name)
        <p class="text-xs font-medium text-danger-500">{{ $message }}</p>
    @enderror
</div>
