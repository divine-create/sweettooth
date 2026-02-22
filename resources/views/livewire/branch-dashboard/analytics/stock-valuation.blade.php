<div class="p-3 space-y-3">
    <!-- Breadcrumb -->
    <x-breadcrumb
        title="Stock Valuation"
        :items="[
            ['label' => 'Dashboard', 'url' => branch_route('branch-dashboard.index')],
            ['label' => 'Analytics'],
            ['label' => 'Stock Valuation']
        ]"
        :compact="false"
        :with-icons="true"
    />

    <div class="flex flex-wrap justify-end gap-2">
        <button wire:click="generateReport"
                wire:loading.attr="disabled"
                wire:target="generateReport"
                class="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg font-medium text-sm disabled:opacity-60">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-3-3v6m8 4H4a1 1 0 01-1-1V6a1 1 0 011-1h10l6 6v7a1 1 0 01-1 1z"/>
            </svg>
            <span wire:loading.remove wire:target="generateReport">Generate Report</span>
            <span wire:loading wire:target="generateReport">Generating...</span>
        </button>
        <button wire:click="exportCSV"
                class="inline-flex items-center gap-2 px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg font-medium text-sm">
            Export CSV
        </button>
        <button wire:click="exportCSV"
                class="inline-flex items-center gap-2 px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg font-medium text-sm">
            Export CSV
        </button>
    </div>

    @if (session('success'))
        <div class="p-3 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-700 rounded text-sm text-green-800 dark:text-green-200">
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
        <div class="p-3 bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-700 rounded text-sm text-yellow-800 dark:text-yellow-200">
            {{ session('warning') }}
        </div>
    @endif

    <!-- Summary Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-3">
        <div class="bg-gradient-to-br from-blue-500 to-blue-600 text-white rounded-lg shadow-sm p-3">
            <p class="text-xs opacity-90">Total Value</p>
            <p class="text-xl font-bold">
                {{ \App\Helpers\LocalizationHelper::formatCurrency($summary['total_value'] ?? 0) }}
            </p>
            <p class="text-xs opacity-75 mt-1">All stock</p>
        </div>
        <div class="bg-green-50 dark:bg-green-900/20 rounded-lg shadow-sm p-3 border border-green-200 dark:border-green-700">
            <p class="text-xs text-gray-600 dark:text-gray-400">Available</p>
            <p class="text-xl font-bold text-green-600 dark:text-green-500">
                {{ \App\Helpers\LocalizationHelper::formatCurrency($summary['available_value'] ?? 0) }}
            </p>
            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Ready to use</p>
        </div>
        <div class="bg-blue-50 dark:bg-blue-900/20 rounded-lg shadow-sm p-3 border border-blue-200 dark:border-blue-700">
            <p class="text-xs text-gray-600 dark:text-gray-400">Reserved</p>
            <p class="text-xl font-bold text-blue-600 dark:text-blue-500">
                {{ \App\Helpers\LocalizationHelper::formatCurrency($summary['reserved_value'] ?? 0) }}
            </p>
            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">In orders</p>
        </div>
        <div class="bg-red-50 dark:bg-red-900/20 rounded-lg shadow-sm p-3 border border-red-200 dark:border-red-700">
            <p class="text-xs text-gray-600 dark:text-gray-400">Damaged</p>
            <p class="text-xl font-bold text-red-600 dark:text-red-500">
                {{ \App\Helpers\LocalizationHelper::formatCurrency($summary['damaged_value'] ?? 0) }}
            </p>
            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Write-off</p>
        </div>
        <div class="bg-purple-50 dark:bg-purple-900/20 rounded-lg shadow-sm p-3 border border-purple-200 dark:border-purple-700">
            <p class="text-xs text-gray-600 dark:text-gray-400">Total Items</p>
            <p class="text-xl font-bold text-purple-600 dark:text-purple-500">{{ number_format($summary['total_items']) }}</p>
            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">SKUs</p>
        </div>
    </div>

    <!-- Filters -->
    <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-sm border border-zinc-200 dark:border-zinc-700 p-3">
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
            <input wire:model.live="searchTerm" 
                placeholder="Search item name or SKU..." 
                class="w-full px-3 py-2 text-sm border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-800 dark:text-zinc-200 focus:ring-2 focus:ring-blue-500">
            <select wire:model.live="selectedCategory" 
                class="w-full px-3 py-2 text-sm border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-800 dark:text-zinc-200 focus:ring-2 focus:ring-blue-500">
                <option value="">All Categories</option>
                @foreach($categories as $cat)
                    <option value="{{ $cat }}">{{ str_replace('_', ' ', ucfirst($cat)) }}</option>
                @endforeach
            </select>
        </div>
    </div>

    <!-- Tab Navigation -->
    <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-sm border border-zinc-200 dark:border-zinc-700 p-2">
        <div class="flex flex-wrap gap-1 bg-zinc-100 dark:bg-zinc-700 p-1 rounded-lg">
            <button wire:click="setViewMode('all')"
                class="px-3 py-1.5 text-xs font-medium rounded transition-all whitespace-nowrap {{ $viewMode === 'all' ? 'bg-white dark:bg-zinc-600 text-blue-600 dark:text-blue-400 shadow-sm' : 'text-zinc-600 dark:text-zinc-400 hover:text-zinc-900' }}">
                📋 All Items
            </button>
            <button wire:click="setViewMode('top')"
                class="px-3 py-1.5 text-xs font-medium rounded transition-all whitespace-nowrap {{ $viewMode === 'top' ? 'bg-white dark:bg-zinc-600 text-blue-600 dark:text-blue-400 shadow-sm' : 'text-zinc-600 dark:text-zinc-400 hover:text-zinc-900' }}">
                🏆 Top Valuable
            </button>
            <button wire:click="setViewMode('category')"
                class="px-3 py-1.5 text-xs font-medium rounded transition-all whitespace-nowrap {{ $viewMode === 'category' ? 'bg-white dark:bg-zinc-600 text-blue-600 dark:text-blue-400 shadow-sm' : 'text-zinc-600 dark:text-zinc-400 hover:text-zinc-900' }}">
                📊 By Category
            </button>
            <button wire:click="setViewMode('low')"
                class="px-3 py-1.5 text-xs font-medium rounded transition-all whitespace-nowrap {{ $viewMode === 'low' ? 'bg-white dark:bg-zinc-600 text-blue-600 dark:text-blue-400 shadow-sm' : 'text-zinc-600 dark:text-zinc-400 hover:text-zinc-900' }}">
                ⚠️ Low Stock
            </button>
        </div>
    </div>

    <!-- All Items Tab -->
    @if($viewMode === 'all')
    <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-sm border border-zinc-200 dark:border-zinc-700 p-4">
        <h3 class="text-base font-semibold text-zinc-800 dark:text-zinc-100 mb-3">All Items</h3>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-zinc-100 dark:bg-zinc-700">
                    <tr>
                        <th wire:click="sortByColumn('item.name')" class="px-3 py-2 text-left text-zinc-700 dark:text-zinc-300 cursor-pointer hover:bg-zinc-200 dark:hover:bg-zinc-600">
                            Item {{ $sortBy === 'item.name' ? ($sortDirection === 'desc' ? '↓' : '↑') : '' }}
                        </th>
                        <th class="px-3 py-2 text-left text-zinc-700 dark:text-zinc-300">Category</th>
                        <th wire:click="sortByColumn('quantity_available')" class="px-3 py-2 text-right text-zinc-700 dark:text-zinc-300 cursor-pointer hover:bg-zinc-200 dark:hover:bg-zinc-600">
                            Qty Avail {{ $sortBy === 'quantity_available' ? ($sortDirection === 'desc' ? '↓' : '↑') : '' }}
                        </th>
                        <th class="px-3 py-2 text-right text-zinc-700 dark:text-zinc-300">Reserved</th>
                        <th wire:click="sortByColumn('average_cost')" class="px-3 py-2 text-right text-zinc-700 dark:text-zinc-300 cursor-pointer hover:bg-zinc-200 dark:hover:bg-zinc-600">
                            Avg Cost {{ $sortBy === 'average_cost' ? ($sortDirection === 'desc' ? '↓' : '↑') : '' }}
                        </th>
                        <th class="px-3 py-2 text-right text-zinc-700 dark:text-zinc-300">Available Value</th>
                        <th wire:click="sortByColumn('value')" class="px-3 py-2 text-right text-zinc-700 dark:text-zinc-300 cursor-pointer hover:bg-zinc-200 dark:hover:bg-zinc-600">
                            Total Value {{ $sortBy === 'value' ? ($sortDirection === 'desc' ? '↓' : '↑') : '' }}
                        </th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                    @forelse($stocks as $stock)
                        <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-700/50">
                            <td class="px-3 py-2">
                                <div class="font-medium text-zinc-900 dark:text-zinc-100">{{ $stock->item->name }}</div>
                                <div class="text-xs text-gray-500 dark:text-gray-400">{{ $stock->item->sku }}</div>
                            </td>
                            <td class="px-3 py-2">
                                <span class="px-2 py-0.5 text-xs font-semibold rounded bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200">
                                    {{ str_replace('_', ' ', ucfirst($stock->item->category)) }}
                                </span>
                            </td>
                            <td class="px-3 py-2 text-right text-zinc-900 dark:text-zinc-100">{{ number_format($stock->quantity_available, 2) }}</td>
                            <td class="px-3 py-2 text-right text-zinc-900 dark:text-zinc-100">{{ number_format($stock->quantity_reserved, 2) }}</td>
                            <td class="px-3 py-2 text-right text-zinc-900 dark:text-zinc-100">
                                {{ \App\Helpers\LocalizationHelper::formatCurrency($stock->average_cost ?? 0) }}
                            </td>
                            <td class="px-3 py-2 text-right font-medium text-green-600 dark:text-green-400">
                                {{ \App\Helpers\LocalizationHelper::formatCurrency($stock->available_value ?? 0) }}
                            </td>
                            <td class="px-3 py-2 text-right font-bold text-blue-600 dark:text-blue-400">
                                {{ \App\Helpers\LocalizationHelper::formatCurrency($stock->total_value ?? 0) }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-3 py-8 text-center text-gray-500 dark:text-gray-400">No items found</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-3">{{ $stocks->links() }}</div>
    </div>
    @endif

    <!-- Top Valuable Items Tab -->
    @if($viewMode === 'top')
    <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-sm border border-zinc-200 dark:border-zinc-700 p-4">
        <h3 class="text-base font-semibold text-zinc-800 dark:text-zinc-100 mb-3">Top 10 Most Valuable Items</h3>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-zinc-100 dark:bg-zinc-700">
                    <tr>
                        <th class="px-3 py-2 text-left text-zinc-700 dark:text-zinc-300">Rank</th>
                        <th class="px-3 py-2 text-left text-zinc-700 dark:text-zinc-300">Item</th>
                        <th class="px-3 py-2 text-left text-zinc-700 dark:text-zinc-300">Category</th>
                        <th class="px-3 py-2 text-right text-zinc-700 dark:text-zinc-300">Qty (Avail)</th>
                        <th class="px-3 py-2 text-right text-zinc-700 dark:text-zinc-300">Unit Cost</th>
                        <th class="px-3 py-2 text-right text-zinc-700 dark:text-zinc-300">Total Value</th>
                        <th class="px-3 py-2 text-right text-zinc-700 dark:text-zinc-300">% of Total</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                    @php
                        $totalValue = $summary['total_value'];
                    @endphp
                    @forelse($topItems as $index => $item)
                        @php
                            $percentage = $totalValue > 0 ? ($item->total_value / $totalValue) * 100 : 0;
                        @endphp
                        <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-700/50">
                            <td class="px-3 py-2">
                                <span class="flex items-center justify-center w-6 h-6 rounded-full bg-blue-100 dark:bg-blue-900 text-blue-600 dark:text-blue-400 text-xs font-bold">
                                    {{ $index + 1 }}
                                </span>
                            </td>
                            <td class="px-3 py-2">
                                <div class="font-medium text-zinc-900 dark:text-zinc-100">{{ $item->item->name }}</div>
                                <div class="text-xs text-gray-500 dark:text-gray-400">{{ $item->item->sku }}</div>
                            </td>
                            <td class="px-3 py-2">{{ str_replace('_', ' ', ucfirst($item->item->category)) }}</td>
                            <td class="px-3 py-2 text-right font-medium text-zinc-900 dark:text-zinc-100">
                                {{ number_format($item->quantity_available + $item->quantity_reserved, 2) }}
                            </td>
                            <td class="px-3 py-2 text-right text-zinc-900 dark:text-zinc-100">
                                {{ \App\Helpers\LocalizationHelper::formatCurrency($item->average_cost ?? 0) }}
                            </td>
                            <td class="px-3 py-2 text-right font-bold text-blue-600 dark:text-blue-400">
                                {{ \App\Helpers\LocalizationHelper::formatCurrency($item->total_value ?? 0) }}
                            </td>
                            <td class="px-3 py-2 text-right">
                                <div class="flex items-center justify-end gap-2">
                                    <div class="h-1.5 bg-blue-200 dark:bg-blue-800 rounded-full" style="width: {{ min($percentage, 50) }}px;"></div>
                                    <span class="text-xs text-zinc-600 dark:text-zinc-400">{{ number_format($percentage, 1) }}%</span>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-3 py-8 text-center text-gray-500 dark:text-gray-400">No items found</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @endif

    <!-- By Category Tab -->
    @if($viewMode === 'category')
    <div class="space-y-3">
        @forelse($categoryData as $category)
        <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-sm border border-zinc-200 dark:border-zinc-700 p-4">
            <div class="flex items-center justify-between mb-3">
                <div>
                    <h3 class="text-base font-semibold text-zinc-800 dark:text-zinc-100">
                        {{ str_replace('_', ' ', ucfirst($category['category'])) }}
                    </h3>
                    <p class="text-xs text-zinc-600 dark:text-zinc-400 mt-0.5">
                        {{ $category['item_count'] }} items • {{ number_format($category['total_qty'], 2) }} units •
                        {{ \App\Helpers\LocalizationHelper::formatCurrency($category['total_value'] ?? 0) }} total value
                    </p>
                </div>
                <div class="text-right">
                    <p class="text-2xl font-bold text-blue-600 dark:text-blue-400">
                        {{ \App\Helpers\LocalizationHelper::formatCurrency($category['total_value'] ?? 0) }}
                    </p>
                    <p class="text-xs text-zinc-600 dark:text-zinc-400 mt-1">{{ number_format(($category['total_value'] / $summary['total_value']) * 100, 1) }}% of total</p>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-zinc-50 dark:bg-zinc-700/50">
                        <tr>
                            <th class="px-3 py-2 text-left text-zinc-700 dark:text-zinc-300">Item</th>
                            <th class="px-3 py-2 text-right text-zinc-700 dark:text-zinc-300">Qty</th>
                            <th class="px-3 py-2 text-right text-zinc-700 dark:text-zinc-300">Avg Cost</th>
                            <th class="px-3 py-2 text-right text-zinc-700 dark:text-zinc-300">Value</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                        @forelse($category['items'] as $item)
                            <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-700/50">
                                <td class="px-3 py-2">
                                    <div class="font-medium text-zinc-900 dark:text-zinc-100 text-xs">{{ $item->item->name }}</div>
                                </td>
                                <td class="px-3 py-2 text-right text-xs">{{ number_format($item->quantity_available, 0) }}</td>
                                <td class="px-3 py-2 text-right text-xs">
                                    {{ \App\Helpers\LocalizationHelper::formatCurrency($item->average_cost ?? 0) }}
                                </td>
                                <td class="px-3 py-2 text-right text-xs font-semibold text-blue-600 dark:text-blue-400">
                                    {{ \App\Helpers\LocalizationHelper::formatCurrency($item->total_value ?? 0) }}
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="px-3 py-3 text-center text-xs text-gray-500">No items</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @empty
            <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-sm border border-zinc-200 dark:border-zinc-700 p-8 text-center">
                <p class="text-gray-500 dark:text-gray-400">No category data available</p>
            </div>
        @endforelse
    </div>
    @endif

    <!-- Low Stock Tab -->
    @if($viewMode === 'low')
    <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-sm border border-red-200 dark:border-red-700 p-4">
        <h3 class="text-base font-semibold text-red-700 dark:text-red-400 mb-3 flex items-center">
            <span class="mr-2">⚠️</span> Low Stock Items (Below 100 units)
        </h3>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-red-50 dark:bg-red-900/20">
                    <tr>
                        <th class="px-3 py-2 text-left text-red-700 dark:text-red-400">Item</th>
                        <th class="px-3 py-2 text-left text-red-700 dark:text-red-400">Category</th>
                        <th class="px-3 py-2 text-right text-red-700 dark:text-red-400">Available</th>
                        <th class="px-3 py-2 text-right text-red-700 dark:text-red-400">Reserved</th>
                        <th class="px-3 py-2 text-right text-red-700 dark:text-red-400">Avg Cost</th>
                        <th class="px-3 py-2 text-right text-red-700 dark:text-red-400">Available Value</th>
                        <th class="px-3 py-2 text-right text-red-700 dark:text-red-400">Reorder</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-red-200 dark:divide-red-700">
                    @forelse($lowStockItems as $item)
                        <tr class="hover:bg-red-50 dark:hover:bg-red-900/20 {{ $item->quantity_available === 0 ? 'bg-red-100 dark:bg-red-900/40' : '' }}">
                            <td class="px-3 py-2">
                                <div class="font-medium text-zinc-900 dark:text-zinc-100">{{ $item->item->name }}</div>
                                <div class="text-xs text-gray-500">{{ $item->item->sku }}</div>
                            </td>
                            <td class="px-3 py-2 text-xs">{{ str_replace('_', ' ', ucfirst($item->item->category)) }}</td>
                            <td class="px-3 py-2 text-right font-bold text-red-600 dark:text-red-400">{{ number_format($item->quantity_available, 2) }}</td>
                            <td class="px-3 py-2 text-right text-zinc-900 dark:text-zinc-100">{{ number_format($item->quantity_reserved, 2) }}</td>
                            <td class="px-3 py-2 text-right">
                                {{ \App\Helpers\LocalizationHelper::formatCurrency($item->average_cost ?? 0) }}
                            </td>
                            <td class="px-3 py-2 text-right font-semibold text-red-600 dark:text-red-400">
                                {{ \App\Helpers\LocalizationHelper::formatCurrency($item->available_value ?? 0) }}
                            </td>
                            <td class="px-3 py-2 text-center">
                                <span class="px-2 py-0.5 text-xs font-semibold rounded bg-red-100 dark:bg-red-900 text-red-800 dark:text-red-200">
                                    {{ $item->quantity_available < 50 ? 'URGENT' : 'Soon' }}
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-3 py-8 text-center text-gray-500 dark:text-gray-400">No low stock items</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @endif

</div>
