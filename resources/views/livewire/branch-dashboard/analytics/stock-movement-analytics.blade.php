<div class="p-3 space-y-3">

    <style>
        .scrollbar-thin::-webkit-scrollbar {
            width: 8px;
        }
        .scrollbar-thin::-webkit-scrollbar-track {
            background: transparent;
        }
        .scrollbar-thin::-webkit-scrollbar-thumb {
            @apply bg-zinc-300 dark:bg-zinc-700 rounded-full;
        }
        .scrollbar-thin::-webkit-scrollbar-thumb:hover {
            @apply bg-zinc-400 dark:bg-zinc-600;
        }
        [x-cloak] {
            display: none !important;
        }
    </style>

    <x-breadcrumb
        title="Stock Movement Analytics"
        :items="[
            ['label' => 'Dashboard', 'url' => branch_route('branch-dashboard.index')],
            ['label' => 'Analytics'],
            ['label' => 'Stock Movement Analytics']
        ]"
        :compact="false"
        :with-icons="true"
    />

    <!-- Today's Summary Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
        <!-- Stock In Today -->
        <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-sm border border-zinc-200 dark:border-zinc-700 p-3">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs font-medium text-zinc-500 dark:text-zinc-400 mb-1">Stock In Today</p>
                    <p class="text-xl font-bold text-green-600 dark:text-green-400">
                        +{{ number_format($analytics['today']['stock_in'] ?? 0, 2) }}
                    </p>
                    <p class="text-xs text-zinc-500 dark:text-zinc-400 mt-1">
                        {{ $analytics['today']['total_movements'] }} movements
                    </p>
                </div>
                <div class="text-3xl text-green-500 opacity-20">↓</div>
            </div>
        </div>

        <!-- Stock Out Today -->
        <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-sm border border-zinc-200 dark:border-zinc-700 p-3">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs font-medium text-zinc-500 dark:text-zinc-400 mb-1">Stock Out Today</p>
                    <p class="text-xl font-bold text-red-600 dark:text-red-400">
                        -{{ number_format($analytics['today']['stock_out'] ?? 0, 2) }}
                    </p>
                    <p class="text-xs text-zinc-500 dark:text-zinc-400 mt-1">
                        Net: {{ number_format(($analytics['today']['stock_in'] ?? 0) - ($analytics['today']['stock_out'] ?? 0), 2) }}
                    </p>
                </div>
                <div class="text-3xl text-red-500 opacity-20">↑</div>
            </div>
        </div>

        <!-- Transfers Today -->
        <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-sm border border-zinc-200 dark:border-zinc-700 p-3">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs font-medium text-zinc-500 dark:text-zinc-400 mb-1">Transfers</p>
                    <p class="text-xl font-bold text-blue-600 dark:text-blue-400">
                        {{ $analytics['today']['transfers'] ?? 0 }}
                    </p>
                    <p class="text-xs text-zinc-500 dark:text-zinc-400 mt-1">Today</p>
                </div>
                <div class="text-3xl text-blue-500 opacity-20">⇄</div>
            </div>
        </div>

        <!-- Latest Movement -->
        <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-sm border border-zinc-200 dark:border-zinc-700 p-3">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs font-medium text-zinc-500 dark:text-zinc-400 mb-1">Latest Movement</p>
                    @if($analytics['latest_movement'])
                        <p class="text-sm font-bold text-zinc-800 dark:text-zinc-200">
                            {{ $analytics['latest_movement']->diffForHumans() }}
                        </p>
                        <p class="text-xs text-zinc-500 dark:text-zinc-400 mt-1">
                            {{ $analytics['latest_movement']->format('M d, H:i') }}
                        </p>
                    @else
                        <p class="text-sm text-zinc-400 dark:text-zinc-500">No movements</p>
                    @endif
                </div>
                <div class="text-3xl text-zinc-500 opacity-20">🕓</div>
            </div>
        </div>
    </div>

    <!-- Period Summary & Insights -->
    <div class="bg-gradient-to-r from-blue-50 to-indigo-50 dark:from-zinc-800 dark:to-zinc-700 rounded-lg shadow-sm border border-blue-200 dark:border-zinc-600 p-4">
        <h3 class="text-sm font-bold text-zinc-800 dark:text-zinc-100 mb-3 flex items-center">
            <svg class="w-4 h-4 mr-1.5 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
            </svg>
            Selected Period Summary
            <span class="ml-2 text-xs font-normal text-zinc-600 dark:text-zinc-400">
                ({{ \Carbon\Carbon::parse($dateFrom)->format('M d') }} - {{ \Carbon\Carbon::parse($dateTo)->format('M d, Y') }})
            </span>
        </h3>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-6 gap-3 text-sm">
            <div class="bg-white dark:bg-zinc-700 rounded p-2.5">
                <p class="text-xs text-zinc-500 dark:text-zinc-400">Total Movements</p>
                <p class="text-lg font-bold text-zinc-800 dark:text-zinc-200">
                    {{ number_format($analytics['period']['total_movements'] ?? 0, 0) }}
                </p>
                @php
                    $periodChange = ($analytics['previous_period']['total_movements'] ?? 0) > 0
                        ? (($analytics['period']['total_movements'] ?? 0) - ($analytics['previous_period']['total_movements'] ?? 0)) / ($analytics['previous_period']['total_movements'] ?? 1) * 100
                        : 0;
                @endphp
                @if($periodChange != 0)
                    <p class="text-xs {{ $periodChange > 0 ? 'text-green-600' : 'text-red-600' }}">
                        {{ $periodChange > 0 ? '↑' : '↓' }} {{ number_format(abs($periodChange), 1) }}% vs previous
                    </p>
                @endif
            </div>

            <div class="bg-white dark:bg-zinc-700 rounded p-2.5">
                <p class="text-xs text-zinc-500 dark:text-zinc-400">Total Stock Ins</p>
                <p class="text-lg font-bold text-green-600 dark:text-green-400">
                    {{ number_format($analytics['period']['stock_in'] ?? 0, 0) }} units
                </p>
            </div>

            <div class="bg-white dark:bg-zinc-700 rounded p-2.5">
                <p class="text-xs text-zinc-500 dark:text-zinc-400">Total Stock Outs</p>
                <p class="text-lg font-bold text-red-600 dark:text-red-400">
                    {{ number_format($analytics['period']['stock_out'] ?? 0, 0) }} units
                </p>
            </div>

            <div class="bg-white dark:bg-zinc-700 rounded p-2.5">
                <p class="text-xs text-zinc-500 dark:text-zinc-400">Net Stock Growth</p>
                @php
                    $netGrowth = ($analytics['period']['stock_in'] ?? 0) - ($analytics['period']['stock_out'] ?? 0);
                @endphp
                <p class="text-lg font-bold {{ $netGrowth >= 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                    {{ $netGrowth >= 0 ? '+' : '' }}{{ number_format($netGrowth, 0) }} units
                </p>
            </div>

            <div class="bg-white dark:bg-zinc-700 rounded p-2.5">
                <p class="text-xs text-zinc-500 dark:text-zinc-400">Adjustments</p>
                <p class="text-lg font-bold text-yellow-600 dark:text-yellow-400">
                    {{ number_format($analytics['period']['adjustments'] ?? 0, 0) }}
                </p>
            </div>

            <div class="bg-white dark:bg-zinc-700 rounded p-2.5">
                <p class="text-xs text-zinc-500 dark:text-zinc-400">Damaged Items</p>
                <p class="text-lg font-bold text-orange-600 dark:text-orange-400">
                    {{ number_format($analytics['period']['damaged'] ?? 0, 0) }} units
                </p>
            </div>
        </div>

        <!-- Additional Insights -->
        <div class="mt-3 pt-3 border-t border-blue-200 dark:border-zinc-600 grid grid-cols-1 sm:grid-cols-3 gap-3 text-xs">
            @if($analytics['most_moved_item'])
                <div class="flex items-start">
                    <span class="text-zinc-600 dark:text-zinc-400">Most Moved Item:</span>
                    <span class="ml-2 font-semibold text-zinc-900 dark:text-zinc-100">
                        {{ $analytics['most_moved_item']->stock->item->name ?? 'N/A' }}
                        <span class="text-zinc-500">({{ $analytics['most_moved_item']->movement_count }} moves)</span>
                    </span>
                </div>
            @endif

            @if($analytics['most_active_user'])
                <div class="flex items-start">
                    <span class="text-zinc-600 dark:text-zinc-400">Most Active User:</span>
                    <span class="ml-2 font-semibold text-zinc-900 dark:text-zinc-100">
                        {{ $analytics['most_active_user']->mover->name ?? 'N/A' }}
                        <span class="text-zinc-500">({{ $analytics['most_active_user']->operation_count }} ops)</span>
                    </span>
                </div>
            @endif

            @if($analytics['peak_hours']->isNotEmpty())
                <div class="flex items-start">
                    <span class="text-zinc-600 dark:text-zinc-400">Peak Activity Hours:</span>
                    <span class="ml-2 font-semibold text-zinc-900 dark:text-zinc-100">
                        {{ $analytics['peak_hours']->pluck('formatted')->take(3)->implode(', ') }}
                    </span>
                </div>
            @endif
        </div>
    </div>

    <!-- Session Messages -->
    @if (session('warning'))
        <div class="p-3 bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-700 rounded text-xs text-yellow-800 dark:text-yellow-200 flex items-start">
            <svg class="w-4 h-4 mr-2 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
            </svg>
            {{ session('warning') }}
        </div>
    @endif

    @if (session('success'))
        <div class="p-3 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-700 rounded text-xs text-green-800 dark:text-green-200 flex items-start">
            <svg class="w-4 h-4 mr-2 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
            </svg>
            <div class="flex flex-wrap items-center gap-2">
                <span>{{ session('success') }}</span>
                @if($generatedReportId)
                    <a href="{{ branch_route('branch-dashboard.reporting.report.view', ['id' => $generatedReportId]) }}"
                       class="inline-flex items-center px-2 py-1 rounded bg-green-700 text-white hover:bg-green-800">
                        View Report
                    </a>
                @endif
            </div>
        </div>
    @endif

    <!-- Filters Section -->
    <div x-data="{ open: true }"
        class="bg-white dark:bg-zinc-800 rounded-lg shadow-sm border border-zinc-200 dark:border-zinc-700 transition-all duration-300">
        <div class="flex justify-between items-center px-3 py-2 border-b border-zinc-200 dark:border-zinc-700">
            <h2 class="text-sm font-semibold text-zinc-800 dark:text-zinc-100 flex items-center">
                <svg class="w-4 h-4 mr-1.5 text-zinc-600 dark:text-zinc-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707L14.293 13H10v5l-4-4v-3.586L3.293 7.293A1 1 0 013 6.586V4z" />
                </svg>
                Filters & Date Range
            </h2>
            <div class="flex gap-2">
                <button @click="open = !open"
                    class="flex items-center px-2.5 py-1 rounded text-xs font-medium bg-blue-600 hover:bg-blue-700 text-white transition-all duration-200">
                    <svg class="w-3.5 h-3.5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path x-show="!open" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8h16M4 16h16" />
                        <path x-show="open" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                    <span x-text="open ? 'Close' : 'Show Filters'"></span>
                </button>
                
                <!-- Reset Filters Button -->
                @if($isAnyFilterActive)
                    <button wire:click="resetFilters"
                        class="flex items-center px-2.5 py-1 rounded text-xs font-medium bg-gray-600 hover:bg-gray-700 text-white transition-all duration-200">
                        <svg class="w-3.5 h-3.5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                        </svg>
                        Reset
                    </button>
                @endif
            </div>
        </div>

        <div x-show="open" x-collapse class="p-3 space-y-3">
            <!-- Search -->
            <div>
                <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Search</label>
                <input type="text" wire:model.live.debounce.300ms="searchTerm"
                    placeholder="Search by item name, SKU, person, or notes..."
                    class="w-full px-4 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-800 dark:text-zinc-200 focus:ring-2 focus:ring-blue-500">
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-6 gap-3">
                <div class="lg:col-span-2">
                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Select Item</label>
                    <x-select.styled
                        wire:model.live="selectedItem"
                        :options="$availableItems"
                        select="label:name|value:id"
                        searchable
                        placeholder="All items..."
                    />
                </div>

                <div>
                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Movement Type</label>
                    <select wire:model.live="movementType"
                        class="w-full px-4 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-800 dark:text-zinc-200 focus:ring-2 focus:ring-blue-500">
                        <option value="">All Types</option>
                        @foreach($movementTypes as $type)
                            <option value="{{ $type }}">{{ ucfirst($type) }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Shift</label>
                    <select wire:model.live="filterShift"
                        class="w-full px-4 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-800 dark:text-zinc-200 focus:ring-2 focus:ring-blue-500">
                        <option value="">All Shifts</option>
                        <option value="morning">Morning</option>
                        <option value="afternoon">Afternoon</option>
                        <option value="night">Night</option>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">From</label>
                    <input type="date" wire:model.live="dateFrom"
                        class="w-full px-4 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-800 dark:text-zinc-200 focus:ring-2 focus:ring-blue-500">
                </div>

                <div>
                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">To</label>
                    <input type="date" wire:model.live="dateTo"
                        class="w-full px-4 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-800 dark:text-zinc-200 focus:ring-2 focus:ring-blue-500">
                </div>
            </div>
        </div>

        <!-- Applied Filters Display -->
        @if($isAnyFilterActive)
            <div class="p-3 border-t border-zinc-200 dark:border-zinc-700">
                <p class="text-xs font-medium text-zinc-600 dark:text-zinc-400 mb-2">Active Filters:</p>
                <div class="flex flex-wrap gap-2">
                    @if($movementType)
                        <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs bg-blue-100 dark:bg-blue-900 text-blue-800 dark:text-blue-200 font-medium">
                            Type: {{ ucfirst($movementType) }}
                            <button wire:click="$set('movementType', '')" class="ml-1 hover:font-bold text-blue-600 dark:text-blue-300">×</button>
                        </span>
                    @endif
                    
                    @if($selectedItem)
                        <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs bg-green-100 dark:bg-green-900 text-green-800 dark:text-green-200 font-medium">
                            Item Selected
                            <button wire:click="$set('selectedItem', null)" class="ml-1 hover:font-bold text-green-600 dark:text-green-300">×</button>
                        </span>
                    @endif
                    
                    @if($searchTerm)
                        <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs bg-purple-100 dark:bg-purple-900 text-purple-800 dark:text-purple-200 font-medium">
                            Search: "{{ substr($searchTerm, 0, 20) }}{{ strlen($searchTerm) > 20 ? '...' : '' }}"
                            <button wire:click="$set('searchTerm', '')" class="ml-1 hover:font-bold text-purple-600 dark:text-purple-300">×</button>
                        </span>
                    @endif
                    
                    @if($filterShift)
                        <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs bg-orange-100 dark:bg-orange-900 text-orange-800 dark:text-orange-200 font-medium">
                            Shift: {{ ucfirst($filterShift) }}
                            <button wire:click="$set('filterShift', '')" class="ml-1 hover:font-bold text-orange-600 dark:text-orange-300">×</button>
                        </span>
                    @endif
                    
                    @if($filterDepartment)
                        <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs bg-indigo-100 dark:bg-indigo-900 text-indigo-800 dark:text-indigo-200 font-medium">
                            Department Selected
                            <button wire:click="$set('filterDepartment', '')" class="ml-1 hover:font-bold text-indigo-600 dark:text-indigo-300">×</button>
                        </span>
                    @endif
                </div>
            </div>
        @endif
    </div>

    <!-- View Mode Tabs & Export -->
    <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-sm border border-zinc-200 dark:border-zinc-700 p-3">
        <div class="flex flex-wrap items-center justify-between gap-2">
            <!-- View Mode Tabs -->
            <div class="flex gap-1 bg-zinc-100 dark:bg-zinc-700 p-1 rounded-lg">
                <button
                    wire:click="setViewMode('table')"
                    class="px-3 py-1.5 text-xs font-medium rounded transition-all {{ $viewMode === 'table' ? 'bg-white dark:bg-zinc-600 text-blue-600 dark:text-blue-400 shadow-sm' : 'text-zinc-600 dark:text-zinc-400 hover:text-zinc-900 dark:hover:text-zinc-200' }}">
                    📋 Table View
                </button>
                <button
                    wire:click="setViewMode('feed')"
                    class="px-3 py-1.5 text-xs font-medium rounded transition-all {{ $viewMode === 'feed' ? 'bg-white dark:bg-zinc-600 text-blue-600 dark:text-blue-400 shadow-sm' : 'text-zinc-600 dark:text-zinc-400 hover:text-zinc-900 dark:hover:text-zinc-200' }}">
                    🕐 Activity Feed
                </button>
            </div>

            <div class="flex items-center gap-2">
                <button
                    wire:click="generateReport"
                    wire:loading.attr="disabled"
                    wire:target="generateReport"
                    class="px-3 py-1.5 bg-blue-600 hover:bg-blue-700 text-white text-xs font-medium rounded-lg transition-colors flex items-center disabled:opacity-60">
                    <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-3-3v6m8 4H4a1 1 0 01-1-1V6a1 1 0 011-1h10l6 6v7a1 1 0 01-1 1z"/>
                    </svg>
                    <span wire:loading.remove wire:target="generateReport">Generate Report</span>
                    <span wire:loading wire:target="generateReport">Generating...</span>
                </button>

                <button
                    wire:click="exportCsv"
                    class="px-3 py-1.5 bg-green-600 hover:bg-green-700 text-white text-xs font-medium rounded-lg transition-colors flex items-center">
                    <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    Export CSV
                </button>
            </div>
        </div>
    </div>

    <!-- Analytics Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-3">
        <!-- Top 10 Most Moved Items -->
        <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-sm border border-zinc-200 dark:border-zinc-700 p-4">
            <h3 class="text-base font-semibold text-zinc-800 dark:text-zinc-100 mb-4">Top 10 Most Moved Items</h3>
            <div class="space-y-2 max-h-[400px] overflow-y-auto scrollbar-thin">
                @forelse($topMovedItems as $index => $item)
                    <div class="flex items-center justify-between p-3 bg-gray-50 dark:bg-zinc-700/50 rounded hover:bg-gray-100 dark:hover:bg-zinc-700 transition-colors">
                        <div class="flex items-center flex-1">
                            <div class="flex items-center justify-center w-8 h-8 rounded-full bg-blue-100 dark:bg-blue-900 text-blue-600 dark:text-blue-400 font-bold text-sm mr-3">
                                {{ $index + 1 }}
                            </div>
                            <div>
                                <p class="font-medium text-zinc-900 dark:text-zinc-100">{{ $item->stock->item->name }}</p>
                                <p class="text-xs text-zinc-500 dark:text-zinc-400">
                                    SKU: {{ $item->stock->item->sku }} | {{ $item->movement_count }} movements
                                </p>
                            </div>
                        </div>
                        <div class="text-right">
                            <p class="font-bold text-blue-600 dark:text-blue-400">{{ number_format($item->total_moved, 2) }}</p>
                            <p class="text-xs text-zinc-500 dark:text-zinc-400">{{ $item->stock->item->uom }}</p>
                        </div>
                    </div>
                @empty
                    <p class="text-center text-zinc-500 dark:text-zinc-400 py-8">No data available</p>
                @endforelse
            </div>
        </div>
    </div>

    <!-- Daily Breakdown -->
    @if($analytics['daily_breakdown']->isNotEmpty())
        <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-sm border border-zinc-200 dark:border-zinc-700 p-4">
            <h3 class="text-base font-semibold text-zinc-800 dark:text-zinc-100 mb-4">Last 7 Days Breakdown</h3>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-zinc-100 dark:bg-zinc-700">
                        <tr>
                            <th class="px-4 py-2 text-left text-zinc-700 dark:text-zinc-300">Date</th>
                            <th class="px-4 py-2 text-right text-zinc-700 dark:text-zinc-300">Stock In</th>
                            <th class="px-4 py-2 text-right text-zinc-700 dark:text-zinc-300">Stock Out</th>
                            <th class="px-4 py-2 text-right text-zinc-700 dark:text-zinc-300">Net Change</th>
                            <th class="px-4 py-2 text-right text-zinc-700 dark:text-zinc-300">Total Movements</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                        @foreach($analytics['daily_breakdown'] as $day)
                            @php
                                $netChange = $day->stock_in - $day->stock_out;
                            @endphp
                            <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-700/50">
                                <td class="px-4 py-2 font-medium text-zinc-900 dark:text-zinc-100">
                                    {{ \Carbon\Carbon::parse($day->date)->format('D, M d, Y') }}
                                </td>
                                <td class="px-4 py-2 text-right text-green-600 dark:text-green-400">
                                    +{{ number_format($day->stock_in, 2) }}
                                </td>
                                <td class="px-4 py-2 text-right text-red-600 dark:text-red-400">
                                    -{{ number_format($day->stock_out, 2) }}
                                </td>
                                <td class="px-4 py-2 text-right font-semibold {{ $netChange >= 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                                    {{ $netChange >= 0 ? '+' : '' }}{{ number_format($netChange, 2) }}
                                </td>
                                <td class="px-4 py-2 text-right text-zinc-900 dark:text-zinc-100">
                                    {{ number_format($day->total_movements) }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    <!-- Stock Velocity (Fastest Moving Items) -->
    <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-sm border border-zinc-200 dark:border-zinc-700 p-4">
        <h3 class="text-base font-semibold text-zinc-800 dark:text-zinc-100 mb-4">Stock Velocity (Fastest Moving Out)</h3>
        <div class="space-y-2">
            @forelse($velocityAnalysis as $index => $item)
                <div class="flex items-center justify-between p-3 bg-gray-50 dark:bg-zinc-700/50 rounded">
                    <div class="flex items-center flex-1">
                        <div class="flex items-center justify-center w-8 h-8 rounded-full bg-purple-100 dark:bg-purple-900 text-purple-600 dark:text-purple-400 font-bold text-sm mr-3">
                            {{ $index + 1 }}
                        </div>
                        <div>
                            <p class="font-medium text-zinc-900 dark:text-zinc-100">{{ $item->stock->item->name }}</p>
                            <p class="text-xs text-zinc-500 dark:text-zinc-400">{{ $item->stock->item->sku }}</p>
                        </div>
                    </div>
                    <div class="text-right">
                        <p class="font-bold text-purple-600 dark:text-purple-400">{{ number_format($item->movement_count) }} moves</p>
                        <p class="text-xs text-zinc-500 dark:text-zinc-400">{{ number_format($item->total_quantity, 2) }} {{ $item->stock->item->uom }}</p>
                    </div>
                </div>
            @empty
                <p class="text-center text-zinc-500 dark:text-zinc-400 py-8">No velocity data available</p>
            @endforelse
        </div>
    </div>

    <!-- Activity Feed View -->
    @if($viewMode === 'feed')
        <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-sm border border-zinc-200 dark:border-zinc-700 p-4">
            <h3 class="text-sm font-bold text-zinc-800 dark:text-zinc-100 mb-4 flex items-center">
                <svg class="w-4 h-4 mr-1.5 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                Real-Time Activity Feed
                <span class="ml-2 text-xs font-normal text-zinc-500 dark:text-zinc-400">(Last 20 movements)</span>
            </h3>

            <div class="space-y-2 max-h-[600px] overflow-y-auto scrollbar-thin">
                @forelse($activityFeed as $activity)
                    @php
                        $typeIcons = [
                            'in' => '🟢',
                            'out' => '🔴',
                            'transfer' => '🟣',
                            'return' => '🟡',
                            'adjustment' => '🟠',
                            'damaged' => '⚠️',
                        ];
                        $icon = $typeIcons[$activity->type] ?? '⚪';
                        $sign = $activity->isInbound() ? '+' : '-';
                    @endphp

                    <div class="flex items-start gap-3 p-3 bg-zinc-50 dark:bg-zinc-700/50 rounded-lg hover:bg-zinc-100 dark:hover:bg-zinc-700 transition-colors">
                        <div class="text-2xl">{{ $icon }}</div>
                        <div class="flex-1 min-w-0">
                            <div class="flex items-start justify-between gap-2">
                                <div class="flex-1">
                                    <p class="text-sm text-zinc-900 dark:text-zinc-100">
                                        <span class="font-semibold {{ $activity->isInbound() ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                                            {{ $sign }}{{ number_format(abs($activity->quantity), 2) }} units
                                        </span>
                                        of
                                        <span class="font-medium">{{ $activity->stock->item->name ?? 'N/A' }}</span>
                                        {{ $activity->type === 'in' ? 'added to' : ($activity->type === 'out' ? 'removed from' : ($activity->type === 'transfer' ? 'transferred from' : 'adjusted in')) }}
                                        <span class="font-medium">{{ $activity->stock->branch->name ?? 'Stock' }}</span>
                                    </p>

                                    @php
                                        $request = ($activity->reference && $activity->reference instanceof \App\Models\ItemRequest)
                                            ? $activity->reference
                                            : null;
                                    @endphp

                                    @if($activity->mover || $request)
                                        <p class="text-xs text-zinc-600 dark:text-zinc-400 mt-0.5">
                                            @if($activity->mover)
                                                by <span class="font-medium">{{ $activity->mover->name }}</span>
                                            @endif
                                            @if($request && $request->department)
                                                {{ $activity->mover ? ' • ' : '' }}dispatched to <span class="font-medium text-blue-600 dark:text-blue-400">{{ $request->department->name }}</span>
                                                @if($request->shift)
                                                    <span class="text-zinc-500">({{ ucfirst($request->shift) }} shift)</span>
                                                @endif
                                            @endif
                                        </p>
                                    @endif

                                    @if($activity->notes)
                                        <p class="text-xs text-zinc-500 dark:text-zinc-500 mt-1 italic">
                                            "{{ Str::limit($activity->notes, 100) }}"
                                        </p>
                                    @endif
                                </div>

                                <div class="text-right flex-shrink-0">
                                    <p class="text-xs font-medium text-zinc-700 dark:text-zinc-300">
                                        {{ $activity->movement_date->format('H:i') }}
                                    </p>
                                    <p class="text-xs text-zinc-500 dark:text-zinc-400">
                                        {{ $activity->movement_date->format('M d') }}
                                    </p>
                                    <p class="text-xs text-zinc-400 dark:text-zinc-500 mt-0.5">
                                        {{ $activity->movement_date->diffForHumans() }}
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="text-center py-12 text-zinc-500 dark:text-zinc-400">
                        <svg class="w-16 h-16 mx-auto mb-3 opacity-30" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" />
                        </svg>
                        <p class="text-sm">No recent activity found</p>
                    </div>
                @endforelse
            </div>
        </div>
    @endif

    <!-- Table View -->
    @if($viewMode === 'table')
        <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-sm border border-zinc-200 dark:border-zinc-700 p-4">
            <h3 class="text-base font-semibold text-zinc-800 dark:text-zinc-100 mb-4">Recent Movements</h3>
            <div class="overflow-x-auto">
                <table class="w-full text-sm text-left">
                    <thead class="text-xs uppercase bg-zinc-100 dark:bg-zinc-700 text-zinc-700 dark:text-zinc-300">
                        <tr>
                            <th class="px-4 py-3">Date</th>
                            <th class="px-4 py-3">Item</th>
                            <th class="px-4 py-3">Type</th>
                            <th class="px-4 py-3">Quantity</th>
                            <th class="px-4 py-3">Before</th>
                            <th class="px-4 py-3">After</th>
                            <th class="px-4 py-3">Moved By</th>
                            <th class="px-4 py-3">Dispatched To</th>
                            <th class="px-4 py-3">Notes</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                        @forelse($movements as $movement)
                            <tr class="bg-white dark:bg-zinc-800 hover:bg-zinc-50 dark:hover:bg-zinc-700/50">
                                <td class="px-4 py-3 text-zinc-900 dark:text-zinc-100">
                                    {{ \Carbon\Carbon::parse($movement->movement_date)->format('M d, Y H:i') }}
                                </td>
                                <td class="px-4 py-3">
                                    <div>
                                        <div class="font-medium text-zinc-900 dark:text-zinc-100">{{ $movement->stock->item->name }}</div>
                                        <div class="text-xs text-gray-500 dark:text-gray-400">{{ $movement->stock->item->sku }}</div>
                                    </div>
                                </td>
                                <td class="px-4 py-3">
                                    @php
                                        $badgeColors = match($movement->type) {
                                            'in' => 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200',
                                            'out' => 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200',
                                            'adjustment' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200',
                                            'transfer' => 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200',
                                            'damaged' => 'bg-orange-100 text-orange-800 dark:bg-orange-900 dark:text-orange-200',
                                            'return' => 'bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-200',
                                            default => 'bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-200'
                                        };
                                    @endphp
                                    <span class="px-2 py-1 text-xs font-semibold rounded {{ $badgeColors }}">
                                        {{ ucfirst($movement->type) }}
                                    </span>
                                </td>
                                <td class="px-4 py-3">
                                    <span class="font-medium {{ $movement->isInbound() ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                                        {{ $movement->isInbound() ? '+' : '-' }}{{ number_format(abs($movement->quantity), 2) }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-zinc-900 dark:text-zinc-100">{{ number_format($movement->quantity_before, 2) }}</td>
                                <td class="px-4 py-3 text-zinc-900 dark:text-zinc-100">{{ number_format($movement->quantity_after, 2) }}</td>
                                <td class="px-4 py-3">
                                    @if($movement->mover)
                                        <span class="text-zinc-900 dark:text-zinc-100">{{ $movement->mover->name }}</span>
                                    @else
                                        <span class="text-gray-400 dark:text-gray-500">System</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3">
                                    @php
                                        $request = ($movement->reference && $movement->reference instanceof \App\Models\ItemRequest)
                                            ? $movement->reference
                                            : null;
                                    @endphp
                                    @if($request && $request->department)
                                        <div>
                                            <div class="font-medium text-blue-600 dark:text-blue-400">{{ $request->department->name }}</div>
                                            @if($request->shift)
                                                <div class="text-xs text-zinc-500 dark:text-zinc-400">{{ ucfirst($request->shift) }} shift</div>
                                            @endif
                                        </div>
                                    @else
                                        <span class="text-gray-400 dark:text-gray-500">-</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3">
                                    <span class="text-xs text-zinc-900 dark:text-zinc-100">{{ Str::limit($movement->notes ?? '-', 50) }}</span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="px-4 py-8 text-center text-gray-500 dark:text-gray-400">
                                    No movements found
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-4">
                {{ $movements->links() }}
            </div>
        </div>
    @endif

</div>
