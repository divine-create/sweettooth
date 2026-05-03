<div class="p-3 space-y-3">

    <x-breadcrumb
        title="Production Store"
        :items="[
            ['label' => 'Dashboard', 'url' => branch_route('branch-dashboard.index')],
            ['label' => 'Production'],
            ['label' => 'Store Stock']
        ]"
        :compact="false"
        :with-icons="true"/>

    <div class="flex justify-end mb-2">
        <a href="{{ branch_route('branch-dashboard.production.material-request', ['deptSlug' => $dept_slug, 'b_id' => $b_id]) }}"
           class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg font-medium flex items-center gap-2">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
            </svg>
            Make Material Request
        </a>
    </div>

    @if(!$store)
        <div class="bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-lg p-6 text-center">
            <svg class="w-12 h-12 mx-auto text-yellow-500 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
            </svg>
            <h3 class="text-lg font-semibold text-yellow-800 dark:text-yellow-200">No Production Store Found</h3>
            <p class="text-yellow-700 dark:text-yellow-300 mt-1">
                This department doesn't have a Production Store yet. Make an item request to start stocking.
            </p>
        </div>
    @else
        <!-- Summary Cards -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-3">
            <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-sm border border-zinc-200 dark:border-zinc-700 p-4">
                <p class="text-sm text-zinc-500 dark:text-zinc-400">Total Items</p>
                <p class="text-2xl font-bold text-zinc-900 dark:text-zinc-100">{{ $stocks->count() }}</p>
            </div>
            <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-sm border border-zinc-200 dark:border-zinc-700 p-4">
                <p class="text-sm text-zinc-500 dark:text-zinc-400">Items in Stock</p>
                <p class="text-2xl font-bold text-green-600 dark:text-green-400">{{ $stocks->where('quantity_available', '>', 0)->count() }}</p>
            </div>
            <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-sm border border-zinc-200 dark:border-zinc-700 p-4">
                <p class="text-sm text-zinc-500 dark:text-zinc-400">Total Quantity</p>
                <p class="text-2xl font-bold text-zinc-900 dark:text-zinc-100">
                    {{ number_format($stocks->sum('quantity_available'), 0) }}
                </p>
            </div>
            <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-sm border border-zinc-200 dark:border-zinc-700 p-4">
                <p class="text-sm text-zinc-500 dark:text-zinc-400">Total Value</p>
                <p class="text-2xl font-bold text-zinc-900 dark:text-zinc-100">
                    {{ \App\Helpers\LocalizationHelper::formatCurrency($totalValue ?? 0) }}
                </p>
            </div>
        </div>

        <!-- Search and Info -->
        <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-sm border border-zinc-200 dark:border-zinc-700 p-4">
            <div class="flex flex-col md:flex-row gap-4 justify-between">
                <div class="flex-1">
                    <input type="text"
                           wire:model.live="search"
                           placeholder="Search items by name or SKU..."
                           class="w-full px-4 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-800 dark:text-zinc-200"/>
                </div>
                <div class="flex items-center gap-2 text-sm text-zinc-500 dark:text-zinc-400">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <span>Items are auto-stocked when inventory dispatches to this department</span>
                </div>
            </div>
        </div>

        <!-- Filter Tabs -->
        <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-sm border border-zinc-200 dark:border-zinc-700 p-4">
            <div class="flex items-center justify-between">
                <div class="flex gap-2">
                    <button wire:click="$set('stockFilter', 'all')"
                            class="px-4 py-2 rounded-lg font-medium transition {{ $stockFilter === 'all' ? 'bg-blue-600 text-white' : 'bg-zinc-100 dark:bg-zinc-700 text-zinc-600 dark:text-zinc-300 hover:bg-zinc-200 dark:hover:bg-zinc-600' }}">
                        All ({{ $stockCounts['all'] }})
                    </button>
                    <button wire:click="$set('stockFilter', 'wip')"
                            class="px-4 py-2 rounded-lg font-medium transition {{ $stockFilter === 'wip' ? 'bg-blue-600 text-white' : 'bg-zinc-100 dark:bg-zinc-700 text-zinc-600 dark:text-zinc-300 hover:bg-zinc-200 dark:hover:bg-zinc-600' }}">
                        WIP Items ({{ $stockCounts['wip'] }})
                    </button>
                    <button wire:click="$set('stockFilter', 'raw')"
                            class="px-4 py-2 rounded-lg font-medium transition {{ $stockFilter === 'raw' ? 'bg-blue-600 text-white' : 'bg-zinc-100 dark:bg-zinc-700 text-zinc-600 dark:text-zinc-300 hover:bg-zinc-200 dark:hover:bg-zinc-600' }}">
                        Raw Materials ({{ $stockCounts['raw'] }})
                    </button>
                </div>
            </div>
        </div>

        <!-- Stock Table -->
        @if($stocks->isEmpty())
            <div class="bg-zinc-50 dark:bg-zinc-800/50 rounded-lg p-8 text-center">
                <svg class="w-16 h-16 mx-auto text-zinc-400 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10m0-10l-8 4"/>
                </svg>
                <h3 class="text-lg font-semibold text-zinc-600 dark:text-zinc-400">No Items in Store</h3>
                <p class="text-zinc-500 dark:text-zinc-500 mt-1">
                    @if($search)
                        No items match your search "{{ $search }}"
                    @else
                        Make an item request to start stocking your production store
                    @endif
                </p>
            </div>
        @else
            <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-sm border border-zinc-200 dark:border-zinc-700 overflow-hidden">
                <table class="w-full">
                    <thead class="bg-zinc-50 dark:bg-zinc-700 border-b border-zinc-200 dark:border-zinc-600">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-zinc-500 dark:text-zinc-400 uppercase">Item</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-zinc-500 dark:text-zinc-400 uppercase">Category</th>
                            <th class="px-4 py-3 text-right text-xs font-semibold text-zinc-500 dark:text-zinc-400 uppercase">Quantity</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-zinc-500 dark:text-zinc-400 uppercase">UOM</th>
                            <th class="px-4 py-3 text-right text-xs font-semibold text-zinc-500 dark:text-zinc-400 uppercase">Avg Cost</th>
                            <th class="px-4 py-3 text-right text-xs font-semibold text-zinc-500 dark:text-zinc-400 uppercase">Value</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-zinc-500 dark:text-zinc-400 uppercase">Last Updated</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                        @foreach($stocks as $stock)
                            @php
                                $isWip = in_array($stock->item_id, $wipProductIds);
                            @endphp
                            <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-700/50">
                                <td class="px-4 py-3">
                                    <div class="flex items-center gap-2">
                                        <div class="font-medium text-zinc-900 dark:text-zinc-100">
                                            {{ $stock->displayItem?->name ?? 'Unknown Item' }}
                                        </div>
                                        @if($isWip)
                                            <span class="px-2 py-0.5 text-xs rounded-full bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200">
                                                WIP
                                            </span>
                                        @endif
                                    </div>
                                    <div class="text-xs text-zinc-500 dark:text-zinc-400">
                                        SKU: {{ $stock->displayItem?->sku ?? 'N/A' }}
                                    </div>
                                </td>
                                <td class="px-4 py-3">
                                    <span class="px-2 py-1 text-xs rounded-full
                                        @if($stock->displayItem?->category?->name === 'Raw Material')
                                            bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200'
                                        @elseif($stock->displayItem?->category?->name === 'Packaging')
                                            bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-200'
                                        @else
                                            bg-zinc-100 text-zinc-800 dark:bg-zinc-700 dark:text-zinc-200'
                                        @endif">
                                        {{ $stock->displayItem?->category?->name ?? 'Uncategorized' }}
                                    </span>
                                </td>
                                <td class="px-4 py-3">
                                    <span class="px-2 py-1 text-xs rounded-full
                                        @if($stock->item?->category?->name === 'Raw Material')
                                            bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200
                                        @elseif($stock->item?->category?->name === 'Packaging')
                                            bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-200
                                        @else
                                            bg-zinc-100 text-zinc-800 dark:bg-zinc-700 dark:text-zinc-200
                                        @endif">
                                        {{ $stock->item?->category?->name ?? 'Uncategorized' }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-right">
                                    <span class="font-medium text-zinc-900 dark:text-zinc-100
                                        @if(($stock->quantity_available ?? 0) <= 0) text-red-600
                                        @elseif(($stock->quantity_available ?? 0) < ($stock->item?->reorder_level ?? 0)) text-yellow-600
                                        @else text-green-600
                                        @endif">
                                        {{ number_format($stock->quantity_available ?? 0, 2) }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-zinc-600 dark:text-zinc-400">
                                    {{ $stock->displayItem?->unitOfMeasure?->symbol ?? 'N/A' }}
                                </td>
                                <td class="px-4 py-3 text-right text-zinc-600 dark:text-zinc-400">
                                    {{ \App\Helpers\LocalizationHelper::formatCurrency($stock->average_cost ?? 0) }}
                                </td>
                                <td class="px-4 py-3 text-right font-medium text-zinc-900 dark:text-zinc-100">
                                    {{ \App\Helpers\LocalizationHelper::formatCurrency(($stock->quantity_available ?? 0) * ($stock->average_cost ?? 0)) }}
                                </td>
                                <td class="px-4 py-3 text-sm text-zinc-500 dark:text-zinc-400">
                                    @if($stock->last_stock_take_date)
                                        {{ \Carbon\Carbon::parse($stock->last_stock_take_date)->format('M d, Y') }}
                                    @else
                                        <span class="text-zinc-400">Never</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    @endif
</div>