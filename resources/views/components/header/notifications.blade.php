<div class="flex items-center gap-2 mr-2 relative">

    @php
        $initialNotifications = collect($notifications)->map(function ($n) {
            // Already-mapped array (from layouts/header.blade.php) OR raw DB row.
            if (is_array($n)) {
                return [
                    'id'           => $n['id'],
                    'title'        => $n['title'] ?? 'Notification',
                    'message'      => $n['message'] ?? '',
                    'type'         => $n['type'] ?? 'announcement',
                    'reference_id' => $n['reference_id'] ?? 0,
                    'term_id'      => $n['term_id'] ?? null,
                    'read'         => (bool) ($n['read'] ?? false),
                    'urgent'       => (bool) ($n['urgent'] ?? false),
                    'days_left'    => $n['days_left'] ?? null,
                ];
            }
            $d = json_decode($n->data);
            return [
                'id'           => $n->id,
                'title'        => $d->title ?? 'Notification',
                'message'      => $d->message ?? '',
                'type'         => $d->type ?? 'announcement',
                'reference_id' => $d->reference_id ?? 0,
                'term_id'      => $d->term_id ?? null,
                'read'         => !is_null($n->read_at),
                'urgent'       => false,
            ];
        })->values();
    @endphp

    <div x-data="notificationBell({{ (int) $notifCount }}, {{ Illuminate\Support\Js::from($initialNotifications->filter(fn($n) => !$n['read'])->values()) }})" @click.away="open = false" class="relative">

        <button @click="open = !open" class="p-2 rounded-lg {{ $hoverClass }} transition relative"
                :class="hasUrgent ? 'animate-pulse' : ''">
            <i data-lucide="bell" class="w-5 h-5" :class="hasUrgent ? 'text-red-500' : ''"></i>

            <span x-show="count > 0" x-text="count > 99 ? '99+' : count"
                  class="absolute -top-1 -right-1 text-white text-[10px] px-1.5 rounded-full"
                  :class="hasUrgent ? 'bg-red-600 animate-pulse' : 'bg-red-500'"
                  style="display:none"></span>
        </button>

        <div x-show="open"
            class="absolute right-0 mt-2 w-80 bg-white rounded-xl shadow-xl border z-50 text-slate-700"
            style="display:none">

            <div class="p-3 border-b font-bold text-sm flex items-center justify-between">
                <span>Notifications</span>
                <span class="text-xs text-gray-400" x-show="count > 0" x-text="count + ' unread'"></span>
            </div>

            <div class="max-h-80 overflow-y-auto">

                <template x-for="notif in notifications" :key="notif.id">
                    <div class="p-3 border-b hover:bg-slate-50 cursor-pointer text-sm"
                         :class="{
                            'bg-indigo-50/50': !notif.read && !notif.urgent,
                            'bg-red-50 border-l-4 border-l-red-500 animate-pulse': notif.urgent,
                         }"
                         @click="handleClick(notif)">
                        <div class="font-semibold flex items-center gap-1"
                             :class="notif.urgent ? 'text-red-700' : ''">
                            <span x-text="notif.title"></span>
                            <span x-show="notif.urgent && notif.days_left >= 0"
                                  class="ml-auto text-[10px] bg-red-600 text-white px-1.5 py-0.5 rounded-full"
                                  x-text="notif.days_left === 0 ? 'Today' : notif.days_left + 'd left'"></span>
                        </div>
                        <div class="text-xs text-gray-500" x-text="notif.message"></div>
                    </div>
                </template>

                <template x-if="notifications.length === 0">
                    <div class="p-4 text-center text-xs text-gray-400">
                        No notifications
                    </div>
                </template>

            </div>
        </div>
    </div>

    {{-- Help --}}
    <button class="p-2 rounded-lg {{ $hoverClass }} transition">
        <i data-lucide="help-circle" class="w-5 h-5"></i>
    </button>

</div>

