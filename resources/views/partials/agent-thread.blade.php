<div class="relative flex min-h-0 flex-1 flex-col">
    <div class="app-sheet-header shrink-0 bg-primary-700 px-4 py-3 text-white">
        <div class="flex items-start justify-between gap-3">
            <div>
                <p class="text-sm font-semibold">{{ __('agent.title') }}</p>
                <p class="text-xs text-primary-100">{{ __('agent.subtitle') }}</p>
                @if (filled(config('services.hermes.chat_url')))
                    <span class="mt-1 inline-flex items-center gap-1 rounded-full bg-white/15 px-2 py-0.5 text-[10px] font-medium text-primary-50">
                        <span class="size-1.5 rounded-full bg-success-300"></span>
                        {{ __('agent.connected') }}
                    </span>
                @endif
            </div>
            <div class="flex items-center gap-1">
                <button
                    type="button"
                    class="rounded-full p-1.5 text-primary-100 hover:bg-primary-600 disabled:opacity-40"
                    x-show="speechSupported"
                    x-cloak
                    @click="startCall()"
                    :disabled="callState !== 'idle'"
                    aria-label="{{ __('agent.call.start') }}"
                    title="{{ __('agent.call.start') }}"
                >
                    <x-icon name="phone" class="size-4"/>
                </button>
                <button type="button"
                        class="rounded-full p-1.5 text-primary-100 hover:bg-primary-600"
                        @click="closeAgent()"
                        aria-label="{{ __('agent.close') }}">
                    <x-icon name="x-mark" class="size-4"/>
                </button>
            </div>
        </div>
    </div>

    <div class="min-h-0 flex-1 space-y-3 overflow-y-auto overscroll-contain bg-ink-50 p-3 text-sm" x-ref="thread">
        <template x-for="(item, index) in messages" :key="index">
            <div :class="item.role === 'user' ? 'ms-8 rounded-2xl bg-primary-600 px-3 py-2 text-white' : 'me-4 rounded-2xl bg-white px-3 py-2 text-ink-800 shadow-sm'">
                <p class="whitespace-pre-line" x-text="item.text"></p>
                <div class="mt-2 space-y-2" x-show="item.cards?.length">
                    <template x-for="(card, cardIndex) in (item.cards || [])" :key="cardIndex">
                        <a :href="card.url"
                           class="block rounded-xl border border-ink-200 bg-ink-50 px-3 py-2 no-underline transition hover:border-primary-200 hover:bg-primary-50">
                            <p class="font-semibold text-ink-900" x-text="card.title"></p>
                            <p class="text-xs text-ink-500" x-show="card.subtitle" x-text="card.subtitle"></p>
                            <p class="mt-1 text-xs font-medium text-primary-700" x-show="card.cta" x-text="card.cta"></p>
                        </a>
                    </template>
                </div>
                <div class="mt-2 flex flex-wrap gap-1" x-show="item.actions?.length">
                    <template x-for="(action, actionIndex) in item.actions" :key="actionIndex">
                        <a :href="action.url"
                           class="inline-flex rounded-full bg-primary-50 px-2.5 py-1 text-xs font-medium text-primary-800"
                           x-text="action.label"></a>
                    </template>
                </div>
            </div>
        </template>
        <div x-cloak x-show="sending && callState === 'idle'" class="me-4 rounded-2xl bg-white px-3 py-2 text-xs text-ink-500 shadow-sm">
            {{ __('agent.thinking') }}
        </div>
    </div>

    <form class="flex shrink-0 gap-2 border-t border-ink-200 bg-white p-3" @submit.prevent="send()" x-show="callState === 'idle'">
        <input x-model="message"
               x-ref="composer"
               type="text"
               inputmode="text"
               enterkeyhint="send"
               class="field-input min-h-11 flex-1 rounded-full text-base leading-normal"
               placeholder="{{ __('agent.placeholder') }}"
               autocomplete="off"
               autocorrect="off"
               autocapitalize="sentences"
               spellcheck="true">
        <button type="submit"
                class="grid size-11 shrink-0 place-items-center rounded-full bg-accent-500 text-white hover:bg-accent-600 disabled:opacity-60"
                :disabled="sending"
                aria-label="{{ __('agent.send') }}">
            <x-icon name="paper-airplane" class="size-4"/>
        </button>
    </form>

    {{-- In-chat voice call overlay --}}
    <div
        x-cloak
        x-show="callState !== 'idle'"
        class="absolute inset-0 z-20 flex flex-col bg-gradient-to-b from-primary-900 via-primary-800 to-ink-950 text-white"
        role="dialog"
        aria-modal="true"
        aria-label="{{ __('agent.call.title') }}"
    >
        <div class="flex flex-1 flex-col items-center justify-center px-6 pt-10 text-center">
            <div class="relative mb-5">
                <div
                    class="absolute inset-0 -m-4 rounded-full opacity-40"
                    :class="{
                        'animate-ping bg-sky-400': callState === 'listening' && ! callMuted,
                        'animate-pulse bg-amber-400': callState === 'thinking' || callState === 'connecting',
                        'bg-emerald-400/60': callState === 'speaking',
                    }"
                ></div>
                <div class="relative grid size-24 place-items-center rounded-full bg-white/10 ring-2 ring-white/30 backdrop-blur">
                    <span class="text-3xl font-bold tracking-tight">م</span>
                </div>
                <div
                    x-show="callState === 'speaking'"
                    class="absolute -bottom-3 inset-x-0 flex justify-center gap-1"
                >
                    <span class="h-3 w-1 animate-pulse rounded-full bg-emerald-300"></span>
                    <span class="h-5 w-1 animate-pulse rounded-full bg-emerald-300 [animation-delay:120ms]"></span>
                    <span class="h-4 w-1 animate-pulse rounded-full bg-emerald-300 [animation-delay:240ms]"></span>
                    <span class="h-6 w-1 animate-pulse rounded-full bg-emerald-300 [animation-delay:80ms]"></span>
                    <span class="h-3 w-1 animate-pulse rounded-full bg-emerald-300 [animation-delay:200ms]"></span>
                </div>
            </div>

            <p class="text-lg font-semibold">{{ __('agent.call.agent_name') }}</p>
            <p class="mt-1 font-mono text-sm tabular-nums text-primary-100" x-text="callTimerLabel">00:00</p>
            <p class="mt-3 text-sm text-primary-100" x-text="callStatusLabel"></p>
            <p class="mt-2 max-w-xs text-xs text-primary-200" x-show="callHint" x-text="callHint"></p>

            <div class="mt-6 w-full max-w-sm space-y-2 text-start">
                <template x-for="(item, index) in callLog" :key="'call-'+index">
                    <div
                        class="rounded-xl px-3 py-2 text-xs leading-relaxed"
                        :class="item.role === 'user' ? 'ms-6 bg-white/15' : 'me-6 bg-black/25'"
                    >
                        <p class="whitespace-pre-line" x-text="item.text"></p>
                    </div>
                </template>
            </div>
        </div>

        <div class="flex items-center justify-center gap-6 px-6 pb-10 pt-4">
            <button
                type="button"
                class="grid size-14 place-items-center rounded-full transition"
                :class="callMuted ? 'bg-white text-ink-900' : 'bg-white/15 text-white hover:bg-white/25'"
                @click="toggleCallMute()"
                :aria-pressed="callMuted"
                aria-label="{{ __('agent.call.mute') }}"
            >
                <x-icon name="microphone" class="size-6"/>
            </button>
            <button
                type="button"
                class="grid size-14 place-items-center rounded-full bg-white/15 text-white transition hover:bg-white/25"
                :class="callSpeakerOn ? 'ring-2 ring-emerald-300' : 'opacity-70'"
                @click="toggleCallSpeaker()"
                :aria-pressed="callSpeakerOn"
                aria-label="{{ __('agent.call.speaker') }}"
            >
                <x-icon name="speaker" class="size-6"/>
            </button>
            <button
                type="button"
                class="grid size-16 place-items-center rounded-full bg-danger-500 text-white shadow-lg transition hover:bg-danger-600"
                @click="endCall()"
                aria-label="{{ __('agent.call.end') }}"
            >
                <x-icon name="phone" class="size-7 rotate-[135deg]"/>
            </button>
        </div>
    </div>
</div>
