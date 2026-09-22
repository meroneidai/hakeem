<x-layouts.admin :title="__('admin.errors.heading')">
    <x-page-header :title="class_basename($report->exception_class)" :subtitle="$report->message">
        <x-slot:actions>
            <form method="POST" action="{{ route('admin.errors.update', $report) }}">
                @csrf
                @method('PUT')
                <input type="hidden" name="action" value="{{ $report->resolved_at ? 'reopen' : 'resolve' }}">
                <x-button size="sm" variant="secondary">{{ $report->resolved_at ? __('admin.errors.reopen') : __('admin.errors.resolve') }}</x-button>
            </form>
        </x-slot:actions>
    </x-page-header>

    <x-card>
        <dl class="grid gap-3 text-sm sm:grid-cols-2">
            <div><dt class="text-ink-400">URL</dt><dd dir="ltr">{{ $report->url }}</dd></div>
            <div><dt class="text-ink-400">{{ __('admin.errors.count') }}</dt><dd>{{ $report->occurrences }}</dd></div>
            <div><dt class="text-ink-400">{{ __('auth.name') }}</dt><dd>{{ $report->user?->name ?? '—' }}</dd></div>
            <div><dt class="text-ink-400">{{ __('admin.errors.last') }}</dt><dd>{{ $report->last_seen_at }}</dd></div>
        </dl>
        @if ($report->trace)
            <pre class="mt-4 overflow-x-auto rounded-lg bg-ink-950 p-3 text-[11px] text-ink-100" dir="ltr">{{ $report->trace }}</pre>
        @endif
    </x-card>
</x-layouts.admin>
