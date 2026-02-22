<div class="space-y-6">
    <div class="rounded-2xl border border-zinc-200 bg-gradient-to-br from-white via-white to-zinc-50 p-6 shadow-sm dark:border-zinc-800 dark:from-zinc-900 dark:via-zinc-900 dark:to-zinc-900/60">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-zinc-500">Accounting</p>
                <h1 class="mt-2 text-3xl font-semibold text-zinc-900 dark:text-white">Transactions</h1>
                <p class="mt-2 text-sm text-zinc-600 dark:text-zinc-400">Review sales, purchases, payments, and inventory adjustments.</p>
            </div>
            <div class="flex flex-wrap items-center gap-2">
                <div class="flex items-center gap-2 rounded-full border border-zinc-200 bg-white px-3 py-2 text-sm shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
                    <span class="text-zinc-400">Search</span>
                    <input class="w-56 border-none bg-transparent p-0 text-sm text-zinc-900 placeholder-zinc-400 focus:outline-none dark:text-white" type="text" placeholder="ID, reference, supplier" wire:model.debounce.300ms="search" />
                </div>
                <label class="text-xs font-semibold uppercase tracking-wide text-zinc-500">Status</label>
                <select class="rounded-full border border-zinc-200 bg-white px-3 py-2 text-xs font-semibold uppercase tracking-wide text-zinc-700 shadow-sm dark:border-zinc-800 dark:bg-zinc-900 dark:text-zinc-200" wire:change="changeStatus($event.target.value)">
                    <option value="all" @selected($status === 'all')>All</option>
                    <option value="pending" @selected($status === 'pending')>Pending</option>
                    <option value="posted" @selected($status === 'posted')>Posted</option>
                    <option value="failed" @selected($status === 'failed')>Failed</option>
                </select>
            </div>
        </div>
    </div>

    @if (session()->has('message'))
        <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700 dark:border-emerald-900/60 dark:bg-emerald-900/30 dark:text-emerald-200">
            {{ session('message') }}
        </div>
    @endif

    @if (session()->has('error'))
        <div class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700 dark:border-rose-900/60 dark:bg-rose-900/30 dark:text-rose-200">
            {{ session('error') }}
        </div>
    @endif

    <div class="flex flex-wrap gap-2">
        <button class="rounded-full border px-4 py-2 text-xs font-semibold uppercase tracking-wide {{ $transactionType === 'sales' ? 'border-zinc-900 bg-zinc-900 text-white' : 'border-zinc-200 bg-white text-zinc-700 dark:border-zinc-800 dark:bg-zinc-900 dark:text-zinc-200' }}"
                wire:click="changeTransactionType('sales')">Sales</button>
        <button class="rounded-full border px-4 py-2 text-xs font-semibold uppercase tracking-wide {{ $transactionType === 'purchases' ? 'border-zinc-900 bg-zinc-900 text-white' : 'border-zinc-200 bg-white text-zinc-700 dark:border-zinc-800 dark:bg-zinc-900 dark:text-zinc-200' }}"
                wire:click="changeTransactionType('purchases')">Purchases</button>
        <button class="rounded-full border px-4 py-2 text-xs font-semibold uppercase tracking-wide {{ $transactionType === 'payments' ? 'border-zinc-900 bg-zinc-900 text-white' : 'border-zinc-200 bg-white text-zinc-700 dark:border-zinc-800 dark:bg-zinc-900 dark:text-zinc-200' }}"
                wire:click="changeTransactionType('payments')">Payments</button>
        <button class="rounded-full border px-4 py-2 text-xs font-semibold uppercase tracking-wide {{ $transactionType === 'adjustments' ? 'border-zinc-900 bg-zinc-900 text-white' : 'border-zinc-200 bg-white text-zinc-700 dark:border-zinc-800 dark:bg-zinc-900 dark:text-zinc-200' }}"
                wire:click="changeTransactionType('adjustments')">Adjustments</button>
    </div>

    <div class="rounded-2xl border border-zinc-200 bg-white shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
        <x-table
            :$headers
            :$rows
            striped
            paginate
            persist
            :filter="['quantity' => 'quantity', 'search' => 'search']"
            :quantity="[10, 25, 50, 100]"
        >
            @interact('column_id', $row)
                <span class="font-mono text-xs text-zinc-600 dark:text-zinc-300">#{{ $row->id }}</span>
            @endinteract

            @interact('column_status', $row)
                @if (($row->display_status ?? '') === 'posted')
                    <span class="rounded-full bg-emerald-100 px-3 py-1 text-xs font-semibold text-emerald-700 dark:bg-emerald-500/20 dark:text-emerald-200">Posted</span>
                @elseif (($row->display_status ?? '') === 'failed')
                    <span class="rounded-full bg-rose-100 px-3 py-1 text-xs font-semibold text-rose-700 dark:bg-rose-500/20 dark:text-rose-200">Failed</span>
                @elseif (($row->display_status ?? '') === 'pending')
                    <span class="rounded-full bg-amber-100 px-3 py-1 text-xs font-semibold text-amber-700 dark:bg-amber-500/20 dark:text-amber-200">Pending</span>
                @else
                    <span class="rounded-full bg-zinc-100 px-3 py-1 text-xs font-semibold text-zinc-600 dark:bg-zinc-800 dark:text-zinc-300">{{ $row->display_status ?? '-' }}</span>
                @endif
            @endinteract

            @interact('column_amount', $row)
                <span class="font-semibold text-zinc-900 dark:text-white">{{ number_format($row->display_amount ?? 0, 2) }}</span>
            @endinteract

            @interact('column_date', $row)
                <span class="text-zinc-600 dark:text-zinc-300">{{ $row->display_date ?? '-' }}</span>
            @endinteract

            @interact('column_action', $row)
                <div class="flex items-center gap-2">
                    <button class="rounded-full border border-zinc-200 px-3 py-1 text-xs font-semibold text-zinc-700 hover:border-zinc-300 hover:bg-zinc-50 dark:border-zinc-800 dark:text-zinc-200 dark:hover:bg-zinc-800" type="button"
                            wire:click="viewTransaction({{ $row->id }})">
                        View
                    </button>
                    @if (($row->gl_posting_status ?? '') === 'failed')
                        <button class="rounded-full border border-blue-200 px-3 py-1 text-xs font-semibold text-blue-700 hover:border-blue-300 hover:bg-blue-50 dark:border-blue-900/50 dark:text-blue-200 dark:hover:bg-blue-900/30" type="button"
                                wire:click="retryFailed('{{ $transactionType }}', {{ $row->id }})">
                            Retry
                        </button>
                    @endif
                </div>
            @endinteract
        </x-table>
    </div>

    <div
        x-data="{ show: @entangle('showDetail') }"
        x-show="show"
        x-cloak
        class="fixed inset-0 z-50 overflow-hidden"
        @keydown.escape.window="show = false"
    >
        <div
            x-show="show"
            x-transition:enter="transition-opacity ease-linear duration-200"
            x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100"
            x-transition:leave="transition-opacity ease-linear duration-200"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            class="fixed inset-0 bg-black/40"
            @click="$wire.closeDetail()"
        ></div>

        <div
            x-show="show"
            x-transition:enter="transform transition ease-in-out duration-200"
            x-transition:enter-start="translate-y-4 opacity-0"
            x-transition:enter-end="translate-y-0 opacity-100"
            x-transition:leave="transform transition ease-in-out duration-200"
            x-transition:leave-start="translate-y-0 opacity-100"
            x-transition:leave-end="translate-y-4 opacity-0"
            class="fixed inset-x-4 top-16 mx-auto w-full max-w-2xl rounded-2xl border border-zinc-200 bg-white p-6 shadow-xl dark:border-zinc-800 dark:bg-zinc-900"
        >
            <div class="flex items-start justify-between">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wide text-zinc-500">Transaction Detail</p>
                    <h3 class="mt-2 text-lg font-semibold text-zinc-900 dark:text-white">
                        {{ strtoupper($detail['type'] ?? 'transaction') }} #{{ $detail['id'] ?? '-' }}
                    </h3>
                </div>
                <button class="rounded-full border border-zinc-200 px-3 py-1 text-xs font-semibold text-zinc-700 hover:border-zinc-300 hover:bg-zinc-50 dark:border-zinc-800 dark:text-zinc-200 dark:hover:bg-zinc-800" type="button" wire:click="closeDetail">
                    Close
                </button>
            </div>

            <div class="mt-4 grid grid-cols-1 gap-3 sm:grid-cols-2">
                <div class="rounded-xl border border-zinc-200 bg-zinc-50 p-3 text-sm dark:border-zinc-800 dark:bg-zinc-900/60">
                    <p class="text-xs uppercase tracking-wide text-zinc-500">Status</p>
                    <p class="mt-2 font-semibold text-zinc-900 dark:text-white">{{ $detail['status'] ?? '-' }}</p>
                </div>
                <div class="rounded-xl border border-zinc-200 bg-zinc-50 p-3 text-sm dark:border-zinc-800 dark:bg-zinc-900/60">
                    <p class="text-xs uppercase tracking-wide text-zinc-500">Amount</p>
                    <p class="mt-2 font-semibold text-zinc-900 dark:text-white">{{ number_format($detail['amount'] ?? 0, 2) }}</p>
                </div>
                <div class="rounded-xl border border-zinc-200 bg-zinc-50 p-3 text-sm dark:border-zinc-800 dark:bg-zinc-900/60">
                    <p class="text-xs uppercase tracking-wide text-zinc-500">Date</p>
                    <p class="mt-2 text-zinc-700 dark:text-zinc-300">{{ $detail['date'] ?? '-' }}</p>
                </div>
                <div class="rounded-xl border border-zinc-200 bg-zinc-50 p-3 text-sm dark:border-zinc-800 dark:bg-zinc-900/60">
                    <p class="text-xs uppercase tracking-wide text-zinc-500">Reference</p>
                    <p class="mt-2 text-zinc-700 dark:text-zinc-300">{{ $detail['reference'] ?? '-' }}</p>
                </div>
                <div class="rounded-xl border border-zinc-200 bg-zinc-50 p-3 text-sm dark:border-zinc-800 dark:bg-zinc-900/60">
                    <p class="text-xs uppercase tracking-wide text-zinc-500">GL Entry</p>
                    <p class="mt-2 text-zinc-700 dark:text-zinc-300">{{ $detail['gl_entry_id'] ?? '-' }}</p>
                </div>
                <div class="rounded-xl border border-zinc-200 bg-zinc-50 p-3 text-sm dark:border-zinc-800 dark:bg-zinc-900/60">
                    <p class="text-xs uppercase tracking-wide text-zinc-500">Posting Error</p>
                    <p class="mt-2 text-zinc-700 dark:text-zinc-300">{{ $detail['gl_posting_error'] ?? '-' }}</p>
                </div>
            </div>
        </div>
    </div>
</div>
