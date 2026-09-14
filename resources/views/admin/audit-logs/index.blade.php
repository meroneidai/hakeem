<x-layouts.admin :title="__('admin.audit.heading')">
    <x-page-header :title="__('admin.audit.heading')" :subtitle="__('admin.audit.subheading')">
        <x-slot:actions>
            <form method="GET" class="flex items-center gap-2">
                <x-select name="action" :placeholder="__('common.all')"
                          :options="$actions->mapWithKeys(fn ($action) => [$action => $action])->all()"
                          :selected="request('action')" class="w-56"/>
                <x-button variant="secondary" size="sm">{{ __('common.filter') }}</x-button>
            </form>
        </x-slot:actions>
    </x-page-header>

    <x-table>
        <x-slot:head>
            <x-th>{{ __('admin.audit.when') }}</x-th>
            <x-th>{{ __('admin.audit.user') }}</x-th>
            <x-th>{{ __('admin.audit.action') }}</x-th>
            <x-th>{{ __('admin.audit.target') }}</x-th>
            <x-th>{{ __('admin.audit.ip') }}</x-th>
        </x-slot:head>

        @forelse ($logs as $log)
            <tr>
                <x-td class="whitespace-nowrap text-sm text-ink-500">
                    {{ $log->created_at?->translatedFormat('d M Y H:i') }}
                </x-td>
                <x-td class="text-sm font-medium text-ink-800">{{ $log->user?->name ?? __('admin.audit.system') }}</x-td>
                <x-td><code class="text-xs text-primary-600" dir="ltr">{{ $log->action }}</code></x-td>
                <x-td class="text-sm text-ink-500" dir="ltr">
                    {{ $log->auditable_type ? class_basename($log->auditable_type).' #'.$log->auditable_id : '—' }}
                </x-td>
                <x-td class="tabular text-xs text-ink-400" dir="ltr">{{ $log->ip_address }}</x-td>
            </tr>
        @empty
            <x-empty-state colspan="5"/>
        @endforelse

        @if ($logs->hasPages())
            <x-slot:footer>{{ $logs->links() }}</x-slot:footer>
        @endif
    </x-table>
</x-layouts.admin>
