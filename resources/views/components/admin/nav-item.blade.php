@props(['href', 'icon' => 'grid', 'pattern' => null, 'badge' => null])

@php
    $active = $pattern ? request()->routeIs($pattern) : request()->url() === $href;
@endphp

<a href="{{ $href }}"
   @class([
       'group flex items-center gap-2.5 rounded-lg px-3 py-2 text-sm transition',
       'bg-primary-600 text-white shadow-sm' => $active,
       'text-ink-600 hover:bg-ink-100 hover:text-ink-900' => ! $active,
   ])>
    <x-icon :name="$icon" class="size-[18px] shrink-0 {{ $active ? 'text-white' : 'text-ink-400 group-hover:text-ink-600' }}"/>
    <span class="flex-1 truncate">{{ $slot }}</span>
    @if ($badge)
        <span @class([
            'rounded-full px-1.5 py-0.5 text-[11px] font-semibold tabular',
            'bg-white/20 text-white' => $active,
            'bg-accent-100 text-accent-700' => ! $active,
        ])>{{ $badge }}</span>
    @endif
</a>
