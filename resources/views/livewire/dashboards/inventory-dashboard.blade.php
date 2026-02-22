<div class="space-y-6">
    <!-- Page Title -->
    <div>
        <h1 class="text-3xl font-bold text-gray-900">Inventory Dashboard</h1>
        <p class="text-gray-600 mt-2">Monitor stock levels, movements, and inventory health</p>
    </div>

    <!-- Filters and Actions -->
    <div class="bg-white rounded-lg border border-gray-200 p-4 flex flex-wrap gap-4 items-center justify-between">
        <div class="flex gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Date Range</label>
                <input type="date" wire:model.debounce-500ms="dateFrom" class="px-3 py-2 border border-gray-300 rounded-lg text-sm">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">To</label>
                <input type="date" wire:model.debounce-500ms="dateTo" class="px-3 py-2 border border-gray-300 rounded-lg text-sm">
            </div>
        </div>
        <div class="flex gap-2">
            <button wire:click="refresh" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition">
                Refresh
            </button>
        </div>
    </div>

    <!-- KPI Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        <!-- Total Stock Value -->
        <div class="bg-white rounded-lg border border-gray-200 p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600 font-medium">Total Stock Value</p>
                    <p class="text-2xl font-bold text-gray-900 mt-2">{{ $totalStockValue ?? \App\Helpers\LocalizationHelper::formatCurrency(0) }}</p>
                </div>
                <div class="p-3 bg-blue-100 rounded-lg">
                    <svg class="w-6 h-6 text-blue-600" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M10.5 1.5H5.75A2.25 2.25 0 003.5 3.75v12.5A2.25 2.25 0 005.75 18.5h8.5a2.25 2.25 0 002.25-2.25V6.5m-10-5v3.75m5-3.75v3.75"></path>
                    </svg>
                </div>
            </div>
            @if(isset($stockValueTrend))
                <p class="text-xs text-green-600 mt-4">{{ $stockValueTrend }}% from last month</p>
            @endif
        </div>

        <!-- Items in Stock -->
        <div class="bg-white rounded-lg border border-gray-200 p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600 font-medium">Items in Stock</p>
                    <p class="text-2xl font-bold text-gray-900 mt-2">{{ $itemsInStock ?? '0' }}</p>
                </div>
                <div class="p-3 bg-green-100 rounded-lg">
                    <svg class="w-6 h-6 text-green-600" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M3 3a1 1 0 011-1h12a1 1 0 011 1v2h2a2 2 0 012 2v9a2 2 0 01-2 2h-2v2a1 1 0 01-1 1H4a1 1 0 01-1-1v-2H1a2 2 0 01-2-2V7a2 2 0 012-2h2V3z"></path>
                    </svg>
                </div>
            </div>
        </div>

        <!-- Low Stock Alerts -->
        <div class="bg-white rounded-lg border border-gray-200 p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600 font-medium">Low Stock Items</p>
                    <p class="text-2xl font-bold text-red-600 mt-2">{{ $lowStockCount ?? '0' }}</p>
                </div>
                <div class="p-3 bg-red-100 rounded-lg">
                    <svg class="w-6 h-6 text-red-600" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                    </svg>
                </div>
            </div>
        </div>

        <!-- Recent Movements -->
        <div class="bg-white rounded-lg border border-gray-200 p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600 font-medium">Movements (24h)</p>
                    <p class="text-2xl font-bold text-purple-600 mt-2">{{ $recentMovements ?? '0' }}</p>
                </div>
                <div class="p-3 bg-purple-100 rounded-lg">
                    <svg class="w-6 h-6 text-purple-600" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M3 3a1 1 0 000 2h11a1 1 0 100-2H3zM3 7a1 1 0 000 2h5a1 1 0 000-2H3zM3 11a1 1 0 100 2h4a1 1 0 100-2H3zM13 16a1 1 0 102 0v-5.586l1.293 1.293a1 1 0 001.414-1.414l-3-3a1 1 0 00-1.414 0l-3 3a1 1 0 101.414 1.414L13 10.414V16z"></path>
                    </svg>
                </div>
            </div>
        </div>
    </div>

    <!-- Charts and Tables Row -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Stock Trend Chart -->
        <div class="lg:col-span-2 bg-white rounded-lg border border-gray-200 p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Stock Trend (Last 30 Days)</h3>
            <div class="flex items-center justify-center h-64 bg-gray-50 rounded-lg">
                <p class="text-gray-500">Chart placeholder - Connect to real data</p>
            </div>
        </div>

        <!-- Health Status -->
        <div class="bg-white rounded-lg border border-gray-200 p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Inventory Health</h3>
            <div class="space-y-4">
                <div>
                    <div class="flex justify-between mb-2">
                        <span class="text-sm font-medium text-gray-700">Stock Accuracy</span>
                        <span class="text-sm font-bold text-green-600">95%</span>
                    </div>
                    <div class="w-full bg-gray-200 rounded-full h-2">
                        <div class="bg-green-600 h-2 rounded-full" style="width: 95%"></div>
                    </div>
                </div>
                <div>
                    <div class="flex justify-between mb-2">
                        <span class="text-sm font-medium text-gray-700">Turnover Rate</span>
                        <span class="text-sm font-bold text-blue-600">72%</span>
                    </div>
                    <div class="w-full bg-gray-200 rounded-full h-2">
                        <div class="bg-blue-600 h-2 rounded-full" style="width: 72%"></div>
                    </div>
                </div>
                <div>
                    <div class="flex justify-between mb-2">
                        <span class="text-sm font-medium text-gray-700">Expiry Risk</span>
                        <span class="text-sm font-bold text-red-600">12%</span>
                    </div>
                    <div class="w-full bg-gray-200 rounded-full h-2">
                        <div class="bg-red-600 h-2 rounded-full" style="width: 12%"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Low Stock Items Table -->
    <div class="bg-white rounded-lg border border-gray-200 p-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">Items Below Minimum Level</h3>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="border-b border-gray-200 bg-gray-50">
                    <tr>
                        <th class="px-4 py-2 text-left font-medium text-gray-900">Item Name</th>
                        <th class="px-4 py-2 text-right font-medium text-gray-900">Current Stock</th>
                        <th class="px-4 py-2 text-right font-medium text-gray-900">Minimum Level</th>
                        <th class="px-4 py-2 text-right font-medium text-gray-900">Status</th>
                        <th class="px-4 py-2 text-center font-medium text-gray-900">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3 text-gray-900">Wheat Flour</td>
                        <td class="px-4 py-3 text-right text-gray-600">5 kg</td>
                        <td class="px-4 py-3 text-right text-gray-600">10 kg</td>
                        <td class="px-4 py-3 text-right">
                            <span class="px-2 py-1 bg-red-100 text-red-700 rounded text-xs font-medium">Critical</span>
                        </td>
                        <td class="px-4 py-3 text-center">
                            <button class="text-blue-600 hover:underline">Reorder</button>
                        </td>
                    </tr>
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3 text-gray-900">Sugar</td>
                        <td class="px-4 py-3 text-right text-gray-600">8 kg</td>
                        <td class="px-4 py-3 text-right text-gray-600">15 kg</td>
                        <td class="px-4 py-3 text-right">
                            <span class="px-2 py-1 bg-yellow-100 text-yellow-700 rounded text-xs font-medium">Low</span>
                        </td>
                        <td class="px-4 py-3 text-center">
                            <button class="text-blue-600 hover:underline">Reorder</button>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>
