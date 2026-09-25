<script>
    document.addEventListener('alpine:init', () => {
        Alpine.store('shell', {
            mobile: false,
            mega: false,
            scrollY: 0,
            toggleMobile() {
                this.mobile = ! this.mobile;
                this.lockScroll(this.mobile);
            },
            openMobile() {
                if (this.mobile) {
                    return;
                }
                this.mobile = true;
                this.lockScroll(true);
            },
            closeMobile() {
                if (! this.mobile) {
                    return;
                }
                this.mobile = false;
                this.lockScroll(false);
            },
            lockScroll(locked) {
                const body = document.body;

                if (locked) {
                    this.scrollY = window.scrollY || window.pageYOffset || 0;
                    body.classList.add('menu-open');
                    body.style.position = 'fixed';
                    body.style.top = `-${this.scrollY}px`;
                    body.style.insetInline = '0';
                    body.style.width = '100%';
                    return;
                }

                body.classList.remove('menu-open');
                body.style.position = '';
                body.style.top = '';
                body.style.insetInline = '';
                body.style.width = '';
                window.scrollTo(0, this.scrollY);
            },
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
            _onViewport: null,
            init() {
                this._onViewport = () => {
                    if (this.open) {
                        this.placePanel();
                    }
                };
                window.visualViewport?.addEventListener('resize', this._onViewport);
                window.visualViewport?.addEventListener('scroll', this._onViewport);
                window.addEventListener('resize', this._onViewport);
                this.$watch('open', (value) => {
                    if (value) {
                        this.$nextTick(() => this.placePanel());
                    } else {
                        this.clearPanel();
                    }
                });
            },
            destroy() {
                window.visualViewport?.removeEventListener('resize', this._onViewport);
                window.visualViewport?.removeEventListener('scroll', this._onViewport);
                window.removeEventListener('resize', this._onViewport);
            },
            onFocus() {
                const y = window.scrollY || window.pageYOffset || 0;
                // Keep shell stable: block iOS scroll-into-view jump on keyboard open.
                requestAnimationFrame(() => {
                    window.scrollTo(0, y);
                    requestAnimationFrame(() => window.scrollTo(0, y));
                });
                if (this.q.trim().length >= 2) {
                    this.open = true;
                    this.$nextTick(() => this.placePanel());
                }
            },
            onInput() {
                clearTimeout(this.timer);
                const term = this.q.trim();
                if (term.length < 2) {
                    this.results = null;
                    this.open = false;
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
                    this.$nextTick(() => this.placePanel());
                }
            },
            placePanel() {
                const panel = this.$refs.results;
                const anchor = this.$refs.anchor || this.$el;
                if (! panel || ! this.open) {
                    return;
                }

                // Desktop keeps CSS absolute dropdown under the form.
                if (window.matchMedia('(min-width: 640px)').matches) {
                    this.clearPanel();
                    return;
                }

                const box = anchor.getBoundingClientRect();
                const vv = window.visualViewport;
                const offsetTop = vv?.offsetTop ?? 0;
                const viewH = vv?.height ?? window.innerHeight;
                const viewBottom = offsetTop + viewH;
                const gutter = 10;
                const dockReserve = 88;
                const gap = 8;
                const width = Math.min(box.width, window.innerWidth - gutter * 2);
                const left = Math.min(
                    Math.max(gutter, box.left),
                    window.innerWidth - gutter - width,
                );

                let top = box.bottom + gap;
                let maxH = viewBottom - top - dockReserve;

                // Flip above the field when there is not enough room below (keyboard / mini).
                if (maxH < 132) {
                    maxH = Math.min(240, box.top - offsetTop - gutter);
                    top = Math.max(offsetTop + gutter, box.top - gap - maxH);
                } else {
                    maxH = Math.min(240, maxH);
                }

                panel.style.position = 'fixed';
                panel.style.left = `${left}px`;
                panel.style.right = 'auto';
                panel.style.width = `${width}px`;
                panel.style.top = `${top}px`;
                panel.style.bottom = 'auto';
                panel.style.maxHeight = `${Math.max(96, maxH)}px`;
                panel.style.zIndex = '45';
                panel.style.overflowY = 'auto';
            },
            clearPanel() {
                const panel = this.$refs.results;
                if (! panel) {
                    return;
                }
                panel.style.position = '';
                panel.style.left = '';
                panel.style.right = '';
                panel.style.width = '';
                panel.style.top = '';
                panel.style.bottom = '';
                panel.style.maxHeight = '';
                panel.style.zIndex = '';
                panel.style.overflowY = '';
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

        Alpine.data('siteAgent', (endpoint, speechOrCsrf, csrfOrLabels, maybeLabels) => {
            // New call: (endpoint, speechEndpoint, csrf, labels)
            // Legacy call: (endpoint, csrf, labels)
            const legacy = maybeLabels === undefined && typeof speechOrCsrf === 'string' && typeof csrfOrLabels === 'object';
            const speechEndpoint = legacy ? '' : (speechOrCsrf || '');
            const csrf = legacy ? speechOrCsrf : csrfOrLabels;
            const labels = legacy ? csrfOrLabels : (maybeLabels || {});

            return {
            open: false,
            sending: false,
            message: '',
            conversationId: null,
            scrollY: 0,
            labels,
            messages: [{ role: 'assistant', text: labels.empty, actions: [], cards: [], forms: [] }],
            storageKey: 'hakeem.agent.chat',
            speechSupported: typeof window !== 'undefined' && !!(window.SpeechRecognition || window.webkitSpeechRecognition),
            callState: 'idle',
            callMuted: false,
            callSpeakerOn: true,
            callStartedAt: null,
            callTimerLabel: '00:00',
            callHint: '',
            callAudio: null,
            callRecognition: null,
            callVadRecognition: null,
            callTimerInterval: null,
            callSilenceTimer: null,
            callThinkingTimer: null,
            callSilenceStrikes: 0,
            callSpeakStartedAt: 0,
            callListenToken: 0,
            init() {
                this.restore();
                this.$watch('open', (value) => this.lockPage(value));
                this.$watch('messages', () => this.persist(), { deep: true });
                this.$watch('conversationId', () => this.persist());
            },
            get callStatusLabel() {
                if (this.callMuted && this.callState === 'listening') {
                    return this.labels.call?.muted || '';
                }
                return this.labels.call?.[this.callState] || '';
            },
            get callLog() {
                return this.messages.filter((item) => item.text).slice(-4);
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
                if (this.callState !== 'idle') {
                    this.endCall({ silent: true });
                }
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
            ensureConversationId() {
                if (! this.conversationId && window.crypto?.randomUUID) {
                    this.conversationId = window.crypto.randomUUID();
                }
                if (! this.conversationId) {
                    this.conversationId = 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, (c) => {
                        const r = Math.random() * 16 | 0;
                        const v = c === 'x' ? r : (r & 0x3 | 0x8);
                        return v.toString(16);
                    });
                }
                return this.conversationId;
            },
            async send(options = {}) {
                const text = (options.text ?? this.message).trim();
                const voiceMode = Boolean(options.voiceMode);
                if (! text || this.sending) {
                    return null;
                }
                if (! options.skipPush) {
                    this.messages.push({ role: 'user', text, actions: [], cards: [], forms: [] });
                }
                if (! options.text) {
                    this.message = '';
                }
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
                            conversation_id: this.ensureConversationId(),
                            locale: labels.locale || 'ar',
                            mode: voiceMode ? 'voice' : 'text',
                        }),
                    });
                    const data = await response.json();
                    this.conversationId = data.conversation_id || this.conversationId;
                    const reply = {
                        role: 'assistant',
                        text: data.reply || (response.ok ? '' : labels.error),
                        actions: data.actions || [],
                        cards: data.cards || [],
                        forms: data.forms || [],
                    };
                    this.messages.push(reply);
                    return reply;
                } catch {
                    const reply = {
                        role: 'assistant',
                        text: labels.error,
                        actions: [],
                        cards: [],
                        forms: [],
                    };
                    this.messages.push(reply);
                    return reply;
                } finally {
                    this.sending = false;
                    this.$nextTick(() => {
                        const pane = this.$refs.thread;
                        if (pane) {
                            pane.scrollTop = pane.scrollHeight;
                        }
                        if (this.callState === 'idle') {
                            this.$refs.composer?.focus({ preventScroll: true });
                        }
                    });
                }
            },

            // ── Voice call ──────────────────────────────────────────────
            async startCall() {
                if (! this.speechSupported) {
                    this.callHint = this.labels.call?.unsupported || '';
                    return;
                }
                if (this.callState !== 'idle') {
                    return;
                }

                this.callState = 'connecting';
                this.callHint = '';
                this.callMuted = false;
                this.callSilenceStrikes = 0;
                this.ensureConversationId();
                this.startCallTimer();

                try {
                    const stream = await navigator.mediaDevices.getUserMedia({ audio: true });
                    stream.getTracks().forEach((track) => track.stop());
                } catch {
                    this.callHint = this.labels.call?.mic_denied || '';
                    this.endCall({ silent: true });
                    return;
                }

                this.callState = 'listening';
                this.listenForTurn();
            },
            endCall(options = {}) {
                this.callListenToken += 1;
                this.clearCallTimers();
                this.stopRecognition();
                this.stopVad();
                this.stopCallAudio();
                this.callState = 'idle';
                this.callMuted = false;
                this.callStartedAt = null;
                this.callTimerLabel = '00:00';
                if (! options.silent && options.sayGoodbye) {
                    this.messages.push({
                        role: 'assistant',
                        text: this.labels.call?.goodbye || '',
                        actions: [],
                        cards: [],
                        forms: [],
                    });
                }
            },
            startCallTimer() {
                this.callStartedAt = Date.now();
                this.callTimerLabel = '00:00';
                clearInterval(this.callTimerInterval);
                this.callTimerInterval = setInterval(() => {
                    if (! this.callStartedAt) {
                        return;
                    }
                    const total = Math.floor((Date.now() - this.callStartedAt) / 1000);
                    const mm = String(Math.floor(total / 60)).padStart(2, '0');
                    const ss = String(total % 60).padStart(2, '0');
                    this.callTimerLabel = `${mm}:${ss}`;
                }, 1000);
            },
            clearCallTimers() {
                clearInterval(this.callTimerInterval);
                clearTimeout(this.callSilenceTimer);
                clearTimeout(this.callThinkingTimer);
                this.callTimerInterval = null;
                this.callSilenceTimer = null;
                this.callThinkingTimer = null;
            },
            toggleCallMute() {
                this.callMuted = ! this.callMuted;
                if (this.callMuted) {
                    this.stopRecognition();
                    clearTimeout(this.callSilenceTimer);
                } else if (this.callState === 'listening') {
                    this.listenForTurn();
                }
            },
            toggleCallSpeaker() {
                this.callSpeakerOn = ! this.callSpeakerOn;
                if (this.callAudio) {
                    this.callAudio.muted = ! this.callSpeakerOn;
                    // Prefer speaker when supported (Android Chrome).
                    if (typeof this.callAudio.setSinkId === 'function' && this.callSpeakerOn) {
                        this.callAudio.setSinkId('').catch(() => {});
                    }
                }
            },
            recognitionFactory() {
                const Ctor = window.SpeechRecognition || window.webkitSpeechRecognition;
                if (! Ctor) {
                    return null;
                }
                const recognition = new Ctor();
                recognition.lang = (labels.locale || 'ar') === 'en' ? 'en-US' : 'ar-EG';
                recognition.interimResults = true;
                recognition.continuous = false;
                recognition.maxAlternatives = 1;
                return recognition;
            },
            /**
             * Isolated STT entry point — swap later for MediaRecorder + /agent/transcribe.
             */
            captureSpeech() {
                return new Promise((resolve, reject) => {
                    const recognition = this.recognitionFactory();
                    if (! recognition) {
                        reject(new Error('unsupported'));
                        return;
                    }

                    let finalText = '';
                    let settled = false;
                    this.callRecognition = recognition;

                    recognition.onresult = (event) => {
                        let interim = '';
                        for (let i = event.resultIndex; i < event.results.length; i += 1) {
                            const chunk = event.results[i][0]?.transcript || '';
                            if (event.results[i].isFinal) {
                                finalText += chunk;
                            } else {
                                interim += chunk;
                            }
                        }
                        if (interim) {
                            this.callHint = interim;
                        }
                    };

                    recognition.onerror = (event) => {
                        if (settled) {
                            return;
                        }
                        settled = true;
                        this.callRecognition = null;
                        if (event.error === 'no-speech' || event.error === 'aborted') {
                            resolve('');
                            return;
                        }
                        reject(new Error(event.error || 'recognition_error'));
                    };

                    recognition.onend = () => {
                        if (settled) {
                            return;
                        }
                        settled = true;
                        this.callRecognition = null;
                        resolve(finalText.trim());
                    };

                    try {
                        recognition.start();
                    } catch (error) {
                        settled = true;
                        this.callRecognition = null;
                        reject(error);
                    }
                });
            },
            stopRecognition() {
                if (! this.callRecognition) {
                    return;
                }
                try {
                    this.callRecognition.onresult = null;
                    this.callRecognition.onerror = null;
                    this.callRecognition.onend = null;
                    this.callRecognition.abort();
                } catch {
                    // Already stopped.
                }
                this.callRecognition = null;
            },
            armSilenceWatch(token) {
                clearTimeout(this.callSilenceTimer);
                this.callSilenceTimer = setTimeout(async () => {
                    if (token !== this.callListenToken || this.callState !== 'listening' || this.callMuted) {
                        return;
                    }
                    this.callSilenceStrikes += 1;
                    this.stopRecognition();
                    if (this.callSilenceStrikes >= 3) {
                        await this.speakText(this.labels.call?.goodbye || '');
                        this.endCall({ silent: true });
                        return;
                    }
                    await this.speakText(this.labels.call?.still_there || '');
                    if (this.callState !== 'idle' && token === this.callListenToken) {
                        this.callState = 'listening';
                        this.listenForTurn();
                    }
                }, 15000);
            },
            async listenForTurn() {
                if (this.callState !== 'listening' || this.callMuted) {
                    return;
                }

                const token = ++this.callListenToken;
                this.armSilenceWatch(token);
                this.callHint = '';

                try {
                    const text = await this.captureSpeech();
                    if (token !== this.callListenToken || this.callState === 'idle') {
                        return;
                    }
                    clearTimeout(this.callSilenceTimer);

                    if (! text) {
                        this.listenForTurn();
                        return;
                    }

                    this.callSilenceStrikes = 0;
                    this.callHint = '';
                    this.messages.push({ role: 'user', text, actions: [], cards: [], forms: [] });
                    await this.handleVoiceTurn(text, token);
                } catch (error) {
                    if (token !== this.callListenToken || this.callState === 'idle') {
                        return;
                    }
                    if (String(error?.message || error) === 'not-allowed') {
                        this.callHint = this.labels.call?.mic_denied || '';
                        this.endCall({ silent: true });
                        return;
                    }
                    this.callHint = this.labels.call?.weak_line || '';
                    setTimeout(() => {
                        if (this.callState === 'listening' && token === this.callListenToken) {
                            this.listenForTurn();
                        }
                    }, 800);
                }
            },
            async handleVoiceTurn(text, token) {
                this.callState = 'thinking';
                this.armThinkingFiller(token);

                const reply = await this.send({ text, voiceMode: true, skipPush: true });
                clearTimeout(this.callThinkingTimer);

                if (token !== this.callListenToken || this.callState === 'idle') {
                    return;
                }

                const spoken = (reply?.text || '').trim();
                if (! spoken) {
                    this.callState = 'listening';
                    this.listenForTurn();
                    return;
                }

                await this.speakText(spoken);
                if (token !== this.callListenToken || this.callState === 'idle') {
                    return;
                }
                this.callState = 'listening';
                this.listenForTurn();
            },
            armThinkingFiller(token) {
                clearTimeout(this.callThinkingTimer);
                this.callThinkingTimer = setTimeout(async () => {
                    if (token !== this.callListenToken || this.callState !== 'thinking') {
                        return;
                    }
                    await this.speakText(this.labels.call?.filler || '', { keepThinking: true, skipVad: true });
                }, 1500);
            },
            firstSentences(text, max = 2) {
                const parts = text
                    .replace(/\s+/g, ' ')
                    .split(/(?<=[.!?؟。])\s+/)
                    .map((part) => part.trim())
                    .filter(Boolean);
                if (parts.length <= max) {
                    return [text.trim()];
                }
                return [parts.slice(0, max).join(' '), parts.slice(max).join(' ')].filter(Boolean);
            },
            async speakText(text, options = {}) {
                const chunks = this.firstSentences(text, 2);
                if (! chunks.length) {
                    return;
                }

                if (! options.keepThinking) {
                    this.callState = 'speaking';
                }

                for (const chunk of chunks) {
                    if (this.callState === 'idle') {
                        return;
                    }
                    const played = await this.playSpeechChunk(chunk, options);
                    if (! played) {
                        this.callHint = this.labels.call?.tts_fallback || '';
                        return;
                    }
                    if (this.callState === 'listening') {
                        // Interrupted via barge-in.
                        return;
                    }
                }
            },
            async playSpeechChunk(text, options = {}) {
                if (! speechEndpoint) {
                    return false;
                }

                this.stopVad();
                this.stopCallAudio();
                this.callSpeakStartedAt = Date.now();

                try {
                    const response = await fetch(speechEndpoint, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            Accept: 'audio/mpeg, application/json',
                            'X-CSRF-TOKEN': csrf,
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                        body: JSON.stringify({ text }),
                    });

                    if (! response.ok) {
                        return false;
                    }

                    const blob = await response.blob();
                    const url = URL.createObjectURL(blob);
                    const audio = new Audio(url);
                    audio.muted = ! this.callSpeakerOn;
                    this.callAudio = audio;
                    if (! options.skipVad) {
                        this.startVadForBargeIn();
                    }

                    await new Promise((resolve) => {
                        audio.onended = () => resolve(true);
                        audio.onerror = () => resolve(false);
                        audio.play().catch(() => resolve(false));
                    });

                    URL.revokeObjectURL(url);
                    this.stopVad();
                    this.callAudio = null;
                    return true;
                } catch {
                    this.stopVad();
                    this.callAudio = null;
                    return false;
                }
            },
            stopCallAudio() {
                if (! this.callAudio) {
                    return;
                }
                try {
                    this.callAudio.pause();
                    this.callAudio.src = '';
                } catch {
                    // Already stopped.
                }
                this.callAudio = null;
                if (window.speechSynthesis) {
                    window.speechSynthesis.cancel();
                }
            },
            startVadForBargeIn() {
                this.stopVad();
                const recognition = this.recognitionFactory();
                if (! recognition) {
                    return;
                }
                recognition.continuous = true;
                recognition.interimResults = true;
                this.callVadRecognition = recognition;

                recognition.onresult = (event) => {
                    if (this.callState !== 'speaking') {
                        return;
                    }
                    if (Date.now() - this.callSpeakStartedAt < 400) {
                        return;
                    }
                    let heard = '';
                    for (let i = event.resultIndex; i < event.results.length; i += 1) {
                        heard += event.results[i][0]?.transcript || '';
                    }
                    if (heard.trim()) {
                        this.interruptSpeaking();
                    }
                };

                recognition.onerror = () => {};
                try {
                    recognition.start();
                } catch {
                    this.callVadRecognition = null;
                }
            },
            stopVad() {
                if (! this.callVadRecognition) {
                    return;
                }
                try {
                    this.callVadRecognition.onresult = null;
                    this.callVadRecognition.abort();
                } catch {
                    // Already stopped.
                }
                this.callVadRecognition = null;
            },
            interruptSpeaking() {
                this.stopCallAudio();
                this.stopVad();
                this.callState = 'listening';
                this.listenForTurn();
            },
            };
        });
    });
</script>
