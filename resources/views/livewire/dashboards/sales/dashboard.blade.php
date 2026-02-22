<div class="min-h-screen bg-zinc-50 dark:bg-zinc-900">
    <!-- Header -->
    <div class="bg-white dark:bg-zinc-800 border-b border-zinc-200 dark:border-zinc-700 sticky top-0 z-40">
        <div class="px-6 py-4">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-zinc-900 dark:text-zinc-100">Sales Dashboard</h1>
                    <p class="text-sm text-zinc-600 dark:text-zinc-400 mt-1">Today's sales performance and metrics</p>
                </div>
                <div class="flex items-center space-x-3">
                    <button wire:click="refresh" 
                        class="p-2 hover:bg-zinc-100 dark:hover:bg-zinc-700 rounded-lg transition-colors"
                        title="Refresh">
                        <svg class="w-5 h-5 text-zinc-600 dark:text-zinc-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                        </svg>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div class="p-6 space-y-6">
        <!-- Sales Alerts -->
        @if(!empty($salesAlerts))
            <div class="space-y-3">
                @foreach($salesAlerts as $alert)
                    <x-dashboard.alert-box :type="$alert['type']" :title="$alert['title']">
                        {{ $alert['message'] }}
                    </x-dashboard.alert-box>
                @endforeach
            </div>
        @endif

        <!-- KPI Cards -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-6">
            <!-- Today Sales -->
            <x-dashboard.metric-card
                title="Today Sales"
                :value="$todaySales"
                color="green"
                format="currency"
                :trend="$todaySales > 500 ? 'up' : 'neutral'"
                icon='<svg class="w-5 h-5 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>'
            />

            <!-- Transactions Today -->
            <x-dashboard.metric-card
                title="Transactions"
                :value="$todayTransactionCount"
                color="blue"
                format="number"
                icon='<svg class="w-5 h-5 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" /></svg>'
            />

            <!-- Average Transaction -->
            <x-dashboard.metric-card
                title="Avg Transaction"
                :value="$averageTransactionValue"
                color="purple"
                format="currency"
                icon='<svg class="w-5 h-5 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" /></svg>'
            />

            <!-- Total Sales (Period) -->
            <x-dashboard.metric-card
                title="Total Sales"
                :value="$totalSales"
                color="indigo"
                format="currency"
                icon='<svg class="w-5 h-5 text-indigo-600 dark:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 8c0 6-8 12-8 12s-8-6-8-12a8 8 0 1116 0z" /></svg>'
            />

            <!-- Transactions (Period) -->
            <x-dashboard.metric-card
                title="Total Trans"
                :value="$transactionCount"
                color="pink"
                format="number"
                icon='<svg class="w-5 h-5 text-pink-600 dark:text-pink-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 12a4 4 0 110-8 4 4 0 010 8zM0 20c0-5.373 4.477-10 10-10s10 4.627 10 10" /></svg>'
            />
        </div>

        <!-- Sales Trend and Top Products -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Sales by Hour (2/3) -->
            <div class="lg:col-span-2">
                <div class="bg-white dark:bg-zinc-800 rounded-lg border border-zinc-200 dark:border-zinc-700 p-6">
                    <h2 class="text-lg font-semibold text-zinc-900 dark:text-zinc-100 mb-4">Sales by Hour</h2>
                    @if(count($salesByHour) > 0)
                        <div class="space-y-3">
                            @foreach($salesByHour as $data)
                                <div class="flex items-center space-x-4">
                                    <span class="text-sm font-medium text-zinc-900 dark:text-zinc-100 w-12">{{ $data->hour }}</span>
                                    <div class="flex-1 bg-zinc-200 dark:bg-zinc-700 rounded-full h-8 flex items-center" style="max-width: {{ min(100, ($data->sales / 1000) * 100) }}%">
                                        <div class="flex-1 h-full bg-gradient-to-r from-green-400 to-green-500 rounded-full flex items-center justify-end pr-2">
                                            @if(($data->sales / 1000) * 100 > 20)
                                                <span class="text-xs font-semibold text-white">{{ $data->transactions }}</span>
                                            @endif
                                        </div>
                                    </div>
                                    <span class="text-sm font-semibold text-zinc-900 dark:text-zinc-100 w-20 text-right">
                                        {{ \App\Helpers\LocalizationHelper::formatCurrency($data->sales ?? 0) }}
                                    </span>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <p class="text-sm text-zinc-600 dark:text-zinc-400 text-center py-6">No sales data available</p>
                    @endif
                </div>
            </div>

            <!-- Top Selling Products (1/3) -->
            <div>
                <div class="bg-white dark:bg-zinc-800 rounded-lg border border-zinc-200 dark:border-zinc-700 p-6">
                    <h2 class="text-lg font-semibold text-zinc-900 dark:text-zinc-100 mb-4">Top Products</h2>
                    @if(count($topSellingItems) > 0)
                        <div class="space-y-3">
                            @foreach($topSellingItems->take(8) as $item)
                                <div class="border-b border-zinc-100 dark:border-zinc-700 pb-3 last:border-0">
                                    <div class="flex items-center justify-between mb-1">
                                        <p class="text-sm font-medium text-zinc-900 dark:text-zinc-100 truncate">{{ $item->name }}</p>
                                        <span class="text-xs font-semibold text-green-600 dark:text-green-400">{{ $item->total_qty }}x</span>
                                    </div>
                                    <div class="flex items-center justify-between">
                                        <div class="flex-1 bg-zinc-200 dark:bg-zinc-700 rounded-full h-1.5 mr-2">
                                            <div class="bg-green-500 h-full rounded-full" style="width: {{ ($item->total_qty / 50) * 100 }}%"></div>
                                        </div>
                                        <span class="text-xs text-zinc-600 dark:text-zinc-400">
                                            {{ \App\Helpers\LocalizationHelper::formatCurrency($item->total_sales ?? 0) }}
                                        </span>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <p class="text-sm text-zinc-600 dark:text-zinc-400 text-center py-6">No sales data</p>
                    @endif
                </div>
            </div>
        </div>

        <!-- Recent Transactions -->
        <div class="bg-white dark:bg-zinc-800 rounded-lg border border-zinc-200 dark:border-zinc-700 p-6">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-lg font-semibold text-zinc-900 dark:text-zinc-100">Recent Transactions</h2>
                <a href="{{ route('branch-dashboard.dashboard.sales', ['b_id' => request('b_id')]) }}" class="text-sm text-blue-600 hover:text-blue-700 dark:text-blue-400">
                    View All →
                </a>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-zinc-200 dark:border-zinc-700">
                            <th class="text-left py-3 px-4 font-semibold text-zinc-900 dark:text-zinc-100">Transaction ID</th>
                            <th class="text-left py-3 px-4 font-semibold text-zinc-900 dark:text-zinc-100">Items</th>
                            <th class="text-right py-3 px-4 font-semibold text-zinc-900 dark:text-zinc-100">Amount</th>
                            <th class="text-left py-3 px-4 font-semibold text-zinc-900 dark:text-zinc-100">Time</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($recentTransactions as $transaction)
                            <tr class="border-b border-zinc-100 dark:border-zinc-700 hover:bg-zinc-50 dark:hover:bg-zinc-700/50">
                                <td class="py-3 px-4 font-medium text-zinc-900 dark:text-zinc-100">#{{ $transaction->id }}</td>
                                <td class="py-3 px-4 text-zinc-600 dark:text-zinc-400">{{ $transaction->item_count ?? 0 }} items</td>
                                <td class="py-3 px-4 text-right font-semibold text-zinc-900 dark:text-zinc-100">
                                    {{ \App\Helpers\LocalizationHelper::formatCurrency($transaction->total_amount ?? 0) }}
                                </td>
                                <td class="py-3 px-4 text-zinc-600 dark:text-zinc-400 text-xs">{{ $transaction->created_at->diffForHumans() }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="py-6 px-4 text-center text-zinc-600 dark:text-zinc-400">
                                    No transactions recorded
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
