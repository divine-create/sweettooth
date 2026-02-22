<div>
    <div class="p-6 bg-gray-50 dark:bg-gray-900 min-h-screen">
        <!-- Header -->
        <div class="mb-6">
            <div class="flex justify-between items-center mb-4">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900 dark:text-white">My Sales Performance</h1>
                    <p class="text-gray-600 dark:text-gray-400 mt-1">Track your personal sales and performance metrics</p>
                </div>
                <div class="flex gap-3">
                    <button wire:click="refreshData" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition">
                        <i class="fas fa-sync-alt mr-2"></i>Refresh
                    </button>
                </div>
            </div>

            <!-- Date Filters -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
                <div class="grid grid-cols-1 md:grid-cols-5 gap-4">
                    <div class="col-span-2">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Quick Filters</label>
                        <div class="flex flex-wrap gap-2">
                            <button wire:click="setDateRange('today')" class="px-3 py-1 rounded {{ $selectedPeriod === 'today' ? 'bg-blue-600 text-white' : 'bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300' }}">Today</button>
                            <button wire:click="setDateRange('yesterday')" class="px-3 py-1 rounded {{ $selectedPeriod === 'yesterday' ? 'bg-blue-600 text-white' : 'bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300' }}">Yesterday</button>
                            <button wire:click="setDateRange('this_week')" class="px-3 py-1 rounded {{ $selectedPeriod === 'this_week' ? 'bg-blue-600 text-white' : 'bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300' }}">This Week</button>
                            <button wire:click="setDateRange('this_month')" class="px-3 py-1 rounded {{ $selectedPeriod === 'this_month' ? 'bg-blue-600 text-white' : 'bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300' }}">This Month</button>
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">From Date</label>
                        <input type="datetime-local" wire:model.live="dateFrom" class="w-full px-3 py-2 border dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">To Date</label>
                        <input type="datetime-local" wire:model.live="dateTo" class="w-full px-3 py-2 border dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                    </div>
                </div>
            </div>
        </div>

        <!-- Overview Cards -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-5 border-l-4 border-blue-600">
                <div class="flex justify-between items-start">
                    <div class="flex-1">
                        <p class="text-gray-600 dark:text-gray-400 text-xs font-medium uppercase">My Total Sales</p>
                        <h3 class="text-2xl font-bold text-gray-900 dark:text-white mt-1">
                            {{ \App\Helpers\LocalizationHelper::formatCurrency($overview['total_sales'] ?? 0) }}
                        </h3>
                        <p class="text-xs mt-1 {{ $overview['growth_rate'] >= 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                            <i class="fas fa-{{ $overview['growth_rate'] >= 0 ? 'arrow-up' : 'arrow-down' }}"></i>
                            {{ number_format(abs($overview['growth_rate']), 1) }}% vs previous
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
                        <p class="text-gray-600 dark:text-gray-400 text-xs font-medium uppercase">My Orders</p>
                        <h3 class="text-2xl font-bold text-gray-900 dark:text-white mt-1">{{ number_format($overview['total_orders']) }}</h3>
                        <p class="text-xs mt-1 text-gray-500 dark:text-gray-400">Orders completed</p>
                    </div>
                    <div class="bg-green-100 dark:bg-green-900 p-2 rounded-lg">
                        <i class="fas fa-shopping-cart text-green-600 dark:text-green-400 text-xl"></i>
                    </div>
                </div>
            </div>

            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-5 border-l-4 border-purple-600">
                <div class="flex justify-between items-start">
                    <div class="flex-1">
                        <p class="text-gray-600 dark:text-gray-400 text-xs font-medium uppercase">Avg Order Value</p>
                        <h3 class="text-2xl font-bold text-gray-900 dark:text-white mt-1">
                            {{ \App\Helpers\LocalizationHelper::formatCurrency($overview['avg_order_value'] ?? 0) }}
                        </h3>
                        <p class="text-xs mt-1 text-gray-500 dark:text-gray-400">Per transaction</p>
                    </div>
                    <div class="bg-purple-100 dark:bg-purple-900 p-2 rounded-lg">
                        <i class="fas fa-wallet text-purple-600 dark:text-purple-400 text-xl"></i>
                    </div>
                </div>
            </div>

            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-5 border-l-4 border-orange-600">
                <div class="flex justify-between items-start">
                    <div class="flex-1">
                        <p class="text-gray-600 dark:text-gray-400 text-xs font-medium uppercase">Discounts Given</p>
                        <h3 class="text-2xl font-bold text-gray-900 dark:text-white mt-1">
                            {{ \App\Helpers\LocalizationHelper::formatCurrency($overview['total_discount'] ?? 0) }}
                        </h3>
                        <p class="text-xs mt-1 text-gray-500 dark:text-gray-400">Total discounts</p>
                    </div>
                    <div class="bg-orange-100 dark:bg-orange-900 p-2 rounded-lg">
                        <i class="fas fa-tag text-orange-600 dark:text-orange-400 text-xl"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tabs -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow mb-6">
            <div class="border-b border-gray-200 dark:border-gray-700">
                <nav class="flex flex-wrap -mb-px">
                    <button wire:click="$set('activeTab', 'overview')" class="px-6 py-4 text-sm font-medium {{ $activeTab === 'overview' ? 'border-b-2 border-blue-600 text-blue-600' : 'text-gray-600 dark:text-gray-400 hover:text-gray-800 dark:hover:text-gray-300' }}">
                        <i class="fas fa-chart-bar mr-2"></i>Overview
                    </button>
                    <button wire:click="$set('activeTab', 'products')" class="px-6 py-4 text-sm font-medium {{ $activeTab === 'products' ? 'border-b-2 border-blue-600 text-blue-600' : 'text-gray-600 dark:text-gray-400 hover:text-gray-800 dark:hover:text-gray-300' }}">
                        <i class="fas fa-box mr-2"></i>My Top Products
                    </button>
                    <button wire:click="$set('activeTab', 'recent')" class="px-6 py-4 text-sm font-medium {{ $activeTab === 'recent' ? 'border-b-2 border-blue-600 text-blue-600' : 'text-gray-600 dark:text-gray-400 hover:text-gray-800 dark:hover:text-gray-300' }}">
                        <i class="fas fa-history mr-2"></i>Recent Sales
                    </button>
                </nav>
            </div>
        </div>

        <!-- Tab Content -->
        <div>
            <!-- Overview Tab -->
            <div x-show="$wire.activeTab === 'overview'" class="space-y-6">
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <!-- Hourly Sales Table -->
                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                        <div class="flex justify-between items-center mb-4">
                            <h3 class="text-lg font-semibold dark:text-white">My Hourly Sales</h3>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                                <thead class="bg-gray-50 dark:bg-gray-700">
                                    <tr>
                                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Hour</th>
                                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Sales</th>
                                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Orders</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                    @forelse($hourlySales as $hour)
                                    <tr>
                                        <td class="px-4 py-2 text-sm text-gray-900 dark:text-gray-100">{{ $hour->hour }}:00</td>
                                        <td class="px-4 py-2 text-sm font-semibold text-gray-900 dark:text-gray-100">
                                            {{ \App\Helpers\LocalizationHelper::formatCurrency($hour->total ?? 0) }}
                                        </td>
                                        <td class="px-4 py-2 text-sm text-gray-600 dark:text-gray-400">{{ number_format($hour->count) }}</td>
                                    </tr>
                                    @empty
                                    <tr>
                                        <td colspan="3" class="px-4 py-4 text-center text-gray-500 dark:text-gray-400">No hourly data</td>
                                    </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Payment Methods Table -->
                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                        <div class="flex justify-between items-center mb-4">
                            <h3 class="text-lg font-semibold dark:text-white">Payment Methods</h3>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                                <thead class="bg-gray-50 dark:bg-gray-700">
                                    <tr>
                                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Method</th>
                                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Transactions</th>
                                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Total</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                    @php $totalPayments = $payments->sum('total'); @endphp
                                    @forelse($payments as $payment)
                                    <tr>
                                        <td class="px-4 py-2">
                                            <span class="px-2 py-1 text-xs font-semibold rounded-full
                                                {{ $payment->payment_method === 'cash' ? 'bg-green-100 dark:bg-green-900 text-green-800 dark:text-green-300' :
                                                   ($payment->payment_method === 'pos' ? 'bg-blue-100 dark:bg-blue-900 text-blue-800 dark:text-blue-300' : 'bg-purple-100 dark:bg-purple-900 text-purple-800 dark:text-purple-300') }}">
                                                {{ ucfirst($payment->payment_method) }}
                                            </span>
                                        </td>
                                        <td class="px-4 py-2 text-sm text-gray-900 dark:text-gray-100">{{ number_format($payment->count) }}</td>
                                        <td class="px-4 py-2 text-sm font-semibold text-gray-900 dark:text-gray-100">
                                            {{ \App\Helpers\LocalizationHelper::formatCurrency($payment->total ?? 0) }}
                                            <span class="text-xs text-gray-500">({{ $totalPayments > 0 ? number_format(($payment->total / $totalPayments) * 100, 1) : 0 }}%)</span>
                                        </td>
                                    </tr>
                                    @empty
                                    <tr>
                                        <td colspan="3" class="px-4 py-4 text-center text-gray-500 dark:text-gray-400">No payment data</td>
                                    </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Daily Sales Table -->
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="text-lg font-semibold dark:text-white">My Daily Sales Trend</h3>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                            <thead class="bg-gray-50 dark:bg-gray-700">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Date</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Total Sales</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Orders</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Avg Order</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                @forelse($dailySales as $day)
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">{{ \Carbon\Carbon::parse($day->date)->format('M d, Y') }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-semibold text-gray-900 dark:text-gray-100">
                                        {{ \App\Helpers\LocalizationHelper::formatCurrency($day->total ?? 0) }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600 dark:text-gray-400">{{ number_format($day->count) }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600 dark:text-gray-400">
                                        {{ \App\Helpers\LocalizationHelper::formatCurrency($day->avg_order ?? 0) }}
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan=\"4\" class=\"px-6 py-8 text-center text-gray-500 dark:text-gray-400\">No daily data available</td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Order Types Table -->
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                    <h3 class="text-lg font-semibold dark:text-white mb-4">Order Types Breakdown</h3>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                            <thead class="bg-gray-50 dark:bg-gray-700">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Order Type</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Count</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Total Amount</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Avg Per Order</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                @forelse($orderTypes as $orderType)
                                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="px-2 py-1 text-xs font-semibold rounded-full bg-blue-100 dark:bg-blue-900 text-blue-800 dark:text-blue-300">
                                            {{ ucfirst(str_replace('_', ' ', $orderType->order_type)) }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">{{ number_format($orderType->count) }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-semibold text-gray-900 dark:text-gray-100">
                                        {{ \App\Helpers\LocalizationHelper::formatCurrency($orderType->total ?? 0) }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600 dark:text-gray-400">
                                        {{ \App\Helpers\LocalizationHelper::formatCurrency(($orderType->count ?? 0) > 0 ? ($orderType->total / $orderType->count) : 0) }}
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="4" class="px-6 py-8 text-center text-gray-500 dark:text-gray-400">No order data available</td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Products Tab -->
            <div x-show="$wire.activeTab === 'products'" class="space-y-6">
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
                                </tr>
                            </thead>
                            <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                @forelse($topProducts as $index => $item)
                                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="inline-flex items-center justify-center w-8 h-8 rounded-full {{ $index < 3 ? 'bg-yellow-100 dark:bg-yellow-900 text-yellow-800 dark:text-yellow-300 font-bold' : 'bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-400' }}">
                                            {{ $index + 1 }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ $item->product->name ?? 'N/A' }}</div>
                                        <div class="text-xs text-gray-500 dark:text-gray-400">{{ $item->product->sku ?? '' }}</div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">{{ number_format($item->total_quantity, 2) }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-semibold text-gray-900 dark:text-gray-100">
                                        {{ \App\Helpers\LocalizationHelper::formatCurrency($item->total_revenue ?? 0) }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600 dark:text-gray-400">{{ number_format($item->order_count) }}</td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="5" class="px-6 py-8 text-center text-gray-500 dark:text-gray-400">No product data available</td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Recent Sales Tab -->
            <div x-show="$wire.activeTab === 'recent'" class="space-y-6">
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                    <h3 class="text-lg font-semibold dark:text-white mb-4">My Recent Sales</h3>
                    <div class="space-y-4">
                        @forelse($recentSales as $sale)
                        <div class="border dark:border-gray-700 rounded-lg p-4 hover:bg-gray-50 dark:hover:bg-gray-700">
                            <div class="flex justify-between items-start mb-2">
                                <div>
                                    <span class="text-sm font-semibold text-gray-900 dark:text-gray-100">Sale #{{ $sale->sale_number }}</span>
                                    <span class="ml-2 px-2 py-1 text-xs rounded-full bg-green-100 dark:bg-green-900 text-green-800 dark:text-green-300">
                                        {{ ucfirst($sale->status) }}
                                    </span>
                                </div>
                                <div class="flex items-center gap-2">
                                    <span class="text-sm text-gray-600 dark:text-gray-400">{{ $sale->sale_time->format('M d, Y h:i A') }}</span>
                                    <button type="button"
                                            wire:click="printReceipt({{ $sale->id }})"
                                            class="inline-flex items-center gap-1 px-3 py-1.5 text-xs rounded bg-blue-600 text-white hover:bg-blue-700">
                                        Print Receipt
                                    </button>
                                </div>
                            </div>
                            <div class="grid grid-cols-2 gap-4 text-sm">
                                <div>
                                    <span class="text-gray-600 dark:text-gray-400">Order Type:</span>
                                    <span class="ml-2 text-gray-900 dark:text-gray-100">{{ ucfirst(str_replace('_', ' ', $sale->order_type)) }}</span>
                                </div>
                                <div>
                                    <span class="text-gray-600 dark:text-gray-400">Items:</span>
                                    <span class="ml-2 text-gray-900 dark:text-gray-100">{{ $sale->saleItems->count() }} items</span>
                                </div>
                                <div>
                                    <span class="text-gray-600 dark:text-gray-400">Subtotal:</span>
                                    <span class="ml-2 text-gray-900 dark:text-gray-100">
                                        {{ \App\Helpers\LocalizationHelper::formatCurrency($sale->subtotal ?? 0) }}
                                    </span>
                                </div>
                                <div>
                                    <span class="text-gray-600 dark:text-gray-400">Total:</span>
                                    <span class="ml-2 font-semibold text-gray-900 dark:text-gray-100">
                                        {{ \App\Helpers\LocalizationHelper::formatCurrency($sale->total ?? 0) }}
                                    </span>
                                </div>
                            </div>
                        </div>
                        @empty
                        <div class="text-center py-12">
                            <i class="fas fa-receipt text-gray-400 text-5xl mb-4"></i>
                            <p class="text-gray-500 dark:text-gray-400">No recent sales found</p>
                        </div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>

@push('scripts')
<script>
    window.addEventListener('print-receipt', (event) => {
        const html = event.detail?.html || '';
        if (!html) return;
        const win = window.open('', '_blank', 'width=420,height=720');
        if (!win) return;
        win.document.open();
        win.document.write(`<html><head><title>Receipt</title></head><body>${html}</body></html>`);
        win.document.close();
        win.focus();
        win.print();
    });
</script>
@endpush
