<div class="p-3 space-y-3">

    <style>
        /* Custom scrollbar styles */
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
        title="Stock Levels"
        :items="[
            ['label' => 'Dashboard', 'url' => route('dashboard')],
            ['label' => 'Inventory'],
            ['label' => 'Stock Levels']
        ]"
        :compact="false"
        :with-icons="true"
    />



    <!-- Bulk Actions Bar -->
    <div x-data="{ selectedIds: @entangle('selectedIds') }" x-show="selectedIds.length > 0" x-cloak
        class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-4">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div class="flex flex-col sm:flex-row sm:items-center gap-2 sm:gap-4">
                <span class="text-sm font-medium text-blue-900 dark:text-blue-100">
                    <span x-text="selectedIds.length"></span> stock record(s) selected
                </span>
                <button wire:click="toggleBulkMode"
                    class="text-sm text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-300 underline text-left">
                    Clear Selection
                </button>
            </div>
            <div class="flex flex-col sm:flex-row gap-2">
                <button wire:click="exportSelected"
                    class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg font-medium transition-colors duration-200 flex items-center justify-center">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    Export Selected
                </button>
            </div>
        </div>
    </div>

    <!-- Filters Section -->
    <div x-data="{ open: false, advanced: false }"
        class="bg-white dark:bg-zinc-800 rounded-lg shadow-sm border border-zinc-200 dark:border-zinc-700 transition-all duration-300">
        <!-- Header / Toggle Button -->
        <div class="flex justify-between items-center px-3 py-2 border-b border-zinc-200 dark:border-zinc-700">
            <h2 class="text-sm font-semibold text-zinc-800 dark:text-zinc-100 flex items-center">
                <svg class="w-4 h-4 mr-1.5 text-zinc-600 dark:text-zinc-400" fill="none" stroke="currentColor"
                    viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707L14.293 13H10v5l-4-4v-3.586L3.293 7.293A1 1 0 013 6.586V4z" />
                </svg>
                Filters
            </h2>

            <button @click="open = !open"
                class="flex items-center px-2.5 py-1 rounded text-xs font-medium bg-blue-600 hover:bg-blue-700 text-white transition-all duration-200">
                <svg class="w-3.5 h-3.5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path x-show="!open" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M4 8h16M4 16h16" />
                    <path x-show="open" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M6 18L18 6M6 6l12 12" />
                </svg>
                <span x-text="open ? 'Close' : 'Show Filters'"></span>
            </button>
        </div>

        <!-- Filter Body -->
        <div x-show="open" x-collapse class="p-3 space-y-3">
            <!-- Basic Filters -->
            <div class="">
                <!-- Advanced Search Toggle -->
                <div>
                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Advanced
                        Search</label>
                    <button @click="advanced = !advanced"
                        class="flex items-center w-full px-4 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-zinc-50 dark:bg-zinc-700 text-zinc-800 dark:text-zinc-200 hover:bg-zinc-100 dark:hover:bg-zinc-600 transition-colors duration-200">
                        <svg class="w-5 h-5 mr-2 text-zinc-500" fill="none" stroke="currentColor"
                            viewBox="0 0 24 24">
                            <path x-show="!advanced" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M5 12h14M12 5l7 7-7 7" />
                            <path x-show="advanced" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M19 12H5m7 7l-7-7 7-7" />
                        </svg>
                        <span x-text="advanced ? 'Hide Advanced' : 'Show Advanced'"></span>
                    </button>
                </div>
            </div>

            <!-- Advanced Search Dropdown -->
            <div x-show="advanced" x-collapse
                class="p-2.5 border border-zinc-200 dark:border-zinc-700 rounded-lg bg-zinc-50 dark:bg-zinc-900/50 mt-2">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                    <!-- Search -->
                    <div class="md:col-span-1">
                        <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Search</label>
                        <div class="relative">
                            <input type="text" wire:model.live="advancedSearch" placeholder="Search keyword..."
                                class="w-full pl-10 pr-4 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-800 dark:text-zinc-200 focus:ring-2 focus:ring-blue-500">
                            <svg class="absolute left-3 top-2.5 w-5 h-5 text-zinc-400" fill="none"
                                stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                        </div>
                    </div>

                    <!-- Date Range -->
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Last Stock Take Date Range</label>
                        <div class="flex space-x-2">
                            <input type="date" wire:model.live="dateFrom"
                                class="w-1/2 px-4 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-800 dark:text-zinc-200 focus:ring-2 focus:ring-blue-500">
                            <input type="date" wire:model.live="dateTo"
                                class="w-1/2 px-4 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-800 dark:text-zinc-200 focus:ring-2 focus:ring-blue-500">
                        </div>
                    </div>
                </div>
            </div>

            <!-- Filters Grid -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
                <!-- Branch Filter -->
                <div>
                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Branch</label>
                    <x-select.styled
                        wire:model.live="filterBranch"
                        :options="$branches->map(fn($branch) => ['label' => $branch->name, 'value' => $branch->id])->toArray()"
                        select="label:label|value:value"
                        placeholder="All Branches"
                        searchable
                    />
                </div>

                <!-- Category Filter -->
                <div>
                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Category</label>
                    <x-select.styled
                        wire:model.live="filterCategory"
                        :options="[
                            ['label' => 'Raw Material', 'value' => 'raw_material'],
                            ['label' => 'Packaging', 'value' => 'packaging'],
                            ['label' => 'Consumable', 'value' => 'consumable'],
                            ['label' => 'Equipment', 'value' => 'equipment']
                        ]"
                        select="label:label|value:value"
                        placeholder="All Categories"
                        searchable
                    />
                </div>

                <!-- Stock Level Filter -->
                <div>
                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Stock Level</label>
                    <x-select.styled
                        wire:model.live="filterStockLevel"
                        :options="[
                            ['label' => 'Critical (< 25% of Reorder)', 'value' => 'critical'],
                            ['label' => 'Low (Below Reorder Level)', 'value' => 'low'],
                            ['label' => 'High (Above Reorder Level)', 'value' => 'high'],
                            ['label' => 'Out of Stock', 'value' => 'out_of_stock'],
                            ['label' => 'Overstock (Above Max)', 'value' => 'overstock']
                        ]"
                        select="label:label|value:value"
                        placeholder="All Stock Levels"
                    />
                </div>

                <!-- Health Status Filter -->
                <div>
                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Health Status</label>
                    <x-select.styled
                        wire:model.live="filterHealthStatus"
                        :options="[
                            ['label' => 'Good', 'value' => 'good'],
                            ['label' => 'Warning', 'value' => 'warning'],
                            ['label' => 'Critical', 'value' => 'critical'],
                            ['label' => 'Expired', 'value' => 'expired']
                        ]"
                        select="label:label|value:value"
                        placeholder="All Health Status"
                    />
                </div>
            </div>

            <!-- Filter Buttons -->
            <div class="flex flex-wrap gap-2 justify-end pt-2.5 border-t border-zinc-200 dark:border-zinc-700">
                <button wire:click="applyFilters"
                    class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg font-medium transition-colors duration-200 flex items-center shadow-sm">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.207A1 1 0 013 6.5V4z" />
                    </svg>
                    Apply
                </button>
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

    <!-- Table -->
    <x-table :$headers :$rows selectable wire:model="selectedIds" striped paginate persist :filter="['quantity' => 'quantity', 'search' => 'search']"
        :quantity="[10, 25, 50, 100]">

        @interact('column_item', $row)
            <span class="font-medium text-zinc-900 dark:text-zinc-100">
                {{ $row->item->name }}
            </span>
        @endinteract

        @interact('column_sku', $row)
            <span class="font-mono text-zinc-600 dark:text-zinc-400">
                {{ $row->item->sku }}
            </span>
        @endinteract

        @interact('column_branch', $row)
            <span class="text-zinc-900 dark:text-zinc-100">
                {{ $row->branch->name }}
            </span>
        @endinteract

        @interact('column_category', $row)
            <span
                class="px-2 py-1 text-xs font-semibold rounded-full
                {{ $row->item->category === 'raw_material' ? 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200' : '' }}
                {{ $row->item->category === 'packaging' ? 'bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-200' : '' }}
                {{ $row->item->category === 'consumable' ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' : '' }}
                {{ $row->item->category === 'equipment' ? 'bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-200' : '' }}">
                {{ ucfirst(str_replace('_', ' ', $row->item->category)) }}
            </span>
        @endinteract

        @interact('column_available', $row)
            @php
                $isBelowReorder = $row->isBelowReorderLevel();
            @endphp
            <div class="flex items-center space-x-1">
                <span class="font-medium {{ $isBelowReorder ? 'text-red-600 dark:text-red-400' : 'text-green-600 dark:text-green-400' }}">
                    {{ number_format($row->quantity_available, 2) }}
                </span>
                <span class="text-xs text-zinc-500 dark:text-zinc-400">{{ strtoupper($row->item->uom) }}</span>
            </div>
        @endinteract

        @interact('column_reserved', $row)
            <span class="text-zinc-700 dark:text-zinc-300">
                {{ number_format($row->quantity_reserved, 2) }}
            </span>
        @endinteract

        @interact('column_damaged', $row)
            <span class="text-red-600 dark:text-red-400">
                {{ number_format($row->quantity_damaged, 2) }}
            </span>
        @endinteract

        @interact('column_total', $row)
            @php
                $exceedsMax = $row->exceedsMaxLevel();
            @endphp
            <span class="font-medium {{ $exceedsMax ? 'text-orange-600 dark:text-orange-400' : 'text-zinc-900 dark:text-zinc-100' }}">
                {{ number_format($row->total_quantity, 2) }}
            </span>
        @endinteract

        @interact('column_reorder_level', $row)
            <span class="text-zinc-600 dark:text-zinc-400">
                {{ $row->item->reorder_level ? number_format($row->item->reorder_level, 2) : 'N/A' }}
            </span>
        @endinteract

        @interact('column_health_status', $row)
            <span
                class="px-2 py-1 text-xs font-semibold rounded-full
                {{ $row->health_status === 'good' ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' : '' }}
                {{ $row->health_status === 'warning' ? 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200' : '' }}
                {{ $row->health_status === 'critical' ? 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200' : '' }}
                {{ $row->health_status === 'expired' ? 'bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-200' : '' }}">
                {{ ucfirst($row->health_status) }}
            </span>
        @endinteract

        @interact('column_last_stock_take', $row)
            <span class="text-xs text-zinc-600 dark:text-zinc-400">
                {{ $row->last_stock_take_date ? $row->last_stock_take_date->format('M d, Y') : 'Never' }}
            </span>
        @endinteract

        @interact('column_action', $row)
            <div class="flex items-center space-x-2">
                <button wire:click="openEditStockModal({{ $row->id }})"
                    class="p-2 text-green-600 hover:text-green-800 dark:text-green-400 dark:hover:text-green-300 hover:bg-green-50 dark:hover:bg-green-900/20 rounded-lg transition-colors"
                    title="Edit Stock">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                    </svg>
                </button>
                <button wire:click="openHistoryModal({{ $row->item->id }})"
                    class="p-2 text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300 hover:bg-blue-50 dark:hover:bg-blue-900/20 rounded-lg transition-colors"
                    title="View History">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </button>
            </div>
        @endinteract
    </x-table>

    <!-- Item History Modal (Slide-in) -->
    <div x-data="{ show: @entangle('showHistoryModal') }" x-show="show" x-cloak class="fixed inset-0 z-50 overflow-hidden"
        @keydown.escape.window="show = false">
        <!-- Backdrop -->
        <div x-show="show" x-transition:enter="transition-opacity ease-linear duration-300"
            x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
            x-transition:leave="transition-opacity ease-linear duration-300" x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0" class="fixed inset-0 bg-black bg-opacity-50"
            @click="$wire.closeHistoryModal()">
        </div>

        <!-- Slide-in Panel -->
        <div x-show="show" x-transition:enter="transform transition ease-in-out duration-300"
            x-transition:enter-start="translate-x-full" x-transition:enter-end="translate-x-0"
            x-transition:leave="transform transition ease-in-out duration-300"
            x-transition:leave-start="translate-x-0" x-transition:leave-end="translate-x-full"
            class="fixed inset-y-0 right-0 w-full md:w-1/2 lg:w-1/2 bg-white dark:bg-zinc-900 shadow-xl flex flex-col">

            <!-- Header -->
            <div class="px-6 py-4 border-b border-zinc-200 dark:border-zinc-700 flex items-center justify-between">
                <h2 class="text-xl font-bold text-zinc-900 dark:text-zinc-100">
                    <svg class="w-6 h-6 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    Item History & Monitoring
                </h2>
                <button wire:click="closeHistoryModal"
                    class="p-2 hover:bg-zinc-100 dark:hover:bg-zinc-800 rounded-lg transition-colors">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <!-- Scrollable Content -->
            <div
                class="flex-1 overflow-y-auto px-6 py-4 scrollbar-thin scrollbar-thumb-zinc-300 dark:scrollbar-thumb-zinc-700 scrollbar-track-transparent">

                @if($historyItemId)
                    @php
                        $item = \App\Models\Item::with(['stocks', 'branch'])->find($historyItemId);
                        $stock = $item ? $item->stocks()->where('branch_id', $item->branch_id)->first() : null;

                        // Get stock movements with filters
                        $movementsQuery = \App\Models\StockMovement::where('stock_id', $stock?->id)
                            ->when($historyDateFrom, fn($q) => $q->whereDate('movement_date', '>=', $historyDateFrom))
                            ->when($historyDateTo, fn($q) => $q->whereDate('movement_date', '<=', $historyDateTo))
                            ->when($historyMovementType, fn($q) => $q->where('type', $historyMovementType))
                            ->with('mover')
                            ->orderBy('movement_date', 'desc')
                            ->get();
                    @endphp

                    @if($item && $stock)
                        <!-- Item Details Card -->
                        <div class="bg-gradient-to-br from-blue-50 to-indigo-50 dark:from-blue-900/20 dark:to-indigo-900/20 rounded-lg p-4 mb-6 border border-blue-200 dark:border-blue-800">
                            <h3 class="text-lg font-semibold text-zinc-900 dark:text-zinc-100 mb-3 flex items-center">
                                <svg class="w-5 h-5 mr-2 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                Item Information
                            </h3>
                            <div class="grid grid-cols-2 gap-3 text-sm">
                                <div>
                                    <span class="text-zinc-600 dark:text-zinc-400">Name:</span>
                                    <p class="font-medium text-zinc-900 dark:text-zinc-100">{{ $item->name }}</p>
                                </div>
                                <div>
                                    <span class="text-zinc-600 dark:text-zinc-400">SKU:</span>
                                    <p class="font-mono text-zinc-900 dark:text-zinc-100">{{ $item->sku }}</p>
                                </div>
                                <div>
                                    <span class="text-zinc-600 dark:text-zinc-400">Branch:</span>
                                    <p class="text-zinc-900 dark:text-zinc-100">{{ $item->branch->name }}</p>
                                </div>
                                <div>
                                    <span class="text-zinc-600 dark:text-zinc-400">Category:</span>
                                    <p class="text-zinc-900 dark:text-zinc-100">{{ ucfirst(str_replace('_', ' ', $item->category)) }}</p>
                                </div>
                            </div>
                        </div>

                        <!-- Current Stock Cards -->
                        <div class="grid grid-cols-4 gap-3 mb-6">
                            <div class="bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-lg p-3 text-center">
                                <div class="text-xs text-green-600 dark:text-green-400 mb-1">Available</div>
                                <div class="text-xl font-bold text-green-700 dark:text-green-300">
                                    {{ number_format($stock->quantity_available, 2) }}
                                </div>
                            </div>
                            <div class="bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-lg p-3 text-center">
                                <div class="text-xs text-yellow-600 dark:text-yellow-400 mb-1">Reserved</div>
                                <div class="text-xl font-bold text-yellow-700 dark:text-yellow-300">
                                    {{ number_format($stock->quantity_reserved, 2) }}
                                </div>
                            </div>
                            <div class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg p-3 text-center">
                                <div class="text-xs text-red-600 dark:text-red-400 mb-1">Damaged</div>
                                <div class="text-xl font-bold text-red-700 dark:text-red-300">
                                    {{ number_format($stock->quantity_damaged, 2) }}
                                </div>
                            </div>
                            <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-3 text-center">
                                <div class="text-xs text-blue-600 dark:text-blue-400 mb-1">Total</div>
                                <div class="text-xl font-bold text-blue-700 dark:text-blue-300">
                                    {{ number_format($stock->total_quantity, 2) }}
                                </div>
                            </div>
                        </div>

                        <!-- History Filters -->
                        <div class="bg-zinc-50 dark:bg-zinc-800 rounded-lg p-4 mb-4 border border-zinc-200 dark:border-zinc-700">
                            <h4 class="text-sm font-semibold text-zinc-900 dark:text-zinc-100 mb-3">Filter History</h4>
                            <div class="grid grid-cols-3 gap-3">
                                <div>
                                    <label class="block text-xs text-zinc-600 dark:text-zinc-400 mb-1">From Date</label>
                                    <input type="date" wire:model.live="historyDateFrom"
                                        class="w-full px-3 py-1.5 text-sm border border-zinc-300 dark:border-zinc-600 rounded bg-white dark:bg-zinc-700 text-zinc-900 dark:text-zinc-100">
                                </div>
                                <div>
                                    <label class="block text-xs text-zinc-600 dark:text-zinc-400 mb-1">To Date</label>
                                    <input type="date" wire:model.live="historyDateTo"
                                        class="w-full px-3 py-1.5 text-sm border border-zinc-300 dark:border-zinc-600 rounded bg-white dark:bg-zinc-700 text-zinc-900 dark:text-zinc-100">
                                </div>
                                <div>
                                    <label class="block text-xs text-zinc-600 dark:text-zinc-400 mb-1">Movement Type</label>
                                    <select wire:model.live="historyMovementType"
                                        class="w-full px-3 py-1.5 text-sm border border-zinc-300 dark:border-zinc-600 rounded bg-white dark:bg-zinc-700 text-zinc-900 dark:text-zinc-100">
                                        <option value="">All Types</option>
                                        <option value="in">In</option>
                                        <option value="out">Out</option>
                                        <option value="adjustment">Adjustment</option>
                                        <option value="transfer">Transfer</option>
                                        <option value="damaged">Damaged</option>
                                        <option value="return">Return</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <!-- Movement History Timeline -->
                        <div>
                            <h4 class="text-sm font-semibold text-zinc-900 dark:text-zinc-100 mb-3">Stock Movement History ({{ $movementsQuery->count() }})</h4>

                            @if($movementsQuery->count() > 0)
                                <div class="space-y-3">
                                    @foreach($movementsQuery as $movement)
                                        <div class="bg-white dark:bg-zinc-800 border border-zinc-200 dark:border-zinc-700 rounded-lg p-3 hover:shadow-md transition-shadow">
                                            <div class="flex items-start justify-between mb-2">
                                                <div class="flex items-center space-x-2">
                                                    <span class="px-2 py-1 text-xs font-semibold rounded-full
                                                        {{ $movement->type === 'in' ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' : '' }}
                                                        {{ $movement->type === 'out' ? 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200' : '' }}
                                                        {{ $movement->type === 'adjustment' ? 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200' : '' }}
                                                        {{ $movement->type === 'transfer' ? 'bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-200' : '' }}
                                                        {{ $movement->type === 'damaged' ? 'bg-orange-100 text-orange-800 dark:bg-orange-900 dark:text-orange-200' : '' }}
                                                        {{ $movement->type === 'return' ? 'bg-indigo-100 text-indigo-800 dark:bg-indigo-900 dark:text-indigo-200' : '' }}">
                                                        {{ ucfirst($movement->type) }}
                                                    </span>
                                                    <span class="text-sm font-medium text-zinc-900 dark:text-zinc-100">
                                                        {{ number_format($movement->quantity, 2) }} {{ strtoupper($item->uom) }}
                                                    </span>
                                                </div>
                                                <span class="text-xs text-zinc-500 dark:text-zinc-400">
                                                    {{ $movement->movement_date->format('M d, Y H:i') }}
                                                </span>
                                            </div>

                                            <div class="flex items-center justify-between text-xs text-zinc-600 dark:text-zinc-400">
                                                <div>
                                                    <span class="font-medium">Before:</span> {{ number_format($movement->quantity_before, 2) }}
                                                    <span class="mx-2">→</span>
                                                    <span class="font-medium">After:</span> {{ number_format($movement->quantity_after, 2) }}
                                                </div>
                                                @if($movement->mover)
                                                    <div>
                                                        By: <span class="font-medium">{{ $movement->mover->name }}</span>
                                                    </div>
                                                @endif
                                            </div>

                                            @if($movement->notes)
                                                <div class="mt-2 text-xs text-zinc-600 dark:text-zinc-400 italic">
                                                    "{{ $movement->notes }}"
                                                </div>
                                            @endif
                                        </div>
                                    @endforeach
                                </div>
                            @else
                                <div class="text-center py-8 text-zinc-500 dark:text-zinc-400">
                                    <svg class="w-12 h-12 mx-auto mb-2 text-zinc-300 dark:text-zinc-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                    <p>No movement history found for the selected filters.</p>
                                </div>
                            @endif
                        </div>
                    @endif
                @endif
            </div>

            <!-- Footer -->
            <div class="px-6 py-4 border-t border-zinc-200 dark:border-zinc-700 flex items-center justify-end">
                <button wire:click="closeHistoryModal"
                    class="px-4 py-2 bg-zinc-200 hover:bg-zinc-300 dark:bg-zinc-700 dark:hover:bg-zinc-600 text-zinc-800 dark:text-zinc-200 rounded-lg font-medium transition-colors">
                    Close
                </button>
            </div>
        </div>
    </div>

    <!-- Stock Edit Modal (Slide-in) -->
    <div x-data="{ show: @entangle('showEditStockModal') }" x-show="show" x-cloak class="fixed inset-0 z-50 overflow-hidden"
        @keydown.escape.window="show = false">
        <!-- Backdrop -->
        <div x-show="show" x-transition:enter="transition-opacity ease-linear duration-300"
            x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
            x-transition:leave="transition-opacity ease-linear duration-300" x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0" class="fixed inset-0 bg-black bg-opacity-50"
            @click="$wire.closeEditStockModal()">
        </div>

        <!-- Slide-in Panel -->
        <div x-show="show" x-transition:enter="transform transition ease-in-out duration-300"
            x-transition:enter-start="translate-x-full" x-transition:enter-end="translate-x-0"
            x-transition:leave="transform transition ease-in-out duration-300"
            x-transition:leave-start="translate-x-0" x-transition:leave-end="translate-x-full"
            class="fixed inset-y-0 right-0 w-full md:w-1/2 lg:w-1/3 bg-white dark:bg-zinc-900 shadow-xl flex flex-col">

            <!-- Header -->
            <div class="px-6 py-4 border-b border-zinc-200 dark:border-zinc-700 flex items-center justify-between">
                <h2 class="text-xl font-bold text-zinc-900 dark:text-zinc-100">
                    <svg class="w-6 h-6 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                    </svg>
                    Edit Stock Levels
                </h2>
                <button wire:click="closeEditStockModal"
                    class="p-2 hover:bg-zinc-100 dark:hover:bg-zinc-800 rounded-lg transition-colors">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <!-- Scrollable Content -->
            <div
                class="flex-1 overflow-y-auto px-6 py-4 scrollbar-thin scrollbar-thumb-zinc-300 dark:scrollbar-thumb-zinc-700 scrollbar-track-transparent">

                @if($editStockId)
                    @php
                        $editStock = \App\Models\Stock::with(['item', 'branch'])->find($editStockId);
                    @endphp

                    @if($editStock)
                        <!-- Item Information Card -->
                        <div class="bg-gradient-to-br from-green-50 to-emerald-50 dark:from-green-900/20 dark:to-emerald-900/20 rounded-lg p-4 mb-6 border border-green-200 dark:border-green-800">
                            <h3 class="text-lg font-semibold text-zinc-900 dark:text-zinc-100 mb-3 flex items-center">
                                <svg class="w-5 h-5 mr-2 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                                </svg>
                                Stock Information
                            </h3>
                            <div class="grid grid-cols-2 gap-3 text-sm">
                                <div>
                                    <span class="text-zinc-600 dark:text-zinc-400">Item:</span>
                                    <p class="font-medium text-zinc-900 dark:text-zinc-100">{{ $editStock->item->name }}</p>
                                </div>
                                <div>
                                    <span class="text-zinc-600 dark:text-zinc-400">SKU:</span>
                                    <p class="font-mono text-zinc-900 dark:text-zinc-100">{{ $editStock->item->sku }}</p>
                                </div>
                                <div>
                                    <span class="text-zinc-600 dark:text-zinc-400">Branch:</span>
                                    <p class="text-zinc-900 dark:text-zinc-100">{{ $editStock->branch->name }}</p>
                                </div>
                                <div>
                                    <span class="text-zinc-600 dark:text-zinc-400">Category:</span>
                                    <p class="text-zinc-900 dark:text-zinc-100">{{ ucfirst(str_replace('_', ' ', $editStock->item->category)) }}</p>
                                </div>
                            </div>
                        </div>

                        <!-- Current Stock Overview -->
                        <div class="bg-zinc-50 dark:bg-zinc-800 rounded-lg p-4 mb-6 border border-zinc-200 dark:border-zinc-700">
                            <h4 class="text-sm font-semibold text-zinc-900 dark:text-zinc-100 mb-3">Current Stock Levels</h4>
                            <div class="grid grid-cols-3 gap-3">
                                <div class="text-center">
                                    <div class="text-xs text-zinc-600 dark:text-zinc-400 mb-1">Available</div>
                                    <div class="text-lg font-bold text-green-600 dark:text-green-400">
                                        {{ number_format($editStock->quantity_available, 2) }}
                                    </div>
                                </div>
                                <div class="text-center">
                                    <div class="text-xs text-zinc-600 dark:text-zinc-400 mb-1">Reserved</div>
                                    <div class="text-lg font-bold text-yellow-600 dark:text-yellow-400">
                                        {{ number_format($editStock->quantity_reserved, 2) }}
                                    </div>
                                </div>
                                <div class="text-center">
                                    <div class="text-xs text-zinc-600 dark:text-zinc-400 mb-1">Damaged</div>
                                    <div class="text-lg font-bold text-red-600 dark:text-red-400">
                                        {{ number_format($editStock->quantity_damaged, 2) }}
                                    </div>
                                </div>
                            </div>
                            <div class="mt-3 pt-3 border-t border-zinc-300 dark:border-zinc-600 text-center">
                                <div class="text-xs text-zinc-600 dark:text-zinc-400 mb-1">Total Stock</div>
                                <div class="text-xl font-bold text-blue-600 dark:text-blue-400">
                                    {{ number_format($editStock->total_quantity, 2) }} {{ strtoupper($editStock->item->uom) }}
                                </div>
                            </div>
                        </div>

                        <!-- Edit Form -->
                        <form wire:submit.prevent="saveStockEdit" class="space-y-4">
                            <!-- Stock Quantities -->
                            <div class="space-y-4">
                                <h4 class="text-sm font-semibold text-zinc-900 dark:text-zinc-100">Update Quantities</h4>

                                <div>
                                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">
                                        Available Quantity <span class="text-red-500">*</span>
                                    </label>
                                    <input type="number" step="0.01" wire:model="editQuantityAvailable"
                                        class="w-full px-4 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-zinc-100 focus:ring-2 focus:ring-green-500"
                                        placeholder="0.00" required>
                                    @error('editQuantityAvailable')
                                        <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                                    @enderror
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">
                                        Reserved Quantity <span class="text-red-500">*</span>
                                    </label>
                                    <input type="number" step="0.01" wire:model="editQuantityReserved"
                                        class="w-full px-4 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-zinc-100 focus:ring-2 focus:ring-green-500"
                                        placeholder="0.00" required>
                                    @error('editQuantityReserved')
                                        <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                                    @enderror
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">
                                        Damaged Quantity <span class="text-red-500">*</span>
                                    </label>
                                    <input type="number" step="0.01" wire:model="editQuantityDamaged"
                                        class="w-full px-4 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-zinc-100 focus:ring-2 focus:ring-green-500"
                                        placeholder="0.00" required>
                                    @error('editQuantityDamaged')
                                        <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>

                            <!-- Live Total Calculation -->
                            <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-4">
                                <div class="flex items-center justify-between">
                                    <span class="text-sm font-medium text-blue-900 dark:text-blue-100">New Total Stock:</span>
                                    <span class="text-lg font-bold text-blue-700 dark:text-blue-300" x-data x-text="
                                        (parseFloat($wire.editQuantityAvailable || 0) +
                                         parseFloat($wire.editQuantityReserved || 0) +
                                         parseFloat($wire.editQuantityDamaged || 0)).toFixed(2)
                                    "></span>
                                </div>
                            </div>

                            <!-- Health Status -->
                            <div>
                                <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">
                                    Health Status <span class="text-red-500">*</span>
                                </label>
                                <select wire:model="editHealthStatus"
                                    class="w-full px-4 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-zinc-100 focus:ring-2 focus:ring-green-500"
                                    required>
                                    <option value="">Select Health Status</option>
                                    <option value="good">Good</option>
                                    <option value="warning">Warning</option>
                                    <option value="critical">Critical</option>
                                    <option value="expired">Expired</option>
                                </select>
                                @error('editHealthStatus')
                                    <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                                @enderror
                            </div>

                            <!-- Expiry Date -->
                            <div>
                                <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">
                                    Expiry Date (Optional)
                                </label>
                                <input type="date" wire:model="editExpiryDate"
                                    class="w-full px-4 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-zinc-100 focus:ring-2 focus:ring-green-500">
                                @error('editExpiryDate')
                                    <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                                @enderror
                            </div>

                            <!-- Notes -->
                            <div>
                                <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">
                                    Notes (Optional)
                                </label>
                                <textarea wire:model="editNotes" rows="3"
                                    class="w-full px-4 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-zinc-100 focus:ring-2 focus:ring-green-500"
                                    placeholder="Add any notes about this stock adjustment..."></textarea>
                                <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">
                                    These notes will be recorded in the stock movement history for audit purposes.
                                </p>
                            </div>
                        </form>
                    @endif
                @endif
            </div>

            <!-- Footer Actions -->
            <div class="px-6 py-4 border-t border-zinc-200 dark:border-zinc-700 flex items-center justify-end space-x-3">
                <button wire:click="closeEditStockModal"
                    class="px-4 py-2 bg-zinc-200 hover:bg-zinc-300 dark:bg-zinc-700 dark:hover:bg-zinc-600 text-zinc-800 dark:text-zinc-200 rounded-lg font-medium transition-colors">
                    Cancel
                </button>
                <button wire:click="saveStockEdit"
                    class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg font-medium transition-colors flex items-center">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M5 13l4 4L19 7" />
                    </svg>
                    Save Changes
                </button>
            </div>
        </div>
    </div>

</div>
