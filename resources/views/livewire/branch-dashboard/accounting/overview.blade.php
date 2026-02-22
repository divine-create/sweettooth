<div class="space-y-6">
    <!-- Custom styles for select dropdown -->
    <style>
        select.custom-select option {
            background-color: white !important;
            color: #171717 !important; /* zinc-900 */
        }
        
        .dark select.custom-select option {
            background-color: #3f3f46 !important; /* zinc-700 */
            color: white !important;
        }
    </style>
    
    <!-- Header -->
    <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-md p-6">
        <h1 class="text-3xl font-bold text-zinc-900 dark:text-white mb-2">Accounting Overview</h1>
        <p class="text-zinc-600 dark:text-zinc-400">System health and status monitoring</p>
    </div>

    <!-- Period Selection -->
    <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-md p-6">
        <label class="block text-sm font-medium text-zinc-900 dark:text-white mb-3">Select Period</label>
        <select wire:model.live="periodId" class="w-full md:w-64 px-4 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-indigo-500">
            <option value="">Select Period</option>
            @foreach ($periods as $period)
                <option value="{{ $period->id }}">{{ $period->name }}</option>
            @endforeach
        </select>
    </div>

    <!-- GL Status Metrics -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-md p-6 border-l-4 border-green-500">
            <p class="text-zinc-600 dark:text-zinc-400 text-sm font-semibold mb-2">POSTED ENTRIES</p>
            <p class="text-3xl font-bold text-green-600 dark:text-green-400">{{ $postedEntries }}</p>
        </div>
        <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-md p-6 border-l-4 border-yellow-500">
            <p class="text-zinc-600 dark:text-zinc-400 text-sm font-semibold mb-2">DRAFT ENTRIES</p>
            <p class="text-3xl font-bold text-yellow-600 dark:text-yellow-400">{{ $draftEntries }}</p>
        </div>
        <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-md p-6 border-l-4 border-blue-500">
            <p class="text-zinc-600 dark:text-zinc-400 text-sm font-semibold mb-2">PENDING POSTINGS</p>
            <p class="text-3xl font-bold text-blue-600 dark:text-blue-400">{{ $pendingPostings }}</p>
        </div>
        <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-md p-6 border-l-4 {{ $failedPostings > 0 ? 'border-red-500' : 'border-green-500' }}">
            <p class="text-zinc-600 dark:text-zinc-400 text-sm font-semibold mb-2">FAILED POSTINGS</p>
            <p class="text-3xl font-bold {{ $failedPostings > 0 ? 'text-red-600 dark:text-red-400' : 'text-green-600 dark:text-green-400' }}">{{ $failedPostings }}</p>
            @if($failedPostings > 0)
                <p class="text-red-600 dark:text-red-400 text-xs mt-2">⚠️ Requires attention</p>
            @endif
        </div>
    </div>

    <!-- Transaction Processing -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-md p-6">
            <h3 class="text-lg font-semibold text-zinc-900 dark:text-white mb-4">Sales Processing</h3>
            <div class="space-y-3">
                <div>
                    <p class="text-sm text-zinc-600 dark:text-zinc-400">Total Sales</p>
                    <p class="text-2xl font-bold text-zinc-900 dark:text-white">{{ $totalSales }}</p>
                </div>
                <div>
                    <p class="text-sm text-zinc-600 dark:text-zinc-400">Completed</p>
                    <p class="text-xl font-bold text-green-600 dark:text-green-400">{{ $completedSales }}</p>
                    <p class="text-xs text-zinc-600 dark:text-zinc-400 mt-1">{{ $totalSales > 0 ? round(($completedSales / $totalSales) * 100) : 0 }}% Complete</p>
                </div>
                <div class="w-full bg-zinc-200 dark:bg-zinc-700 rounded-full h-2 mt-2">
                    <div class="bg-green-500 h-2 rounded-full" style="width: {{ $totalSales > 0 ? round(($completedSales / $totalSales) * 100) : 0 }}%"></div>
                </div>
            </div>
        </div>

        <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-md p-6">
            <h3 class="text-lg font-semibold text-zinc-900 dark:text-white mb-4">Purchase Processing</h3>
            <div class="space-y-3">
                <div>
                    <p class="text-sm text-zinc-600 dark:text-zinc-400">Total Purchases</p>
                    <p class="text-2xl font-bold text-zinc-900 dark:text-white">{{ $totalPurchases }}</p>
                </div>
                <div>
                    <p class="text-sm text-zinc-600 dark:text-zinc-400">Approved</p>
                    <p class="text-xl font-bold text-green-600 dark:text-green-400">{{ $approvedPurchases }}</p>
                    <p class="text-xs text-zinc-600 dark:text-zinc-400 mt-1">{{ $totalPurchases > 0 ? round(($approvedPurchases / $totalPurchases) * 100) : 0 }}% Approved</p>
                </div>
                <div class="w-full bg-zinc-200 dark:bg-zinc-700 rounded-full h-2 mt-2">
                    <div class="bg-green-500 h-2 rounded-full" style="width: {{ $totalPurchases > 0 ? round(($approvedPurchases / $totalPurchases) * 100) : 0 }}%"></div>
                </div>
            </div>
        </div>

        <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-md p-6">
            <h3 class="text-lg font-semibold text-zinc-900 dark:text-white mb-4">Payment Processing</h3>
            <div class="space-y-3">
                <div>
                    <p class="text-sm text-zinc-600 dark:text-zinc-400">Total Payments</p>
                    <p class="text-2xl font-bold text-zinc-900 dark:text-white">{{ $totalPayments }}</p>
                </div>
                <div>
                    <p class="text-sm text-zinc-600 dark:text-zinc-400">Completed</p>
                    <p class="text-xl font-bold text-green-600 dark:text-green-400">{{ $completedPayments }}</p>
                    <p class="text-xs text-zinc-600 dark:text-zinc-400 mt-1">{{ $totalPayments > 0 ? round(($completedPayments / $totalPayments) * 100) : 0 }}% Complete</p>
                </div>
                <div class="w-full bg-zinc-200 dark:bg-zinc-700 rounded-full h-2 mt-2">
                    <div class="bg-green-500 h-2 rounded-full" style="width: {{ $totalPayments > 0 ? round(($completedPayments / $totalPayments) * 100) : 0 }}%"></div>
                </div>
            </div>
        </div>
    </div>

    <!-- GL Balance Status -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-md p-6 {{ $isBalanced ? 'border-l-4 border-green-500' : 'border-l-4 border-red-500' }}">
            <h3 class="text-lg font-semibold text-zinc-900 dark:text-white mb-4">GL Balance Status</h3>
            <div class="grid grid-cols-2 gap-4 mb-4">
                <div>
                    <p class="text-sm text-zinc-600 dark:text-zinc-400">Total Debits</p>
                    <p class="text-2xl font-bold text-zinc-900 dark:text-white">{{ number_format($totalDebits, 2) }}</p>
                </div>
                <div>
                    <p class="text-sm text-zinc-600 dark:text-zinc-400">Total Credits</p>
                    <p class="text-2xl font-bold text-zinc-900 dark:text-white">{{ number_format($totalCredits, 2) }}</p>
                </div>
            </div>
            <div class="border-t border-zinc-200 dark:border-zinc-700 pt-4 text-center">
                @if($isBalanced)
                    <span class="inline-block px-3 py-1 bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-300 rounded-full text-sm font-semibold">✓ BALANCED</span>
                    <p class="text-zinc-600 dark:text-zinc-400 text-sm mt-2">Debits equal credits</p>
                @else
                    <span class="inline-block px-3 py-1 bg-red-100 dark:bg-red-900/30 text-red-800 dark:text-red-300 rounded-full text-sm font-semibold">✗ UNBALANCED</span>
                    <p class="text-red-600 dark:text-red-400 text-sm mt-2">Difference: {{ number_format(abs($totalDebits - $totalCredits), 2) }}</p>
                @endif
            </div>
        </div>

        <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-md p-6">
            <h3 class="text-lg font-semibold text-zinc-900 dark:text-white mb-4">Recent Posted Entries</h3>
            @if($recentPostedEntries->count())
                <div class="overflow-y-auto max-h-64">
                    <table class="w-full text-sm">
                        <thead class="bg-zinc-50 dark:bg-zinc-700/50 sticky top-0 border-b border-zinc-200 dark:border-zinc-700">
                            <tr>
                                <th class="px-4 py-2 text-left font-semibold text-zinc-900 dark:text-white">Date</th>
                                <th class="px-4 py-2 text-left font-semibold text-zinc-900 dark:text-white">Account</th>
                                <th class="px-4 py-2 text-right font-semibold text-zinc-900 dark:text-white">Amount</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                            @foreach($recentPostedEntries as $entry)
                                <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-700/30">
                                    <td class="px-4 py-2 text-zinc-700 dark:text-zinc-300">{{ $entry->entry_date?->format('M d') }}</td>
                                    <td class="px-4 py-2 text-zinc-700 dark:text-zinc-300">{{ $entry->glAccount?->account_name ?? 'N/A' }}</td>
                                    <td class="px-4 py-2 text-right text-zinc-700 dark:text-zinc-300 font-medium">{{ number_format($entry->debit + $entry->credit, 2) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <p class="text-zinc-600 dark:text-zinc-400 text-sm text-center py-8">No recent entries</p>
            @endif
        </div>
    </div>

    <!-- Failed Postings Alerts -->
    @if($failedPostings > 0)
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            @if($failedSales->count())
                <div class="bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 rounded-lg p-4">
                    <h4 class="text-amber-900 dark:text-amber-300 font-semibold mb-2">⚠️ Failed Sales GL Postings ({{ $failedSales->count() }})</h4>
                    <ul class="text-sm text-amber-800 dark:text-amber-400 space-y-1">
                        @foreach($failedSales as $sale)
                            <li>Sale #{{ $sale->id ?? 'N/A' }} - {{ $sale->created_at?->format('M d, Y') }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif
            @if($failedPurchases->count())
                <div class="bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 rounded-lg p-4">
                    <h4 class="text-amber-900 dark:text-amber-300 font-semibold mb-2">⚠️ Failed Purchase GL Postings ({{ $failedPurchases->count() }})</h4>
                    <ul class="text-sm text-amber-800 dark:text-amber-400 space-y-1">
                        @foreach($failedPurchases as $purchase)
                            <li>Purchase #{{ $purchase->id ?? 'N/A' }} - {{ $purchase->created_at?->format('M d, Y') }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif
        </div>

        @if($failedPayments->count())
            <div class="bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 rounded-lg p-4">
                <h4 class="text-amber-900 dark:text-amber-300 font-semibold mb-2">⚠️ Failed Payment GL Postings ({{ $failedPayments->count() }})</h4>
                <ul class="text-sm text-amber-800 dark:text-amber-400 space-y-1">
                    @foreach($failedPayments as $payment)
                        <li>Payment #{{ $payment->id ?? 'N/A' }} - {{ $payment->created_at?->format('M d, Y') }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="flex justify-start">
            <a href="{{ route('branch-dashboard.accounting.posting-status', ['b_id' => request('b_id')]) }}" 
               class="px-6 py-2 bg-red-600 hover:bg-red-700 text-white rounded-lg font-medium transition">
                View All Failed Postings & Fix
            </a>
        </div>
    @endif
</div>
