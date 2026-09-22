<x-layouts.admin :title="__('admin.attendance.heading')">
    <x-page-header :title="__('admin.attendance.heading')" :subtitle="__('admin.attendance.subheading')">
        <x-slot:actions>
            @if ($openShift)
                <form method="POST" action="{{ route('admin.attendance.clock-out') }}">
                    @csrf
                    <x-button variant="accent">{{ __('admin.attendance.clock_out') }}</x-button>
                </form>
            @else
                <form method="POST" action="{{ route('admin.attendance.clock-in') }}">
                    @csrf
                    <x-button variant="accent">{{ __('admin.attendance.clock_in') }}</x-button>
                </form>
            @endif
        </x-slot:actions>
    </x-page-header>

    @if ($openShift)
        <x-alert tone="info" class="mb-4">{{ __('admin.attendance.open_since', ['time' => $openShift->clocked_in_at->format('H:i')]) }}</x-alert>
    @endif

    <x-table>
        <x-slot:head>
            <x-th>{{ __('auth.name') }}</x-th>
            <x-th>{{ __('admin.attendance.in') }}</x-th>
            <x-th>{{ __('admin.attendance.out') }}</x-th>
            <x-th>{{ __('admin.attendance.hours') }}</x-th>
        </x-slot:head>
        @forelse ($shifts as $shift)
            <tr>
                <x-td>{{ $shift->user?->name }}</x-td>
                <x-td>{{ $shift->clocked_in_at?->format('Y-m-d H:i') }}</x-td>
                <x-td>{{ $shift->clocked_out_at?->format('Y-m-d H:i') ?? '—' }}</x-td>
                <x-td>{{ $shift->hours() }}</x-td>
            </tr>
        @empty
            <x-empty-state colspan="4"/>
        @endforelse
        @if ($shifts->hasPages())
            <x-slot:footer>{{ $shifts->links() }}</x-slot:footer>
        @endif
    </x-table>
</x-layouts.admin>
