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

        @if($canProduction)
        <div class="bg-white dark:bg-zinc-800 rounded-xl shadow-sm border border-zinc-200 dark:border-zinc-700 p-4">
            <p class="text-xs font-semibold text-zinc-500 dark:text-zinc-400 uppercase tracking-wide mb-1">Pending Production</p>
            <p class="text-2xl font-bold {{ ($kpis['pending_production_requests'] ?? 0) > 0 ? 'text-amber-600 dark:text-amber-400' : 'text-zinc-400' }}">
                {{ $kpis['pending_production_requests'] ?? 0 }}
            </p>
            <p class="text-xs text-zinc-500 mt-0.5">requests awaiting</p>
        </div>

        <div class="bg-white dark:bg-zinc-800 rounded-xl shadow-sm border border-zinc-200 dark:border-zinc-700 p-4">
            <p class="text-xs font-semibold text-zinc-500 dark:text-zinc-400 uppercase tracking-wide mb-1">Today's Production</p>
            <p class="text-2xl font-bold text-zinc-900 dark:text-white">{{ $kpis['today_production_batches'] ?? 0 }}</p>
            <p class="text-xs text-zinc-500 mt-0.5">batches produced</p>
        </div>
        @endif

        @if($canInventory)
        <div class="bg-white dark:bg-zinc-800 rounded-xl shadow-sm border border-zinc-200 dark:border-zinc-700 p-4">
            <p class="text-xs font-semibold text-zinc-500 dark:text-zinc-400 uppercase tracking-wide mb-1">Pending Purchases</p>
            <p class="text-2xl font-bold {{ ($kpis['pending_purchases'] ?? 0) > 0 ? 'text-amber-600 dark:text-amber-400' : 'text-zinc-400' }}">
                {{ $kpis['pending_purchases'] ?? 0 }}
            </p>
            <p class="text-xs text-zinc-500 mt-0.5">awaiting approval</p>
        </div>

        <div class="bg-white dark:bg-zinc-800 rounded-xl shadow-sm border {{ ($kpis['low_stock_items'] ?? 0) > 0 ? 'border-red-200 dark:border-red-800' : 'border-zinc-200 dark:border-zinc-700' }} p-4">
            <p class="text-xs font-semibold {{ ($kpis['low_stock_items'] ?? 0) > 0 ? 'text-red-500' : 'text-zinc-500 dark:text-zinc-400' }} uppercase tracking-wide mb-1">Low Stock</p>
            <p class="text-2xl font-bold {{ ($kpis['low_stock_items'] ?? 0) > 0 ? 'text-red-600 dark:text-red-400' : 'text-zinc-400' }}">
                {{ $kpis['low_stock_items'] ?? 0 }}
            </p>
            <p class="text-xs text-zinc-500 mt-0.5">items below reorder</p>
        </div>
        @endif

        @if($canHr)
        <div class="bg-white dark:bg-zinc-800 rounded-xl shadow-sm border border-zinc-200 dark:border-zinc-700 p-4">
            <p class="text-xs font-semibold text-zinc-500 dark:text-zinc-400 uppercase tracking-wide mb-1">Active Staff</p>
            <p class="text-2xl font-bold text-zinc-900 dark:text-white">{{ $kpis['total_employees'] ?? 0 }}</p>
            <p class="text-xs text-zinc-500 mt-0.5">employees</p>
        </div>
        @endif
    </div>

    <!-- Quick Nav -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
        @if($canProduction)
        <a href="{{ branch_route('branch-dashboard.dashboard.production') }}" class="flex items-center gap-3 p-3 bg-white dark:bg-zinc-800 rounded-lg border border-zinc-200 dark:border-zinc-700 hover:border-orange-400 hover:shadow-sm transition">
            <svg class="w-6 h-6 text-orange-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
            <span class="text-sm font-medium text-zinc-700 dark:text-zinc-300">Production</span>
        </a>
        @endif
        @if($canSales)
        <a href="{{ branch_route('branch-dashboard.dashboard.sales') }}" class="flex items-center gap-3 p-3 bg-white dark:bg-zinc-800 rounded-lg border border-zinc-200 dark:border-zinc-700 hover:border-blue-400 hover:shadow-sm transition">
            <svg class="w-6 h-6 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
            <span class="text-sm font-medium text-zinc-700 dark:text-zinc-300">Sales</span>
        </a>
        @endif
        @if($canAccounting)
        <a href="{{ branch_route('branch-dashboard.accounting.dashboard') }}" class="flex items-center gap-3 p-3 bg-white dark:bg-zinc-800 rounded-lg border border-zinc-200 dark:border-zinc-700 hover:border-indigo-400 hover:shadow-sm transition">
            <svg class="w-6 h-6 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 11h.01M12 11h.01M15 11h.01M4 20h16a2 2 0 002-2V6a2 2 0 00-2-2H4a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
            <span class="text-sm font-medium text-zinc-700 dark:text-zinc-300">Accounting</span>
        </a>
        @endif
        @if($canInventory)
        <a href="{{ branch_route('branch-dashboard.inventory.items.index') }}" class="flex items-center gap-3 p-3 bg-white dark:bg-zinc-800 rounded-lg border border-zinc-200 dark:border-zinc-700 hover:border-emerald-400 hover:shadow-sm transition">
            <svg class="w-6 h-6 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
            <span class="text-sm font-medium text-zinc-700 dark:text-zinc-300">Inventory</span>
        </a>
        @endif
        @if($canAccounting)
        <a href="{{ branch_route('branch-dashboard.accounting.reports.index') }}" class="flex items-center gap-3 p-3 bg-white dark:bg-zinc-800 rounded-lg border border-zinc-200 dark:border-zinc-700 hover:border-violet-400 hover:shadow-sm transition">
            <svg class="w-6 h-6 text-violet-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
            <span class="text-sm font-medium text-zinc-700 dark:text-zinc-300">Reports</span>
        </a>
        @endif
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
