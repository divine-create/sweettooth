<div class="p-4 space-y-4">
    {{-- Header with Filters --}}
    <div class="bg-gradient-to-r from-green-600 to-green-700 text-white rounded-lg shadow-lg p-4">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div>
                <h2 class="text-2xl font-bold">Inventory Analytics</h2>
                <p class="text-sm opacity-90 mt-1">Comprehensive inventory overview and insights</p>
            </div>
            <div class="flex items-center gap-2">
                <button wire:click="refresh"
                    class="px-4 py-2 bg-white/20 hover:bg-white/30 rounded-lg font-medium transition-colors flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                    </svg>
                    Refresh
                </button>
                <button wire:click="exportCSV"
                    class="px-4 py-2 bg-white/20 hover:bg-white/30 rounded-lg font-medium transition-colors flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    CSV
                </button>
                <button wire:click="exportCSV"
                    class="px-4 py-2 bg-white/20 hover:bg-white/30 rounded-lg font-medium transition-colors flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    CSV
                </button>
            </div>
        </div>

        {{-- Date Filters --}}
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">
            <div>
                <label class="block text-sm font-medium opacity-90 mb-1">From Date</label>
                <input type="date" wire:model.live="dateFrom"
                    class="w-full px-4 py-2 border border-white/30 rounded-lg bg-white/10 text-white placeholder-white/60 focus:ring-2 focus:ring-white/50">
            </div>
            <div>
                <label class="block text-sm font-medium opacity-90 mb-1">To Date</label>
                <input type="date" wire:model.live="dateTo"
                    class="w-full px-4 py-2 border border-white/30 rounded-lg bg-white/10 text-white placeholder-white/60 focus:ring-2 focus:ring-white/50">
            </div>
        </div>
    </div>

    {{-- Key Metrics --}}
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
        {{-- Total Stock Value --}}
        <div class="bg-gradient-to-br from-blue-500 to-blue-600 text-white rounded-lg shadow-lg p-5">
            <p class="text-sm opacity-90 font-medium">Total Stock Value</p>
            <p class="text-3xl font-bold mt-2">N{{ number_format($totalStockValue, 2) }}</p>
            <p class="text-xs opacity-75 mt-2">{{ $totalItems }} items in inventory</p>
        </div>

        {{-- Purchases --}}
        <div class="bg-gradient-to-br from-green-500 to-green-600 text-white rounded-lg shadow-lg p-5">
            <p class="text-sm opacity-90 font-medium">Purchases (Period)</p>
            <p class="text-3xl font-bold mt-2">{{ $totalPurchases }}</p>
            <p class="text-xs opacity-75 mt-2">N{{ number_format($purchaseValue, 2) }} value</p>
        </div>

        {{-- Stock Movements --}}
        <div class="bg-gradient-to-br from-purple-500 to-purple-600 text-white rounded-lg shadow-lg p-5">
            <p class="text-sm opacity-90 font-medium">Stock Movements</p>
            <p class="text-3xl font-bold mt-2">{{ $totalMovements }}</p>
            <p class="text-xs opacity-75 mt-2">
                <span class="text-green-200">+{{ number_format($movementsIn, 0) }}</span> /
                <span class="text-red-200">-{{ number_format($movementsOut, 0) }}</span>
            </p>
        </div>

        {{-- Alerts --}}
        <div class="bg-gradient-to-br from-red-500 to-red-600 text-white rounded-lg shadow-lg p-5">
            <p class="text-sm opacity-90 font-medium">Attention Needed</p>
            <p class="text-3xl font-bold mt-2">{{ $lowStockItems + $criticalItems + $expiredItems }}</p>
            <p class="text-xs opacity-75 mt-2">
                {{ $lowStockItems }} low | {{ $criticalItems }} critical | {{ $expiredItems }} expired
            </p>
        </div>
    </div>

    {{-- Insights Section --}}
    @if(!empty($insights))
    <div class="bg-white dark:bg-zinc-800 rounded-lg shadow p-4">
        <h3 class="text-lg font-semibold text-zinc-800 dark:text-zinc-200 mb-3">Key Insights</h3>
        <div class="space-y-2">
            @foreach($insights as $insight)
            <div class="flex items-start gap-3 p-3 rounded-lg {{
                $insight['type'] === 'critical' ? 'bg-red-50 dark:bg-red-900/20 border-l-4 border-red-500' :
                ($insight['type'] === 'warning' ? 'bg-yellow-50 dark:bg-yellow-900/20 border-l-4 border-yellow-500' :
                ($insight['type'] === 'positive' ? 'bg-green-50 dark:bg-green-900/20 border-l-4 border-green-500' :
                'bg-blue-50 dark:bg-blue-900/20 border-l-4 border-blue-500'))
            }}">
                <span class="text-xl">{{ $insight['icon'] ?? '' }}</span>
                <p class="text-sm text-zinc-700 dark:text-zinc-300">{{ $insight['message'] ?? '' }}</p>
            </div>
            @endforeach
        </div>
    </div>
    @endif

    {{-- Stock Health Table --}}
    @if(!empty($stockHealthData))
    <div class="bg-white dark:bg-zinc-800 rounded-lg shadow p-4">
        <h3 class="text-lg font-semibold text-zinc-800 dark:text-zinc-200 mb-3">Stock Health Status</h3>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-zinc-200 dark:divide-zinc-700">
                <thead class="bg-zinc-50 dark:bg-zinc-900">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase">Item</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase">Stock Level</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase">Reorder Level</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase">Health</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase">Status</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase">Last Movement</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                    @foreach($stockHealthData as $stock)
                    <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-700/50">
                        <td class="px-4 py-3 text-sm font-medium text-zinc-900 dark:text-zinc-100">{{ $stock['item_name'] }}</td>
                        <td class="px-4 py-3 text-sm text-right text-zinc-600 dark:text-zinc-400">{{ number_format($stock['stock_level'], 2) }} {{ $stock['uom'] }}</td>
                        <td class="px-4 py-3 text-sm text-right text-zinc-600 dark:text-zinc-400">{{ number_format($stock['reorder_level'], 2) }}</td>
                        <td class="px-4 py-3">
                            <div class="w-full bg-zinc-200 dark:bg-zinc-700 rounded-full h-2">
                                <div class="h-2 rounded-full {{
                                    $stock['health_percentage'] < 30 ? 'bg-red-500' :
                                    ($stock['health_percentage'] < 60 ? 'bg-yellow-500' :
                                    ($stock['health_percentage'] < 80 ? 'bg-orange-500' : 'bg-green-500'))
                                }}" style="width: {{ min(100, $stock['health_percentage']) }}%"></div>
                            </div>
                            <span class="text-xs text-zinc-500">{{ $stock['health_percentage'] }}%</span>
                        </td>
                        <td class="px-4 py-3 text-center">
                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium {{
                                $stock['status'] === 'critical' ? 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400' :
                                ($stock['status'] === 'low' ? 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400' :
                                ($stock['status'] === 'moderate' ? 'bg-orange-100 text-orange-800 dark:bg-orange-900/30 dark:text-orange-400' :
                                'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400'))
                            }}">
                                {{ $stock['status_icon'] }} {{ ucfirst($stock['status']) }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-sm text-right text-zinc-600 dark:text-zinc-400">{{ $stock['last_movement'] }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif

    {{-- Department Breakdown --}}
    @if(!empty($departmentBreakdown))
    <div class="bg-white dark:bg-zinc-800 rounded-lg shadow p-4">
        <h3 class="text-lg font-semibold text-zinc-800 dark:text-zinc-200 mb-3">Category Breakdown</h3>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-zinc-200 dark:divide-zinc-700">
                <thead class="bg-zinc-50 dark:bg-zinc-900">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase">Category</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase">Stock Value</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase">Items</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase">Stock In</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase">Stock Out</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase">Low Items</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase">Requests</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                    @foreach($departmentBreakdown as $dept)
                    <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-700/50">
                        <td class="px-4 py-3 text-sm font-medium text-zinc-900 dark:text-zinc-100">{{ $dept['category'] }}</td>
                        <td class="px-4 py-3 text-sm text-right text-zinc-600 dark:text-zinc-400">N{{ number_format($dept['stock_value'], 2) }}</td>
                        <td class="px-4 py-3 text-sm text-center text-zinc-600 dark:text-zinc-400">{{ $dept['item_count'] }}</td>
                        <td class="px-4 py-3 text-sm text-right text-green-600 dark:text-green-400">+{{ number_format($dept['stock_in'], 2) }}</td>
                        <td class="px-4 py-3 text-sm text-right text-red-600 dark:text-red-400">-{{ number_format($dept['stock_out'], 2) }}</td>
                        <td class="px-4 py-3 text-sm text-center {{ $dept['low_items'] > 0 ? 'text-red-600 dark:text-red-400 font-bold' : 'text-zinc-600 dark:text-zinc-400' }}">{{ $dept['low_items'] }}</td>
                        <td class="px-4 py-3 text-sm text-center text-zinc-600 dark:text-zinc-400">{{ $dept['requests'] }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif

    {{-- Performance Metrics --}}
    @if(!empty($performanceMetrics))
    <div class="bg-white dark:bg-zinc-800 rounded-lg shadow p-4">
        <h3 class="text-lg font-semibold text-zinc-800 dark:text-zinc-200 mb-3">Performance Metrics</h3>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
            <div class="p-4 bg-zinc-50 dark:bg-zinc-900 rounded-lg">
                <p class="text-sm text-zinc-500 dark:text-zinc-400">Avg Turnover Rate</p>
                <p class="text-2xl font-bold text-zinc-800 dark:text-zinc-200">{{ number_format($performanceMetrics['average_turnover_rate'], 2) }}</p>
                <p class="text-xs text-zinc-400">units/day</p>
            </div>
            @if($performanceMetrics['fastest_moving'])
            <div class="p-4 bg-zinc-50 dark:bg-zinc-900 rounded-lg">
                <p class="text-sm text-zinc-500 dark:text-zinc-400">Fastest Moving</p>
                <p class="text-lg font-bold text-zinc-800 dark:text-zinc-200 truncate">{{ $performanceMetrics['fastest_moving']['name'] }}</p>
                <p class="text-xs text-zinc-400">{{ number_format($performanceMetrics['fastest_moving']['quantity'], 0) }} units</p>
            </div>
            @endif
            @if($performanceMetrics['slowest_moving'])
            <div class="p-4 bg-zinc-50 dark:bg-zinc-900 rounded-lg">
                <p class="text-sm text-zinc-500 dark:text-zinc-400">Slowest Moving</p>
                <p class="text-lg font-bold text-zinc-800 dark:text-zinc-200 truncate">{{ $performanceMetrics['slowest_moving']['name'] }}</p>
                <p class="text-xs text-zinc-400">{{ number_format($performanceMetrics['slowest_moving']['quantity'], 0) }} units</p>
            </div>
            @endif
            @if($performanceMetrics['most_requested'])
            <div class="p-4 bg-zinc-50 dark:bg-zinc-900 rounded-lg">
                <p class="text-sm text-zinc-500 dark:text-zinc-400">Most Requested</p>
                <p class="text-lg font-bold text-zinc-800 dark:text-zinc-200 truncate">{{ $performanceMetrics['most_requested']['name'] }}</p>
                <p class="text-xs text-zinc-400">{{ number_format($performanceMetrics['most_requested']['quantity'], 0) }} requests</p>
            </div>
            @endif
        </div>
    </div>
    @endif

    {{-- Top Alerts --}}
    @if(!empty($topAlerts))
    <div class="bg-white dark:bg-zinc-800 rounded-lg shadow p-4">
        <h3 class="text-lg font-semibold text-zinc-800 dark:text-zinc-200 mb-3">Top Alerts</h3>
        <div class="space-y-2">
            @foreach($topAlerts as $alert)
            <div class="flex items-center justify-between p-3 rounded-lg {{
                $alert['type'] === 'critical' || $alert['type'] === 'expired' ? 'bg-red-50 dark:bg-red-900/20' : 'bg-yellow-50 dark:bg-yellow-900/20'
            }}">
                <div class="flex items-center gap-3">
                    <span class="text-xl">{{ $alert['icon'] }}</span>
                    <span class="text-sm text-zinc-700 dark:text-zinc-300">{{ $alert['message'] }}</span>
                </div>
                <span class="text-xs px-2 py-1 bg-white dark:bg-zinc-800 rounded">{{ $alert['action'] }}</span>
            </div>
            @endforeach
        </div>
    </div>
    @endif
</div>
