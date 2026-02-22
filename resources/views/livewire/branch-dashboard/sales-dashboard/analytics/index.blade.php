<div>
    <div x-data="{ autoRefresh: @entangle('autoRefresh') }" class="p-6 bg-gray-50 dark:bg-gray-900 min-h-screen">
        <!-- Header -->
        <div class="mb-6">
            <div class="flex justify-between items-center mb-4">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900 dark:text-white">Sales Analytics</h1>
                    <p class="text-gray-600 dark:text-gray-400 mt-1">Comprehensive sales insights and performance metrics</p>
                </div>
                <div class="flex gap-3">
                    <button wire:click="generateReport"
                            wire:loading.attr="disabled"
                            wire:target="generateReport"
                            class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition disabled:opacity-60">
                        <i class="fas fa-file-circle-plus mr-2"></i>
                        <span wire:loading.remove wire:target="generateReport">Generate Report</span>
                        <span wire:loading wire:target="generateReport">Generating...</span>
                    </button>
                    <button wire:click="refreshData" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition">
                        <i class="fas fa-sync-alt mr-2"></i>Refresh
                    </button>
                    <div class="relative" x-data="{ open: false }">
                        <button @click="open = !open" class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition">
                            <i class="fas fa-download mr-2"></i>Export
                        </button>
                        <div x-show="open" @click.away="open = false" class="absolute right-0 mt-2 w-48 bg-white dark:bg-gray-800 rounded-lg shadow-lg z-10">
                            <button wire:click="exportData('csv')" class="block w-full text-left px-4 py-2 hover:bg-gray-100 dark:hover:bg-gray-700 dark:text-gray-200">Export CSV</button>
                        </div>
                    </div>
                </div>
            </div>

            @if (session('success'))
                <div class="mb-4 p-3 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-700 rounded text-sm text-green-800 dark:text-green-200">
                    <div class="flex flex-wrap items-center gap-2">
                        <span>{{ session('success') }}</span>
                        @if($generatedReportId)
                            <a href="{{ branch_route('branch-dashboard.reporting.report.view', ['id' => $generatedReportId]) }}"
                               class="inline-flex items-center px-2 py-1 rounded bg-green-700 text-white hover:bg-green-800 text-xs">
                                View Report
                            </a>
                        @endif
                    </div>
                </div>
            @endif

            @if (session('warning'))
                <div class="mb-4 p-3 bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-700 rounded text-sm text-yellow-800 dark:text-yellow-200">
                    {{ session('warning') }}
                </div>
            @endif
    
            <!-- Filters -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
                <div class="grid grid-cols-1 md:grid-cols-6 gap-4">
                    <!-- Period Quick Filters -->
                    <div class="col-span-2">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Quick Filters</label>
                        <div class="flex flex-wrap gap-2">
                            <button wire:click="setDateRange('today')" class="px-3 py-1 rounded {{ $selectedPeriod === 'today' ? 'bg-blue-600 text-white' : 'bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300' }}">Today</button>
                            <button wire:click="setDateRange('yesterday')" class="px-3 py-1 rounded {{ $selectedPeriod === 'yesterday' ? 'bg-blue-600 text-white' : 'bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300' }}">Yesterday</button>
                            <button wire:click="setDateRange('this_week')" class="px-3 py-1 rounded {{ $selectedPeriod === 'this_week' ? 'bg-blue-600 text-white' : 'bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300' }}">This Week</button>
                            <button wire:click="setDateRange('this_month')" class="px-3 py-1 rounded {{ $selectedPeriod === 'this_month' ? 'bg-blue-600 text-white' : 'bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300' }}">This Month</button>
                        </div>
                    </div>

                    <!-- Custom Date Range -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">From Date</label>
                        <input type="datetime-local" wire:model.live="dateFrom" class="w-full px-3 py-2 border dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                        @error('dateFrom') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">To Date</label>
                        <input type="datetime-local" wire:model.live="dateTo" class="w-full px-3 py-2 border dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                    </div>

                    <!-- Order Type Filter -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Order Type</label>
                        <select wire:model.live="orderType" class="w-full px-3 py-2 border dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                            <option value="all">All Types</option>
                            <option value="dine_in">Dine In</option>
                            <option value="takeaway">Takeaway</option>
                            <option value="delivery">Delivery</option>
                            <option value="glovo">Glovo</option>
                        </select>
                    </div>

                    <!-- Payment Method Filter -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Payment Method</label>
                        <select wire:model.live="paymentMethod" class="w-full px-3 py-2 border dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                            <option value="all">All Methods</option>
                            <option value="cash">Cash</option>
                            <option value="pos">POS</option>
                            <option value="transfer">Transfer</option>
                        </select>
                    </div>
                </div>
    
                <div class="mt-3 flex items-center gap-4">
                    <label class="flex items-center">
                        <input type="checkbox" x-model="autoRefresh" class="mr-2">
                        <span class="text-sm text-gray-700 dark:text-gray-300">Auto-refresh every 30s</span>
                    </label>
                </div>
            </div>
        </div>
    
        <!-- Overview Cards -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
            <!-- Row 1: Primary Metrics -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-5 border-l-4 border-blue-600">
                <div class="flex justify-between items-start">
                    <div class="flex-1">
                        <p class="text-gray-600 dark:text-gray-400 text-xs font-medium uppercase">Total Sales</p>
                        <h3 class="text-2xl font-bold text-gray-900 dark:text-white mt-1">{{ $this->formatCurrency($overview['total_sales']) }}</h3>
                        <p class="text-xs mt-1 {{ $overview['growth_rate'] >= 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                            <i class="fas fa-{{ $overview['growth_rate'] >= 0 ? 'arrow-up' : 'arrow-down' }}"></i>
                            {{ $this->formatPercentage(abs($overview['growth_rate'])) }} vs previous
                        </p>
                    </div>
                    <div class="bg-blue-100 dark:bg-blue-900 p-2 rounded-lg">
                        <i class="fas fa-chart-line text-blue-600 dark:text-blue-400 text-xl"></i>
                    </div>
                </div>
            </div>

            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-5 border-l-4 border-green-600">
                <div class="flex justify-between items-start">
                    <div class="flex-1">
                        <p class="text-gray-600 dark:text-gray-400 text-xs font-medium uppercase">Net Revenue</p>
                        <h3 class="text-2xl font-bold text-gray-900 dark:text-white mt-1">{{ $this->formatCurrency($overview['net_revenue']) }}</h3>
                        <p class="text-xs mt-1 text-gray-500 dark:text-gray-400">After discounts</p>
                    </div>
                    <div class="bg-green-100 dark:bg-green-900 p-2 rounded-lg">
                        <i class="fas fa-money-bill-wave text-green-600 dark:text-green-400 text-xl"></i>
                    </div>
                </div>
            </div>

            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-5 border-l-4 border-purple-600">
                <div class="flex justify-between items-start">
                    <div class="flex-1">
                        <p class="text-gray-600 dark:text-gray-400 text-xs font-medium uppercase">Total Orders</p>
                        <h3 class="text-2xl font-bold text-gray-900 dark:text-white mt-1">{{ number_format($overview['total_orders']) }}</h3>
                        <p class="text-xs mt-1 {{ $overview['order_growth_rate'] >= 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                            <i class="fas fa-{{ $overview['order_growth_rate'] >= 0 ? 'arrow-up' : 'arrow-down' }}"></i>
                            {{ number_format(abs($overview['order_growth_rate']), 1) }}% vs previous
                        </p>
                    </div>
                    <div class="bg-purple-100 dark:bg-purple-900 p-2 rounded-lg">
                        <i class="fas fa-shopping-cart text-purple-600 dark:text-purple-400 text-xl"></i>
                    </div>
                </div>
            </div>

            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-5 border-l-4 border-indigo-600">
                <div class="flex justify-between items-start">
                    <div class="flex-1">
                        <p class="text-gray-600 dark:text-gray-400 text-xs font-medium uppercase">Avg Order Value</p>
                        <h3 class="text-2xl font-bold text-gray-900 dark:text-white mt-1">{{ $this->formatCurrency($overview['avg_order_value']) }}</h3>
                        <p class="text-xs mt-1 text-gray-500 dark:text-gray-400">Per transaction</p>
                    </div>
                    <div class="bg-indigo-100 dark:bg-indigo-900 p-2 rounded-lg">
                        <i class="fas fa-wallet text-indigo-600 dark:text-indigo-400 text-xl"></i>
                    </div>
                </div>
            </div>

            <!-- Row 2: Financial Metrics -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-5 border-l-4 border-emerald-600">
                <div class="flex justify-between items-start">
                    <div class="flex-1">
                        <p class="text-gray-600 dark:text-gray-400 text-xs font-medium uppercase">Gross Profit</p>
                        <h3 class="text-2xl font-bold text-gray-900 dark:text-white mt-1">{{ $this->formatCurrency($profit['gross_profit']) }}</h3>
                        <p class="text-xs mt-1 text-gray-500 dark:text-gray-400">{{ $this->formatPercentage($profit['gross_margin']) }} margin</p>
                    </div>
                    <div class="bg-emerald-100 dark:bg-emerald-900 p-2 rounded-lg">
                        <i class="fas fa-chart-pie text-emerald-600 dark:text-emerald-400 text-xl"></i>
                    </div>
                </div>
            </div>

            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-5 border-l-4 border-orange-600">
                <div class="flex justify-between items-start">
                    <div class="flex-1">
                        <p class="text-gray-600 dark:text-gray-400 text-xs font-medium uppercase">Total Discount</p>
                        <h3 class="text-2xl font-bold text-gray-900 dark:text-white mt-1">{{ $this->formatCurrency($overview['total_discount']) }}</h3>
                        <p class="text-xs mt-1 text-gray-500 dark:text-gray-400">{{ $this->formatPercentage($overview['discount_rate']) }} of subtotal</p>
                    </div>
                    <div class="bg-orange-100 dark:bg-orange-900 p-2 rounded-lg">
                        <i class="fas fa-tag text-orange-600 dark:text-orange-400 text-xl"></i>
                    </div>
                </div>
            </div>

            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-5 border-l-4 border-red-600">
                <div class="flex justify-between items-start">
                    <div class="flex-1">
                        <p class="text-gray-600 dark:text-gray-400 text-xs font-medium uppercase">Refunds</p>
                        <h3 class="text-2xl font-bold text-gray-900 dark:text-white mt-1">{{ $this->formatCurrency($overview['total_refunds']) }}</h3>
                        <p class="text-xs mt-1 text-gray-500 dark:text-gray-400">{{ number_format($overview['refund_count']) }} orders ({{ $this->formatPercentage($overview['refund_rate']) }})</p>
                    </div>
                    <div class="bg-red-100 dark:bg-red-900 p-2 rounded-lg">
                        <i class="fas fa-undo text-red-600 dark:text-red-400 text-xl"></i>
                    </div>
                </div>
            </div>

            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-5 border-l-4 border-teal-600">
                <div class="flex justify-between items-start">
                    <div class="flex-1">
                        <p class="text-gray-600 dark:text-gray-400 text-xs font-medium uppercase">Total Tax</p>
                        <h3 class="text-2xl font-bold text-gray-900 dark:text-white mt-1">{{ $this->formatCurrency($overview['total_tax']) }}</h3>
                        <p class="text-xs mt-1 text-gray-500 dark:text-gray-400">Collected</p>
                    </div>
                    <div class="bg-teal-100 dark:bg-teal-900 p-2 rounded-lg">
                        <i class="fas fa-receipt text-teal-600 dark:text-teal-400 text-xl"></i>
                    </div>
                </div>
            </div>
        </div>
    
        <!-- Tabs Navigation -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow mb-6">
            <div class="border-b border-gray-200 dark:border-gray-700">
                <nav class="flex flex-wrap -mb-px">
                    <button wire:click="$set('activeTab', 'overview')" class="px-6 py-4 text-sm font-medium {{ $activeTab === 'overview' ? 'border-b-2 border-blue-600 text-blue-600' : 'text-gray-600 dark:text-gray-400 hover:text-gray-800 dark:hover:text-gray-300' }}">
                        <i class="fas fa-chart-pie mr-2"></i>Overview
                    </button>
                    <button wire:click="$set('activeTab', 'trends')" class="px-6 py-4 text-sm font-medium {{ $activeTab === 'trends' ? 'border-b-2 border-blue-600 text-blue-600' : 'text-gray-600 dark:text-gray-400 hover:text-gray-800 dark:hover:text-gray-300' }}">
                        <i class="fas fa-chart-area mr-2"></i>Trends
                    </button>
                    <button wire:click="$set('activeTab', 'products')" class="px-6 py-4 text-sm font-medium {{ $activeTab === 'products' ? 'border-b-2 border-blue-600 text-blue-600' : 'text-gray-600 dark:text-gray-400 hover:text-gray-800 dark:hover:text-gray-300' }}">
                        <i class="fas fa-box mr-2"></i>Products
                    </button>
                    <button wire:click="$set('activeTab', 'categories')" class="px-6 py-4 text-sm font-medium {{ $activeTab === 'categories' ? 'border-b-2 border-blue-600 text-blue-600' : 'text-gray-600 dark:text-gray-400 hover:text-gray-800 dark:hover:text-gray-300' }}">
                        <i class="fas fa-th-large mr-2"></i>Categories
                    </button>
                    <button wire:click="$set('activeTab', 'shifts')" class="px-6 py-4 text-sm font-medium {{ $activeTab === 'shifts' ? 'border-b-2 border-blue-600 text-blue-600' : 'text-gray-600 dark:text-gray-400 hover:text-gray-800 dark:hover:text-gray-300' }}">
                        <i class="fas fa-clock mr-2"></i>Shifts
                    </button>
                </nav>
            </div>
        </div>
    
        <!-- Tab Content -->
        <div>
            <!-- Overview Tab -->
            <div x-show="$wire.activeTab === 'overview'" class="space-y-6">
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <!-- Payment Methods Chart -->
                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                        <h3 class="text-lg font-semibold dark:text-white mb-4">Payment Methods</h3>
                        <div id="paymentMethodsChart" style="height: 300px;"></div>
                    </div>
            
                    <!-- Order Types Chart -->
                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                        <h3 class="text-lg font-semibold dark:text-white mb-4">Order Types Distribution</h3>
                        <div id="orderTypesChart" style="height: 300px;"></div>
                    </div>
                </div>
    
                <!-- Payment Breakdown Table -->
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                    <h3 class="text-lg font-semibold dark:text-white mb-4">Payment Method Details</h3>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                            <thead class="bg-gray-50 dark:bg-gray-700">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Method</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Transactions</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Total Amount</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Avg Transaction</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">% of Total</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                @php $totalAmount = $payments->sum('total'); @endphp
                                @foreach($payments as $payment)
                                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="px-2 py-1 text-xs font-semibold rounded-full 
                                            {{ $payment->payment_method === 'cash' ? 'bg-green-100 dark:bg-green-900 text-green-800 dark:text-green-300' : 
                                               ($payment->payment_method === 'pos' ? 'bg-blue-100 dark:bg-blue-900 text-blue-800 dark:text-blue-300' : 
                                               ($payment->payment_method === 'transfer' ? 'bg-purple-100 dark:bg-purple-900 text-purple-800 dark:text-purple-300' : 'bg-gray-100 dark:bg-gray-700 text-gray-800 dark:text-gray-300')) }}">
                                            {{ ucfirst($payment->payment_method) }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">{{ number_format($payment->count) }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-semibold text-gray-900 dark:text-gray-100">{{ $this->formatCurrency($payment->total) }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600 dark:text-gray-400">{{ $this->formatCurrency($payment->total / $payment->count) }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600 dark:text-gray-400">{{ $this->formatPercentage(($payment->total / $totalAmount) * 100) }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
    
            <!-- Trends Tab -->
            <div x-show="$wire.activeTab === 'trends'" class="space-y-6">
                <!-- Hourly Sales Chart -->
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                    <h3 class="text-lg font-semibold dark:text-white mb-4">Hourly Sales Performance</h3>
                    <div id="hourlySalesChart" style="height: 400px;"></div>
                </div>
            
                <!-- Daily Sales Chart -->
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                    <h3 class="text-lg font-semibold dark:text-white mb-4">Daily Sales Trend</h3>
                    <div id="dailySalesChart" style="height: 400px;"></div>
                </div>
            </div>
    
            <!-- Products Tab -->
            <div x-show="$wire.activeTab === 'products'" class="space-y-6">
                <!-- Top Products Chart -->
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                    <h3 class="text-lg font-semibold dark:text-white mb-4">Top 10 Products by Revenue</h3>
                    <div id="topProductsChart" style="height: 400px;"></div>
                </div>
            
                <!-- Products Table -->
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                    <h3 class="text-lg font-semibold dark:text-white mb-4">Product Performance Details</h3>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                            <thead class="bg-gray-50 dark:bg-gray-700">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Rank</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Product</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Units Sold</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Revenue</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Orders</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Avg Price</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                @foreach($topProducts as $index => $item)
                                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="inline-flex items-center justify-center w-8 h-8 rounded-full 
                                            {{ $index < 3 ? 'bg-yellow-100 dark:bg-yellow-900 text-yellow-800 dark:text-yellow-300 font-bold' : 'bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-400' }}">
                                            {{ $index + 1 }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ $item->product->name ?? 'N/A' }}</div>
                                        <div class="text-xs text-gray-500 dark:text-gray-400">{{ $item->product->sku ?? '' }}</div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">{{ number_format($item->total_quantity, 2) }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-semibold text-gray-900 dark:text-gray-100">{{ $this->formatCurrency($item->total_revenue) }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600 dark:text-gray-400">{{ number_format($item->order_count) }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600 dark:text-gray-400">{{ $this->formatCurrency($item->total_revenue / $item->total_quantity) }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Categories Tab -->
            <div x-show="$wire.activeTab === 'categories'" class="space-y-6">
                <!-- Category Chart -->
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                    <h3 class="text-lg font-semibold dark:text-white mb-4">Sales by Category</h3>
                    <div id="categoriesChart" style="height: 400px;"></div>
                </div>

                <!-- Categories Table -->
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                    <h3 class="text-lg font-semibold dark:text-white mb-4">Category Performance Details</h3>
                    @if($categories->isEmpty())
                        <div class="text-center py-12">
                            <i class="fas fa-th-large text-gray-400 text-5xl mb-4"></i>
                            <p class="text-gray-500 dark:text-gray-400">No category data available for this period</p>
                        </div>
                    @else
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                                <thead class="bg-gray-50 dark:bg-gray-700">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Category</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Products</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Units Sold</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Revenue</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Orders</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">% of Total</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                    @php $totalRevenue = $categories->sum('total_revenue'); @endphp
                                    @foreach($categories as $category)
                                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700">
                                        <td class="px-6 py-4">
                                            <div class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ $category->category_name }}</div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600 dark:text-gray-400">
                                            {{ number_format($category->product_count) }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">
                                            {{ number_format($category->total_quantity, 2) }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-semibold text-gray-900 dark:text-gray-100">
                                            {{ $this->formatCurrency($category->total_revenue) }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600 dark:text-gray-400">
                                            {{ number_format($category->order_count) }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="flex items-center">
                                                <div class="flex-1">
                                                    <div class="bg-gray-200 dark:bg-gray-700 rounded-full h-2 mr-2">
                                                        <div class="bg-blue-600 h-2 rounded-full" style="width: {{ $totalRevenue > 0 ? ($category->total_revenue / $totalRevenue) * 100 : 0 }}%"></div>
                                                    </div>
                                                </div>
                                                <span class="text-sm text-gray-600 dark:text-gray-400 ml-2">
                                                    {{ $this->formatPercentage($totalRevenue > 0 ? ($category->total_revenue / $totalRevenue) * 100 : 0) }}
                                                </span>
                                            </div>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Shifts Tab -->
            <div x-show="$wire.activeTab === 'shifts'" class="space-y-6">
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                    <h3 class="text-lg font-semibold dark:text-white mb-4">Shift Performance</h3>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                            <thead class="bg-gray-50 dark:bg-gray-700">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Shift #</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Date</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Type</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Employee</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Orders</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Sales</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Cash Variance</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Status</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                @foreach($shifts as $shift)
                                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-gray-100">{{ $shift['shift_number'] }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600 dark:text-gray-400">{{ \Carbon\Carbon::parse($shift['shift_date'])->format('M d, Y') }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="px-2 py-1 text-xs font-semibold rounded-full 
                                            {{ $shift['shift_type'] === 'morning' ? 'bg-yellow-100 dark:bg-yellow-900 text-yellow-800 dark:text-yellow-300' : 
                                               ($shift['shift_type'] === 'afternoon' ? 'bg-orange-100 dark:bg-orange-900 text-orange-800 dark:text-orange-300' : 'bg-indigo-100 dark:bg-indigo-900 text-indigo-800 dark:text-indigo-300') }}">
                                            {{ ucfirst($shift['shift_type']) }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">{{ $shift['employee_name'] }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">{{ number_format($shift['total_orders']) }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-semibold text-gray-900 dark:text-gray-100">{{ $this->formatCurrency($shift['total_sales']) }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm {{ $shift['cash_variance'] == 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                                        {{ $this->formatCurrency($shift['cash_variance']) }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="px-2 py-1 text-xs font-semibold rounded-full 
                                            {{ $shift['status'] === 'verified' ? 'bg-green-100 dark:bg-green-900 text-green-800 dark:text-green-300' : 
                                               ($shift['status'] === 'closed' ? 'bg-blue-100 dark:bg-blue-900 text-blue-800 dark:text-blue-300' : 
                                               ($shift['status'] === 'submitted' ? 'bg-yellow-100 dark:bg-yellow-900 text-yellow-800 dark:text-yellow-300' : 'bg-gray-100 dark:bg-gray-700 text-gray-800 dark:text-gray-300')) }}">
                                            {{ ucfirst($shift['status']) }}
                                        </span>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    
        @push('scripts')
        <script src="https://code.highcharts.com/highcharts.js"></script>
        <script src="https://code.highcharts.com/modules/exporting.js"></script>
        <script src="https://code.highcharts.com/modules/export-data.js"></script>
        <script>
            document.addEventListener('livewire:initialized', () => {
                initializeCharts();

                // Auto-refresh functionality
                let refreshInterval = null;

                Livewire.on('refresh-charts', () => {
                    initializeCharts();
                });

                // Watch for autoRefresh wire model changes
                const autoRefreshWatch = () => {
                    const shouldRefresh = @this.autoRefresh;

                    if (shouldRefresh && !refreshInterval) {
                        refreshInterval = setInterval(() => {
                            @this.call('refreshData');
                        }, 30000);
                        console.log('Auto-refresh enabled');
                    } else if (!shouldRefresh && refreshInterval) {
                        clearInterval(refreshInterval);
                        refreshInterval = null;
                        console.log('Auto-refresh disabled');
                    }
                };

                // Watch for changes in autoRefresh
                Livewire.hook('commit', ({component, commit, respond}) => {
                    if (component.name === 'branch-dashboard.sales-dashboard.analytics.index') {
                        autoRefreshWatch();
                    }
                });

                // Initial check
                autoRefreshWatch();
            });
    
            function initializeCharts() {
                // Payment Methods Pie Chart
                const paymentData = @json($payments);
                Highcharts.chart('paymentMethodsChart', {
                    chart: { type: 'pie' },
                    title: { text: null },
                    plotOptions: {
                        pie: {
                            allowPointSelect: true,
                            cursor: 'pointer',
                            dataLabels: {
                                enabled: true,
                                format: '<b>{point.name}</b>: {point.percentage:.1f}%'
                            }
                        }
                    },
                    series: [{
                        name: 'Amount',
                        colorByPoint: true,
                        data: paymentData.map(p => ({
                            name: p.payment_method.charAt(0).toUpperCase() + p.payment_method.slice(1),
                            y: parseFloat(p.total)
                        }))
                    }],
                    credits: { enabled: false }
                });
    
                // Order Types Column Chart
                const orderTypeData = @json($orderTypes);
                Highcharts.chart('orderTypesChart', {
                    chart: { type: 'column' },
                    title: { text: null },
                    xAxis: {
                        categories: orderTypeData.map(o => o.order_type.replace('_', ' ').toUpperCase()),
                        crosshair: true
                    },
                    yAxis: {
                        min: 0,
                        title: { text: 'Total Sales ({{ $this->getCurrencySymbol() }})' }
                    },
                    series: [{
                        name: 'Sales',
                        data: orderTypeData.map(o => parseFloat(o.total)),
                        colorByPoint: true
                    }],
                    legend: { enabled: false },
                    credits: { enabled: false }
                });
    
                // Hourly Sales Chart
                const hourlyData = @json($hourlySales);
                Highcharts.chart('hourlySalesChart', {
                    chart: { type: 'areaspline' },
                    title: { text: null },
                    xAxis: {
                        categories: hourlyData.map(h => h.hour + ':00'),
                        title: { text: 'Hour of Day' }
                    },
                    yAxis: [
                         {
                             title: { text: 'Sales Amount ({{ $this->getCurrencySymbol() }})' },
                             labels: { format: '{{ $this->getCurrencySymbol() }}{value}' }
                         },
                        {
                            title: { text: 'Number of Orders' },
                            opposite: true
                        }
                    ],
                    series: [
                        {
                            name: 'Sales',
                            data: hourlyData.map(h => parseFloat(h.total)),
                            yAxis: 0,
                            color: '#3b82f6'
                        },
                        {
                            name: 'Orders',
                            data: hourlyData.map(h => parseInt(h.count)),
                            yAxis: 1,
                            color: '#10b981'
                        }
                    ],
                    credits: { enabled: false }
                });
    
                // Daily Sales Chart
                const dailyData = @json($dailySales);
                Highcharts.chart('dailySalesChart', {
                    chart: { type: 'line' },
                    title: { text: null },
                    xAxis: {
                        categories: dailyData.map(d => new Date(d.date).toLocaleDateString()),
                        title: { text: 'Date' }
                    },
                    yAxis: {
                        title: { text: 'Amount ({{ $this->getCurrencySymbol() }})' }
                    },
                    series: [
                        {
                            name: 'Total Sales',
                            data: dailyData.map(d => parseFloat(d.total)),
                            color: '#3b82f6'
                        },
                        {
                            name: 'Average Order',
                            data: dailyData.map(d => parseFloat(d.avg_order)),
                            color: '#f59e0b'
                        }
                    ],
                    credits: { enabled: false }
                });
    
                // Top Products Bar Chart
                const productData = @json($topProducts);
                Highcharts.chart('topProductsChart', {
                    chart: { type: 'bar' },
                    title: { text: null },
                    xAxis: {
                        categories: productData.map(p => p.product?.name || 'N/A'),
                        title: { text: null }
                    },
                    yAxis: {
                        min: 0,
                        title: { text: 'Revenue ({{ $this->getCurrencySymbol() }})' }
                    },
                    series: [{
                        name: 'Revenue',
                        data: productData.map(p => parseFloat(p.total_revenue)),
                        colorByPoint: true
                    }],
                    legend: { enabled: false },
                    credits: { enabled: false }
                });

                // Categories Chart
                const categoryData = @json($categories);
                if (categoryData && categoryData.length > 0) {
                    Highcharts.chart('categoriesChart', {
                        chart: { type: 'column' },
                        title: { text: null },
                        xAxis: {
                            categories: categoryData.map(c => c.category_name),
                            crosshair: true
                        },
                        yAxis: {
                            min: 0,
                            title: { text: 'Revenue ({{ $this->getCurrencySymbol() }})' }
                        },
                        tooltip: {
                            formatter: function() {
                                const cat = categoryData[this.point.index];
                                return '<b>' + cat.category_name + '</b><br/>' +
                                       'Revenue: {{ $this->getCurrencySymbol() }}' + parseFloat(cat.total_revenue).toLocaleString() + '<br/>' +
                                       'Units Sold: ' + parseFloat(cat.total_quantity).toLocaleString() + '<br/>' +
                                       'Orders: ' + parseInt(cat.order_count).toLocaleString() + '<br/>' +
                                       'Products: ' + parseInt(cat.product_count);
                            }
                        },
                        series: [{
                            name: 'Revenue',
                            data: categoryData.map(c => parseFloat(c.total_revenue)),
                            colorByPoint: true
                        }],
                        legend: { enabled: false },
                        credits: { enabled: false }
                    });
                }
            }
        </script>
        @endpush
    </div>
</div> 