<script>
window.notificationBell = function(initialCount, initialList) {
    return {
        open: false,
        count: initialCount,
        notifications: initialList,
        pollMs: 30000,
        pollTimer: null,
        get hasUrgent() {
            return this.notifications.some(n => n.urgent);
        },
        init() {
            this.pollTimer = setInterval(() => this.poll(), this.pollMs);
            document.addEventListener('visibilitychange', () => {
                if (!document.hidden) this.poll();
            });

            // 🔴 Real-time via Laravel Reverb
            const userId = {{ auth()->id() ?? 'null' }};
            if (userId && window.Echo) {
                try {
                    window.Echo.private('App.Models.User.' + userId)
                        .notification((n) => {
                            this.notifications = [{
                                id: n.id,
                                title: n.title,
                                message: n.message,
                                type: n.type,
                                reference_id: n.reference_id,
                                term_id: n.term_id ?? null,
                                read: false,
                                urgent: n.urgent ?? false,
                                days_left: n.days_left ?? null,
                            }, ...this.notifications].slice(0, 10);
                            this.count++;
                            this.chime();
                        });
                } catch (e) {
                    console.warn('Echo subscribe failed', e);
                }
            }
        },
        async poll() {
            try {
                const r = await fetch('{{ route('notifications.feed') }}', {
                    headers: { 'Accept': 'application/json' },
                    credentials: 'same-origin',
                });
                if (!r.ok) return;
                const data = await r.json();
                const prevCount = this.count;
                this.count = data.count;
                this.notifications = (data.notifications || []).filter(n => !n.read);
                if (data.count > prevCount) {
                    this.chime();
                }
            } catch (e) { /* ignore */ }
        },
        chime() {
            try {
                const ctx = new (window.AudioContext || window.webkitAudioContext)();
                const o = ctx.createOscillator();
                const g = ctx.createGain();
                o.connect(g); g.connect(ctx.destination);
                o.type = 'sine'; o.frequency.value = 880;
                g.gain.setValueAtTime(0.05, ctx.currentTime);
                o.start(); o.stop(ctx.currentTime + 0.15);
            } catch (e) {}
        },
        handleClick(notif) {
            // Enrollment "is open" notifications are sticky — they stay in the
            // bell until the student's StudentEnrollment becomes "enrolled".
            // Just open the form; the model hook clears the bell row when the
            // registrar finalises the enrolment.
            if (notif.type === 'enrollment') {
                window.location.href = "{{ url('/apply') }}" + '/' + notif.term_id;
                return;
            }

            // "term_created" bells are also sticky for the admission manager:
            // they remain in the bell until the admission manager actually
            // creates an EnrollmentSetting for the term. Just navigate.
            if (notif.type === 'term_created') {
                window.location.href = '/admission/enrollment-settings';
                return;
            }

            // Virtual 2FA nudge (no DB row): deep-link to the Security tab.
            if (notif.type === 'security_2fa') {
                window.location.href = "{{ route('settings.profile') }}" + '#security';
                return;
            }

            // Virtual "test open" bell (no DB row): sticky until the student submits.
            // Just open the start page — the row clears itself once submitted.
            if (notif.type === 'assessment_open') {
                window.location.href = "{{ url('/student/assessments') }}" + '/' + notif.reference_id + '/start';
                return;
            }

            fetch(`/notifications/${notif.id}/read`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Content-Type': 'application/json'
                }
            }).then(() => {
                // Remove from list so the bell only shows unread items
                this.notifications = this.notifications.filter(x => x.id !== notif.id);
                if (!notif.read && this.count > 0) this.count--;

                if (notif.type === 'chat_message') {
                    const target = '/communication/chat#thread-' + notif.reference_id;
                    if (window.location.pathname === '/communication/chat') {
                        window.location.hash = 'thread-' + notif.reference_id;
                        window.dispatchEvent(new CustomEvent('open-chat-thread', { detail: { id: Number(notif.reference_id) } }));
                    } else {
                        window.location.href = target;
                    }
                    return;
                }
                if (typeof handleNotification === 'function') {
                    handleNotification(notif.type, notif.reference_id);
                }
            });
        },
    };
}
</script>