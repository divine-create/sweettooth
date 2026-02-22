<div class="space-y-6">
    <!-- Header -->
    <div class="bg-white rounded-lg shadow-md p-6">
        <h1 class="text-3xl font-bold mb-2">Accounting Dashboard</h1>
        
        @if ($periodStatus)
            <div class="flex items-center gap-4 text-sm text-gray-600">
                <span class="font-semibold">{{ $periodStatus['name'] }}</span>
                <span class="px-3 py-1 bg-blue-100 text-blue-800 rounded-full text-xs font-semibold">
                    {{ $periodStatus['status'] }}
                </span>
                <span>{{ $periodStatus['start'] }} to {{ $periodStatus['end'] }}</span>
            </div>
        @else
            <div class="p-4 bg-yellow-50 border border-yellow-200 rounded">
                <p class="text-yellow-800">No open accounting period. Please create one to begin posting entries.</p>
            </div>
        @endif
    </div>

    <!-- Status Alerts -->
    @if (!empty($unbalancedAccounts) && !$unbalancedAccounts['is_balanced'])
        <div class="bg-red-50 border border-red-200 rounded-lg p-4">
            <p class="text-red-800 font-semibold">⚠️ Trial Balance Not Balanced</p>
            <p class="text-red-700 text-sm">Difference: {{ number_format($unbalancedAccounts['difference'], 2) }}</p>
        </div>
    @elseif (!empty($unbalancedAccounts) && $unbalancedAccounts['is_balanced'])
        <div class="bg-green-50 border border-green-200 rounded-lg p-4">
            <p class="text-green-800 font-semibold">✓ Trial Balance Balanced</p>
        </div>
    @endif

    <!-- Quick Actions Navigation -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
        <a href="{{ route('branch-dashboard.accounting.accounts') }}" class="bg-white rounded-lg shadow-md p-4 hover:shadow-lg transition border-l-4 border-indigo-500">
            <p class="text-sm font-semibold text-gray-600 mb-1">Chart of Accounts</p>
            <p class="text-lg text-indigo-600">Manage General Ledger Accounts</p>
        </a>

        <a href="{{ route('branch-dashboard.accounting.periods') }}" class="bg-white rounded-lg shadow-md p-4 hover:shadow-lg transition border-l-4 border-cyan-500">
            <p class="text-sm font-semibold text-gray-600 mb-1">Accounting Periods</p>
            <p class="text-lg text-cyan-600">Open/Close Periods</p>
        </a>

        <a href="{{ route('branch-dashboard.accounting.journal-entry') }}" class="bg-white rounded-lg shadow-md p-4 hover:shadow-lg transition border-l-4 border-pink-500">
            <p class="text-sm font-semibold text-gray-600 mb-1">Journal Entries</p>
            <p class="text-lg text-pink-600">Create Manual Entries</p>
        </a>

        <a href="{{ route('branch-dashboard.accounting.bank-reconciliation') }}" class="bg-white rounded-lg shadow-md p-4 hover:shadow-lg transition border-l-4 border-orange-500">
            <p class="text-sm font-semibold text-gray-600 mb-1">Bank Reconciliation</p>
            <p class="text-lg text-orange-600">Reconcile Accounts</p>
        </a>

        <a href="{{ route('branch-dashboard.accounting.bank-positions') }}" class="bg-white rounded-lg shadow-md p-4 hover:shadow-lg transition border-l-4 border-emerald-500">
            <p class="text-sm font-semibold text-gray-600 mb-1">Bank Positions</p>
            <p class="text-lg text-emerald-600">Daily Positions</p>
        </a>

        <a href="{{ route('branch-dashboard.accounting.cash-positions') }}" class="bg-white rounded-lg shadow-md p-4 hover:shadow-lg transition border-l-4 border-amber-500">
            <p class="text-sm font-semibold text-gray-600 mb-1">Cash Positions</p>
            <p class="text-lg text-amber-600">Cash Counts</p>
        </a>

        <a href="{{ route('branch-dashboard.accounting.reports.trial-balance') }}" class="bg-white rounded-lg shadow-md p-4 hover:shadow-lg transition border-l-4 border-violet-500">
            <p class="text-sm font-semibold text-gray-600 mb-1">Trial Balance</p>
            <p class="text-lg text-violet-600">View Report</p>
        </a>

        <a href="{{ route('branch-dashboard.accounting.reports.balance-sheet') }}" class="bg-white rounded-lg shadow-md p-4 hover:shadow-lg transition border-l-4 border-rose-500">
            <p class="text-sm font-semibold text-gray-600 mb-1">Balance Sheet</p>
            <p class="text-lg text-rose-600">View Report</p>
        </a>
    </div>

    <!-- Key Metrics -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div class="bg-white rounded-lg shadow-md p-6 border-l-4 border-blue-500">
            <p class="text-gray-600 text-sm font-semibold mb-2">TOTAL ASSETS</p>
            <p class="text-3xl font-bold text-blue-600">{{ $this->formatCurrency($dashboardStats['total_assets'] ?? 0) }}</p>
            <p class="text-xs text-gray-500 mt-2">{{ $dashboardStats['total_entries'] ?? 0 }} entries</p>
        </div>

        <div class="bg-white rounded-lg shadow-md p-6 border-l-4 border-red-500">
            <p class="text-gray-600 text-sm font-semibold mb-2">TOTAL LIABILITIES</p>
            <p class="text-3xl font-bold text-red-600">{{ $this->formatCurrency($dashboardStats['total_liabilities'] ?? 0) }}</p>
        </div>

        <div class="bg-white rounded-lg shadow-md p-6 border-l-4 border-green-500">
            <p class="text-gray-600 text-sm font-semibold mb-2">TOTAL EQUITY</p>
            <p class="text-3xl font-bold text-green-600">{{ $this->formatCurrency($dashboardStats['total_equity'] ?? 0) }}</p>
        </div>

        <div class="bg-white rounded-lg shadow-md p-6 border-l-4 border-purple-500">
            <p class="text-gray-600 text-sm font-semibold mb-2">NET INCOME</p>
            <p class="text-3xl font-bold text-purple-600">{{ $this->formatCurrency($dashboardStats['net_income'] ?? 0) }}</p>
        </div>
    </div>

    <!-- Financial Summary -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
        <div class="bg-white rounded-lg shadow-md p-6">
            <h3 class="text-lg font-bold mb-4">Income Summary</h3>
            <div class="space-y-3">
                <div class="flex justify-between items-center pb-3 border-b">
                    <span class="text-gray-600">Total Revenue</span>
                    <span class="font-semibold">{{ $this->formatCurrency($dashboardStats['total_revenue'] ?? 0) }}</span>
                </div>
                <div class="flex justify-between items-center pb-3 border-b">
                    <span class="text-gray-600">Total Expenses</span>
                    <span class="font-semibold">{{ $this->formatCurrency($dashboardStats['total_expenses'] ?? 0) }}</span>
                </div>
                <div class="flex justify-between items-center pt-2">
                    <span class="text-gray-700 font-semibold">Net Income</span>
                    <span class="text-lg font-bold text-green-600">{{ $this->formatCurrency($dashboardStats['net_income'] ?? 0) }}</span>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow-md p-6">
            <h3 class="text-lg font-bold mb-4">Balance Sheet Summary</h3>
            <div class="space-y-3">
                <div class="flex justify-between items-center pb-3 border-b">
                    <span class="text-gray-600">Total Assets</span>
                    <span class="font-semibold">{{ $this->formatCurrency($dashboardStats['total_assets'] ?? 0) }}</span>
                </div>
                <div class="flex justify-between items-center pb-3 border-b">
                    <span class="text-gray-600">Total Liabilities + Equity</span>
                    <span class="font-semibold">{{ $this->formatCurrency(($dashboardStats['total_liabilities'] ?? 0) + ($dashboardStats['total_equity'] ?? 0)) }}</span>
                </div>
                <div class="flex justify-between items-center pt-2">
                    <span class="text-gray-700 font-semibold">Difference</span>
                    <span class="text-lg font-bold {{ abs(($dashboardStats['total_assets'] ?? 0) - (($dashboardStats['total_liabilities'] ?? 0) + ($dashboardStats['total_equity'] ?? 0))) < 0.01 ? 'text-green-600' : 'text-red-600' }}">
                        {{ $this->formatCurrency(abs(($dashboardStats['total_assets'] ?? 0) - (($dashboardStats['total_liabilities'] ?? 0) + ($dashboardStats['total_equity'] ?? 0)))) }}
                    </span>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Entries -->
    <div class="bg-white rounded-lg shadow-md p-6">
        <h3 class="text-lg font-bold mb-4">Recent General Ledger Entries</h3>
        
        @if (!empty($recentEntries))
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-gray-100 border-b-2 border-gray-300">
                        <tr>
                            <th class="px-4 py-2 text-left">Date</th>
                            <th class="px-4 py-2 text-left">Account</th>
                            <th class="px-4 py-2 text-left">Description</th>
                            <th class="px-4 py-2 text-right">Debit</th>
                            <th class="px-4 py-2 text-right">Credit</th>
                            <th class="px-4 py-2 text-left">Ref #</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($recentEntries as $entry)
                            <tr class="border-b hover:bg-gray-50">
                                <td class="px-4 py-2">{{ $entry['date'] }}</td>
                                <td class="px-4 py-2 font-mono text-xs">{{ $entry['account'] }}</td>
                                <td class="px-4 py-2">{{ $entry['description'] }}</td>
                                <td class="px-4 py-2 text-right">{{ $entry['debit'] > 0 ? $this->formatCurrency($entry['debit']) : '-' }}</td>
                                <td class="px-4 py-2 text-right">{{ $entry['credit'] > 0 ? $this->formatCurrency($entry['credit']) : '-' }}</td>
                                <td class="px-4 py-2 text-xs">{{ $entry['reference'] }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="p-4 bg-gray-50 border border-gray-200 rounded text-center text-gray-500">
                No General Ledger entries yet
            </div>
        @endif
    </div>
</div>
