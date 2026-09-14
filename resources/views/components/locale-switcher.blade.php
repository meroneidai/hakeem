@php
    $current = app()->getLocale();
    $target = $current === 'ar' ? 'en' : 'ar';
@endphp

<form method="POST" action="{{ route('locale.switch') }}">
    @csrf
    <input type="hidden" name="locale" value="{{ $target }}">
    <button type="submit"
            class="inline-flex items-center gap-1.5 rounded-lg px-2.5 py-1.5 text-xs font-medium text-ink-600 transition hover:bg-ink-100">
        <x-icon name="globe" class="size-4"/>
        {{ config("hakeem.locales.{$target}.native") }}
    </button>
</form>
