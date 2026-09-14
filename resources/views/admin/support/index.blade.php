@php
    $statusTones = [
        'open' => 'warning', 'in_progress' => 'primary', 'waiting_on_customer' => 'neutral',
        'resolved' => 'success', 'closed' => 'neutral',
    ];
    $priorityTones = ['low' => 'neutral', 'normal' => 'neutral', 'high' => 'warning', 'urgent' => 'danger'];

    $statusOptions = collect(App\Models\SupportTicket::STATUSES)
        ->mapWithKeys(fn ($status) => [$status => __('admin.support.statuses.'.$status)])->all();
    $priorityOptions = collect(App\Models\SupportTicket::PRIORITIES)
        ->mapWithKeys(fn ($priority) => [$priority => __('admin.support.priorities.'.$priority)])->all();
@endphp

<x-layouts.admin :title="__('admin.support.heading')">
    <x-page-header :title="__('admin.support.heading')" :subtitle="__('admin.support.subheading')">
        <x-slot:actions>
            <form method="GET" class="flex flex-wrap items-center gap-2">
                <x-select name="status" :placeholder="__('common.all')" :options="$statusOptions"
                          :selected="request('status')" class="w-40"/>
                <x-select name="priority" :placeholder="__('common.all')" :options="$priorityOptions"
                          :selected="request('priority')" class="w-36"/>
                <x-checkbox name="mine" :label="__('admin.support.assign_to_me')" :checked="request()->boolean('mine')"/>
                <x-button variant="secondary" size="sm">{{ __('common.filter') }}</x-button>
            </form>
        </x-slot:actions>
    </x-page-header>

    <div class="mb-5 grid gap-3 sm:grid-cols-3 lg:grid-cols-5">
        @foreach ($statusOptions as $status => $label)
            <x-stat :label="$label" :value="$counts[$status] ?? 0"
                    :href="route('admin.support.index', ['status' => $status])"
                    :tone="$status === 'open' ? 'warning' : 'ink'"/>
        @endforeach
    </div>

    <x-table>
        <x-slot:head>
            <x-th>{{ __('admin.support.reference') }}</x-th>
            <x-th>{{ __('admin.support.subject') }}</x-th>
            <x-th>{{ __('admin.support.opened_by') }}</x-th>
            <x-th>{{ __('admin.support.channel') }}</x-th>
            <x-th>{{ __('admin.support.priority') }}</x-th>
            <x-th>{{ __('common.status') }}</x-th>
            <x-th>{{ __('admin.support.assigned_agent') }}</x-th>
            <x-th class="text-end">{{ __('common.actions') }}</x-th>
        </x-slot:head>

        @forelse ($tickets as $ticket)
            <tr>
                <x-td><code class="text-xs text-ink-500" dir="ltr">{{ $ticket->reference }}</code></x-td>
                <x-td class="max-w-xs">
                    <a href="{{ route('admin.support.show', $ticket) }}" class="font-medium text-ink-900 hover:text-primary-600">
                        {{ $ticket->subject }}
                    </a>
                    <span class="mt-0.5 block text-xs text-ink-400">
                        {{ __('admin.support.categories.'.$ticket->category) }} · {{ $ticket->created_at->diffForHumans() }}
                    </span>
                </x-td>
                <x-td class="text-sm">{{ $ticket->openedBy->name }}</x-td>
                <x-td class="text-sm">{{ __('admin.support.channels.'.$ticket->channel) }}</x-td>
                <x-td><x-badge :tone="$priorityTones[$ticket->priority]">{{ __('admin.support.priorities.'.$ticket->priority) }}</x-badge></x-td>
                <x-td><x-badge :tone="$statusTones[$ticket->status]">{{ __('admin.support.statuses.'.$ticket->status) }}</x-badge></x-td>
                <x-td class="text-sm text-ink-500">{{ $ticket->assignedAgent?->name ?? __('admin.support.unassigned') }}</x-td>
                <x-td>
                    <div class="flex justify-end">
                        <x-button :href="route('admin.support.show', $ticket)" variant="ghost" size="sm">
                            {{ __('common.view') }}
                        </x-button>
                    </div>
                </x-td>
            </tr>
        @empty
            <x-empty-state colspan="8"/>
        @endforelse

        @if ($tickets->hasPages())
            <x-slot:footer>{{ $tickets->links() }}</x-slot:footer>
        @endif
    </x-table>
</x-layouts.admin>
