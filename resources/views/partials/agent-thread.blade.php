<div class="bg-primary-700 px-4 py-3 text-white">
    <div class="flex items-start justify-between gap-3">
        <div>
            <p class="text-sm font-semibold">{{ __('agent.title') }}</p>
            <p class="text-xs text-primary-100">{{ __('agent.subtitle') }}</p>
        </div>
        <button type="button" class="rounded-full p-1 text-primary-100 hover:bg-primary-600 lg:hidden" @click="open = false" aria-label="{{ __('agent.close') }}">
            <x-icon name="x-mark" class="size-4"/>
        </button>
    </div>
</div>
<div class="flex-1 space-y-3 overflow-y-auto bg-ink-50 p-3 text-sm" x-ref="thread">
    <template x-for="(item, index) in messages" :key="index">
        <div :class="item.role === 'user' ? 'ms-8 rounded-2xl bg-primary-600 px-3 py-2 text-white' : 'me-6 rounded-2xl bg-white px-3 py-2 text-ink-800 shadow-sm'">
            <p class="whitespace-pre-line" x-text="item.text"></p>
            <div class="mt-2 space-y-1" x-show="item.actions?.length">
                <template x-for="(action, actionIndex) in item.actions" :key="actionIndex">
                    <a :href="action.url" class="block rounded-lg bg-primary-50 px-2 py-1 text-xs font-medium text-primary-800" x-text="action.label"></a>
                </template>
            </div>
        </div>
    </template>
</div>
<form class="flex gap-2 border-t border-ink-200 p-3" @submit.prevent="send()">
    <input x-model="message"
           class="field-input flex-1 rounded-full text-sm"
           placeholder="{{ __('agent.placeholder') }}"
           autocomplete="off">
    <button type="submit" class="grid size-10 place-items-center rounded-full bg-accent-500 text-white hover:bg-accent-600" :disabled="sending">
        <x-icon name="paper-airplane" class="size-4"/>
    </button>
</form>
