@props(['label'])

<div class="space-y-1">
    <p class="px-3 pb-1 pt-4 text-[11px] font-semibold uppercase tracking-wide text-ink-400">{{ $label }}</p>
    {{ $slot }}
</div>
