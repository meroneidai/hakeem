<x-layouts.admin :title="__('admin.errors.heading')">
    <x-page-header :title="__('admin.errors.heading')" :subtitle="__('admin.errors.subheading')"/>

    <form method="GET" class="mb-4 grid gap-2 sm:grid-cols-3">
        <x-input name="q" :value="request('q')" :placeholder="__('common.search')"/>
        <x-select name="status" :placeholder="__('common.status')" :selected="request('status')"
                  :options="['open' => __('admin.errors.open'), 'resolved' => __('admin.errors.resolved')]"/>
        <x-button variant="secondary">{{ __('common.filter') }}</x-button>
    </form>

    <x-table>
        <x-slot:head>
            <x-th>{{ __('admin.errors.class') }}</x-th>
            <x-th>{{ __('admin.errors.message') }}</x-th>
            <x-th>{{ __('admin.errors.count') }}</x-th>
            <x-th>{{ __('admin.errors.last') }}</x-th>
            <x-th></x-th>
        </x-slot:head>
        @forelse ($reports as $report)
            <tr>
                <x-td class="text-xs" dir="ltr">{{ class_basename($report->exception_class) }}</x-td>
                <x-td class="max-w-sm truncate">{{ $report->message }}</x-td>
                <x-td>{{ $report->occurrences }}</x-td>
                <x-td class="text-xs">{{ $report->last_seen_at?->diffForHumans() }}</x-td>
                <x-td><a href="{{ route('admin.errors.show', $report) }}" class="text-sm text-primary-700">{{ __('common.view') }}</a></x-td>
            </tr>
        @empty
            <x-empty-state colspan="5"/>
        @endforelse
        @if ($reports->hasPages())
            <x-slot:footer>{{ $reports->links() }}</x-slot:footer>
        @endif
    </x-table>
</x-layouts.admin>
