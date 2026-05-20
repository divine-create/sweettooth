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
        title="Stock Management"
        :items="[
            ['label' => 'Dashboard', 'url' => branch_route('branch-dashboard.index')],
            ['label' => 'Inventory'],
            ['label' => 'Stock Management']
        ]"
        :compact="false"
        :with-icons="true"
    />

    <!-- Header with Action Buttons -->
    <div class="flex justify-end gap-2 mb-3">
       <button wire:click="exportCSV" 
           class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg font-medium transition-colors duration-200 flex items-center shadow-sm">
           <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
               <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
           </svg>
           Export CSV
       </button>
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
            <!-- Advanced Search -->
            <div>
                <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Advanced Search</label>
                <input type="text" wire:model.live.debounce.300ms="search"
                    placeholder="Search by item name or SKU..."
                    class="w-full px-4 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-800 dark:text-zinc-200 focus:ring-2 focus:ring-blue-500">
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
                <div>
                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Category</label>
                    <select wire:model.live="filterCategory"
                        class="w-full px-4 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-800 dark:text-zinc-200 focus:ring-2 focus:ring-blue-500">
                        <option value="">All Categories</option>
                        <option value="raw_material">Raw Material</option>
                        <option value="packaging">Packaging</option>
                        <option value="consumable">Consumable</option>
                        <option value="equipment">Equipment</option>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Stock Status</label>
                    <select wire:model.live="filterStatus"
                        class="w-full px-4 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-800 dark:text-zinc-200 focus:ring-2 focus:ring-blue-500">
                        <option value="">All</option>
                        <option value="low_stock">Low Stock</option>
                        <option value="overstock">Overstock</option>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Health Status</label>
                    <select wire:model.live="filterHealthStatus"
                        class="w-full px-4 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-800 dark:text-zinc-200 focus:ring-2 focus:ring-blue-500">
                        <option value="">All</option>
                        <option value="good">Good</option>
                        <option value="warning">Warning</option>
                        <option value="critical">Critical</option>
                        <option value="expired">Expired</option>
                    </select>
                </div>
            </div>

            <div class="flex justify-end pt-2.5 border-t border-zinc-200 dark:border-zinc-700">
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
    <x-table
        :headers="[
            ['index' => 'item_name', 'label' => 'Item Name'],
            ['index' => 'sku', 'label' => 'SKU'],
            ['index' => 'category', 'label' => 'Category'],
            ['index' => 'available_quantity', 'label' => 'Qty Available'],
            ['index' => 'reserved_quantity', 'label' => 'Reserved'],
            ['index' => 'total_quantity', 'label' => 'Total'],
            ['index' => 'uom', 'label' => 'UOM'],
            ['index' => 'last_stock_date', 'label' => 'Last Stock Date'],
            ['index' => 'action', 'label' => 'Actions'],
        ]"
        :rows="$stocks"
        striped
        paginate
        persist
        :filter="['quantity' => 'quantity', 'search' => 'search']"
        :quantity="[10, 25, 50, 100]">

        @interact('column_item_name', $row)
            <span class="font-medium text-zinc-900 dark:text-zinc-100">
                {{ $row->item->name ?? 'N/A' }}
            </span>
        @endinteract

        @interact('column_sku', $row)
            <span class="font-mono text-zinc-900 dark:text-zinc-100">
                {{ $row->item->sku ?? 'N/A' }}
            </span>
        @endinteract

        @interact('column_category', $row)
            @php
                $categoryColors = [
                    'raw_material' => 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200',
                    'packaging' => 'bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-200',
                    'consumable' => 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200',
                    'equipment' => 'bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-200',
                ];
            @endphp
            <span class="px-2 py-1 text-xs font-semibold rounded-full {{ $categoryColors[$row->item->category?->name ?? ''] ?? '' }}">
                {{ ucfirst(str_replace('_', ' ', $row->item->category?->name ?? 'N/A')) }}
            </span>
        @endinteract

        @interact('column_available_quantity', $row)
            @php
                $isLowStock = $row->item && $row->item->reorder_level && $row->available_quantity < $row->item->reorder_level;
            @endphp
            <span class="font-medium {{ $isLowStock ? 'text-red-600 dark:text-red-400' : 'text-zinc-900 dark:text-zinc-100' }}">
                {{ number_format($row->available_quantity, 2) }}
            </span>
        @endinteract

        @interact('column_reserved_quantity', $row)
            <span class="text-zinc-900 dark:text-zinc-100">
                {{ number_format($row->reserved_quantity, 2) }}
            </span>
        @endinteract

        @interact('column_total_quantity', $row)
            <span class="font-medium text-zinc-900 dark:text-zinc-100">
                {{ number_format($row->total_quantity, 2) }}
            </span>
        @endinteract

        @interact('column_uom', $row)
            <span class="text-zinc-600 dark:text-zinc-400 uppercase">
                {{ $row->item->uom ?? 'N/A' }}
            </span>
        @endinteract

        @interact('column_last_stock_date', $row)
            <span class="text-zinc-600 dark:text-zinc-400">
                {{ $row->last_stock_date ? $row->last_stock_date->format('Y-m-d') : 'N/A' }}
            </span>
        @endinteract

        @interact('column_action', $row)
            <div class="flex items-center space-x-2">
                <button x-data="{ loading: false }"
                    @click="loading = true; $wire.openEditModal({{ $row->id }}).finally(() => { loading = false })"
                    :disabled="loading"
                    class="p-2 text-yellow-600 hover:text-yellow-800 dark:text-yellow-400 dark:hover:text-yellow-300 hover:bg-yellow-50 dark:hover:bg-yellow-900/20 rounded-lg transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                    title="Edit Stock">
                    <svg x-show="!loading" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                    </svg>
                    <svg x-show="loading" class="w-5 h-5 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z" />
                    </svg>
                </button>
                <button wire:click="openBatchPanel({{ $row->id }})"
                    class="p-2 text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300 hover:bg-blue-50 dark:hover:bg-blue-900/20 rounded-lg transition-colors"
                    title="View Batches">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                    </svg>
                </button>
            </div>
        @endinteract
    </x-table>

    <!-- Modals -->
    @include('livewire.branch-dashboard.inventory.partials.stocks-edit-modal')
    @include('livewire.branch-dashboard.inventory.partials.stocks-audit-modal')
    @include('livewire.branch-dashboard.inventory.partials.stocks-batch-panel')

</div>
