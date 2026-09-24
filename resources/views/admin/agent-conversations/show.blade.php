<x-layouts.admin :title="__('admin.agent_conversations.heading')">
    <x-page-header
        :title="$conversation->patient?->name ?? $conversation->visitor_name ?? __('admin.agent_conversations.anonymous')"
        :subtitle="__('admin.agent_conversations.channels.'.$conversation->channel).' · '.$conversation->started_at?->timezone(config('hakeem.display_timezone'))->format('Y-m-d H:i')"
    >
        <x-slot:actions>
            <x-button :href="route('admin.agent-conversations.index')" variant="secondary" size="sm">{{ __('common.back') }}</x-button>
            @if ($conversation->patient)
                <x-button :href="route('admin.users.show', $conversation->patient)" variant="secondary" size="sm">{{ __('admin.users.detail') }}</x-button>
            @endif
        </x-slot:actions>
    </x-page-header>

    <div class="space-y-3">
        @forelse ($conversation->messages as $message)
            <x-card>
                <div class="mb-2 flex flex-wrap items-center justify-between gap-2 text-xs text-ink-400">
                    <span class="font-medium {{ $message->role === 'user' ? 'text-primary-700' : 'text-teal-700' }}">
                        {{ $message->role }}
                    </span>
                    <span>
                        {{ $message->created_at?->timezone(config('hakeem.display_timezone'))->format('Y-m-d H:i:s') }}
                        @if ($message->latency_ms)
                            · {{ __('admin.agent_conversations.latency') }}: {{ $message->latency_ms }}ms
                        @endif
                    </span>
                </div>
                <p class="whitespace-pre-wrap text-sm text-ink-800">{{ $message->body }}</p>
                @if (! empty($message->actions))
                    <div class="mt-3 flex flex-wrap gap-2">
                        @foreach ($message->actions as $action)
                            <span class="rounded-full bg-ink-50 px-2.5 py-1 text-xs text-ink-600">
                                {{ $action['label'] ?? ($action['type'] ?? 'action') }}
                            </span>
                        @endforeach
                    </div>
                @endif
                @if (! empty($message->forms))
                    <div class="mt-3 rounded-xl bg-primary-50 p-3 text-xs text-primary-800">
                        <pre class="overflow-x-auto whitespace-pre-wrap">{{ json_encode($message->forms, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) }}</pre>
                    </div>
                @endif
            </x-card>
        @empty
            <x-card>
                <p class="text-sm text-ink-400">{{ __('admin.agent_conversations.empty') }}</p>
            </x-card>
        @endforelse
    </div>
</x-layouts.admin>
