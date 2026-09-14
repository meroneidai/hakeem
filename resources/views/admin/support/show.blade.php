@php
    $statusOptions = collect(App\Models\SupportTicket::STATUSES)
        ->mapWithKeys(fn ($status) => [$status => __('admin.support.statuses.'.$status)])->all();
    $priorityOptions = collect(App\Models\SupportTicket::PRIORITIES)
        ->mapWithKeys(fn ($priority) => [$priority => __('admin.support.priorities.'.$priority)])->all();
    $categoryOptions = collect(App\Models\SupportTicket::CATEGORIES)
        ->mapWithKeys(fn ($category) => [$category => __('admin.support.categories.'.$category)])->all();
@endphp

<x-layouts.admin :title="$ticket->subject">
    <x-page-header :title="$ticket->subject" :subtitle="$ticket->reference.' · '.__('admin.support.channels.'.$ticket->channel)">
        <x-slot:actions>
            <x-button :href="route('admin.support.index')" variant="ghost">{{ __('common.back') }}</x-button>
        </x-slot:actions>
    </x-page-header>

    <div class="grid gap-5 lg:grid-cols-[2fr_1fr]">
        <div class="space-y-5">
            <x-card :title="__('admin.support.conversation')">
                @if ($ticket->messages->isEmpty())
                    <x-empty-state :message="__('admin.support.no_messages')"/>
                @else
                    <ul class="space-y-3">
                        @foreach ($ticket->messages as $message)
                            <li @class([
                                'rounded-lg border p-3',
                                'border-warning-500/30 bg-warning-50' => $message->is_internal_note,
                                'border-ink-200 bg-white' => ! $message->is_internal_note,
                            ])>
                                <div class="mb-1.5 flex items-baseline justify-between gap-3 text-xs">
                                    <span class="font-semibold text-ink-800">
                                        {{ $message->sender?->name ?? __('admin.audit.system') }}
                                        @if ($message->is_internal_note)
                                            <x-badge tone="warning" class="ms-1">{{ __('admin.support.internal_note') }}</x-badge>
                                        @endif
                                    </span>
                                    <span class="text-ink-400">{{ $message->sent_at->diffForHumans() }}</span>
                                </div>
                                <p class="whitespace-pre-line text-sm text-ink-700">{{ $message->body }}</p>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </x-card>

            <x-card :title="__('admin.support.reply')">
                <form method="POST" action="{{ route('admin.support.reply', $ticket) }}" class="space-y-3">
                    @csrf

                    <x-field name="body">
                        <x-textarea name="body" rows="4" :placeholder="__('admin.support.reply_placeholder')"/>
                    </x-field>

                    <x-checkbox name="is_internal_note" :label="__('admin.support.internal_note')"/>

                    <div class="flex justify-end">
                        <x-button variant="accent">{{ __('admin.support.send') }}</x-button>
                    </div>
                </form>
            </x-card>
        </div>

        <x-card :title="__('common.status')">
            <form method="POST" action="{{ route('admin.support.update', $ticket) }}" class="space-y-4">
                @csrf
                @method('PUT')

                <x-field :label="__('common.status')" name="status" required>
                    <x-select name="status" :options="$statusOptions" :selected="$ticket->status"/>
                </x-field>

                <x-field :label="__('admin.support.priority')" name="priority" required>
                    <x-select name="priority" :options="$priorityOptions" :selected="$ticket->priority"/>
                </x-field>

                <x-field :label="__('admin.support.category')" name="category" required>
                    <x-select name="category" :options="$categoryOptions" :selected="$ticket->category"/>
                </x-field>

                <x-field :label="__('admin.support.assigned_agent')" name="assigned_agent_id">
                    <x-select name="assigned_agent_id" :placeholder="__('admin.support.unassigned')"
                              :options="$agents->pluck('name', 'id')->all()" :selected="$ticket->assigned_agent_id"/>
                </x-field>

                <x-button variant="primary" class="w-full">{{ __('common.save_changes') }}</x-button>
            </form>

            <dl class="mt-5 space-y-2 border-t border-ink-100 pt-4 text-sm">
                <div class="flex justify-between gap-2">
                    <dt class="text-ink-500">{{ __('admin.support.opened_by') }}</dt>
                    <dd class="font-medium text-ink-800">{{ $ticket->openedBy->name }}</dd>
                </div>
                <div class="flex justify-between gap-2">
                    <dt class="text-ink-500">{{ __('auth.phone') }}</dt>
                    <dd class="tabular text-ink-800" dir="ltr">{{ $ticket->openedBy->phone }}</dd>
                </div>
                <div class="flex justify-between gap-2">
                    <dt class="text-ink-500">{{ __('common.created_at') }}</dt>
                    <dd class="text-ink-800">{{ $ticket->created_at->translatedFormat('d M Y H:i') }}</dd>
                </div>
                <div class="flex justify-between gap-2">
                    <dt class="text-ink-500">{{ __('admin.support.first_response') }}</dt>
                    <dd class="text-ink-800">{{ $ticket->first_response_at?->diffForHumans() ?? '—' }}</dd>
                </div>
                <div class="flex justify-between gap-2">
                    <dt class="text-ink-500">{{ __('admin.support.resolved_at') }}</dt>
                    <dd class="text-ink-800">{{ $ticket->resolved_at?->translatedFormat('d M Y H:i') ?? '—' }}</dd>
                </div>
            </dl>
        </x-card>
    </div>
</x-layouts.admin>
