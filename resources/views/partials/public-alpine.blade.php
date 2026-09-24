<script>
    document.addEventListener('alpine:init', () => {
        Alpine.store('shell', {
            mobile: false,
            mega: false,
        });

        Alpine.store('labCart', {
            count: {{ (int) ($cartCount ?? 0) }},
            keys: @json($cartKeys ?? []),
            lines: [],
            total_label: '',
            toast: '',
            busy: false,
            toastTimer: null,
            csrf: @js(csrf_token()),
            storeUrl: @js(route('labs.cart.store')),
            updateUrl: @js(route('labs.cart.update')),
            destroyUrl: @js(route('labs.cart.destroy')),
            apply(payload) {
                if (! payload) {
                    return;
                }
                this.count = payload.count ?? 0;
                this.keys = payload.keys ?? [];
                if (Array.isArray(payload.lines)) {
                    this.lines = payload.lines;
                }
                if (typeof payload.total_label === 'string') {
                    this.total_label = payload.total_label;
                }
                if (payload.message) {
                    this.flash(payload.message);
                }
            },
            has(type, id) {
                return this.keys.includes(String(type) + ':' + String(id));
            },
            flash(message) {
                this.toast = message;
                clearTimeout(this.toastTimer);
                this.toastTimer = setTimeout(() => { this.toast = ''; }, 2400);
            },
            async send(url, method, body) {
                if (this.busy) {
                    return;
                }
                this.busy = true;
                try {
                    const data = new FormData();
                    data.append('_token', this.csrf);
                    if (method !== 'POST') {
                        data.append('_method', method);
                    }
                    Object.entries(body).forEach(([key, value]) => data.append(key, value));
                    const response = await fetch(url, {
                        method: 'POST',
                        headers: {
                            Accept: 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                        body: data,
                    });
                    const payload = await response.json().catch(() => ({}));
                    if (! response.ok) {
                        this.flash(payload.message || @js(__('labs.cart.error')));
                        return;
                    }
                    this.apply(payload);
                } finally {
                    this.busy = false;
                }
            },
            add(type, id) {
                return this.send(this.storeUrl, 'POST', { type, id });
            },
            setQty(type, id, qty) {
                return this.send(this.updateUrl, 'PATCH', { type, id, qty });
            },
            remove(type, id) {
                return this.send(this.destroyUrl, 'DELETE', { type, id });
            },
        });

        Alpine.data('headerSearch', (endpoint) => ({
            q: '',
            open: false,
            loading: false,
            results: null,
            timer: null,
            endpoint,
            onInput() {
                clearTimeout(this.timer);
                const term = this.q.trim();
                if (term.length < 2) {
                    this.results = null;
                    return;
                }
                this.timer = setTimeout(() => this.run(), 220);
            },
            async run() {
                this.loading = true;
                this.open = true;
                try {
                    const url = this.endpoint + '?' + new URLSearchParams({ q: this.q.trim(), limit: '6' });
                    const response = await fetch(url, { headers: { Accept: 'application/json' } });
                    this.results = await response.json();
                } finally {
                    this.loading = false;
                }
            },
            get hasHits() {
                if (! this.results) {
                    return false;
                }
                return (this.results.doctors?.length || 0)
                    + (this.results.clinics?.length || 0)
                    + (this.results.services?.length || 0)
                    + (this.results.offers?.length || 0)
                    + (this.results.specialties?.length || 0) > 0;
            },
        }));

        Alpine.data('liveFilters', (config) => ({
            q: config.q || '',
            type: config.type || 'all',
            specialty: config.specialty || '',
            governorate: config.governorate || '',
            city: config.city || '',
            gender: config.gender || '',
            min_experience: config.min_experience || '',
            category: config.category || '',
            max_price: config.max_price || '',
            cities: config.cities || {},
            endpoint: config.endpoint,
            target: config.target,
            loading: false,
            timer: null,
            requestId: 0,
            get cityOptions() {
                return this.cities[this.governorate] || [];
            },
            schedule() {
                clearTimeout(this.timer);
                this.timer = setTimeout(() => this.run(), 220);
            },
            async run() {
                const params = {
                    q: this.q,
                    type: this.type,
                    specialty: this.specialty,
                    governorate: this.governorate,
                    city: this.city,
                    gender: this.gender,
                    min_experience: this.min_experience,
                    category: this.category,
                    max_price: this.max_price,
                };
                Object.keys(params).forEach((key) => {
                    if (params[key] === '' || params[key] === null) {
                        delete params[key];
                    }
                });

                const url = this.endpoint + '?' + new URLSearchParams(params);
                const request = ++this.requestId;
                this.loading = true;

                try {
                    const response = await fetch(url, {
                        headers: { 'X-Search-Fragment': '1', 'X-Requested-With': 'XMLHttpRequest' },
                    });
                    const markup = await response.text();

                    // A slower earlier keystroke must not overwrite the newest results.
                    if (request !== this.requestId || ! response.ok) {
                        return;
                    }

                    document.getElementById(this.target).innerHTML = markup;
                    window.history.replaceState({}, '', url);
                } finally {
                    if (request === this.requestId) {
                        this.loading = false;
                    }
                }
            },
        }));

        Alpine.data('siteAgent', (endpoint, csrf, labels) => ({
            open: false,
            sending: false,
            message: '',
            conversationId: null,
            scrollY: 0,
            labels,
            messages: [{ role: 'assistant', text: labels.empty, actions: [], cards: [], forms: [] }],
            storageKey: 'hakeem.agent.chat',
            init() {
                this.restore();
                this.$watch('open', (value) => this.lockPage(value));
                this.$watch('messages', () => this.persist(), { deep: true });
                this.$watch('conversationId', () => this.persist());
            },
            restore() {
                try {
                    const raw = window.localStorage.getItem(this.storageKey);
                    if (! raw) {
                        return;
                    }
                    const saved = JSON.parse(raw);
                    const maxAge = 24 * 60 * 60 * 1000;
                    if (! saved?.saved_at || (Date.now() - saved.saved_at) > maxAge) {
                        window.localStorage.removeItem(this.storageKey);
                        return;
                    }
                    if (Array.isArray(saved.messages) && saved.messages.length) {
                        this.messages = saved.messages;
                    }
                    if (saved.conversationId) {
                        this.conversationId = saved.conversationId;
                    }
                } catch {
                    window.localStorage.removeItem(this.storageKey);
                }
            },
            persist() {
                try {
                    window.localStorage.setItem(this.storageKey, JSON.stringify({
                        saved_at: Date.now(),
                        conversationId: this.conversationId,
                        messages: this.messages.slice(-40),
                    }));
                } catch {
                    // Ignore quota / private mode failures.
                }
            },
            toggle() {
                this.open ? this.closeAgent() : this.openAgent();
            },
            openAgent() {
                this.open = true;
                this.$nextTick(() => this.$refs.composer?.focus({ preventScroll: true }));
            },
            closeAgent() {
                this.open = false;
            },
            lockPage(locked) {
                const body = document.body;

                if (locked) {
                    this.scrollY = window.scrollY || window.pageYOffset || 0;
                    body.classList.add('app-sheet-open');
                    body.style.position = 'fixed';
                    body.style.top = `-${this.scrollY}px`;
                    body.style.insetInline = '0';
                    body.style.width = '100%';
                    return;
                }

                body.classList.remove('app-sheet-open');
                body.style.position = '';
                body.style.top = '';
                body.style.insetInline = '';
                body.style.width = '';
                window.scrollTo(0, this.scrollY);
            },
            async send() {
                const text = this.message.trim();
                if (! text || this.sending) {
                    return;
                }
                this.messages.push({ role: 'user', text, actions: [], cards: [], forms: [] });
                this.message = '';
                this.sending = true;
                try {
                    const response = await fetch(endpoint, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            Accept: 'application/json',
                            'X-CSRF-TOKEN': csrf,
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                        body: JSON.stringify({
                            message: text,
                            conversation_id: this.conversationId,
                        }),
                    });
                    const data = await response.json();
                    this.conversationId = data.conversation_id;
                    this.messages.push({
                        role: 'assistant',
                        text: data.reply || (response.ok ? '' : labels.error),
                        actions: data.actions || [],
                        cards: data.cards || [],
                        forms: data.forms || [],
                    });
                } catch {
                    this.messages.push({
                        role: 'assistant',
                        text: labels.error,
                        actions: [],
                        cards: [],
                        forms: [],
                    });
                } finally {
                    this.sending = false;
                    this.$nextTick(() => {
                        const pane = this.$refs.thread;
                        if (pane) {
                            pane.scrollTop = pane.scrollHeight;
                        }
                        this.$refs.composer?.focus({ preventScroll: true });
                    });
                }
            },
        }));
    });
</script>
