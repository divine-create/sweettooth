<div class="p-4 space-y-6">
    <div>
        <h1 class="text-2xl font-bold text-zinc-900 dark:text-white">Branch Overview</h1>
        <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ now()->format('l, F j, Y') }}</p>
    </div>

    <!-- KPI Cards -->
    <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4">
        <div class="bg-white dark:bg-zinc-800 rounded-xl shadow-sm border border-zinc-200 dark:border-zinc-700 p-4">
            <p class="text-xs font-semibold text-zinc-500 dark:text-zinc-400 uppercase tracking-wide mb-1">Today's Sales</p>
            <p class="text-2xl font-bold text-zinc-900 dark:text-white">{{ $kpis['today_sales_count'] }}</p>
            <p class="text-xs text-zinc-500 mt-0.5">transactions</p>
        </div>

        <div class="bg-white dark:bg-zinc-800 rounded-xl shadow-sm border border-zinc-200 dark:border-zinc-700 p-4">
            <p class="text-xs font-semibold text-zinc-500 dark:text-zinc-400 uppercase tracking-wide mb-1">Today's Revenue</p>
            <p class="text-2xl font-bold text-green-600 dark:text-green-400">{{ number_format($kpis['today_sales_revenue'], 0) }}</p>
            <p class="text-xs text-zinc-500 mt-0.5">completed sales</p>
        </div>

        <div class="bg-white dark:bg-zinc-800 rounded-xl shadow-sm border border-zinc-200 dark:border-zinc-700 p-4">
            <p class="text-xs font-semibold text-zinc-500 dark:text-zinc-400 uppercase tracking-wide mb-1">Active Shifts</p>
            <p class="text-2xl font-bold {{ $kpis['active_shifts'] > 0 ? 'text-blue-600 dark:text-blue-400' : 'text-zinc-400' }}">
                {{ $kpis['active_shifts'] }}
            </p>
            <p class="text-xs text-zinc-500 mt-0.5">on duty</p>
        </div>

        <div class="bg-white dark:bg-zinc-800 rounded-xl shadow-sm border border-zinc-200 dark:border-zinc-700 p-4">
            <p class="text-xs font-semibold text-zinc-500 dark:text-zinc-400 uppercase tracking-wide mb-1">Pending Purchases</p>
            <p class="text-2xl font-bold {{ $kpis['pending_purchases'] > 0 ? 'text-amber-600 dark:text-amber-400' : 'text-zinc-400' }}">
                {{ $kpis['pending_purchases'] }}
            </p>
            <p class="text-xs text-zinc-500 mt-0.5">awaiting approval</p>
        </div>

        <div class="bg-white dark:bg-zinc-800 rounded-xl shadow-sm border border-zinc-200 dark:border-zinc-700 p-4">
            <p class="text-xs font-semibold text-zinc-500 dark:text-zinc-400 uppercase tracking-wide mb-1">Active Staff</p>
            <p class="text-2xl font-bold text-zinc-900 dark:text-white">{{ $kpis['total_employees'] }}</p>
            <p class="text-xs text-zinc-500 mt-0.5">employees</p>
        </div>

        <div class="bg-white dark:bg-zinc-800 rounded-xl shadow-sm border {{ $kpis['low_stock_items'] > 0 ? 'border-red-200 dark:border-red-800' : 'border-zinc-200 dark:border-zinc-700' }} p-4">
            <p class="text-xs font-semibold {{ $kpis['low_stock_items'] > 0 ? 'text-red-500' : 'text-zinc-500 dark:text-zinc-400' }} uppercase tracking-wide mb-1">Low Stock</p>
            <p class="text-2xl font-bold {{ $kpis['low_stock_items'] > 0 ? 'text-red-600 dark:text-red-400' : 'text-zinc-400' }}">
                {{ $kpis['low_stock_items'] }}
            </p>
            <p class="text-xs text-zinc-500 mt-0.5">items below reorder</p>
        </div>
    </div>

    <!-- Quick Nav -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
        <a href="{{ branch_route('branch-dashboard.accounting.dashboard') }}" class="flex items-center gap-3 p-3 bg-white dark:bg-zinc-800 rounded-lg border border-zinc-200 dark:border-zinc-700 hover:border-indigo-400 hover:shadow-sm transition">
            <svg class="w-6 h-6 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 11h.01M12 11h.01M15 11h.01M4 20h16a2 2 0 002-2V6a2 2 0 00-2-2H4a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
            <span class="text-sm font-medium text-zinc-700 dark:text-zinc-300">Accounting</span>
        </a>
        <a href="{{ branch_route('branch-dashboard.inventory.items.index') }}" class="flex items-center gap-3 p-3 bg-white dark:bg-zinc-800 rounded-lg border border-zinc-200 dark:border-zinc-700 hover:border-emerald-400 hover:shadow-sm transition">
            <svg class="w-6 h-6 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
            <span class="text-sm font-medium text-zinc-700 dark:text-zinc-300">Inventory</span>
        </a>
        <a href="{{ branch_route('branch-dashboard.accounting.reports.index') }}" class="flex items-center gap-3 p-3 bg-white dark:bg-zinc-800 rounded-lg border border-zinc-200 dark:border-zinc-700 hover:border-violet-400 hover:shadow-sm transition">
            <svg class="w-6 h-6 text-violet-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
            <span class="text-sm font-medium text-zinc-700 dark:text-zinc-300">Reports</span>
        </a>
        <a href="{{ branch_route('branch-dashboard.accounting.posting-status') }}" class="flex items-center gap-3 p-3 bg-white dark:bg-zinc-800 rounded-lg border border-zinc-200 dark:border-zinc-700 hover:border-amber-400 hover:shadow-sm transition">
            <svg class="w-6 h-6 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            <span class="text-sm font-medium text-zinc-700 dark:text-zinc-300">GL Posting</span>
        </a>
    </div>

    <!-- Recent Sales -->
    @if($recentSales->isNotEmpty())
    <div class="bg-white dark:bg-zinc-800 rounded-xl shadow-sm border border-zinc-200 dark:border-zinc-700 overflow-hidden">
        <div class="px-4 py-3 border-b border-zinc-200 dark:border-zinc-700">
            <h2 class="text-sm font-semibold text-zinc-800 dark:text-zinc-200">Recent Sales</h2>
        </div>
        <table class="w-full text-sm">
            <thead class="bg-zinc-50 dark:bg-zinc-700/40">
                <tr>
                    <th class="px-4 py-2 text-left text-xs font-semibold text-zinc-500 uppercase">Ref</th>
                    <th class="px-4 py-2 text-left text-xs font-semibold text-zinc-500 uppercase">Time</th>
                    <th class="px-4 py-2 text-right text-xs font-semibold text-zinc-500 uppercase">Amount</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-100 dark:divide-zinc-700">
                @foreach($recentSales as $sale)
                    <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-700/30">
                        <td class="px-4 py-2 font-mono text-xs text-zinc-700 dark:text-zinc-300">{{ $sale->sale_reference ?? $sale->id }}</td>
                        <td class="px-4 py-2 text-zinc-500">{{ $sale->sale_time?->format('H:i') }}</td>
                        <td class="px-4 py-2 text-right font-semibold text-zinc-900 dark:text-white">{{ number_format($sale->total, 2) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif
</div>
