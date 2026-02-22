<div>
    <div class="p-6 bg-gray-50 dark:bg-gray-900 min-h-screen">
        <!-- Header Section -->
        <div class="mb-6 flex justify-between items-center">
            <div>
                <h1 class="text-3xl font-bold text-gray-900 dark:text-white">Executive Dashboard</h1>
                <p class="text-gray-600 dark:text-gray-400 mt-1">{{ $branchName }} - Comprehensive Business Overview</p>
            </div>

            <!-- Date Range & Export -->
            <div class="flex gap-3">
                <select wire:model.live="dateRange" class="px-4 py-2 border dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                    <option value="today">Today</option>
                    <option value="week">This Week</option>
                    <option value="month">This Month</option>
                    <option value="custom">Custom Range</option>
                </select>

                <button wire:click="exportReport" class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 flex items-center gap-2">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3" />
                    </svg>
                    Export Report
                </button>
            </div>
        </div>

        @if($dateRange === 'custom')
        <div class="mb-6 bg-white dark:bg-gray-800 rounded-lg shadow p-4">
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Start Date</label>
                    <input type="date" wire:model.live="startDate" class="w-full px-3 py-2 border dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">End Date</label>
                    <input type="date" wire:model.live="endDate" class="w-full px-3 py-2 border dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                </div>
            </div>
        </div>
        @endif

        <!-- Overall Metrics Cards -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-6 mb-6">
            <!-- Total Revenue -->
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm hover:shadow-md transition-all p-6">
                <div class="flex items-center justify-between">
                    <div class="flex-1">
                        <p class="text-sm text-gray-600 dark:text-gray-400">Total Revenue</p>
                        <p class="text-2xl font-bold text-green-600 dark:text-green-400 mt-1">
                            {{ \App\Helpers\LocalizationHelper::formatCurrency($overallMetrics['total_revenue'] ?? 0) }}
                        </p>
                    </div>
                    <div class="p-3 bg-green-100 dark:bg-green-900/30 rounded-xl">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6 text-green-600 dark:text-green-400">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v12m-3-2.818l.879.659c1.171.879 3.07.879 4.242 0 1.172-.879 1.172-2.303 0-3.182C13.536 12.219 12.768 12 12 12c-.725 0-1.45-.22-2.003-.659-1.106-.879-1.106-2.303 0-3.182s2.9-.879 4.006 0l.415.33M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                </div>
            </div>

            <!-- Employees -->
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm hover:shadow-md transition-all p-6">
                <div class="flex items-center justify-between">
                    <div class="flex-1">
                        <p class="text-sm text-gray-600 dark:text-gray-400">Active Employees</p>
                        <p class="text-2xl font-bold text-blue-600 dark:text-blue-400 mt-1">{{ $overallMetrics['active_employees'] }}/{{ $overallMetrics['employee_total'] }}</p>
                    </div>
                    <div class="p-3 bg-blue-100 dark:bg-blue-900/30 rounded-xl">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6 text-blue-600 dark:text-blue-400">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z" />
                        </svg>
                    </div>
                </div>
            </div>

            <!-- Inventory Value -->
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm hover:shadow-md transition-all p-6">
                <div class="flex items-center justify-between">
                    <div class="flex-1">
                        <p class="text-sm text-gray-600 dark:text-gray-400">Inventory Value</p>
                        <p class="text-2xl font-bold text-amber-600 dark:text-amber-400 mt-1">
                            {{ \App\Helpers\LocalizationHelper::formatCurrency($overallMetrics['inventory_value'] ?? 0) }}
                        </p>
                    </div>
                    <div class="p-3 bg-amber-100 dark:bg-amber-900/30 rounded-xl">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6 text-amber-600 dark:text-amber-400">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M20.25 7.5l-.625 10.632a2.25 2.25 0 01-2.247 2.118H6.622a2.25 2.25 0 01-2.247-2.118L3.75 7.5M10 11.25h4M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125z" />
                        </svg>
                    </div>
                </div>
            </div>

            <!-- Active Issues -->
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm hover:shadow-md transition-all p-6">
                <div class="flex items-center justify-between">
                    <div class="flex-1">
                        <p class="text-sm text-gray-600 dark:text-gray-400">Active Issues</p>
                        <p class="text-2xl font-bold {{ $overallMetrics['active_issues'] > 0 ? 'text-red-600 dark:text-red-400' : 'text-green-600 dark:text-green-400' }} mt-1">{{ $overallMetrics['active_issues'] }}</p>
                    </div>
                    <div class="p-3 {{ $overallMetrics['active_issues'] > 0 ? 'bg-red-100 dark:bg-red-900/30' : 'bg-green-100 dark:bg-green-900/30' }} rounded-xl">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6 {{ $overallMetrics['active_issues'] > 0 ? 'text-red-600 dark:text-red-400' : 'text-green-600 dark:text-green-400' }}">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
                        </svg>
                    </div>
                </div>
                @if($overallMetrics['active_issues'] > 0)
                <div class="mt-3 text-xs text-gray-600 dark:text-gray-400">
                    <p>Low Stock: {{ $overallMetrics['low_stock_count'] }}</p>
                    <p>Expiry Alerts: {{ $overallMetrics['expiry_alerts'] }}</p>
                    <p>Variances: {{ $overallMetrics['variance_count'] }}</p>
                </div>
                @endif
            </div>

            <!-- Production Efficiency (Placeholder) -->
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm hover:shadow-md transition-all p-6">
                <div class="flex items-center justify-between">
                    <div class="flex-1">
                        <p class="text-sm text-gray-600 dark:text-gray-400">Avg Efficiency</p>
                        @php
                            $avgEfficiency = count($productionSummary) > 0 ? array_sum(array_column($productionSummary, 'efficiency')) / count($productionSummary) : 0;
                        @endphp
                        <p class="text-2xl font-bold text-purple-600 dark:text-purple-400 mt-1">{{ number_format($avgEfficiency, 1) }}%</p>
                    </div>
                    <div class="p-3 bg-purple-100 dark:bg-purple-900/30 rounded-xl">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6 text-purple-600 dark:text-purple-400">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 13.5l10.5-11.25L12 10.5h8.25L9.75 21.75 12 13.5H3.75z" />
                        </svg>
                    </div>
                </div>
            </div>
        </div>

        <!-- Revenue Trend Chart -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-6 mb-6">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-xl font-semibold text-gray-800 dark:text-gray-100">Revenue Trend</h3>
                <span class="text-sm text-gray-500 dark:text-gray-400">{{ ucfirst($dateRange) }}</span>
            </div>

            <!-- Simple Bar Chart -->
            <div class="h-64 flex items-end justify-between gap-2">
                @foreach($revenueTrend as $day)
                @php
                    $maxRevenue = max(array_column($revenueTrend, 'revenue')) ?: 1;
                    $height = ($day['revenue'] / $maxRevenue) * 100;
                @endphp
                <div class="flex-1 flex flex-col items-center">
                    <div class="w-full bg-gradient-to-t from-blue-600 to-blue-400 rounded-t-lg hover:from-blue-700 hover:to-blue-500 transition-all cursor-pointer group relative"
                         style="height: {{ $height }}%;"
                         title="{{ \App\Helpers\LocalizationHelper::formatCurrency($day['revenue'] ?? 0) }}">
                        <div class="absolute -top-8 left-1/2 transform -translate-x-1/2 bg-gray-900 text-white text-xs rounded py-1 px-2 opacity-0 group-hover:opacity-100 transition-opacity whitespace-nowrap">
                            {{ \App\Helpers\LocalizationHelper::formatCurrency($day['revenue'] ?? 0) }}
                        </div>
                    </div>
                    <span class="text-xs text-gray-600 dark:text-gray-400 mt-2">{{ $day['date'] }}</span>
                </div>
                @endforeach
            </div>
        </div>

        <!-- Department Summaries Grid -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">

            <!-- Sales Summary -->
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-6">
                <div class="flex items-center gap-3 mb-4">
                    <div class="p-2 bg-green-100 dark:bg-green-900/30 rounded-lg">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5 text-green-600 dark:text-green-400">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 3h1.386c.51 0 .955.343 1.087.835l.383 1.437M7.5 14.25a3 3 0 00-3 3h15.75m-12.75-3h11.218c1.121-2.3 2.1-4.684 2.924-7.138a60.114 60.114 0 00-16.536-1.84M7.5 14.25L5.106 5.272M6 20.25a.75.75 0 11-1.5 0 .75.75 0 011.5 0zm12.75 0a.75.75 0 11-1.5 0 .75.75 0 011.5 0z" />
                        </svg>
                    </div>
                    <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-100">Sales Department</h3>
                </div>

                <div class="space-y-4">
                    @forelse($salesSummary as $dept)
                    <div class="border-l-4 border-green-500 pl-4 py-2">
                        <p class="font-medium text-gray-900 dark:text-gray-100">{{ $dept['department_name'] }}</p>
                        <div class="grid grid-cols-2 gap-2 mt-2 text-sm">
                            <div>
                                <p class="text-gray-600 dark:text-gray-400">Revenue</p>
                                <p class="font-semibold text-green-600 dark:text-green-400">
                                    {{ \App\Helpers\LocalizationHelper::formatCurrency($dept['total_sales'] ?? 0) }}
                                </p>
                            </div>
                            <div>
                                <p class="text-gray-600 dark:text-gray-400">Orders</p>
                                <p class="font-semibold text-gray-900 dark:text-gray-100">{{ $dept['order_count'] }}</p>
                            </div>
                            <div class="col-span-2">
                                <p class="text-gray-600 dark:text-gray-400">Top Product</p>
                                <p class="font-medium text-gray-900 dark:text-gray-100 text-xs">{{ $dept['top_product'] }} ({{ number_format($dept['top_product_qty']) }})</p>
                            </div>
                        </div>
                    </div>
                    @empty
                    <p class="text-sm text-gray-500 dark:text-gray-400">No sales data available</p>
                    @endforelse
                </div>
            </div>

            <!-- Production Summary -->
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-6">
                <div class="flex items-center gap-3 mb-4">
                    <div class="p-2 bg-purple-100 dark:bg-purple-900/30 rounded-lg">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5 text-purple-600 dark:text-purple-400">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M11.42 15.17L17.25 21A2.652 2.652 0 0021 17.25l-5.877-5.877M11.42 15.17l2.496-3.03c.317-.384.74-.626 1.208-.766M11.42 15.17l-4.655 5.653a2.548 2.548 0 11-3.586-3.586l6.837-5.63m5.108-.233c.55-.164 1.163-.188 1.743-.14a4.5 4.5 0 004.486-6.336l-3.276 3.277a3.004 3.004 0 01-2.25-2.25l3.276-3.276a4.5 4.5 0 00-6.336 4.486c.091 1.076-.071 2.264-.904 2.95l-.102.085m-1.745 1.437L5.909 7.5H4.5L2.25 3.75l1.5-1.5L7.5 4.5v1.409l4.26 4.26m-1.745 1.437l1.745-1.437m6.615 8.206L15.75 15.75M4.867 19.125h.008v.008h-.008v-.008z" />
                        </svg>
                    </div>
                    <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-100">Production Department</h3>
                </div>

                <div class="space-y-4">
                    @forelse($productionSummary as $dept)
                    <div class="border-l-4 border-purple-500 pl-4 py-2">
                        <p class="font-medium text-gray-900 dark:text-gray-100">{{ $dept['department_name'] }}</p>
                        <div class="grid grid-cols-2 gap-2 mt-2 text-sm">
                            <div>
                                <p class="text-gray-600 dark:text-gray-400">Produced</p>
                                <p class="font-semibold text-gray-900 dark:text-gray-100">{{ number_format($dept['total_produced']) }}</p>
                            </div>
                            <div>
                                <p class="text-gray-600 dark:text-gray-400">Efficiency</p>
                                <p class="font-semibold text-purple-600 dark:text-purple-400">{{ $dept['efficiency'] }}%</p>
                            </div>
                            <div>
                                <p class="text-gray-600 dark:text-gray-400">Batches</p>
                                <p class="font-semibold text-gray-900 dark:text-gray-100">{{ $dept['batches_count'] }}</p>
                            </div>
                            <div>
                                <p class="text-gray-600 dark:text-gray-400">Wastage</p>
                                <p class="font-semibold text-red-600 dark:text-red-400">{{ number_format($dept['wastage']) }}</p>
                            </div>
                        </div>
                    </div>
                    @empty
                    <p class="text-sm text-gray-500 dark:text-gray-400">No production data available</p>
                    @endforelse
                </div>
            </div>

            <!-- Inventory Summary -->
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-6">
                <div class="flex items-center gap-3 mb-4">
                    <div class="p-2 bg-amber-100 dark:bg-amber-900/30 rounded-lg">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5 text-amber-600 dark:text-amber-400">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6A2.25 2.25 0 016 3.75h2.25A2.25 2.25 0 0110.5 6v2.25a2.25 2.25 0 01-2.25 2.25H6a2.25 2.25 0 01-2.25-2.25V6zM3.75 15.75A2.25 2.25 0 016 13.5h2.25a2.25 2.25 0 012.25 2.25V18a2.25 2.25 0 01-2.25 2.25H6A2.25 2.25 0 013.75 18v-2.25zM13.5 6a2.25 2.25 0 012.25-2.25H18A2.25 2.25 0 0120.25 6v2.25A2.25 2.25 0 0118 10.5h-2.25a2.25 2.25 0 01-2.25-2.25V6zM13.5 15.75a2.25 2.25 0 012.25-2.25H18a2.25 2.25 0 012.25 2.25V18A2.25 2.25 0 0118 20.25h-2.25A2.25 2.25 0 0113.5 18v-2.25z" />
                        </svg>
                    </div>
                    <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-100">Inventory Overview</h3>
                </div>

                <div class="space-y-3">
                    <div class="flex justify-between items-center py-2 border-b dark:border-gray-700">
                        <span class="text-sm text-gray-600 dark:text-gray-400">Total Items</span>
                        <span class="font-semibold text-gray-900 dark:text-gray-100">{{ number_format($inventorySummary['total_items']) }}</span>
                    </div>
                    <div class="flex justify-between items-center py-2 border-b dark:border-gray-700">
                        <span class="text-sm text-gray-600 dark:text-gray-400">Stock Value</span>
                        <span class="font-semibold text-amber-600 dark:text-amber-400">
                            {{ \App\Helpers\LocalizationHelper::formatCurrency($inventorySummary['stock_value'] ?? 0) }}
                        </span>
                    </div>
                    <div class="flex justify-between items-center py-2 border-b dark:border-gray-700">
                        <span class="text-sm text-gray-600 dark:text-gray-400">Low Stock</span>
                        <span class="font-semibold text-orange-600 dark:text-orange-400">{{ $inventorySummary['low_stock_items'] }}</span>
                    </div>
                    <div class="flex justify-between items-center py-2 border-b dark:border-gray-700">
                        <span class="text-sm text-gray-600 dark:text-gray-400">Out of Stock</span>
                        <span class="font-semibold text-red-600 dark:text-red-400">{{ $inventorySummary['out_of_stock'] }}</span>
                    </div>
                    <div class="flex justify-between items-center py-2">
                        <span class="text-sm text-gray-600 dark:text-gray-400">Recent Movements</span>
                        <span class="font-semibold text-gray-900 dark:text-gray-100">{{ number_format($inventorySummary['recent_movements']) }}</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-6">
            <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-100 mb-4">Quick Actions</h3>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <a href="{{ route('branch-dashboard.sales-dashboard.analytics.index', ['b_id' => $branchId]) }}"
                   class="flex flex-col items-center gap-2 p-4 border dark:border-gray-700 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition-all">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-8 h-8 text-green-600 dark:text-green-400">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75zM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V8.625zM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V4.125z" />
                    </svg>
                    <span class="text-sm font-medium text-gray-900 dark:text-gray-100 text-center">Sales Reports</span>
                </a>

                <a href="{{ route('branch-dashboard.inventory.stocks', ['b_id' => $branchId]) }}"
                   class="flex flex-col items-center gap-2 p-4 border dark:border-gray-700 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition-all">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-8 h-8 text-amber-600 dark:text-amber-400">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M20.25 7.5l-.625 10.632a2.25 2.25 0 01-2.247 2.118H6.622a2.25 2.25 0 01-2.247-2.118L3.75 7.5m6 4.125l2.25 2.25m0 0l2.25 2.25M12 13.875l2.25-2.25M12 13.875l-2.25 2.25M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125z" />
                    </svg>
                    <span class="text-sm font-medium text-gray-900 dark:text-gray-100 text-center">Inventory</span>
                </a>

                <a href="{{ route('branch-dashboard.production.daily-produce.index', ['deptSlug' => 'kitchen', 'b_id' => $branchId]) }}"
                   class="flex flex-col items-center gap-2 p-4 border dark:border-gray-700 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition-all">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-8 h-8 text-purple-600 dark:text-purple-400">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M11.42 15.17L17.25 21A2.652 2.652 0 0021 17.25l-5.877-5.877M11.42 15.17l2.496-3.03c.317-.384.74-.626 1.208-.766M11.42 15.17l-4.655 5.653a2.548 2.548 0 11-3.586-3.586l6.837-5.63m5.108-.233c.55-.164 1.163-.188 1.743-.14a4.5 4.5 0 004.486-6.336l-3.276 3.277a3.004 3.004 0 01-2.25-2.25l3.276-3.276a4.5 4.5 0 00-6.336 4.486c.091 1.076-.071 2.264-.904 2.95l-.102.085m-1.745 1.437L5.909 7.5H4.5L2.25 3.75l1.5-1.5L7.5 4.5v1.409l4.26 4.26m-1.745 1.437l1.745-1.437m6.615 8.206L15.75 15.75M4.867 19.125h.008v.008h-.008v-.008z" />
                    </svg>
                    <span class="text-sm font-medium text-gray-900 dark:text-gray-100 text-center">Production</span>
                </a>

                <a href="{{ route('branch-dashboard.employee.index', ['b_id' => $branchId]) }}"
                   class="flex flex-col items-center gap-2 p-4 border dark:border-gray-700 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition-all">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-8 h-8 text-blue-600 dark:text-blue-400">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z" />
                    </svg>
                    <span class="text-sm font-medium text-gray-900 dark:text-gray-100 text-center">Employees</span>
                </a>
            </div>
        </div>
    </div>
</div>
