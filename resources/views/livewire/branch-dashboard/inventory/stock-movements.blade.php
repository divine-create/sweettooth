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
        title="Stock Movements"
        :items="[
            ['label' => 'Dashboard', 'url' => branch_route('branch-dashboard.index')],
            ['label' => 'Inventory'],
            ['label' => 'Stock Movements']
        ]"
        :compact="false"
        :with-icons="true"
    />

    <!-- Analytics Summary Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 mb-3">
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

        <!-- Transfers -->
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

    <!-- Weekly Summary & Insights -->
    <div class="bg-gradient-to-r from-blue-50 to-indigo-50 dark:from-zinc-800 dark:to-zinc-700 rounded-lg shadow-sm border border-blue-200 dark:border-zinc-600 p-4 mb-3">
        <h3 class="text-sm font-bold text-zinc-800 dark:text-zinc-100 mb-3 flex items-center">
            <svg class="w-4 h-4 mr-1.5 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
            </svg>
            This Week's Summary
        </h3>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 text-sm">
            <div class="bg-white dark:bg-zinc-700 rounded p-2.5">
                <p class="text-xs text-zinc-500 dark:text-zinc-400">Total Stock Ins</p>
                <p class="text-lg font-bold text-green-600 dark:text-green-400">
                    {{ number_format($analytics['week']['stock_in'] ?? 0, 0) }} units
                </p>
                @php
                    $weekChange = ($analytics['last_week']['total_movements'] ?? 0) > 0
                        ? (($analytics['week']['total_movements'] ?? 0) - ($analytics['last_week']['total_movements'] ?? 0)) / ($analytics['last_week']['total_movements'] ?? 1) * 100
                        : 0;
                @endphp
                @if($weekChange != 0)
                    <p class="text-xs {{ $weekChange > 0 ? 'text-green-600' : 'text-red-600' }}">
                        {{ $weekChange > 0 ? '↑' : '↓' }} {{ number_format(abs($weekChange), 1) }}% vs last week
                    </p>
                @endif
            </div>

            <div class="bg-white dark:bg-zinc-700 rounded p-2.5">
                <p class="text-xs text-zinc-500 dark:text-zinc-400">Total Stock Outs</p>
                <p class="text-lg font-bold text-red-600 dark:text-red-400">
                    {{ number_format($analytics['week']['stock_out'] ?? 0, 0) }} units
                </p>
            </div>

            <div class="bg-white dark:bg-zinc-700 rounded p-2.5">
                <p class="text-xs text-zinc-500 dark:text-zinc-400">Net Stock Growth</p>
                @php
                    $netGrowth = ($analytics['week']['stock_in'] ?? 0) - ($analytics['week']['stock_out'] ?? 0);
                @endphp
                <p class="text-lg font-bold {{ $netGrowth >= 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                    {{ $netGrowth >= 0 ? '+' : '' }}{{ number_format($netGrowth, 0) }} units
                </p>
            </div>

            <div class="bg-white dark:bg-zinc-700 rounded p-2.5">
                <p class="text-xs text-zinc-500 dark:text-zinc-400">Total Movements</p>
                <p class="text-lg font-bold text-zinc-800 dark:text-zinc-200">
                    {{ number_format($analytics['week']['total_movements'] ?? 0, 0) }}
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

    <!-- View Mode Tabs & Export -->
    <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-sm border border-zinc-200 dark:border-zinc-700 mb-3 p-3">
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

            <!-- Export Buttons -->
            <div class="flex gap-1">
                <button
                    wire:click="exportCSV"
                    class="px-3 py-1.5 bg-green-600 hover:bg-green-700 text-white text-xs font-medium rounded-lg transition-colors flex items-center">
                    <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    CSV
                </button>
                <button
                    wire:click="exportCsv"
                    class="px-3 py-1.5 bg-blue-600 hover:bg-blue-700 text-white text-xs font-medium rounded-lg transition-colors flex items-center">
                    <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                    </svg>
                    CSV
                </button>
            </div>
        </div>
    </div>

    <!-- Filters Section -->
    <div x-data="{ open: false }"
        class="bg-white dark:bg-zinc-800 rounded-lg shadow-sm border border-zinc-200 dark:border-zinc-700 transition-all duration-300">
        <div class="flex justify-between items-center px-3 py-2 border-b border-zinc-200 dark:border-zinc-700">
            <h2 class="text-sm font-semibold text-zinc-800 dark:text-zinc-100 flex items-center">
                <svg class="w-4 h-4 mr-1.5 text-zinc-600 dark:text-zinc-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707L14.293 13H10v5l-4-4v-3.586L3.293 7.293A1 1 0 013 6.586V4z" />
                </svg>
                Filters
            </h2>
            <button @click="open = !open"
                class="flex items-center px-2.5 py-1 rounded text-xs font-medium bg-blue-600 hover:bg-blue-700 text-white transition-all duration-200">
                <svg class="w-3.5 h-3.5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path x-show="!open" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8h16M4 16h16" />
                    <path x-show="open" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
                <span x-text="open ? 'Close' : 'Show Filters'"></span>
            </button>
        </div>

        <div x-show="open" x-collapse class="p-3 space-y-3">
            <!-- Search -->
            <div>
                <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Search</label>
                <input type="text" wire:model.live.debounce.300ms="search"
                    placeholder="Search by item name, SKU, person, or notes..."
                    class="w-full px-4 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-800 dark:text-zinc-200 focus:ring-2 focus:ring-blue-500">
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3">
                <div>
                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Movement Type</label>
                    <select wire:model.live="filterType"
                        class="w-full px-4 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-800 dark:text-zinc-200 focus:ring-2 focus:ring-blue-500">
                        <option value="">All Types</option>
                        <option value="in">In</option>
                        <option value="out">Out</option>
                        <option value="adjustment">Adjustment</option>
                        <option value="transfer">Transfer</option>
                        <option value="damaged">Damaged</option>
                        <option value="return">Return</option>
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
                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Department</label>
                    <select wire:model.live="filterDepartment"
                        class="w-full px-4 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-800 dark:text-zinc-200 focus:ring-2 focus:ring-blue-500">
                        <option value="">All Departments</option>
                        @foreach($departments as $dept)
                            <option value="{{ $dept->id }}">{{ $dept->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Date From</label>
                    <input type="date" wire:model.live="filterDateFrom"
                        class="w-full px-4 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-800 dark:text-zinc-200 focus:ring-2 focus:ring-blue-500">
                </div>

                <div>
                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Date To</label>
                    <input type="date" wire:model.live="filterDateTo"
                        class="w-full px-4 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-800 dark:text-zinc-200 focus:ring-2 focus:ring-blue-500">
                </div>
            </div>

            <div class="flex flex-wrap gap-2 justify-end pt-2.5 border-t border-zinc-200 dark:border-zinc-700">
                <button wire:click="resetFilters"
                    class="px-4 py-2 bg-zinc-200 hover:bg-zinc-300 dark:bg-zinc-700 dark:hover:bg-zinc-600 text-zinc-800 dark:text-zinc-200 rounded-lg font-medium transition-colors duration-200 flex items-center">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                    </svg>
                    Reset
                </button>
            </div>
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

                                    @if($activity->mover)
                                        <p class="text-xs text-zinc-600 dark:text-zinc-400 mt-0.5">
                                            by <span class="font-medium">{{ $activity->mover->name }}</span>
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
        <x-table
        :headers="[
            ['index' => 'date_time', 'label' => 'Date & Time'],
            ['index' => 'item', 'label' => 'Item'],
            ['index' => 'type', 'label' => 'Type'],
            ['index' => 'quantity', 'label' => 'Quantity Change'],
            ['index' => 'stock_levels', 'label' => 'Stock Levels'],
            ['index' => 'shift', 'label' => 'Shift'],
            ['index' => 'department', 'label' => 'Department'],
            ['index' => 'people', 'label' => 'People Involved'],
            ['index' => 'purpose', 'label' => 'Purpose/Notes'],
        ]"
        :rows="$movements"
        striped
        paginate
        persist
        :filter="['quantity' => 'quantity', 'search' => 'search']"
        :quantity="[10, 25, 50, 100]">

        @interact('column_date_time', $row)
            <div class="text-zinc-900 dark:text-zinc-100">
                <div class="font-medium">{{ $row->movement_date->format('Y-m-d') }}</div>
                <div class="text-xs text-zinc-500 dark:text-zinc-400">{{ $row->movement_date->format('H:i') }}</div>
            </div>
        @endinteract

        @interact('column_item', $row)
            <div>
                <div class="font-medium text-zinc-900 dark:text-zinc-100">
                    {{ $row->stock->item->name ?? 'N/A' }}
                </div>
                <div class="text-xs text-zinc-500 dark:text-zinc-400">
                    SKU: {{ $row->stock->item->sku ?? 'N/A' }}
                </div>
            </div>
        @endinteract

        @interact('column_type', $row)
            @php
                $typeColors = [
                    'in' => 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200',
                    'out' => 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200',
                    'adjustment' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200',
                    'transfer' => 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200',
                    'damaged' => 'bg-orange-100 text-orange-800 dark:bg-orange-900 dark:text-orange-200',
                    'return' => 'bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-200',
                ];
                $typeIcons = [
                    'in' => '↓',
                    'out' => '↑',
                    'adjustment' => '⟳',
                    'transfer' => '⇄',
                    'damaged' => '⚠',
                    'return' => '↩',
                ];
            @endphp
            <span class="px-2 py-1 text-xs font-semibold rounded-full {{ $typeColors[$row->type] ?? '' }}">
                {{ $typeIcons[$row->type] ?? '' }} {{ ucfirst($row->type) }}
            </span>
        @endinteract

        @interact('column_quantity', $row)
            @php
                $quantityClass = $row->isInbound() ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400';
                $sign = $row->isInbound() ? '+' : '-';
            @endphp
            <div class="font-semibold {{ $quantityClass }}">
                {{ $sign }}{{ number_format(abs($row->quantity), 2) }}
            </div>
            <div class="text-xs text-zinc-500 dark:text-zinc-400">
                {{ $row->stock->item->uom ?? '' }}
            </div>
        @endinteract

        @interact('column_stock_levels', $row)
            <div class="text-sm">
                <div class="text-zinc-600 dark:text-zinc-400">
                    Before: <span class="font-medium text-zinc-900 dark:text-zinc-100">{{ number_format($row->quantity_before, 2) }}</span>
                </div>
                <div class="text-zinc-600 dark:text-zinc-400">
                    After: <span class="font-medium text-zinc-900 dark:text-zinc-100">{{ number_format($row->quantity_after, 2) }}</span>
                </div>
            </div>
        @endinteract

        @interact('column_shift', $row)
            @php
                $shift = null;
                if ($row->reference && $row->reference instanceof \App\Models\ItemRequest) {
                    $shift = $row->reference->shift;
                }
                $shiftColors = [
                    'morning' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200',
                    'afternoon' => 'bg-orange-100 text-orange-800 dark:bg-orange-900 dark:text-orange-200',
                    'night' => 'bg-indigo-100 text-indigo-800 dark:bg-indigo-900 dark:text-indigo-200',
                ];
            @endphp
            @if($shift)
                <span class="px-2 py-1 text-xs font-semibold rounded-full {{ $shiftColors[$shift] ?? '' }}">
                    {{ ucfirst($shift) }}
                </span>
            @else
                <span class="text-xs text-zinc-400 dark:text-zinc-500">N/A</span>
            @endif
        @endinteract

        @interact('column_department', $row)
            @php
                $department = null;
                if ($row->reference && $row->reference instanceof \App\Models\ItemRequest) {
                    $department = $row->reference->department;
                }
            @endphp
            @if($department)
                <div class="text-zinc-900 dark:text-zinc-100">
                    {{ $department->name }}
                </div>
            @else
                <span class="text-xs text-zinc-400 dark:text-zinc-500">N/A</span>
            @endif
        @endinteract

        @interact('column_people', $row)
            <div class="text-sm space-y-1">
                @php
                    $request = ($row->reference && $row->reference instanceof \App\Models\ItemRequest) ? $row->reference : null;
                @endphp

                @if($request)
                    @if($request->requester)
                        <div class="text-zinc-600 dark:text-zinc-400">
                            <span class="text-xs">Ordered:</span>
                            <span class="font-medium text-zinc-900 dark:text-zinc-100">{{ $request->requester->name }}</span>
                        </div>
                    @endif

                    @if($request->approver)
                        <div class="text-zinc-600 dark:text-zinc-400">
                            <span class="text-xs">Approved:</span>
                            <span class="font-medium text-zinc-900 dark:text-zinc-100">{{ $request->approver->name }}</span>
                        </div>
                    @endif
                @endif

                @if($row->mover)
                    <div class="text-zinc-600 dark:text-zinc-400">
                        <span class="text-xs">{{ $row->type === 'out' ? 'Dispatched' : 'Moved' }}:</span>
                        <span class="font-medium text-zinc-900 dark:text-zinc-100">{{ $row->mover->name }}</span>
                    </div>
                @endif

                @if(!$request && !$row->mover)
                    <span class="text-xs text-zinc-400 dark:text-zinc-500">N/A</span>
                @endif
            </div>
        @endinteract

        @interact('column_purpose', $row)
            <div class="text-sm">
                @if($row->reference)
                    <div class="text-zinc-600 dark:text-zinc-400 mb-1">
                        <span class="font-medium text-zinc-900 dark:text-zinc-100">
                            {{ class_basename($row->reference_type) }}
                        </span>
                        @if($row->reference instanceof \App\Models\ItemRequest)
                            <span class="text-xs">: {{ $row->reference->request_number }}</span>
                        @else
                            <span class="text-xs">: #{{ $row->reference_id }}</span>
                        @endif
                    </div>
                @endif

                @if($row->notes)
                    <div class="text-zinc-600 dark:text-zinc-400">
                        {{ Str::limit($row->notes, 80) }}
                    </div>
                @else
                    <span class="text-xs text-zinc-400 dark:text-zinc-500">No notes</span>
                @endif
            </div>
        @endinteract
    </x-table>
    @endif

</div>
