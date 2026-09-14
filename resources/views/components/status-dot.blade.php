@props(['active' => true])

<x-badge :tone="$active ? 'success' : 'neutral'">
    {{ $active ? __('common.active') : __('common.inactive') }}
</x-badge>
