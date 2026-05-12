<div class="flex items-center gap-2 mr-2 relative">

    @php
        $initialNotifications = $notifications->map(function ($n) {
            $d = json_decode($n->data);
            return [
                'id'           => $n->id,
                'title'        => $d->title ?? 'Notification',
                'message'      => $d->message ?? '',
                'type'         => $d->type ?? 'announcement',
                'reference_id' => $d->reference_id ?? 0,
                'term_id'      => $d->term_id ?? null,
                'read'         => !is_null($n->read_at),
            ];
        })->values();
    @endphp

    <div x-data="notificationBell({{ (int) $notifCount }}, {{ Illuminate\Support\Js::from($initialNotifications->filter(fn($n) => !$n['read'])->values()) }})" class="relative">

        <button @click="open = !open" class="p-2 rounded-lg {{ $hoverClass }} transition relative">
            <i data-lucide="bell" class="w-5 h-5"></i>

            <span x-show="count > 0" x-text="count > 99 ? '99+' : count"
                  class="absolute -top-1 -right-1 bg-red-500 text-white text-[10px] px-1.5 rounded-full"
                  style="display:none"></span>
        </button>

        <div x-show="open"
            @click.away="open = false"
            class="absolute right-0 mt-2 w-80 bg-white rounded-xl shadow-xl border z-50 text-slate-700"
            style="display:none">

            <div class="p-3 border-b font-bold text-sm flex items-center justify-between">
                <span>Notifications</span>
                <span class="text-xs text-gray-400" x-show="count > 0" x-text="count + ' unread'"></span>
            </div>

            <div class="max-h-80 overflow-y-auto">

                <template x-for="notif in notifications" :key="notif.id">
                    <div class="p-3 border-b hover:bg-slate-50 cursor-pointer text-sm"
                         :class="{ 'bg-indigo-50/50': !notif.read }"
                         @click="handleClick(notif)">
                        <div class="font-semibold" x-text="notif.title"></div>
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

                if (notif.type === 'term_created') {
                    window.location.href = '/admission/enrollment-settings';
                    return;
                }
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