<x-layouts.admin :title="__('admin.clinics.heading')">
    <x-page-header :title="__('admin.clinics.heading')" :subtitle="__('admin.clinics.subheading')"/>

    <form method="GET" class="mb-4 grid gap-2 sm:grid-cols-3">
        <x-input name="q" :value="$filters['q'] ?? ''" :placeholder="__('common.search')"/>
        <select name="status" class="field-input">
            <option value="">{{ __('common.status') }}</option>
            @foreach ($statuses as $status)
                <option value="{{ $status->value }}" @selected(($filters['status'] ?? '') === $status->value)>{{ $status->label() }}</option>
            @endforeach
        </select>
        <x-button variant="secondary">{{ __('common.filter') }}</x-button>
    </form>

    <x-table>
        <x-slot:head>
            <x-th>{{ __('auth.name') }}</x-th>
            <x-th>{{ __('admin.users.heading') }}</x-th>
            <x-th>{{ __('admin.nav.plans') }}</x-th>
            <x-th>{{ __('common.status') }}</x-th>
            <x-th>{{ __('admin.users.bookings') }}</x-th>
            <x-th></x-th>
        </x-slot:head>
        @forelse ($clinics as $clinic)
            <tr>
                <x-td class="font-medium">{{ $clinic->name }}</x-td>
                <x-td>{{ $clinic->owner?->name }}</x-td>
                <x-td>{{ $clinic->plan?->name }}</x-td>
                <x-td><x-badge :tone="$clinic->verification_status->tone()">{{ $clinic->verification_status->label() }}</x-badge></x-td>
                <x-td>{{ $clinic->bookings_count }}</x-td>
                <x-td><a href="{{ route('admin.clinics.show', $clinic) }}" class="text-sm font-medium text-primary-700">{{ __('common.view') }}</a></x-td>
            </tr>
        @empty
            <x-empty-state colspan="6"/>
        @endforelse
        @if ($clinics->hasPages())
            <x-slot:footer>{{ $clinics->links() }}</x-slot:footer>
        @endif
    </x-table>
</x-layouts.admin>
