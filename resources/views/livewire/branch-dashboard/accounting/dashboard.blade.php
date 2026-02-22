<div class="space-y-6">
    <!-- Header -->
    <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-md p-6">
        <h1 class="text-3xl font-bold text-zinc-900 dark:text-white mb-2">Accounting Dashboard</h1>
        
        @if ($currentPeriod)
            <div class="flex items-center gap-4 text-sm text-zinc-600 dark:text-zinc-400">
                <span class="font-semibold">{{ $currentPeriod->name ?? "{$currentPeriod->month}/{$currentPeriod->year}" }}</span>
                <span class="px-3 py-1 bg-blue-100 dark:bg-blue-900/30 text-blue-800 dark:text-blue-300 rounded-full text-xs font-semibold">
                    {{ ucfirst($currentPeriod->status) }}
                </span>
                <span>{{ $currentPeriod->period_start->format('M d, Y') }} to {{ $currentPeriod->period_end->format('M d, Y') }}</span>
            </div>
        @else
            <div class="p-4 bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded">
                <p class="text-yellow-800 dark:text-yellow-300">No open accounting period. Please create one to begin posting entries.</p>
            </div>
        @endif
    </div>

    <!-- Quick Navigation -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
        <a href="{{ route('branch-dashboard.accounting.accounts') }}" class="bg-white dark:bg-zinc-800 rounded-lg shadow-md p-4 hover:shadow-lg transition border-l-4 border-indigo-500 hover:border-indigo-600">
            <p class="text-sm font-semibold text-zinc-600 dark:text-zinc-400 mb-1">Chart of Accounts</p>
            <p class="text-lg text-indigo-600 dark:text-indigo-400 font-medium">Manage General Ledger Accounts</p>
        </a>

        <a href="{{ route('branch-dashboard.accounting.periods') }}" class="bg-white dark:bg-zinc-800 rounded-lg shadow-md p-4 hover:shadow-lg transition border-l-4 border-cyan-500 hover:border-cyan-600">
            <p class="text-sm font-semibold text-zinc-600 dark:text-zinc-400 mb-1">Accounting Periods</p>
            <p class="text-lg text-cyan-600 dark:text-cyan-400 font-medium">Open/Close Periods</p>
        </a>

        <a href="{{ route('branch-dashboard.accounting.journal-entry') }}" class="bg-white dark:bg-zinc-800 rounded-lg shadow-md p-4 hover:shadow-lg transition border-l-4 border-pink-500 hover:border-pink-600">
            <p class="text-sm font-semibold text-zinc-600 dark:text-zinc-400 mb-1">Journal Entries</p>
            <p class="text-lg text-pink-600 dark:text-pink-400 font-medium">Create Manual Entries</p>
        </a>

        <a href="{{ route('branch-dashboard.accounting.bank-reconciliation') }}" class="bg-white dark:bg-zinc-800 rounded-lg shadow-md p-4 hover:shadow-lg transition border-l-4 border-orange-500 hover:border-orange-600">
            <p class="text-sm font-semibold text-zinc-600 dark:text-zinc-400 mb-1">Bank Reconciliation</p>
            <p class="text-lg text-orange-600 dark:text-orange-400 font-medium">Reconcile Accounts</p>
        </a>
    </div>

    <!-- Secondary Navigation -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
        <a href="{{ route('branch-dashboard.accounting.inventory-valuation') }}" class="bg-white dark:bg-zinc-800 rounded-lg shadow-md p-4 hover:shadow-lg transition border-l-4 border-emerald-500 hover:border-emerald-600">
            <p class="text-sm font-semibold text-zinc-600 dark:text-zinc-400 mb-1">Inventory Valuation</p>
            <p class="text-lg text-emerald-600 dark:text-emerald-400 font-medium">Post Stock to GL</p>
        </a>

        <a href="{{ route('branch-dashboard.accounting.posting-status') }}" class="bg-white dark:bg-zinc-800 rounded-lg shadow-md p-4 hover:shadow-lg transition border-l-4 border-amber-500 hover:border-amber-600">
            <p class="text-sm font-semibold text-zinc-600 dark:text-zinc-400 mb-1">Posting Status</p>
            <p class="text-lg text-amber-600 dark:text-amber-400 font-medium">Monitor & Bulk Post</p>
        </a>

        <a href="{{ route('branch-dashboard.accounting.reports.index') }}" class="bg-white dark:bg-zinc-800 rounded-lg shadow-md p-4 hover:shadow-lg transition border-l-4 border-violet-500 hover:border-violet-600">
            <p class="text-sm font-semibold text-zinc-600 dark:text-zinc-400 mb-1">Financial Reports</p>
            <p class="text-lg text-violet-600 dark:text-violet-400 font-medium">View All Reports</p>
        </a>

        <a href="{{ route('branch-dashboard.accounting.reports.trial-balance') }}" class="bg-white dark:bg-zinc-800 rounded-lg shadow-md p-4 hover:shadow-lg transition border-l-4 border-rose-500 hover:border-rose-600">
            <p class="text-sm font-semibold text-zinc-600 dark:text-zinc-400 mb-1">Trial Balance</p>
            <p class="text-lg text-rose-600 dark:text-rose-400 font-medium">Quick Access</p>
        </a>
    </div>

    <!-- Key Metrics -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-md p-6 border-l-4 border-blue-500">
            <p class="text-zinc-600 dark:text-zinc-400 text-sm font-semibold mb-2">TOTAL ENTRIES</p>
            <p class="text-3xl font-bold text-blue-600 dark:text-blue-400">{{ number_format($totalEntries) }}</p>
        </div>

        <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-md p-6 border-l-4 border-green-500">
            <p class="text-zinc-600 dark:text-zinc-400 text-sm font-semibold mb-2">TOTAL DEBITS</p>
            <p class="text-3xl font-bold text-green-600 dark:text-green-400">{{ number_format($totalDebits, 2) }}</p>
        </div>

        <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-md p-6 border-l-4 border-red-500">
            <p class="text-zinc-600 dark:text-zinc-400 text-sm font-semibold mb-2">TOTAL CREDITS</p>
            <p class="text-3xl font-bold text-red-600 dark:text-red-400">{{ number_format($totalCredits, 2) }}</p>
        </div>

        <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-md p-6 border-l-4 {{ $isBalanced ? 'border-green-500' : 'border-red-500' }}">
            <p class="text-zinc-600 dark:text-zinc-400 text-sm font-semibold mb-2">BALANCE</p>
            <p class="text-3xl font-bold {{ $isBalanced ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                {{ $isBalanced ? '✓ Balanced' : '✗ Not Balanced' }}
            </p>
        </div>
    </div>

    <!-- Status Cards -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-md p-6">
            <h3 class="text-lg font-semibold text-zinc-900 dark:text-white mb-4">Journal Entry Status</h3>
            <div class="space-y-3">
                <div class="flex justify-between items-center">
                    <span class="text-zinc-600 dark:text-zinc-400">Posted Entries</span>
                    <span class="font-semibold text-zinc-900 dark:text-white">{{ $postedEntries }}</span>
                </div>
                <div class="flex justify-between items-center">
                    <span class="text-zinc-600 dark:text-zinc-400">Draft Entries</span>
                    <span class="font-semibold text-zinc-900 dark:text-white">{{ $draftEntries }}</span>
                </div>
            </div>
        </div>

        <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-md p-6">
            <h3 class="text-lg font-semibold text-zinc-900 dark:text-white mb-4">GL Posting Status</h3>
            <div class="space-y-3">
                <div class="flex justify-between items-center">
                    <span class="text-zinc-600 dark:text-zinc-400">Pending</span>
                    <span class="font-semibold text-yellow-600 dark:text-yellow-400">{{ $pendingPostings }}</span>
                </div>
                <div class="flex justify-between items-center">
                    <span class="text-zinc-600 dark:text-zinc-400">Failed</span>
                    <span class="font-semibold text-red-600 dark:text-red-400">{{ $failedPostings }}</span>
                </div>
            </div>
        </div>

        <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-md p-6">
            <h3 class="text-lg font-semibold text-zinc-900 dark:text-white mb-4">Transaction Summary</h3>
            <div class="space-y-2 text-sm">
                <div class="flex justify-between">
                    <span class="text-zinc-600 dark:text-zinc-400">Sales: {{ $completedSales }}/{{ $totalSales }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-zinc-600 dark:text-zinc-400">Purchases: {{ $approvedPurchases }}/{{ $totalPurchases }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-zinc-600 dark:text-zinc-400">Payments: {{ $completedPayments }}/{{ $totalPayments }}</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent GL Entries -->
    <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-md p-6">
        <h3 class="text-lg font-semibold text-zinc-900 dark:text-white mb-4">Recent General Ledger Entries</h3>
        
        @if ($recentEntries->count() > 0)
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-zinc-50 dark:bg-zinc-700/50 border-b border-zinc-200 dark:border-zinc-700">
                        <tr>
                            <th class="px-4 py-2 text-left font-semibold text-zinc-900 dark:text-white">Date</th>
                            <th class="px-4 py-2 text-left font-semibold text-zinc-900 dark:text-white">Account</th>
                            <th class="px-4 py-2 text-left font-semibold text-zinc-900 dark:text-white">Description</th>
                            <th class="px-4 py-2 text-right font-semibold text-zinc-900 dark:text-white">Debit</th>
                            <th class="px-4 py-2 text-right font-semibold text-zinc-900 dark:text-white">Credit</th>
                            <th class="px-4 py-2 text-left font-semibold text-zinc-900 dark:text-white">Reference</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                        @foreach ($recentEntries as $entry)
                            <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-700/30 transition">
                                <td class="px-4 py-3 text-zinc-700 dark:text-zinc-300">{{ $entry->entry_date->format('M d, Y') }}</td>
                                <td class="px-4 py-3 font-mono text-xs text-zinc-600 dark:text-zinc-400">{{ $entry->glAccount->account_number ?? 'N/A' }}</td>
                                <td class="px-4 py-3 text-zinc-700 dark:text-zinc-300">{{ Str::limit($entry->description, 40) }}</td>
                                <td class="px-4 py-3 text-right text-zinc-700 dark:text-zinc-300">{{ $entry->debit > 0 ? number_format($entry->debit, 2) : '-' }}</td>
                                <td class="px-4 py-3 text-right text-zinc-700 dark:text-zinc-300">{{ $entry->credit > 0 ? number_format($entry->credit, 2) : '-' }}</td>
                                <td class="px-4 py-3 text-xs font-mono text-zinc-600 dark:text-zinc-400">{{ $entry->reference }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="p-4 bg-zinc-50 dark:bg-zinc-700/30 border border-zinc-200 dark:border-zinc-700 rounded text-center text-zinc-500 dark:text-zinc-400">
                No General Ledger entries posted yet
            </div>
        @endif
    </div>
</div>
