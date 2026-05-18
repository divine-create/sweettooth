<div class="space-y-6">
    <!-- Header -->
    <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-md p-6">
        <h1 class="text-3xl font-bold text-zinc-900 dark:text-white mb-2">GL Posting Status Monitor</h1>
        <p class="text-zinc-600 dark:text-zinc-400">Monitor and manage GL posting status for transactions</p>
    </div>

    <!-- Flash Messages -->
    @if(session('message'))
        <div class="bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-lg px-4 py-3 text-green-700 dark:text-green-300 text-sm font-medium">
            {{ session('message') }}
        </div>
    @endif
    @if(session('error'))
        <div class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg px-4 py-3 text-red-700 dark:text-red-300 text-sm font-medium">
            {{ session('error') }}
        </div>
    @endif

    <!-- Stats Cards -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-md p-6 border-l-4 border-blue-500">
            <p class="text-zinc-600 dark:text-zinc-400 text-sm font-semibold mb-2">TOTAL</p>
            <p class="text-3xl font-bold text-blue-600 dark:text-blue-400">{{ $stats['total'] ?? 0 }}</p>
        </div>
        <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-md p-6 border-l-4 border-green-500">
            <p class="text-zinc-600 dark:text-zinc-400 text-sm font-semibold mb-2">POSTED</p>
            <p class="text-3xl font-bold text-green-600 dark:text-green-400">{{ $stats['posted'] ?? 0 }}</p>
        </div>
        <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-md p-6 border-l-4 border-yellow-500">
            <p class="text-zinc-600 dark:text-zinc-400 text-sm font-semibold mb-2">PENDING</p>
            <p class="text-3xl font-bold text-yellow-600 dark:text-yellow-400">{{ $stats['pending'] ?? 0 }}</p>
        </div>
        <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-md p-6 border-l-4 border-red-500">
            <p class="text-zinc-600 dark:text-zinc-400 text-sm font-semibold mb-2">FAILED</p>
            <p class="text-3xl font-bold text-red-600 dark:text-red-400">{{ $stats['failed'] ?? 0 }}</p>
        </div>
    </div>

    <!-- Filters -->
    <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-md p-6">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-zinc-900 dark:text-white mb-2">Transaction Type</label>
                <select 
                    wire:change="changeTransactionType($event.target.value)"
                    wire:model="transactionType"
                    class="w-full px-4 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-indigo-500"
                >
                    <option value="sales">Sales</option>
                    <option value="purchases">Purchases</option>
                    <option value="payments">Payments</option>
                    <option value="adjustments">Adjustments</option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-zinc-900 dark:text-white mb-2">Status</label>
                <select 
                    wire:change="changeStatus($event.target.value)"
                    wire:model="status"
                    class="w-full px-4 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-indigo-500"
                >
                    <option value="all">All</option>
                    <option value="posted">Posted</option>
                    <option value="pending">Pending</option>
                    <option value="failed">Failed</option>
                </select>
            </div>
        </div>
    </div>

    <!-- Bulk Actions -->
    @if(($stats['failed'] ?? 0) > 0)
        <div class="flex items-center justify-between bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg px-4 py-3">
            <span class="text-sm text-red-700 dark:text-red-300 font-medium">
                {{ $stats['failed'] }} failed transaction(s) need attention
            </span>
            <button wire:click="retryAllFailed"
                    wire:confirm="Retry all {{ $stats['failed'] }} failed {{ $transactionType }} transactions?"
                    class="px-4 py-1.5 bg-red-600 hover:bg-red-700 text-white text-sm font-semibold rounded-lg flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                </svg>
                Retry All Failed ({{ $stats['failed'] }})
            </button>
        </div>
    @endif

    <!-- Transactions Table -->
    <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-md overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-zinc-50 dark:bg-zinc-700/50 border-b border-zinc-200 dark:border-zinc-700">
                    <tr>
                        <th class="px-6 py-3 text-left font-semibold text-zinc-900 dark:text-white">Transaction ID</th>
                        <th class="px-6 py-3 text-left font-semibold text-zinc-900 dark:text-white">Type</th>
                        <th class="px-6 py-3 text-left font-semibold text-zinc-900 dark:text-white">Amount</th>
                        <th class="px-6 py-3 text-left font-semibold text-zinc-900 dark:text-white">Status</th>
                        <th class="px-6 py-3 text-left font-semibold text-zinc-900 dark:text-white">Date</th>
                        <th class="px-6 py-3 text-center font-semibold text-zinc-900 dark:text-white">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                    @forelse ($transactions as $transaction)
                        <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-700/30 transition">
                            <td class="px-6 py-4 text-sm text-zinc-900 dark:text-white">{{ $transaction->id }}</td>
                            <td class="px-6 py-4 text-sm text-zinc-900 dark:text-white">{{ ucfirst($transactionType) }}</td>
                            <td class="px-6 py-4 text-sm font-medium text-zinc-900 dark:text-white">
                                {{ isset($transaction->amount) ? number_format($transaction->amount, 2) : 'N/A' }}
                            </td>
                            <td class="px-6 py-4 text-sm">
                                <span class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full 
                                    @if($transaction->gl_posting_status === 'posted')
                                        bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-300
                                    @elseif($transaction->gl_posting_status === 'pending')
                                        bg-yellow-100 dark:bg-yellow-900/30 text-yellow-800 dark:text-yellow-300
                                    @else
                                        bg-red-100 dark:bg-red-900/30 text-red-800 dark:text-red-300
                                    @endif
                                ">
                                    {{ ucfirst($transaction->gl_posting_status) }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-sm text-zinc-600 dark:text-zinc-400">
                                @if(isset($transaction->sale_time))
                                    {{ $transaction->sale_time->format('M d, Y H:i') }}
                                @elseif(isset($transaction->purchase_date))
                                    {{ $transaction->purchase_date->format('M d, Y') }}
                                @elseif(isset($transaction->payment_time))
                                    {{ $transaction->payment_time->format('M d, Y H:i') }}
                                @elseif(isset($transaction->movement_date))
                                    {{ $transaction->movement_date->format('M d, Y') }}
                                @else
                                    N/A
                                @endif
                            </td>
                            <td class="px-6 py-4 text-center">
                                @if($transaction->gl_posting_status === 'failed')
                                    <button 
                                        wire:click="retryFailed('{{ $transactionType }}', {{ $transaction->id }})"
                                        class="text-indigo-600 dark:text-indigo-400 hover:text-indigo-900 dark:hover:text-indigo-300 font-medium text-sm"
                                    >
                                        Retry
                                    </button>
                                @else
                                    <span class="text-zinc-400 text-sm">—</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-8 text-center text-zinc-600 dark:text-zinc-400">
                                No transactions found
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Pagination -->
    @if ($transactions->hasPages())
        <div class="flex justify-center">
            {{ $transactions->links() }}
        </div>
    @endif
</div>
