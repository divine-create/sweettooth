<div class="space-y-6">
    <!-- Header -->
    <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-md p-6">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold text-zinc-900 dark:text-white">Income Statement</h1>
                <p class="text-zinc-600 dark:text-zinc-400">Profit & Loss statement showing revenues, expenses, and net income</p>
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
                <button wire:click="exportToCsv" class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition">
                    Export CSV
                </button>
                <a href="{{ route('branch-dashboard.prints.accounting.income-statement', array_filter(['period_id' => $periodId, 'format' => 'pdf'])) }}"
                   target="_blank"
                   class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition flex items-center gap-1">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                    PDF
                </a>
            </div>
        </div>
    </div>

    <!-- Key Financial Metrics -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-md p-6 border-l-4 border-green-500">
            <p class="text-zinc-600 dark:text-zinc-400 text-sm font-semibold mb-2">TOTAL REVENUE</p>
            <p class="text-2xl font-bold text-green-600 dark:text-green-400">{{ $this->formatCurrency($data['total_revenue'] ?? 0) }}</p>
        </div>

        <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-md p-6 border-l-4 border-blue-500">
            <p class="text-zinc-600 dark:text-zinc-400 text-sm font-semibold mb-2">GROSS PROFIT</p>
            <p class="text-2xl font-bold text-blue-600 dark:text-blue-400">{{ $this->formatCurrency($data['gross_profit'] ?? 0) }}</p>
            <p class="text-xs text-zinc-500 dark:text-zinc-400 mt-1">Margin: {{ number_format($data['gross_profit_margin'] ?? 0, 1) }}%</p>
        </div>

        <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-md p-6 border-l-4 border-purple-500">
            <p class="text-zinc-600 dark:text-zinc-400 text-sm font-semibold mb-2">EBIT</p>
            <p class="text-2xl font-bold text-purple-600 dark:text-purple-400">{{ $this->formatCurrency($data['ebit'] ?? 0) }}</p>
            <p class="text-xs text-zinc-500 dark:text-zinc-400 mt-1">Margin: {{ number_format($data['ebit_margin'] ?? 0, 1) }}%</p>
        </div>

        <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-md p-6 border-l-4 {{ ($data['net_income'] ?? 0) >= 0 ? 'border-green-500' : 'border-red-500' }}">
            <p class="text-zinc-600 dark:text-zinc-400 text-sm font-semibold mb-2">NET INCOME</p>
            <p class="text-2xl font-bold {{ ($data['net_income'] ?? 0) >= 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                {{ $this->formatCurrency($data['net_income'] ?? 0) }}
            </p>
            <p class="text-xs text-zinc-500 dark:text-zinc-400 mt-1">Margin: {{ number_format($data['net_profit_margin'] ?? 0, 1) }}%</p>
        </div>
    </div>

    <!-- Income Statement Detail -->
    <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-md overflow-hidden">
        @if (($data['total_revenue'] ?? 0) > 0 || !empty($data['revenues']))
            <div class="divide-y divide-zinc-200 dark:divide-zinc-700">
                <!-- Revenue Section -->
                <div class="p-4 bg-green-50 dark:bg-green-900/20">
                    <h3 class="text-lg font-bold text-green-800 dark:text-green-300">REVENUE</h3>
                </div>
                @forelse ($data['revenues'] ?? [] as $account)
                    <div class="px-6 py-3 flex justify-between items-center hover:bg-zinc-50 dark:hover:bg-zinc-700/30">
                        <div>
                            <span class="font-mono text-sm text-zinc-500 dark:text-zinc-400">{{ $account['account_number'] }}</span>
                            <span class="ml-2 text-zinc-900 dark:text-white">{{ $account['account_name'] }}</span>
                        </div>
                        <span class="font-mono font-semibold text-zinc-900 dark:text-white">{{ $this->formatCurrency($account['balance']) }}</span>
                    </div>
                @empty
                    <div class="px-6 py-3 text-zinc-500 dark:text-zinc-400 italic">No revenue recorded</div>
                @endforelse
                <div class="px-6 py-3 flex justify-between items-center bg-green-100 dark:bg-green-900/30 font-bold">
                    <span class="text-green-800 dark:text-green-300">Total Revenue</span>
                    <span class="font-mono text-green-800 dark:text-green-300">{{ $this->formatCurrency($data['total_revenue'] ?? 0) }}</span>
                </div>

                <!-- COGS Section -->
                <div class="p-4 bg-orange-50 dark:bg-orange-900/20">
                    <h3 class="text-lg font-bold text-orange-800 dark:text-orange-300">COST OF GOODS SOLD</h3>
                </div>
                @forelse ($data['cogs'] ?? [] as $account)
                    <div class="px-6 py-3 flex justify-between items-center hover:bg-zinc-50 dark:hover:bg-zinc-700/30">
                        <div>
                            <span class="font-mono text-sm text-zinc-500 dark:text-zinc-400">{{ $account['account_number'] }}</span>
                            <span class="ml-2 text-zinc-900 dark:text-white">{{ $account['account_name'] }}</span>
                        </div>
                        <span class="font-mono font-semibold text-zinc-900 dark:text-white">{{ $this->formatCurrency($account['balance']) }}</span>
                    </div>
                @empty
                    <div class="px-6 py-3 text-zinc-500 dark:text-zinc-400 italic">No COGS recorded</div>
                @endforelse
                <div class="px-6 py-3 flex justify-between items-center bg-orange-100 dark:bg-orange-900/30 font-bold">
                    <span class="text-orange-800 dark:text-orange-300">Total COGS</span>
                    <span class="font-mono text-orange-800 dark:text-orange-300">{{ $this->formatCurrency($data['total_cogs'] ?? 0) }}</span>
                </div>

                <!-- Gross Profit -->
                <div class="px-6 py-4 flex justify-between items-center bg-blue-100 dark:bg-blue-900/30 font-bold text-lg">
                    <span class="text-blue-800 dark:text-blue-300">GROSS PROFIT</span>
                    <span class="font-mono text-blue-800 dark:text-blue-300">{{ $this->formatCurrency($data['gross_profit'] ?? 0) }}</span>
                </div>

                <!-- Operating Expenses -->
                <div class="p-4 bg-red-50 dark:bg-red-900/20">
                    <h3 class="text-lg font-bold text-red-800 dark:text-red-300">OPERATING EXPENSES</h3>
                </div>
                @forelse ($data['opex'] ?? [] as $account)
                    <div class="px-6 py-3 flex justify-between items-center hover:bg-zinc-50 dark:hover:bg-zinc-700/30">
                        <div>
                            <span class="font-mono text-sm text-zinc-500 dark:text-zinc-400">{{ $account['account_number'] }}</span>
                            <span class="ml-2 text-zinc-900 dark:text-white">{{ $account['account_name'] }}</span>
                        </div>
                        <span class="font-mono font-semibold text-zinc-900 dark:text-white">{{ $this->formatCurrency($account['balance']) }}</span>
                    </div>
                @empty
                    <div class="px-6 py-3 text-zinc-500 dark:text-zinc-400 italic">No operating expenses recorded</div>
                @endforelse
                <div class="px-6 py-3 flex justify-between items-center bg-red-100 dark:bg-red-900/30 font-bold">
                    <span class="text-red-800 dark:text-red-300">Total Operating Expenses</span>
                    <span class="font-mono text-red-800 dark:text-red-300">{{ $this->formatCurrency($data['total_opex'] ?? 0) }}</span>
                </div>

                <!-- Admin Expenses -->
                @if (!empty($data['admin']))
                    <div class="p-4 bg-purple-50 dark:bg-purple-900/20">
                        <h3 class="text-lg font-bold text-purple-800 dark:text-purple-300">ADMINISTRATIVE EXPENSES</h3>
                    </div>
                    @foreach ($data['admin'] as $account)
                        <div class="px-6 py-3 flex justify-between items-center hover:bg-zinc-50 dark:hover:bg-zinc-700/30">
                            <div>
                                <span class="font-mono text-sm text-zinc-500 dark:text-zinc-400">{{ $account['account_number'] }}</span>
                                <span class="ml-2 text-zinc-900 dark:text-white">{{ $account['account_name'] }}</span>
                            </div>
                            <span class="font-mono font-semibold text-zinc-900 dark:text-white">{{ $this->formatCurrency($account['balance']) }}</span>
                        </div>
                    @endforeach
                    <div class="px-6 py-3 flex justify-between items-center bg-purple-100 dark:bg-purple-900/30 font-bold">
                        <span class="text-purple-800 dark:text-purple-300">Total Admin Expenses</span>
                        <span class="font-mono text-purple-800 dark:text-purple-300">{{ $this->formatCurrency($data['total_admin'] ?? 0) }}</span>
                    </div>
                @endif

                <!-- EBIT -->
                <div class="px-6 py-4 flex justify-between items-center bg-purple-200 dark:bg-purple-900/50 font-bold text-lg">
                    <span class="text-purple-900 dark:text-purple-200">EBIT (Operating Income)</span>
                    <span class="font-mono text-purple-900 dark:text-purple-200">{{ $this->formatCurrency($data['ebit'] ?? 0) }}</span>
                </div>

                <!-- Finance Costs -->
                @if (!empty($data['finance']))
                    <div class="p-4 bg-yellow-50 dark:bg-yellow-900/20">
                        <h3 class="text-lg font-bold text-yellow-800 dark:text-yellow-300">FINANCE COSTS</h3>
                    </div>
                    @foreach ($data['finance'] as $account)
                        <div class="px-6 py-3 flex justify-between items-center hover:bg-zinc-50 dark:hover:bg-zinc-700/30">
                            <div>
                                <span class="font-mono text-sm text-zinc-500 dark:text-zinc-400">{{ $account['account_number'] }}</span>
                                <span class="ml-2 text-zinc-900 dark:text-white">{{ $account['account_name'] }}</span>
                            </div>
                            <span class="font-mono font-semibold text-zinc-900 dark:text-white">{{ $this->formatCurrency($account['balance']) }}</span>
                        </div>
                    @endforeach
                    <div class="px-6 py-3 flex justify-between items-center bg-yellow-100 dark:bg-yellow-900/30 font-bold">
                        <span class="text-yellow-800 dark:text-yellow-300">Total Finance Costs</span>
                        <span class="font-mono text-yellow-800 dark:text-yellow-300">{{ $this->formatCurrency($data['total_finance'] ?? 0) }}</span>
                    </div>
                @endif

                <!-- Net Income -->
                <div class="px-6 py-4 flex justify-between items-center {{ ($data['net_income'] ?? 0) >= 0 ? 'bg-green-200 dark:bg-green-900/50' : 'bg-red-200 dark:bg-red-900/50' }} font-bold text-xl">
                    <span class="{{ ($data['net_income'] ?? 0) >= 0 ? 'text-green-900 dark:text-green-200' : 'text-red-900 dark:text-red-200' }}">NET INCOME</span>
                    <span class="font-mono {{ ($data['net_income'] ?? 0) >= 0 ? 'text-green-900 dark:text-green-200' : 'text-red-900 dark:text-red-200' }}">{{ $this->formatCurrency($data['net_income'] ?? 0) }}</span>
                </div>
            </div>
        @else
            <div class="p-8 text-center">
                <div class="text-zinc-400 dark:text-zinc-500 mb-4">
                    <svg class="w-16 h-16 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                    </svg>
                </div>
                <h3 class="text-lg font-semibold text-zinc-700 dark:text-zinc-300 mb-2">No Financial Data</h3>
                <p class="text-zinc-500 dark:text-zinc-400">No revenue or expense entries have been posted for this period.</p>
                <div class="mt-4 flex justify-center gap-3">
                    <a href="{{ route('branch-dashboard.accounting.journal-entry') }}" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition">
                        Create Journal Entry
                    </a>
                    <a href="{{ route('branch-dashboard.accounting.posting-status') }}" class="px-4 py-2 bg-zinc-200 dark:bg-zinc-700 text-zinc-700 dark:text-zinc-300 rounded-lg hover:bg-zinc-300 dark:hover:bg-zinc-600 transition">
                        Post Sales & Purchases
                    </a>
                </div>
            </div>
        @endif
    </div>
</div>
