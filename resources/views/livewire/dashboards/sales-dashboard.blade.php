<div class="space-y-6">
    <!-- Page Title -->
    <div>
        <h1 class="text-3xl font-bold text-gray-900">Sales Dashboard</h1>
        <p class="text-gray-600 mt-2">Track sales performance, transactions, and customer activity</p>
    </div>

    <!-- Filters -->
    <div class="bg-white rounded-lg border border-gray-200 p-4 flex flex-wrap gap-4 items-center justify-between">
        <div class="flex gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Time Range</label>
                <select wire:model.debounce-500ms="timeRange" class="px-3 py-2 border border-gray-300 rounded-lg text-sm">
                    <option value="today">Today</option>
                    <option value="week">This Week</option>
                    <option value="month">This Month</option>
                </select>
            </div>
        </div>
        <div class="flex gap-2">
            <button wire:click="refresh" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition">
                Refresh
            </button>
        </div>
    </div>

    <!-- KPI Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-6">
        <!-- Today's Sales -->
        <div class="bg-white rounded-lg border border-gray-200 p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600 font-medium">Today's Sales</p>
                    <p class="text-2xl font-bold text-gray-900 mt-2">{{ $todaysSales ?? \App\Helpers\LocalizationHelper::formatCurrency(0) }}</p>
                </div>
                <div class="p-3 bg-green-100 rounded-lg">
                    <svg class="w-6 h-6 text-green-600" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M8.16 2.75a.75.75 0 00-1.32 0l-.5 1.45H4.75a.75.75 0 000 1.5h1.13l-.5 1.45a.75.75 0 001.32.44l.5-1.45h1.62l-.5 1.45a.75.75 0 001.32.44l.5-1.45H10a.75.75 0 000-1.5h-1.13l.5-1.45a.75.75 0 00-1.32-.44l-.5 1.45H5.83l.5-1.45a.75.75 0 00-1.32-.44l-.5 1.45H2.75a.75.75 0 000-1.5h1.13l.5-1.45z"></path>
                    </svg>
                </div>
            </div>
        </div>

        <!-- Transactions -->
        <div class="bg-white rounded-lg border border-gray-200 p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600 font-medium">Transactions</p>
                    <p class="text-2xl font-bold text-gray-900 mt-2">{{ $transactionCount ?? '0' }}</p>
                </div>
                <div class="p-3 bg-blue-100 rounded-lg">
                    <svg class="w-6 h-6 text-blue-600" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M4 4a2 2 0 00-2 2v4a2 2 0 002 2V6h10a2 2 0 00-2-2H4zm2 6a2 2 0 012-2h8a2 2 0 012 2v4a2 2 0 01-2 2H8a2 2 0 01-2-2v-4zm6 4a2 2 0 100-4 2 2 0 000 4z"></path>
                    </svg>
                </div>
            </div>
        </div>

        <!-- Till Balance -->
        <div class="bg-white rounded-lg border border-gray-200 p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600 font-medium">Till Balance</p>
                    <p class="text-2xl font-bold text-gray-900 mt-2">{{ $tillBalance ?? \App\Helpers\LocalizationHelper::formatCurrency(0) }}</p>
                </div>
                <div class="p-3 bg-purple-100 rounded-lg">
                    <svg class="w-6 h-6 text-purple-600" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M5 2a1 1 0 011-1h8a1 1 0 011 1v1h4a1 1 0 011 1v2a1 1 0 01-1 1H2a1 1 0 01-1-1V4a1 1 0 011-1h4V2zm0 5h10v7a2 2 0 11-4 0 2 2 0 11-4 0v-7z" clip-rule="evenodd"></path>
                    </svg>
                </div>
            </div>
        </div>

        <!-- Best Sellers -->
        <div class="bg-white rounded-lg border border-gray-200 p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600 font-medium">Best Sellers</p>
                    <p class="text-2xl font-bold text-orange-600 mt-2">{{ $bestSellersCount ?? '0' }}</p>
                </div>
                <div class="p-3 bg-orange-100 rounded-lg">
                    <svg class="w-6 h-6 text-orange-600" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"></path>
                    </svg>
                </div>
            </div>
        </div>

        <!-- Customers -->
        <div class="bg-white rounded-lg border border-gray-200 p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600 font-medium">Customers</p>
                    <p class="text-2xl font-bold text-indigo-600 mt-2">{{ $customerCount ?? '0' }}</p>
                </div>
                <div class="p-3 bg-indigo-100 rounded-lg">
                    <svg class="w-6 h-6 text-indigo-600" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M13 6a3 3 0 11-6 0 3 3 0 016 0zM18 8a2 2 0 11-4 0 2 2 0 014 0zM14 15a4 4 0 00-8 0v2h8v-2zM6 8a2 2 0 11-4 0 2 2 0 014 0zM16 18v-2a4 4 0 00-8 0v2h8z"></path>
                    </svg>
                </div>
            </div>
        </div>
    </div>

    <!-- Sales Trend Chart -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2 bg-white rounded-lg border border-gray-200 p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Sales Trend</h3>
            <div class="flex items-center justify-center h-64 bg-gray-50 rounded-lg">
                <p class="text-gray-500">Chart placeholder - Connect to real data</p>
            </div>
        </div>

        <!-- Quick Stats -->
        <div class="bg-white rounded-lg border border-gray-200 p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Quick Stats</h3>
            <div class="space-y-3">
                <div class="flex justify-between items-center p-3 bg-gray-50 rounded-lg">
                    <span class="text-sm text-gray-600">Avg. Transaction</span>
                    <span class="font-bold text-gray-900">{{ $avgTransaction ?? \App\Helpers\LocalizationHelper::formatCurrency(0) }}</span>
                </div>
                <div class="flex justify-between items-center p-3 bg-gray-50 rounded-lg">
                    <span class="text-sm text-gray-600">Cash vs Card</span>
                    <span class="font-bold text-gray-900">{{ $cashVsCard ?? '60:40' }}</span>
                </div>
                <div class="flex justify-between items-center p-3 bg-gray-50 rounded-lg">
                    <span class="text-sm text-gray-600">Peak Hour</span>
                    <span class="font-bold text-gray-900">{{ $peakHour ?? 'N/A' }}</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Top Products -->
    <div class="bg-white rounded-lg border border-gray-200 p-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">Top Selling Products</h3>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="border-b border-gray-200 bg-gray-50">
                    <tr>
                        <th class="px-4 py-2 text-left font-medium text-gray-900">Product Name</th>
                        <th class="px-4 py-2 text-right font-medium text-gray-900">Units Sold</th>
                        <th class="px-4 py-2 text-right font-medium text-gray-900">Revenue</th>
                        <th class="px-4 py-2 text-right font-medium text-gray-900">% of Total</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3 text-gray-900">Sourdough Bread</td>
                        <td class="px-4 py-3 text-right text-gray-600">45</td>
                        <td class="px-4 py-3 text-right text-gray-600">{{ \App\Helpers\LocalizationHelper::formatCurrency(225) }}</td>
                        <td class="px-4 py-3 text-right text-gray-600">15%</td>
                    </tr>
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3 text-gray-900">Vanilla Gelato</td>
                        <td class="px-4 py-3 text-right text-gray-600">38</td>
                        <td class="px-4 py-3 text-right text-gray-600">{{ \App\Helpers\LocalizationHelper::formatCurrency(190) }}</td>
                        <td class="px-4 py-3 text-right text-gray-600">12%</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>
