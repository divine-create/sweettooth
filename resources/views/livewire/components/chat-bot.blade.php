<div
    x-data="{
        open: @entangle('open'),
        pending: '',
        scrollDown() { this.$nextTick(() => { if (this.$refs.body) this.$refs.body.scrollTop = this.$refs.body.scrollHeight }) },
    }"
    x-on:chat-updated.window="pending = ''; scrollDown()"
>
    {{-- Launcher --}}
    <button type="button" class="cb-launcher" x-show="!open" x-transition
        @click="open = true; scrollDown(); $nextTick(() => $refs.input && $refs.input.focus())"
        aria-label="Open assistant">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.7">
            <path stroke-linecap="round" stroke-linejoin="round" d="M7.5 8.5h9m-9 3.5h6M21 12a8.5 8.5 0 0 1-12.4 7.55L3.5 21l1.05-4.36A8.5 8.5 0 1 1 21 12Z" />
        </svg>
        <span class="cb-ping"></span>
    </button>

    {{-- Panel --}}
    <div class="cb-panel" x-show="open" x-cloak x-transition
        @keydown.escape.window="open = false">

        {{-- Header --}}
        <div class="cb-header">
            <div class="cb-header-avatar">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.7">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M7.5 8.5h9m-9 3.5h6M21 12a8.5 8.5 0 0 1-12.4 7.55L3.5 21l1.05-4.36A8.5 8.5 0 1 1 21 12Z" />
                </svg>
            </div>
            <div class="cb-header-title">
                <div class="cb-header-name">Sweettooth Assistant</div>
                <div class="cb-status"><span class="cb-status-dot"></span> Online</div>
            </div>
            @if ($messages)
                <button type="button" class="cb-icon-btn" wire:click="clearChat" title="Clear conversation">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.7">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 7h12M9 7V5h6v2m-7 0 1 12h6l1-12" />
                    </svg>
                </button>
            @endif
            <button type="button" class="cb-icon-btn" @click="open = false" title="Close">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                </svg>
            </button>
        </div>

        {{-- Messages --}}
        <div x-ref="body" class="cb-body">
            @forelse ($messages as $message)
                @if ($message['role'] === 'user')
                    <div class="cb-row cb-row-user">
                        <div class="cb-bubble-user">{{ $message['content'] }}</div>
                    </div>
                @else
                    <div class="cb-row">
                        <div class="cb-avatar">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.7">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M7.5 8.5h9m-9 3.5h6M21 12a8.5 8.5 0 0 1-12.4 7.55L3.5 21l1.05-4.36A8.5 8.5 0 1 1 21 12Z" />
                            </svg>
                        </div>
                        <div class="cb-bubble-bot cb-md">{!! $this->md($message['content']) !!}</div>
                    </div>
                @endif
            @empty
                <div class="cb-empty">
                    <div class="cb-empty-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.6">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M7.5 8.5h9m-9 3.5h6M21 12a8.5 8.5 0 0 1-12.4 7.55L3.5 21l1.05-4.36A8.5 8.5 0 1 1 21 12Z" />
                        </svg>
                    </div>
                    <p class="cb-empty-title">How can I help?</p>
                    <p class="cb-empty-sub">Ask about your branch data or how to use the app.</p>
                    <div class="cb-suggestions">
                        @foreach ($suggestions as $s)
                            <button type="button" class="cb-suggestion" wire:click="suggest({{ $loop->index }})">{{ $s }}</button>
                        @endforeach
                    </div>
                </div>
            @endforelse

            {{-- Optimistic echo of the message being sent --}}
            <div x-show="pending" class="cb-row cb-row-user">
                <div class="cb-bubble-user" x-text="pending"></div>
            </div>

            {{-- Typing indicator --}}
            <div class="cb-row cb-typing" wire:loading.flex wire:target="send,suggest">
                <div class="cb-avatar">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.7">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M7.5 8.5h9m-9 3.5h6M21 12a8.5 8.5 0 0 1-12.4 7.55L3.5 21l1.05-4.36A8.5 8.5 0 1 1 21 12Z" />
                    </svg>
                </div>
                <div class="cb-bubble-bot"><span class="cb-dots"><span class="cb-dot"></span><span class="cb-dot"></span><span class="cb-dot"></span></span></div>
            </div>
        </div>

        {{-- Composer --}}
        <form class="cb-composer" wire:submit="send"
            @submit="if ($refs.input.value.trim()) { pending = $refs.input.value; scrollDown() }">
            <input type="text" class="cb-input" wire:model="draft" autocomplete="off" x-ref="input"
                @disabled($thinking) placeholder="Ask anything…" />
            <button type="submit" class="cb-send" @disabled($thinking)>
                <svg wire:loading.remove wire:target="send,suggest" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m5 12 14-7-5 16-3.5-6.5L5 12Z" />
                </svg>
                <svg wire:loading wire:target="send,suggest" class="cb-spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" style="opacity:.25"></circle>
                    <path fill="currentColor" style="opacity:.75" d="M4 12a8 8 0 0 1 8-8v4a4 4 0 0 0-4 4H4z"></path>
                </svg>
            </button>
        </form>
    </div>

    @once
        <style>
            [x-cloak] { display: none !important; }

            .cb-launcher { position: fixed; bottom: 1.25rem; right: 1.25rem; z-index: 2147483000;
                height: 3.5rem; width: 3.5rem; display: flex; align-items: center; justify-content: center;
                border: none; border-radius: 9999px; cursor: pointer; color: #fff;
                background: linear-gradient(135deg, #6366f1, #7c3aed);
                box-shadow: 0 12px 28px -6px rgba(79, 70, 229, .55); transition: transform .15s, box-shadow .15s; }
            .cb-launcher:hover { transform: scale(1.06); box-shadow: 0 16px 34px -6px rgba(79, 70, 229, .65); }
            .cb-launcher svg { height: 1.55rem; width: 1.55rem; }
            .cb-ping { position: absolute; top: 0; right: 0; height: .8rem; width: .8rem; border-radius: 9999px;
                background: #10b981; box-shadow: 0 0 0 2px #18181b; }
            .cb-ping::after { content: ''; position: absolute; inset: 0; border-radius: 9999px;
                background: #34d399; animation: cb-ping 1.4s cubic-bezier(0,0,.2,1) infinite; }
            @keyframes cb-ping { 75%, 100% { transform: scale(2); opacity: 0; } }

            .cb-panel { position: fixed; bottom: 1.25rem; right: 1.25rem; z-index: 2147483000;
                display: flex; flex-direction: column;
                width: min(24rem, calc(100vw - 2.5rem)); height: min(34rem, calc(100vh - 2.5rem));
                background: #18181b; border: 1px solid #2a2a30; border-radius: 1rem;
                box-shadow: 0 25px 50px -12px rgba(0, 0, 0, .6); overflow: hidden;
                font-family: inherit; color: #e4e4e7; }

            .cb-header { display: flex; align-items: center; gap: .75rem; padding: .75rem 1rem;
                background: linear-gradient(90deg, #4f46e5, #7c3aed); color: #fff; }
            .cb-header-avatar { height: 2.25rem; width: 2.25rem; flex: none; display: flex; align-items: center;
                justify-content: center; border-radius: 9999px; background: rgba(255, 255, 255, .16); }
            .cb-header-avatar svg { height: 1.25rem; width: 1.25rem; }
            .cb-header-title { flex: 1; min-width: 0; }
            .cb-header-name { font-size: .875rem; font-weight: 600; line-height: 1.15; }
            .cb-status { display: flex; align-items: center; gap: .35rem; font-size: 11px; color: rgba(255, 255, 255, .82); }
            .cb-status-dot { height: 6px; width: 6px; border-radius: 9999px; background: #6ee7b7; }
            .cb-icon-btn { height: 30px; width: 30px; flex: none; display: flex; align-items: center; justify-content: center;
                border: none; background: transparent; color: #fff; border-radius: .5rem; cursor: pointer; transition: background .15s; }
            .cb-icon-btn:hover { background: rgba(255, 255, 255, .18); }
            .cb-icon-btn svg { height: 18px; width: 18px; }

            .cb-body { flex: 1; overflow-y: auto; padding: 1rem; display: flex; flex-direction: column; gap: 1rem;
                background: #1f1f24; }
            .cb-row { display: flex; gap: .5rem; align-items: flex-start; }
            .cb-row-user { justify-content: flex-end; }
            .cb-avatar { margin-top: 2px; height: 1.75rem; width: 1.75rem; flex: none; display: flex; align-items: center;
                justify-content: center; border-radius: 9999px; color: #fff; background: linear-gradient(135deg, #6366f1, #7c3aed); }
            .cb-avatar svg { height: 1rem; width: 1rem; }
            .cb-bubble-user { max-width: 85%; background: #4f46e5; color: #fff; padding: .5rem .875rem;
                border-radius: 1rem; border-bottom-right-radius: .25rem; font-size: .875rem; line-height: 1.45;
                box-shadow: 0 1px 2px rgba(0, 0, 0, .25); white-space: pre-wrap; word-break: break-word; }
            .cb-bubble-bot { max-width: 85%; background: #2a2a30; color: #f4f4f5; padding: .5rem .875rem;
                border-radius: 1rem; border-bottom-left-radius: .25rem; font-size: .875rem; line-height: 1.5;
                box-shadow: 0 1px 2px rgba(0, 0, 0, .25); word-break: break-word; }

            .cb-empty { margin: auto 0; display: flex; flex-direction: column; align-items: center; text-align: center; padding: .5rem; }
            .cb-empty-icon { height: 3rem; width: 3rem; display: flex; align-items: center; justify-content: center;
                border-radius: .9rem; color: #fff; background: linear-gradient(135deg, #6366f1, #7c3aed);
                box-shadow: 0 10px 24px -6px rgba(79, 70, 229, .5); margin-bottom: .75rem; }
            .cb-empty-icon svg { height: 1.5rem; width: 1.5rem; }
            .cb-empty-title { font-size: .9rem; font-weight: 600; color: #f4f4f5; margin: 0; }
            .cb-empty-sub { font-size: .75rem; color: #a1a1aa; margin: .25rem 0 0; }
            .cb-suggestions { margin-top: 1rem; display: flex; flex-direction: column; gap: .5rem; width: 100%; }
            .cb-suggestion { width: 100%; text-align: left; font-size: .75rem; color: #d4d4d8; background: #2a2a30;
                border: 1px solid #3f3f46; border-radius: .75rem; padding: .55rem .75rem; cursor: pointer; transition: .15s; }
            .cb-suggestion:hover { border-color: #6366f1; background: #312e81; color: #fff; }

            .cb-typing { display: none; }
            .cb-dots { display: inline-flex; gap: 4px; padding: 2px 0; }
            .cb-dot { width: 6px; height: 6px; border-radius: 9999px; background: #a1a1aa; animation: cb-bounce 1.2s infinite ease-in-out; }
            .cb-dot:nth-child(2) { animation-delay: .15s; }
            .cb-dot:nth-child(3) { animation-delay: .3s; }
            @keyframes cb-bounce { 0%, 80%, 100% { transform: translateY(0); opacity: .5; } 40% { transform: translateY(-4px); opacity: 1; } }

            .cb-composer { display: flex; align-items: center; gap: .5rem; padding: .75rem;
                border-top: 1px solid #2a2a30; background: #18181b; }
            .cb-input { flex: 1; min-width: 0; background: #2a2a30; border: 1px solid #3f3f46; border-radius: .75rem;
                padding: .625rem .875rem; font-size: .875rem; color: #fff; outline: none; transition: .15s; }
            .cb-input::placeholder { color: #a1a1aa; }
            .cb-input:focus { border-color: #6366f1; box-shadow: 0 0 0 2px rgba(99, 102, 241, .3); }
            .cb-input:disabled { opacity: .6; }
            .cb-send { height: 2.5rem; width: 2.5rem; flex: none; display: flex; align-items: center; justify-content: center;
                border: none; border-radius: .75rem; background: #4f46e5; color: #fff; cursor: pointer; transition: background .15s; }
            .cb-send:hover { background: #4338ca; }
            .cb-send:disabled { opacity: .5; cursor: not-allowed; }
            .cb-send svg { height: 1.25rem; width: 1.25rem; }
            .cb-spin { animation: cb-spin 1s linear infinite; }
            @keyframes cb-spin { to { transform: rotate(360deg); } }

            /* Markdown inside assistant bubbles */
            .cb-md > :first-child { margin-top: 0; }
            .cb-md > :last-child { margin-bottom: 0; }
            .cb-md p { margin: .35rem 0; }
            .cb-md ul, .cb-md ol { margin: .35rem 0; padding-left: 1.15rem; }
            .cb-md ul { list-style: disc; }
            .cb-md ol { list-style: decimal; }
            .cb-md li { margin: .15rem 0; }
            .cb-md strong { font-weight: 600; color: #fff; }
            .cb-md h1, .cb-md h2, .cb-md h3 { font-weight: 600; margin: .5rem 0 .25rem; font-size: .95rem; color: #fff; }
            .cb-md a { color: #a5b4fc; text-decoration: underline; }
            .cb-md code { background: rgba(255, 255, 255, .12); padding: .05rem .3rem; border-radius: .3rem; font-size: .8rem; }
            .cb-md pre { background: rgba(0, 0, 0, .35); padding: .5rem; border-radius: .5rem; overflow-x: auto; margin: .35rem 0; }
            .cb-md pre code { background: transparent; padding: 0; }
            .cb-md table { width: 100%; border-collapse: collapse; margin: .35rem 0; font-size: .8rem; }
            .cb-md th, .cb-md td { border: 1px solid rgba(113, 113, 122, .4); padding: .25rem .4rem; text-align: left; }
        </style>
    @endonce
</div>
