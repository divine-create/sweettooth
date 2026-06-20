<div>
    {{-- Floating launcher --}}
    <button type="button" wire:click="$toggle('open')"
        class="fixed bottom-5 right-5 z-40 flex h-14 w-14 items-center justify-center rounded-full bg-indigo-600 text-white shadow-lg transition hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-400"
        aria-label="Open assistant">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
            <path stroke-linecap="round" stroke-linejoin="round" d="M8 10h8M8 14h5m-5 6 -4 1 1-4A8 8 0 1 1 12 20H8z" />
        </svg>
    </button>

    {{-- Panel --}}
    <div x-data x-show="$wire.open" x-cloak x-transition.opacity
        class="fixed bottom-24 right-5 z-40 flex h-[32rem] w-[22rem] flex-col overflow-hidden rounded-xl border border-gray-200 bg-white shadow-2xl dark:border-gray-700 dark:bg-gray-900">

        {{-- Header --}}
        <div class="flex items-center justify-between bg-indigo-600 px-4 py-3 text-white">
            <div class="font-semibold">Sweettooth Assistant</div>
            <div class="flex items-center gap-2">
                <button type="button" wire:click="clearChat" class="text-xs opacity-80 hover:opacity-100">Clear</button>
                <button type="button" wire:click="$set('open', false)" class="opacity-80 hover:opacity-100" aria-label="Close">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>

        {{-- Messages --}}
        <div class="flex-1 space-y-3 overflow-y-auto p-4 text-sm" x-ref="log"
            x-init="$watch('$wire.messages', () => $nextTick(() => $refs.log.scrollTop = $refs.log.scrollHeight))">
            @forelse ($messages as $message)
                <div class="flex {{ $message['role'] === 'user' ? 'justify-end' : 'justify-start' }}">
                    <div class="max-w-[85%] whitespace-pre-wrap rounded-lg px-3 py-2 {{ $message['role'] === 'user' ? 'bg-indigo-600 text-white' : 'bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-100' }}">
                        {{ $message['content'] }}
                    </div>
                </div>
            @empty
                <p class="mt-6 text-center text-gray-400">
                    Ask me about how to use the software or about your branch's data.
                </p>
            @endforelse

            @if ($thinking)
                <div class="flex justify-start">
                    <div class="rounded-lg bg-gray-100 px-3 py-2 text-gray-500 dark:bg-gray-800">…</div>
                </div>
            @endif
        </div>

        {{-- Composer --}}
        <form wire:submit="send" class="flex items-center gap-2 border-t border-gray-200 p-3 dark:border-gray-700">
            <input type="text" wire:model="draft" autocomplete="off"
                @disabled($thinking)
                placeholder="Type your question…"
                class="flex-1 rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-800 dark:text-white" />
            <button type="submit" @disabled($thinking)
                class="rounded-lg bg-indigo-600 px-3 py-2 text-white transition hover:bg-indigo-700 disabled:opacity-50">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 12h14M13 6l6 6-6 6" />
                </svg>
            </button>
        </form>
    </div>
</div>
