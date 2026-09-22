@props(['counts', 'cases', 'routeName', 'filters' => []])

<div {{ $attributes->merge(['class' => 'grid gap-3 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6']) }}>
    @foreach ($cases as $status)
        <x-metric-tile
            :label="$status->label()"
            :value="$counts[$status->value] ?? 0"
            :href="route($routeName, array_filter(array_merge($filters, ['status' => $status->value]), fn ($value) => $value !== null && $value !== ''))"
            :tone="$status->tone()"
            icon="calendar"
        />
    @endforeach
</div>
