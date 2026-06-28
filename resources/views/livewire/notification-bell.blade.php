<div wire:poll.30s="poll">
    <flux:dropdown position="bottom" align="end">
        <button type="button" class="relative inline-flex items-center justify-center w-9 h-9 rounded-full hover:bg-zinc-100 dark:hover:bg-zinc-800 transition-colors">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-zinc-600 dark:text-zinc-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
            </svg>
            @if($unreadCount > 0)
                <span class="absolute -top-0.5 -right-0.5 flex items-center justify-center min-w-[18px] h-[18px] px-1 rounded-full bg-red-500 text-white text-[10px] font-bold leading-none">
                    {{ $unreadCount > 99 ? '99+' : $unreadCount }}
                </span>
            @endif
        </button>

        <flux:menu class="w-80 max-h-[440px] overflow-y-auto">
            <div class="flex items-center justify-between px-3 py-2 border-b border-zinc-100 dark:border-zinc-700">
                <span class="text-sm font-semibold text-zinc-800 dark:text-zinc-100">Notifications</span>
                <div class="flex items-center gap-3">
                    <button type="button"
                            x-data="{ muted: JSON.parse(localStorage.getItem('notif_sound_muted') || 'false') }"
                            x-on:click="muted = !muted; localStorage.setItem('notif_sound_muted', JSON.stringify(muted))"
                            class="text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-200"
                            x-bind:title="muted ? 'Sound off — click to enable' : 'Sound on — click to mute'">
                        <svg x-show="!muted" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.536 8.464a5 5 0 010 7.072M17.95 6.05a8 8 0 010 11.9M5 9v6h4l5 4V5L9 9H5z"/></svg>
                        <svg x-show="muted" x-cloak class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 9v6h4l5 4V5L9 9H5z M17 9l4 4m0-4l-4 4"/></svg>
                    </button>
                    @if($unreadCount > 0)
                        <button wire:click="markAllRead" type="button" class="text-xs text-blue-600 dark:text-blue-400 hover:underline">
                            Mark all read
                        </button>
                    @endif
                </div>
            </div>

            @forelse($notifications as $notification)
                @php
                    $data = $notification->data;
                    $isUnread = is_null($notification->read_at);
                    $icon = match($data['type'] ?? '') {
                        'approval_request'         => 'clipboard-document-check',
                        'approval_status_changed'  => 'check-circle',
                        'gl_posting_failed'        => 'exclamation-triangle',
                        'production_request_status' => 'cog-6-tooth',
                        'stock_low'                => 'cube',
                        'shift_ending_warning'     => 'clock',
                        'shift_completed'          => 'check',
                        default                    => 'bell',
                    };
                    $iconColor = match($data['type'] ?? '') {
                        'gl_posting_failed'        => 'text-red-500',
                        'stock_low'                => 'text-amber-500',
                        'approval_request'         => 'text-blue-500',
                        'approval_status_changed'  => 'text-green-500',
                        default                    => 'text-zinc-500',
                    };
                @endphp
                <div
                    wire:click="markRead('{{ $notification->id }}')"
                    class="flex items-start gap-3 px-3 py-2.5 cursor-pointer hover:bg-zinc-50 dark:hover:bg-zinc-800 transition-colors {{ $isUnread ? 'bg-blue-50/60 dark:bg-blue-900/20' : '' }}"
                    @if(!empty($data['action_url']))
                        onclick="window.location.href='{{ $data['action_url'] }}'"
                    @endif
                >
                    <div class="mt-0.5 flex-shrink-0">
                        <flux:icon :icon="$icon" class="w-4 h-4 {{ $iconColor }}" />
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-xs font-medium text-zinc-800 dark:text-zinc-100 leading-snug truncate">
                            {{ $data['message'] ?? 'Notification' }}
                        </p>
                        <p class="text-[11px] text-zinc-500 dark:text-zinc-400 mt-0.5">
                            {{ $notification->created_at->diffForHumans() }}
                        </p>
                    </div>
                    @if($isUnread)
                        <div class="mt-1.5 flex-shrink-0 w-1.5 h-1.5 rounded-full bg-blue-500"></div>
                    @endif
                </div>
            @empty
                <div class="flex flex-col items-center justify-center py-8 text-zinc-400 dark:text-zinc-500">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 mb-2 opacity-50" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                    </svg>
                    <span class="text-xs">No notifications yet</span>
                </div>
            @endforelse
        </flux:menu>
    </flux:dropdown>

    @script
    <script>
        // Play a short chime whenever the server signals a newly-arrived notification.
        $wire.on('notification-received', () => {
            if (JSON.parse(localStorage.getItem('notif_sound_muted') || 'false')) return;
            try {
                const Ctx = window.AudioContext || window.webkitAudioContext;
                if (!Ctx) return;
                const ctx = new Ctx();
                if (ctx.state === 'suspended') { ctx.resume(); }
                const note = (freq, start, dur) => {
                    const o = ctx.createOscillator(), g = ctx.createGain();
                    o.connect(g); g.connect(ctx.destination);
                    o.type = 'sine'; o.frequency.value = freq;
                    const t = ctx.currentTime + start;
                    g.gain.setValueAtTime(0.0001, t);
                    g.gain.exponentialRampToValueAtTime(0.22, t + 0.02);
                    g.gain.exponentialRampToValueAtTime(0.0001, t + dur);
                    o.start(t); o.stop(t + dur + 0.02);
                };
                note(880, 0, 0.18);
                note(1175, 0.15, 0.25);
            } catch (e) {}
        });
    </script>
    @endscript
</div>
