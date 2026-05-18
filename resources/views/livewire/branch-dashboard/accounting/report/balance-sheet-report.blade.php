<div class="space-y-6">
    <!-- Header -->
    <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-md p-6">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold text-zinc-900 dark:text-white">Balance Sheet</h1>
                <p class="text-zinc-600 dark:text-zinc-400">Statement of financial position showing assets, liabilities, and equity</p>
            </div>
            <div class="flex items-center gap-3">
                <select wire:model.live="periodId" class="px-4 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white">
                    <option value="">All Periods</option>
                    @foreach ($periods as $period)
                        <option value="{{ $period->id }}">{{ $period->name ?? "{$period->month}/{$period->year}" }}</option>
                    @endforeach
                </select>
                <button wire:click="toggleComparative" class="px-4 py-2 {{ $isComparative ? 'bg-blue-600 text-white' : 'bg-zinc-200 dark:bg-zinc-700 text-zinc-700 dark:text-zinc-300' }} rounded-lg hover:opacity-90 transition">
                    Comparative
                </button>
                <button wire:click="toggleRatios" class="px-4 py-2 {{ $showRatios ? 'bg-purple-600 text-white' : 'bg-zinc-200 dark:bg-zinc-700 text-zinc-700 dark:text-zinc-300' }} rounded-lg hover:opacity-90 transition">
                    Ratios
                </button>
                <button wire:click="exportToCsv" class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition">
                    Export CSV
                </button>
                <a href="{{ route('branch-dashboard.prints.accounting.balance-sheet', array_filter(['period_id' => $periodId, 'format' => 'pdf'])) }}"
                   target="_blank"
                   class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition flex items-center gap-1">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                    PDF
                </a>
            </div>
        </div>
    </div>

    <!-- Key Metrics -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-md p-6 border-l-4 border-blue-500">
            <p class="text-zinc-600 dark:text-zinc-400 text-sm font-semibold mb-2">TOTAL ASSETS</p>
            <p class="text-2xl font-bold text-blue-600 dark:text-blue-400">{{ $this->formatCurrency($data['total_assets'] ?? 0) }}</p>
        </div>

        <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-md p-6 border-l-4 border-red-500">
            <p class="text-zinc-600 dark:text-zinc-400 text-sm font-semibold mb-2">TOTAL LIABILITIES</p>
            <p class="text-2xl font-bold text-red-600 dark:text-red-400">{{ $this->formatCurrency($data['total_liabilities'] ?? 0) }}</p>
        </div>

        <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-md p-6 border-l-4 border-purple-500">
            <p class="text-zinc-600 dark:text-zinc-400 text-sm font-semibold mb-2">TOTAL EQUITY</p>
            <p class="text-2xl font-bold text-purple-600 dark:text-purple-400">{{ $this->formatCurrency($data['total_equity_with_re'] ?? 0) }}</p>
        </div>

        <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-md p-6 border-l-4 {{ ($data['is_balanced'] ?? false) ? 'border-green-500' : 'border-yellow-500' }}">
            <p class="text-zinc-600 dark:text-zinc-400 text-sm font-semibold mb-2">BALANCE CHECK</p>
            <p class="text-2xl font-bold {{ ($data['is_balanced'] ?? false) ? 'text-green-600 dark:text-green-400' : 'text-yellow-600 dark:text-yellow-400' }}">
                {{ ($data['is_balanced'] ?? false) ? 'Balanced' : 'Diff: ' . $this->formatCurrency(abs($data['difference'] ?? 0)) }}
            </p>
        </div>
    </div>

    <!-- Financial Ratios -->
    @if ($showRatios && !empty($ratios))
        <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-md p-6">
            <h3 class="text-lg font-semibold text-zinc-900 dark:text-white mb-4">Financial Ratios</h3>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-6">
                <div class="text-center p-4 bg-zinc-50 dark:bg-zinc-700/30 rounded-lg">
                    <p class="text-3xl font-bold text-blue-600 dark:text-blue-400">{{ number_format($ratios['current_ratio'] ?? 0, 2) }}</p>
                    <p class="text-sm text-zinc-600 dark:text-zinc-400 mt-1">Current Ratio</p>
                </div>
                <div class="text-center p-4 bg-zinc-50 dark:bg-zinc-700/30 rounded-lg">
                    <p class="text-3xl font-bold text-red-600 dark:text-red-400">{{ number_format($ratios['debt_to_equity'] ?? 0, 2) }}</p>
                    <p class="text-sm text-zinc-600 dark:text-zinc-400 mt-1">Debt to Equity</p>
                </div>
                <div class="text-center p-4 bg-zinc-50 dark:bg-zinc-700/30 rounded-lg">
                    <p class="text-3xl font-bold text-purple-600 dark:text-purple-400">{{ number_format(($ratios['equity_ratio'] ?? 0) * 100, 1) }}%</p>
                    <p class="text-sm text-zinc-600 dark:text-zinc-400 mt-1">Equity Ratio</p>
                </div>
                <div class="text-center p-4 bg-zinc-50 dark:bg-zinc-700/30 rounded-lg">
                    <p class="text-3xl font-bold text-green-600 dark:text-green-400">{{ number_format($ratios['working_capital'] ?? 0, 2) }}</p>
                    <p class="text-sm text-zinc-600 dark:text-zinc-400 mt-1">Working Capital</p>
                </div>
            </div>
        </div>
    @endif

    <!-- Balance Sheet Detail -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Assets -->
        <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-md overflow-hidden">
            <div class="p-4 bg-blue-50 dark:bg-blue-900/20 border-b border-zinc-200 dark:border-zinc-700">
                <h3 class="text-lg font-bold text-blue-800 dark:text-blue-300">ASSETS</h3>
            </div>
            @if (!empty($data['assets']) && count($data['assets']) > 0)
                <div class="divide-y divide-zinc-200 dark:divide-zinc-700">
                    @foreach ($data['assets'] as $account)
                        <div class="px-6 py-3 flex justify-between items-center hover:bg-zinc-50 dark:hover:bg-zinc-700/30">
                            <div>
                                <span class="font-mono text-sm text-zinc-500 dark:text-zinc-400">{{ $account['account_number'] }}</span>
                                <span class="ml-2 text-zinc-900 dark:text-white">{{ $account['account_name'] }}</span>
                            </div>
                            <span class="font-mono font-semibold text-zinc-900 dark:text-white">{{ $this->formatCurrency($account['balance']) }}</span>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="p-6 text-center text-zinc-500 dark:text-zinc-400">No asset accounts with balances</div>
            @endif
            <div class="px-6 py-4 flex justify-between items-center bg-blue-100 dark:bg-blue-900/30 font-bold">
                <span class="text-blue-800 dark:text-blue-300">TOTAL ASSETS</span>
                <span class="font-mono text-blue-800 dark:text-blue-300">{{ $this->formatCurrency($data['total_assets'] ?? 0) }}</span>
            </div>
        </div>

        <!-- Liabilities & Equity -->
        <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-md overflow-hidden">
            <!-- Liabilities -->
            <div class="p-4 bg-red-50 dark:bg-red-900/20 border-b border-zinc-200 dark:border-zinc-700">
                <h3 class="text-lg font-bold text-red-800 dark:text-red-300">LIABILITIES</h3>
            </div>
            @if (!empty($data['liabilities']) && count($data['liabilities']) > 0)
                <div class="divide-y divide-zinc-200 dark:divide-zinc-700">
                    @foreach ($data['liabilities'] as $account)
                        <div class="px-6 py-3 flex justify-between items-center hover:bg-zinc-50 dark:hover:bg-zinc-700/30">
                            <div>
                                <span class="font-mono text-sm text-zinc-500 dark:text-zinc-400">{{ $account['account_number'] }}</span>
                                <span class="ml-2 text-zinc-900 dark:text-white">{{ $account['account_name'] }}</span>
                            </div>
                            <span class="font-mono font-semibold text-zinc-900 dark:text-white">{{ $this->formatCurrency($account['balance']) }}</span>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="p-6 text-center text-zinc-500 dark:text-zinc-400">No liability accounts with balances</div>
            @endif
            <div class="px-6 py-3 flex justify-between items-center bg-red-100 dark:bg-red-900/30 font-bold">
                <span class="text-red-800 dark:text-red-300">Total Liabilities</span>
                <span class="font-mono text-red-800 dark:text-red-300">{{ $this->formatCurrency($data['total_liabilities'] ?? 0) }}</span>
            </div>

            <!-- Equity -->
            <div class="p-4 bg-purple-50 dark:bg-purple-900/20 border-b border-zinc-200 dark:border-zinc-700 border-t">
                <h3 class="text-lg font-bold text-purple-800 dark:text-purple-300">EQUITY</h3>
            </div>
            @if (!empty($data['equity']) && count($data['equity']) > 0)
                <div class="divide-y divide-zinc-200 dark:divide-zinc-700">
                    @foreach ($data['equity'] as $account)
                        <div class="px-6 py-3 flex justify-between items-center hover:bg-zinc-50 dark:hover:bg-zinc-700/30">
                            <div>
                                <span class="font-mono text-sm text-zinc-500 dark:text-zinc-400">{{ $account['account_number'] }}</span>
                                <span class="ml-2 text-zinc-900 dark:text-white">{{ $account['account_name'] }}</span>
                            </div>
                            <span class="font-mono font-semibold text-zinc-900 dark:text-white">{{ $this->formatCurrency($account['balance']) }}</span>
                        </div>
                    @endforeach
                </div>
            @endif
            @if (($data['retained_earnings'] ?? 0) != 0)
                <div class="px-6 py-3 flex justify-between items-center hover:bg-zinc-50 dark:hover:bg-zinc-700/30 border-t border-zinc-200 dark:border-zinc-700">
                    <div>
                        <span class="font-mono text-sm text-zinc-500 dark:text-zinc-400">3020</span>
                        <span class="ml-2 text-zinc-900 dark:text-white">Retained Earnings</span>
                    </div>
                    <span class="font-mono font-semibold text-zinc-900 dark:text-white">{{ $this->formatCurrency($data['retained_earnings'] ?? 0) }}</span>
                </div>
            @endif
            <div class="px-6 py-3 flex justify-between items-center bg-purple-100 dark:bg-purple-900/30 font-bold">
                <span class="text-purple-800 dark:text-purple-300">Total Equity</span>
                <span class="font-mono text-purple-800 dark:text-purple-300">{{ $this->formatCurrency($data['total_equity_with_re'] ?? 0) }}</span>
            </div>

            <!-- Total L&E -->
            <div class="px-6 py-4 flex justify-between items-center bg-zinc-200 dark:bg-zinc-700 font-bold text-lg">
                <span class="text-zinc-900 dark:text-white">TOTAL LIABILITIES & EQUITY</span>
                <span class="font-mono text-zinc-900 dark:text-white">{{ $this->formatCurrency($data['total_liabilities_equity'] ?? 0) }}</span>
            </div>
        </div>
    </div>

    <!-- No Data Message -->
    @if (empty($data['assets']) && empty($data['liabilities']) && empty($data['equity']))
        <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-md p-8 text-center">
            <div class="text-zinc-400 dark:text-zinc-500 mb-4">
                <svg class="w-16 h-16 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                </svg>
            </div>
            <h3 class="text-lg font-semibold text-zinc-700 dark:text-zinc-300 mb-2">No Balance Sheet Data</h3>
            <p class="text-zinc-500 dark:text-zinc-400">No account balances found. Post transactions to generate balance sheet data.</p>
            <div class="mt-4 flex justify-center gap-3">
                <a href="{{ route('branch-dashboard.accounting.journal-entry') }}" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition">
                    Create Journal Entry
                </a>
                <a href="{{ route('branch-dashboard.accounting.posting-status') }}" class="px-4 py-2 bg-zinc-200 dark:bg-zinc-700 text-zinc-700 dark:text-zinc-300 rounded-lg hover:bg-zinc-300 dark:hover:bg-zinc-600 transition">
                    Post Transactions
                </a>
            </div>
        </div>
    @endif
</div>
