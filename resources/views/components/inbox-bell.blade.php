@auth
<div class="relative" x-data="{
        open: false,
        unread: 0,
        items: [],
        seen: 0,
        endpoint: @js(route('inbox.index')),
        csrf: @js(csrf_token()),
        async load() {
            try {
                const response = await fetch(this.endpoint, { headers: { Accept: 'application/json' }, credentials: 'same-origin' });
                if (! response.ok) {
                    return;
                }
                const payload = await response.json();
                if (payload.unread > this.seen && this.seen > 0) {
                    this.chime();
                }
                this.unread = payload.unread;
                this.items = payload.notifications || [];
                this.seen = payload.unread;
            } catch (e) {}
        },
        chime() {
            try {
                const ctx = new (window.AudioContext || window.webkitAudioContext)();
                const osc = ctx.createOscillator();
                const gain = ctx.createGain();
                osc.type = 'sine';
                osc.frequency.value = 880;
                gain.gain.value = 0.07;
                osc.connect(gain);
                gain.connect(ctx.destination);
                osc.start();
                osc.stop(ctx.currentTime + 0.16);
            } catch (e) {}
        },
        async mark(item) {
            await fetch(@js(url('/inbox')) + '/' + item.id, {
                method: 'PUT',
                headers: { 'X-CSRF-TOKEN': this.csrf, Accept: 'application/json', 'Content-Type': 'application/json' },
                credentials: 'same-origin',
                body: JSON.stringify({}),
            });
            item.read_at = new Date().toISOString();
            this.unread = Math.max(0, this.unread - 1);
            if (item.url) {
                window.location = item.url;
            }
        },
        init() {
            this.load();
            setInterval(() => this.load(), 15000);
        }
     }">
    <button type="button" class="relative rounded-lg p-2 text-ink-600 hover:bg-ink-100" @click="open = !open" :aria-expanded="open">
        <x-icon name="bell" class="size-5"/>
        <span x-show="unread > 0" x-text="unread" class="absolute -top-0.5 -end-0.5 grid min-w-4 place-items-center rounded-full bg-accent-500 px-1 text-[10px] font-bold text-white"></span>
    </button>
    <div x-cloak x-show="open" @click.outside="open = false" class="absolute end-0 z-50 mt-2 w-80 overflow-hidden rounded-xl border border-ink-200 bg-white shadow-lg">
        <p class="border-b border-ink-100 px-3 py-2 text-xs font-semibold text-ink-500">{{ __('admin.inbox.heading') }}</p>
        <ul class="max-h-80 overflow-y-auto text-sm">
            <template x-for="item in items" :key="item.id">
                <li>
                    <button type="button" class="block w-full px-3 py-2 text-start hover:bg-ink-50" :class="item.read_at ? 'text-ink-500' : 'font-medium text-ink-900'" @click="mark(item)">
                        <span x-text="item.title"></span>
                        <span class="mt-0.5 block text-[11px] text-ink-400" x-text="item.created_at"></span>
                    </button>
                </li>
            </template>
        </ul>
        <p x-show="items.length === 0" class="px-3 py-4 text-center text-xs text-ink-400">{{ __('admin.inbox.empty') }}</p>
    </div>
</div>
@endauth
