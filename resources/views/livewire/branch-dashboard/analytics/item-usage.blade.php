<div class="p-3 space-y-3">

    <x-breadcrumb
        title="Item Usage Analytics"
        :items="[
            ['label' => 'Dashboard', 'url' => branch_route('branch-dashboard.index')],
            ['label' => 'Analytics'],
            ['label' => 'Item Usage Analytics']
        ]"
        :compact="false"
        :with-icons="true"
    />

    <!-- Filters -->
    <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-sm border border-zinc-200 dark:border-zinc-700 p-4">
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4">
            <div>
                <label class="block text-xs font-medium text-zinc-500 dark:text-zinc-400 mb-1">From</label>
                <input type="date" wire:model.live="dateFrom"
                    class="w-full px-3 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-800 dark:text-zinc-200 text-sm">
            </div>
            <div>
                <label class="block text-xs font-medium text-zinc-500 dark:text-zinc-400 mb-1">To</label>
                <input type="date" wire:model.live="dateTo"
                    class="w-full px-3 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-800 dark:text-zinc-200 text-sm">
            </div>
            <div>
                <label class="block text-xs font-medium text-zinc-500 dark:text-zinc-400 mb-1">Category</label>
                <select wire:model.live="categoryFilter"
                    class="w-full px-3 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-800 dark:text-zinc-200 text-sm">
                    <option value="">All Categories</option>
                    @foreach($categories as $id => $name)
                        <option value="{{ $id }}">{{ $name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-medium text-zinc-500 dark:text-zinc-400 mb-1">Department</label>
                <select wire:model.live="departmentFilter"
                    class="w-full px-3 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-800 dark:text-zinc-200 text-sm">
                    <option value="">All Departments</option>
                    @foreach($departments as $id => $name)
                        <option value="{{ $id }}">{{ $name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-medium text-zinc-500 dark:text-zinc-400 mb-1">Search</label>
                <input type="text" wire:model.live="searchTerm" placeholder="Search items..."
                    class="w-full px-3 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-800 dark:text-zinc-200 text-sm">
            </div>
        </div>
    </div>

    <!-- Actions -->
    <div class="flex flex-wrap justify-end gap-2">
        <button wire:click="generateReport"
            wire:loading.attr="disabled"
            wire:target="generateReport"
            class="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg font-medium transition-all text-sm">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-3-3v6m8 4H4a1 1 0 01-1-1V6a1 1 0 011-1h10l6 6v7a1 1 0 01-1 1z"/>
            </svg>
            <span wire:loading.remove wire:target="generateReport">Save as Report</span>
            <span wire:loading wire:target="generateReport">Generating...</span>
        </button>
    </div>

    @if (session('success'))
        <div class="p-3 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-700 rounded text-sm text-green-800 dark:text-green-200">
            {{ session('success') }}
            @if($generatedReportId)
                <a href="{{ branch_route('branch-dashboard.inventory.reports.show', ['id' => $generatedReportId, 'b_id' => $b_id]) }}"
                   class="ml-2 inline-flex items-center px-2 py-1 rounded bg-green-700 text-white hover:bg-green-800 text-xs">
                    View Report
                </a>
            @endif
        </div>
    @endif

    @if (session('error'))
        <div class="p-3 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-700 rounded text-sm text-red-800 dark:text-red-200">
            {{ session('error') }}
        </div>
    @endif

    <!-- Summary Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-sm border border-zinc-200 dark:border-zinc-700 p-4">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs font-medium text-zinc-500 dark:text-zinc-400 mb-1">Total Used</p>
                    <p class="text-xl font-bold text-blue-600 dark:text-blue-400">
                        {{ number_format($summaryData['total_quantity_used'] ?? 0, 2) }}
                    </p>
                    <p class="text-xs text-zinc-500 dark:text-zinc-400 mt-1">Units</p>
                </div>
                <div class="text-3xl text-blue-500 opacity-20">📦</div>
            </div>
        </div>

        <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-sm border border-zinc-200 dark:border-zinc-700 p-4">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs font-medium text-zinc-500 dark:text-zinc-400 mb-1">Production Usage</p>
                    <p class="text-xl font-bold text-green-600 dark:text-green-400">
                        {{ number_format($summaryData['production_usage'] ?? 0, 2) }}
                    </p>
                    <p class="text-xs text-zinc-500 dark:text-zinc-400 mt-1">Units</p>
                </div>
                <div class="text-3xl text-green-500 opacity-20">🏭</div>
            </div>
        </div>

        <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-sm border border-zinc-200 dark:border-zinc-700 p-4">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs font-medium text-zinc-500 dark:text-zinc-400 mb-1">Dispatch Usage</p>
                    <p class="text-xl font-bold text-purple-600 dark:text-purple-400">
                        {{ number_format($summaryData['dispatch_usage'] ?? 0, 2) }}
                    </p>
                    <p class="text-xs text-zinc-500 dark:text-zinc-400 mt-1">Units</p>
                </div>
                <div class="text-3xl text-purple-500 opacity-20">🚚</div>
            </div>
        </div>

        <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-sm border border-zinc-200 dark:border-zinc-700 p-4">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs font-medium text-zinc-500 dark:text-zinc-400 mb-1">Unique Items</p>
                    <p class="text-xl font-bold text-amber-600 dark:text-amber-400">
                        {{ number_format($summaryData['unique_items'] ?? 0) }}
                    </p>
                    <p class="text-xs text-zinc-500 dark:text-zinc-400 mt-1">Items used</p>
                </div>
                <div class="text-3xl text-amber-500 opacity-20">📋</div>
            </div>
        </div>
    </div>

    <!-- More Summary -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-sm border border-zinc-200 dark:border-zinc-700 p-4">
            <p class="text-xs font-medium text-zinc-500 dark:text-zinc-400 mb-1">Avg Daily Usage</p>
            <p class="text-lg font-bold text-zinc-800 dark:text-zinc-100">
                {{ number_format($summaryData['average_daily_usage'] ?? 0, 2) }}
            </p>
        </div>
        <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-sm border border-zinc-200 dark:border-zinc-700 p-4">
            <p class="text-xs font-medium text-zinc-500 dark:text-zinc-400 mb-1">Total Dispatches</p>
            <p class="text-lg font-bold text-zinc-800 dark:text-zinc-100">
                {{ number_format($summaryData['dispatches_count'] ?? 0) }}
            </p>
        </div>
        <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-sm border border-zinc-200 dark:border-zinc-700 p-4">
            <p class="text-xs font-medium text-zinc-500 dark:text-zinc-400 mb-1">Damaged</p>
            <p class="text-lg font-bold text-red-600 dark:text-red-400">
                {{ number_format($summaryData['damaged_usage'] ?? 0, 2) }}
            </p>
        </div>
    </div>

    <!-- Usage by Source -->
    <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-sm border border-zinc-200 dark:border-zinc-700 p-4">
        <h3 class="text-sm font-semibold text-zinc-800 dark:text-zinc-100 mb-4">Usage by Source</h3>
        @if(count($usageBySource) > 0)
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-zinc-200 dark:divide-zinc-700">
                    <thead class="bg-zinc-50 dark:bg-zinc-900">
                        <tr>
                            <th class="px-4 py-2 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400">Source</th>
                            <th class="px-4 py-2 text-right text-xs font-medium text-zinc-500 dark:text-zinc-400">Count</th>
                            <th class="px-4 py-2 text-right text-xs font-medium text-zinc-500 dark:text-zinc-400">Quantity</th>
                            <th class="px-4 py-2 text-right text-xs font-medium text-zinc-500 dark:text-zinc-400">%</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                        @foreach($usageBySource as $source)
                            <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-700/50">
                                <td class="px-4 py-2 text-sm text-zinc-800 dark:text-zinc-200">{{ $source['source'] }}</td>
                                <td class="px-4 py-2 text-sm text-right text-zinc-800 dark:text-zinc-200">{{ number_format($source['count']) }}</td>
                                <td class="px-4 py-2 text-sm text-right text-zinc-800 dark:text-zinc-200">{{ number_format($source['quantity'], 2) }}</td>
                                <td class="px-4 py-2 text-sm text-right text-zinc-800 dark:text-zinc-200">{{ $source['percentage'] }}%</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <p class="text-sm text-zinc-500 dark:text-zinc-400 text-center py-4">No usage data available</p>
        @endif
    </div>

    <!-- Usage by Category -->
    <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-sm border border-zinc-200 dark:border-zinc-700 p-4">
        <h3 class="text-sm font-semibold text-zinc-800 dark:text-zinc-100 mb-4">Usage by Category</h3>
        @if(count($usageByCategory) > 0)
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-zinc-200 dark:divide-zinc-700">
                    <thead class="bg-zinc-50 dark:bg-zinc-900">
                        <tr>
                            <th class="px-4 py-2 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400">Category</th>
                            <th class="px-4 py-2 text-right text-xs font-medium text-zinc-500 dark:text-zinc-400">Quantity</th>
                            <th class="px-4 py-2 text-right text-xs font-medium text-zinc-500 dark:text-zinc-400">%</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                        @foreach($usageByCategory as $cat)
                            <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-700/50">
                                <td class="px-4 py-2 text-sm text-zinc-800 dark:text-zinc-200">{{ $cat['category'] }}</td>
                                <td class="px-4 py-2 text-sm text-right text-zinc-800 dark:text-zinc-200">{{ number_format($cat['quantity'], 2) }}</td>
                                <td class="px-4 py-2 text-sm text-right text-zinc-800 dark:text-zinc-200">{{ $cat['percentage'] }}%</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <p class="text-sm text-zinc-500 dark:text-zinc-400 text-center py-4">No category data available</p>
        @endif
    </div>

    <!-- Top Consumed Items -->
    <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-sm border border-zinc-200 dark:border-zinc-700 p-4">
        <h3 class="text-sm font-semibold text-zinc-800 dark:text-zinc-100 mb-4">Top Consumed Items</h3>
        @if(count($topConsumedItems) > 0)
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-zinc-200 dark:divide-zinc-700">
                    <thead class="bg-zinc-50 dark:bg-zinc-900">
                        <tr>
                            <th class="px-4 py-2 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400">Item</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400">SKU</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400">Category</th>
                            <th class="px-4 py-2 text-right text-xs font-medium text-zinc-500 dark:text-zinc-400">Total Qty</th>
                            <th class="px-4 py-2 text-right text-xs font-medium text-zinc-500 dark:text-zinc-400">Movements</th>
                            <th class="px-4 py-2 text-right text-xs font-medium text-zinc-500 dark:text-zinc-400">Avg Qty</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                        @foreach($topConsumedItems as $item)
                            <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-700/50">
                                <td class="px-4 py-2 text-sm text-zinc-800 dark:text-zinc-200">{{ $item['item_name'] }}</td>
                                <td class="px-4 py-2 text-sm text-zinc-500 dark:text-zinc-400">{{ $item['sku'] }}</td>
                                <td class="px-4 py-2 text-sm text-zinc-500 dark:text-zinc-400">{{ $item['category'] }}</td>
                                <td class="px-4 py-2 text-sm text-right text-zinc-800 dark:text-zinc-200">{{ number_format($item['total_quantity'], 2) }}</td>
                                <td class="px-4 py-2 text-sm text-right text-zinc-800 dark:text-zinc-200">{{ number_format($item['movement_count']) }}</td>
                                <td class="px-4 py-2 text-sm text-right text-zinc-800 dark:text-zinc-200">{{ number_format($item['avg_quantity'], 2) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <p class="text-sm text-zinc-500 dark:text-zinc-400 text-center py-4">No consumed items found</p>
        @endif
    </div>

    <!-- Dispatch Breakdown -->
    @if(count($dispatchBreakdown) > 0)
        <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-sm border border-zinc-200 dark:border-zinc-700 p-4">
            <h3 class="text-sm font-semibold text-zinc-800 dark:text-zinc-100 mb-4">Dispatch Breakdown by Department</h3>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-zinc-200 dark:divide-zinc-700">
                    <thead class="bg-zinc-50 dark:bg-zinc-900">
                        <tr>
                            <th class="px-4 py-2 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400">Department</th>
                            <th class="px-4 py-2 text-right text-xs font-medium text-zinc-500 dark:text-zinc-400">Dispatches</th>
                            <th class="px-4 py-2 text-right text-xs font-medium text-zinc-500 dark:text-zinc-400">Quantity</th>
                            <th class="px-4 py-2 text-right text-xs font-medium text-zinc-500 dark:text-zinc-400">%</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                        @foreach($dispatchBreakdown as $dept)
                            <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-700/50">
                                <td class="px-4 py-2 text-sm text-zinc-800 dark:text-zinc-200">{{ $dept['department'] }}</td>
                                <td class="px-4 py-2 text-sm text-right text-zinc-800 dark:text-zinc-200">{{ number_format($dept['count']) }}</td>
                                <td class="px-4 py-2 text-sm text-right text-zinc-800 dark:text-zinc-200">{{ number_format($dept['quantity'], 2) }}</td>
                                <td class="px-4 py-2 text-sm text-right text-zinc-800 dark:text-zinc-200">{{ $dept['percentage'] }}%</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif

</div>
