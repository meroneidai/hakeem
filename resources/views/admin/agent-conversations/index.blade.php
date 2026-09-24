<x-layouts.admin :title="__('admin.agent_conversations.heading')">
    <x-page-header :title="__('admin.agent_conversations.heading')" :subtitle="__('admin.agent_conversations.subheading')"/>

    <form method="GET" class="mb-4 grid gap-2 sm:grid-cols-2 lg:grid-cols-5">
        <x-input name="q" :value="$filters['q'] ?? ''" :placeholder="__('common.search')"/>
        <x-select
            name="channel"
            :placeholder="__('admin.agent_conversations.channel')"
            :selected="$filters['channel'] ?? ''"
            :options="[
                'web' => __('admin.agent_conversations.channels.web'),
                'whatsapp' => __('admin.agent_conversations.channels.whatsapp'),
                'telegram' => __('admin.agent_conversations.channels.telegram'),
            ]"
        />
        <x-input name="from" type="date" :value="$filters['from'] ?? ''"/>
        <x-input name="to" type="date" :value="$filters['to'] ?? ''"/>
        <x-button variant="secondary">{{ __('common.filter') }}</x-button>
    </form>

    <x-table>
        <x-slot:head>
            <x-th>{{ __('admin.agent_conversations.started') }}</x-th>
            <x-th>{{ __('admin.agent_conversations.channel') }}</x-th>
            <x-th>{{ __('admin.agent_conversations.visitor') }}</x-th>
            <x-th>{{ __('admin.agent_conversations.messages') }}</x-th>
            <x-th>{{ __('admin.agent_conversations.last_message') }}</x-th>
            <x-th></x-th>
        </x-slot:head>
        @forelse ($conversations as $conversation)
            @php
                $snippet = $conversation->messages->first()?->body;
            @endphp
            <tr>
                <x-td class="text-xs text-ink-500">
                    {{ ($conversation->last_message_at ?? $conversation->started_at)?->timezone(config('hakeem.display_timezone'))->format('Y-m-d H:i') }}
                </x-td>
                <x-td>{{ __('admin.agent_conversations.channels.'.$conversation->channel) }}</x-td>
                <x-td class="font-medium text-ink-900">
                    {{ $conversation->patient?->name ?? $conversation->visitor_name ?? __('admin.agent_conversations.anonymous') }}
                    @if ($conversation->patient?->phone)
                        <span class="block text-xs text-ink-400" dir="ltr">{{ $conversation->patient->phone }}</span>
                    @endif
                </x-td>
                <x-td class="tabular">{{ $conversation->message_count }}</x-td>
                <x-td class="max-w-xs truncate text-sm text-ink-600">{{ \Illuminate\Support\Str::limit($snippet, 80) }}</x-td>
                <x-td>
                    <a href="{{ route('admin.agent-conversations.show', $conversation) }}" class="text-sm font-medium text-primary-700 hover:underline">
                        {{ __('common.view') }}
                    </a>
                </x-td>
            </tr>
        @empty
            <x-empty-state :colspan="6" :message="__('admin.agent_conversations.empty')"/>
        @endforelse
        @if ($conversations->hasPages())
            <x-slot:footer>{{ $conversations->links() }}</x-slot:footer>
        @endif
    </x-table>
</x-layouts.admin>
