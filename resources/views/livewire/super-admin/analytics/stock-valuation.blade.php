<div class="p-3 space-y-3">
    <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-sm border border-zinc-200 dark:border-zinc-700 p-4">
        <div class="flex items-center justify-between mb-6">
            <h2 class="text-lg font-semibold text-zinc-800 dark:text-zinc-100">Stock Valuation</h2>
            <div class="flex gap-2">
                <button wire:click="exportCSV" class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium bg-green-600 hover:bg-green-700 text-white rounded-lg transition-all duration-200 hover:shadow-lg active:scale-95">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2m0 0v-8m0 8H3m15 0h3"/>
                    </svg>
                    CSV
                </button>

                <button wire:click="exportCSV" class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg transition-all duration-200 hover:shadow-lg active:scale-95">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                    Excel
                </button>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4 mb-6">
            <div class="bg-gradient-to-br from-blue-500 to-blue-600 text-white rounded-lg shadow-sm p-4">
                <p class="text-sm opacity-90">Total Value</p>
                <p class="text-2xl font-bold">
                    {{ \App\Helpers\LocalizationHelper::formatCurrency($summary['total_value'] ?? 0) }}
                </p>
            </div>
            <div class="bg-green-50 dark:bg-green-900/20 rounded-lg shadow-sm p-4">
                <p class="text-sm text-gray-600 dark:text-gray-400">Available</p>
                <p class="text-2xl font-bold text-green-600 dark:text-green-500">
                    {{ \App\Helpers\LocalizationHelper::formatCurrency($summary['available_value'] ?? 0) }}
                </p>
            </div>
            <div class="bg-blue-50 dark:bg-blue-900/20 rounded-lg shadow-sm p-4">
                <p class="text-sm text-gray-600 dark:text-gray-400">Reserved</p>
                <p class="text-2xl font-bold text-blue-600 dark:text-blue-500">
                    {{ \App\Helpers\LocalizationHelper::formatCurrency($summary['reserved_value'] ?? 0) }}
                </p>
            </div>
            <div class="bg-red-50 dark:bg-red-900/20 rounded-lg shadow-sm p-4">
                <p class="text-sm text-gray-600 dark:text-gray-400">Damaged</p>
                <p class="text-2xl font-bold text-red-600 dark:text-red-500">
                    {{ \App\Helpers\LocalizationHelper::formatCurrency($summary['damaged_value'] ?? 0) }}
                </p>
            </div>
            <div class="bg-purple-50 dark:bg-purple-900/20 rounded-lg shadow-sm p-4">
                <p class="text-sm text-gray-600 dark:text-gray-400">Total Items</p>
                <p class="text-2xl font-bold text-purple-600 dark:text-purple-500">{{ $summary['total_items'] }}</p>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
            <input wire:model.live="searchTerm" placeholder="Search..." class="w-full px-4 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-800 dark:text-zinc-200 focus:ring-2 focus:ring-blue-500">
            <select wire:model.live="selectedCategory" class="w-full px-4 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-800 dark:text-zinc-200 focus:ring-2 focus:ring-blue-500">
                <option value="">All Categories</option>
                @foreach($categories as $cat)
                    <option value="{{ $cat }}">{{ str_replace('_', ' ', ucfirst($cat)) }}</option>
                @endforeach
            </select>
        </div>

        <!-- View Mode Tabs -->
        <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-sm border border-zinc-200 dark:border-zinc-700 p-2 mb-6">
            <div class="flex flex-wrap gap-1 bg-zinc-100 dark:bg-zinc-700 p-1 rounded-lg">
                <button wire:click="setViewMode('table')" class="px-3 py-2 text-xs font-medium rounded transition-all whitespace-nowrap {{ $viewMode === 'table' ? 'bg-white dark:bg-zinc-600 text-blue-600 dark:text-blue-400 shadow-sm' : 'text-zinc-600 dark:text-zinc-400 hover:text-zinc-900 dark:hover:text-zinc-200' }}">
                    📋 Table
                </button>
                <button wire:click="setViewMode('cards')" class="px-3 py-2 text-xs font-medium rounded transition-all whitespace-nowrap {{ $viewMode === 'cards' ? 'bg-white dark:bg-zinc-600 text-blue-600 dark:text-blue-400 shadow-sm' : 'text-zinc-600 dark:text-zinc-400 hover:text-zinc-900 dark:hover:text-zinc-200' }}">
                    🎴 Cards
                </button>
                <button wire:click="setViewMode('list')" class="px-3 py-2 text-xs font-medium rounded transition-all whitespace-nowrap {{ $viewMode === 'list' ? 'bg-white dark:bg-zinc-600 text-blue-600 dark:text-blue-400 shadow-sm' : 'text-zinc-600 dark:text-zinc-400 hover:text-zinc-900 dark:hover:text-zinc-200' }}">
                    📝 List
                </button>
                <button wire:click="setViewMode('bars')" class="px-3 py-2 text-xs font-medium rounded transition-all whitespace-nowrap {{ $viewMode === 'bars' ? 'bg-white dark:bg-zinc-600 text-blue-600 dark:text-blue-400 shadow-sm' : 'text-zinc-600 dark:text-zinc-400 hover:text-zinc-900 dark:hover:text-zinc-200' }}">
                    📊 Visual Bars
                </button>
                <button wire:click="setViewMode('accordion')" class="px-3 py-2 text-xs font-medium rounded transition-all whitespace-nowrap {{ $viewMode === 'accordion' ? 'bg-white dark:bg-zinc-600 text-blue-600 dark:text-blue-400 shadow-sm' : 'text-zinc-600 dark:text-zinc-400 hover:text-zinc-900 dark:hover:text-zinc-200' }}">
                    📂 By Category
                </button>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
            <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-sm border border-zinc-200 dark:border-zinc-700 p-4">
                <h3 class="text-base font-semibold text-zinc-800 dark:text-zinc-100 mb-4">Valuation by Category</h3>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm text-left">
                        <thead class="text-xs uppercase bg-zinc-100 dark:bg-zinc-700 text-zinc-700 dark:text-zinc-300">
                            <tr>
                                <th class="px-4 py-3">Category</th>
                                <th class="px-4 py-3 text-right">Total Value</th>
                                <th class="px-4 py-3 text-right">Percentage</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                            @forelse($categoryValuation['labels'] as $index => $category)
                                <tr class="bg-white dark:bg-zinc-800 hover:bg-zinc-50 dark:hover:bg-zinc-700/50">
                                    <td class="px-4 py-3 font-medium">{{ $category }}</td>
                                    <td class="px-4 py-3 text-right text-blue-600 dark:text-blue-400 font-semibold">
                                        {{ \App\Helpers\LocalizationHelper::formatCurrency($categoryValuation['series'][$index] ?? 0) }}
                                    </td>
                                    <td class="px-4 py-3 text-right">{{ number_format(($categoryValuation['series'][$index] ?? 0) / array_sum($categoryValuation['series']) * 100, 1) }}%</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3" class="px-4 py-8 text-center text-gray-500 dark:text-gray-400">No data available</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-sm border border-zinc-200 dark:border-zinc-700 p-4">
                <h3 class="text-base font-semibold text-zinc-800 dark:text-zinc-100 mb-4">Top 10 Most Valuable Items</h3>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm text-left">
                        <thead class="text-xs uppercase bg-zinc-100 dark:bg-zinc-700 text-zinc-700 dark:text-zinc-300">
                            <tr>
                                <th class="px-4 py-3">Item Name</th>
                                <th class="px-4 py-3 text-right">Quantity</th>
                                <th class="px-4 py-3 text-right">Avg Cost</th>
                                <th class="px-4 py-3 text-right">Total Value</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                            @forelse($topItems as $item)
                                <tr class="bg-white dark:bg-zinc-800 hover:bg-zinc-50 dark:hover:bg-zinc-700/50">
                                    <td class="px-4 py-3 font-medium">{{ $item->item->name }}</td>
                                    <td class="px-4 py-3 text-right">{{ number_format($item->quantity_available + $item->quantity_reserved, 2) }}</td>
                                    <td class="px-4 py-3 text-right">
                                        {{ \App\Helpers\LocalizationHelper::formatCurrency($item->average_cost ?? 0) }}
                                    </td>
                                    <td class="px-4 py-3 text-right text-blue-600 dark:text-blue-400 font-bold">
                                        {{ \App\Helpers\LocalizationHelper::formatCurrency($item->total_value ?? 0) }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="px-4 py-8 text-center text-gray-500 dark:text-gray-400">No items found</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- TABLE VIEW -->
        @if($viewMode === 'table')
        <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-sm border border-zinc-200 dark:border-zinc-700 p-4">
            <h3 class="text-base font-semibold text-zinc-800 dark:text-zinc-100 mb-4">Detailed Valuation</h3>
            <div class="overflow-x-auto">
                <table class="w-full text-sm text-left">
                    <thead class="text-xs uppercase bg-zinc-100 dark:bg-zinc-700 text-zinc-700 dark:text-zinc-300">
                        <tr>
                            <th wire:click="sortByColumn('item.name')" class="px-4 py-3 cursor-pointer hover:bg-zinc-200 dark:hover:bg-zinc-600">Item</th>
                            <th class="px-4 py-3">Category</th>
                            <th wire:click="sortByColumn('quantity_available')" class="px-4 py-3 cursor-pointer hover:bg-zinc-200 dark:hover:bg-zinc-600">Qty Available</th>
                            <th class="px-4 py-3">Qty Reserved</th>
                            <th wire:click="sortByColumn('average_cost')" class="px-4 py-3 cursor-pointer hover:bg-zinc-200 dark:hover:bg-zinc-600">Avg Cost</th>
                            <th wire:click="sortByColumn('available_value')" class="px-4 py-3 cursor-pointer hover:bg-zinc-200 dark:hover:bg-zinc-600">Available Value</th>
                            <th wire:click="sortByColumn('total_value')" class="px-4 py-3 cursor-pointer hover:bg-zinc-200 dark:hover:bg-zinc-600">Total Value</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                        @forelse($stocks as $stock)
                            <tr class="bg-white dark:bg-zinc-800 hover:bg-zinc-50 dark:hover:bg-zinc-700/50">
                                <td class="px-4 py-3">
                                    <div>
                                        <div class="font-medium text-zinc-900 dark:text-zinc-100">{{ $stock->item->name }}</div>
                                        <div class="text-xs text-gray-500 dark:text-gray-400">{{ $stock->item->sku }}</div>
                                    </div>
                                </td>
                                <td class="px-4 py-3">
                                    <span class="px-2 py-1 text-xs font-semibold rounded bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200">
                                        {{ str_replace('_', ' ', ucfirst($stock->item->category)) }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-zinc-900 dark:text-zinc-100">{{ number_format($stock->quantity_available, 2) }}</td>
                                <td class="px-4 py-3 text-zinc-900 dark:text-zinc-100">{{ number_format($stock->quantity_reserved, 2) }}</td>
                                <td class="px-4 py-3 text-zinc-900 dark:text-zinc-100">
                                    {{ \App\Helpers\LocalizationHelper::formatCurrency($stock->average_cost ?? 0) }}
                                </td>
                                <td class="px-4 py-3 font-medium text-green-600 dark:text-green-400">
                                    {{ \App\Helpers\LocalizationHelper::formatCurrency($stock->available_value ?? 0) }}
                                </td>
                                <td class="px-4 py-3 font-bold text-blue-600 dark:text-blue-400">
                                    {{ \App\Helpers\LocalizationHelper::formatCurrency($stock->total_value ?? 0) }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-4 py-8 text-center text-gray-500 dark:text-gray-400">No items found</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="mt-4">{{ $stocks->links() }}</div>
        </div>
        @endif

        <!-- CARDS VIEW -->
        @if($viewMode === 'cards')
        <div>
            <h3 class="text-base font-semibold text-zinc-800 dark:text-zinc-100 mb-4">Stock Items</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                @forelse($stocks as $stock)
                    <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-sm border border-zinc-200 dark:border-zinc-700 p-4 hover:shadow-md transition-shadow">
                        <div class="flex justify-between items-start mb-3">
                            <div>
                                <h4 class="font-medium text-zinc-900 dark:text-zinc-100">{{ $stock->item->name }}</h4>
                                <p class="text-xs text-gray-500 dark:text-gray-400">{{ $stock->item->sku }}</p>
                            </div>
                            <span class="px-2 py-1 text-xs font-semibold rounded bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200">
                                {{ str_replace('_', ' ', ucfirst($stock->item->category)) }}
                            </span>
                        </div>
                        <div class="space-y-2 text-sm">
                            <div class="flex justify-between">
                                <span class="text-zinc-600 dark:text-zinc-400">Available:</span>
                                <span class="font-medium text-zinc-900 dark:text-zinc-100">{{ number_format($stock->quantity_available, 2) }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-zinc-600 dark:text-zinc-400">Reserved:</span>
                                <span class="font-medium text-zinc-900 dark:text-zinc-100">{{ number_format($stock->quantity_reserved, 2) }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-zinc-600 dark:text-zinc-400">Avg Cost:</span>
                                <span class="font-medium text-zinc-900 dark:text-zinc-100">
                                    {{ \App\Helpers\LocalizationHelper::formatCurrency($stock->average_cost ?? 0) }}
                                </span>
                            </div>
                            <div class="border-t border-zinc-200 dark:border-zinc-700 pt-2 mt-2">
                                <div class="flex justify-between">
                                    <span class="text-zinc-600 dark:text-zinc-400">Total Value:</span>
                                    <span class="font-bold text-blue-600 dark:text-blue-400">
                                        {{ \App\Helpers\LocalizationHelper::formatCurrency($stock->total_value ?? 0) }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="col-span-3 text-center py-8">
                        <p class="text-gray-500 dark:text-gray-400">No items found</p>
                    </div>
                @endforelse
            </div>
            <div class="mt-4">{{ $stocks->links() }}</div>
        </div>
        @endif

        <!-- LIST VIEW -->
        @if($viewMode === 'list')
        <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-sm border border-zinc-200 dark:border-zinc-700 p-4">
            <h3 class="text-base font-semibold text-zinc-800 dark:text-zinc-100 mb-4">Stock Items</h3>
            <div class="space-y-2">
                @forelse($stocks as $stock)
                    <div class="flex items-center justify-between p-3 bg-zinc-50 dark:bg-zinc-700/50 rounded border border-zinc-200 dark:border-zinc-700 hover:bg-zinc-100 dark:hover:bg-zinc-700">
                        <div class="flex-1">
                            <div class="font-medium text-zinc-900 dark:text-zinc-100">{{ $stock->item->name }}</div>
                            <div class="text-xs text-gray-500 dark:text-gray-400">{{ $stock->item->sku }} • {{ str_replace('_', ' ', ucfirst($stock->item->category)) }}</div>
                        </div>
                        <div class="hidden sm:flex gap-4 text-sm text-right">
                            <div>
                                <div class="text-xs text-gray-500 dark:text-gray-400">Qty</div>
                                <div class="font-medium text-zinc-900 dark:text-zinc-100">{{ number_format($stock->quantity_available + $stock->quantity_reserved, 1) }}</div>
                            </div>
                            <div>
                                <div class="text-xs text-gray-500 dark:text-gray-400">Value</div>
                                <div class="font-bold text-blue-600 dark:text-blue-400">
                                    {{ \App\Helpers\LocalizationHelper::formatCurrency($stock->total_value ?? 0) }}
                                </div>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="text-center py-8">
                        <p class="text-gray-500 dark:text-gray-400">No items found</p>
                    </div>
                @endforelse
            </div>
            <div class="mt-4">{{ $stocks->links() }}</div>
        </div>
        @endif

        <!-- VISUAL BARS VIEW -->
        @if($viewMode === 'bars')
        <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-sm border border-zinc-200 dark:border-zinc-700 p-4">
            <h3 class="text-base font-semibold text-zinc-800 dark:text-zinc-100 mb-4">Stock Valuation Visualization</h3>
            @php
                $maxValue = $stocks->max('total_value') ?? 1;
            @endphp
            <div class="space-y-4">
                @forelse($stocks as $stock)
                    @php
                        $percentage = ($stock->total_value / $maxValue) * 100;
                    @endphp
                    <div>
                        <div class="flex justify-between items-center mb-1">
                            <div>
                                <div class="text-sm font-medium text-zinc-900 dark:text-zinc-100">{{ Str::limit($stock->item->name, 30) }}</div>
                                <div class="text-xs text-gray-500 dark:text-gray-400">{{ $stock->item->sku }}</div>
                            </div>
                            <div class="text-sm font-bold text-blue-600 dark:text-blue-400 min-w-fit ml-2">
                                {{ \App\Helpers\LocalizationHelper::formatCurrency($stock->total_value ?? 0) }}
                            </div>
                        </div>
                        <div class="w-full bg-zinc-200 dark:bg-zinc-700 rounded-full h-2">
                            <div class="bg-gradient-to-r from-blue-500 to-blue-600 h-2 rounded-full" style="width: {{ $percentage }}%"></div>
                        </div>
                    </div>
                @empty
                    <div class="text-center py-8">
                        <p class="text-gray-500 dark:text-gray-400">No items found</p>
                    </div>
                @endforelse
            </div>
            <div class="mt-4">{{ $stocks->links() }}</div>
        </div>
        @endif

        <!-- ACCORDION VIEW (BY CATEGORY) -->
        @if($viewMode === 'accordion')
        <div class="space-y-3">
            <h3 class="text-base font-semibold text-zinc-800 dark:text-zinc-100">Stock by Category</h3>
            @php
                $totalValue = $summary['total_value'] ?? 1;
            @endphp
            @forelse($categoryGroupedData as $categoryData)
                <details class="bg-white dark:bg-zinc-800 rounded-lg shadow-sm border border-zinc-200 dark:border-zinc-700">
                    <summary class="cursor-pointer p-4 hover:bg-zinc-50 dark:hover:bg-zinc-700/50 select-none">
                        <div class="flex items-center justify-between">
                            <div>
                                <h4 class="font-semibold text-zinc-900 dark:text-zinc-100">{{ str_replace('_', ' ', ucfirst($categoryData['category'])) }}</h4>
                                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                    {{ $categoryData['item_count'] }} items • {{ number_format($categoryData['total_qty'], 1) }} units
                                </p>
                            </div>
                            <div class="text-right">
                                <div class="font-bold text-blue-600 dark:text-blue-400">
                                    {{ \App\Helpers\LocalizationHelper::formatCurrency($categoryData['total_value'] ?? 0) }}
                                </div>
                                <div class="text-xs text-gray-500 dark:text-gray-400">{{ number_format(($categoryData['total_value'] / $totalValue) * 100, 1) }}%</div>
                            </div>
                        </div>
                    </summary>
                    <div class="p-4 border-t border-zinc-200 dark:border-zinc-700 space-y-2">
                        @forelse($categoryData['items'] as $item)
                            <div class="flex items-center justify-between p-2 bg-zinc-50 dark:bg-zinc-700/50 rounded text-sm">
                                <div>
                                    <div class="font-medium text-zinc-900 dark:text-zinc-100">{{ Str::limit($item->item->name, 25) }}</div>
                                    <div class="text-xs text-gray-500 dark:text-gray-400">{{ $item->item->sku }}</div>
                                </div>
                                <div class="text-right">
                                    <div class="text-xs text-gray-600 dark:text-gray-400">{{ number_format($item->quantity_available + $item->quantity_reserved, 1) }} units</div>
                                    <div class="font-bold text-blue-600 dark:text-blue-400">
                                        {{ \App\Helpers\LocalizationHelper::formatCurrency($item->total_value ?? 0) }}
                                    </div>
                                </div>
                            </div>
                        @empty
                            <p class="text-center text-gray-500 dark:text-gray-400 py-2 text-sm">No items in this category</p>
                        @endforelse
                    </div>
                </details>
            @empty
                <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-sm border border-zinc-200 dark:border-zinc-700 p-8 text-center">
                    <p class="text-gray-500 dark:text-gray-400">No category data available</p>
                </div>
            @endforelse
        </div>
        @endif
    </div>

    @endpush
</div>
