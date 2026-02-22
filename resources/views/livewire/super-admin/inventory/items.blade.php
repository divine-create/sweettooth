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
        title="Items Management"
        :items="[
            ['label' => 'Dashboard', 'url' => route('dashboard')],
            ['label' => 'Inventory'],
            ['label' => 'Items Management']
        ]"
        :compact="false"
        :with-icons="true"
    />

    <!-- Low Stock Alert -->
    @php
        $lowStockQuery = App\Models\Item::query()
            ->with(['stocks', 'branch', 'unitOfMeasure'])
            ->where('status', 'active')
            ->where('reorder_level', '>', 0)
            ->whereHas('stocks', function($q) {
                $q->whereColumn('quantity_available', '<=', 'items.reorder_level');
            });

        // Apply branch filter if selected
        if ($filterBranch) {
            $lowStockQuery->where('branch_id', $filterBranch);
        }

        $lowStockItems = $lowStockQuery->get();
    @endphp

    @if($lowStockItems->count() > 0)
    <div class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg p-4">
        <div class="flex items-start">
            <div class="flex-shrink-0">
                <svg class="w-6 h-6 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                </svg>
            </div>
            <div class="ml-3 flex-1">
                <h3 class="text-sm font-semibold text-red-900 dark:text-red-100 mb-2">
                    Low Stock Alert - {{ $lowStockItems->count() }} Item(s) Below Reorder Level{{ $filterBranch ? ' in Selected Branch' : ' Across All Branches' }}
                </h3>
                <div class="space-y-1">
                    @foreach($lowStockItems as $item)
                        @php
                            $currentStock = $item->getCurrentStock($item->branch_id);
                        @endphp
                        <div class="flex justify-between items-center bg-white dark:bg-zinc-800 p-2 rounded">
                            <div>
                                <span class="font-medium text-zinc-900 dark:text-zinc-100">{{ $item->name }}</span>
                                <span class="text-sm text-zinc-600 dark:text-zinc-400 ml-2">({{ $item->sku }})</span>
                                @if(!$filterBranch)
                                    <span class="text-xs text-zinc-500 dark:text-zinc-400 ml-2">- {{ $item->branch->name }}</span>
                                @endif
                            </div>
                            <div class="text-right">
                                <span class="text-sm font-semibold text-red-600 dark:text-red-400">
                                    {{ number_format($currentStock, 2) }} {{ $item->unitOfMeasure?->symbol ?? 'N/A' }}
                                </span>
                                <span class="text-xs text-zinc-500 dark:text-zinc-400 ml-1">
                                    / {{ number_format($item->reorder_level, 2) }} {{ $item->unitOfMeasure?->symbol ?? 'N/A' }}
                                </span>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
    @endif

    <!-- Header with Add Button -->
    <div class="flex justify-between items-center">
        <button wire:click="openCreateModal"
            class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg font-medium transition-colors duration-200 flex items-center shadow-sm">
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
            </svg>
            Add New Item
        </button>
    </div>



    <!-- Bulk Actions Bar -->
    <div x-data="{ selectedIds: @entangle('selectedIds') }" x-show="selectedIds.length > 0" x-cloak
        class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-4">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div class="flex flex-col sm:flex-row sm:items-center gap-2 sm:gap-4">
                <span class="text-sm font-medium text-blue-900 dark:text-blue-100">
                    <span x-text="selectedIds.length"></span> item(s) selected
                </span>
                <button wire:click="toggleBulkMode"
                    class="text-sm text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-300 underline text-left">
                    Clear Selection
                </button>
            </div>
            <div class="flex flex-col sm:flex-row gap-2">
                <button wire:click="bulkDeleteItems"
                    class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-lg font-medium transition-colors duration-200 flex items-center justify-center">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                    </svg>
                    Delete Selected
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
            <!-- Advanced Search -->
            <div>
                <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Advanced Search</label>
                <input type="text" wire:model.live.debounce.300ms="search"
                    placeholder="Search by item name, SKU, branch name, or category..."
                    class="w-full px-4 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-800 dark:text-zinc-200 focus:ring-2 focus:ring-blue-500">
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
                <!-- Branch Filter -->
                <div>
                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Branch</label>
                    <select wire:model.live="filterBranch"
                        class="w-full px-4 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-800 dark:text-zinc-200 focus:ring-2 focus:ring-blue-500">
                        <option value="">All Branches</option>
                        @foreach($branches as $branch)
                            <option value="{{ $branch->id }}">{{ $branch->name }}</option>
                        @endforeach
                    </select>
                </div>

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
                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Stock Level</label>
                    <select wire:model.live="filterStockLevel"
                        class="w-full px-4 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-800 dark:text-zinc-200 focus:ring-2 focus:ring-blue-500">
                        <option value="">All Stock Levels</option>
                        <option value="low">Low Stock</option>
                        <option value="high">High Stock</option>
                        <option value="out_of_stock">Out of Stock</option>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Status</label>
                    <select wire:model.live="filterStatus"
                        class="w-full px-4 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-800 dark:text-zinc-200 focus:ring-2 focus:ring-blue-500">
                        <option value="">All Status</option>
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                    </select>
                </div>
            </div>

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
        @interact('column_sku', $row)
            <span class="font-mono text-zinc-900 dark:text-zinc-100">
                {{ $row->sku }}
            </span>
        @endinteract

        @interact('column_branch', $row)
            <span class="text-zinc-900 dark:text-zinc-100">
                {{ $row->branch ? $row->branch->name : 'N/A' }}
            </span>
        @endinteract

        @interact('column_category', $row)
            <span
                class="px-2 py-1 text-xs font-semibold rounded-full
                {{ $row->category === 'raw_material' ? 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200' : '' }}
                {{ $row->category === 'packaging' ? 'bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-200' : '' }}
                {{ $row->category === 'consumable' ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' : '' }}
                {{ $row->category === 'equipment' ? 'bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-200' : '' }}">
                {{ ucfirst(str_replace('_', ' ', $row->category)) }}
            </span>
        @endinteract

        @interact('column_uom', $row)
            <span class="text-zinc-600 dark:text-zinc-400">
                @php
                    $uom = $row->unitOfMeasure ?? \App\Models\UnitOfMeasure::find($row->uom_id);
                @endphp
                {{ $uom?->symbol ?? 'No UOM' }}
                <span class="text-xs text-zinc-400">(ID: {{ $row->uom_id }})</span>
            </span>
        @endinteract

        @interact('column_stock', $row)
            @php
                $currentStock = $row->getCurrentStock();
                $isBelowReorder = $row->isBelowReorderLevel();
                $stock = $row->stocks()->where('branch_id', $row->branch_id)->first();
            @endphp
            <div x-data="{ editing: false, quantity: {{ $currentStock }} }" class="flex items-center space-x-2">
                <div x-show="!editing" @click="editing = true"
                    class="cursor-pointer hover:bg-zinc-100 dark:hover:bg-zinc-700 px-2 py-1 rounded transition-colors">
                    <span class="font-medium {{ $isBelowReorder ? 'text-red-600 dark:text-red-400' : 'text-zinc-900 dark:text-zinc-100' }}">
                        {{ number_format($currentStock, 2) }}
                    </span>
                    <svg class="w-4 h-4 inline ml-1 text-zinc-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                    </svg>
                </div>
                <div x-show="editing" class="flex items-center space-x-1" x-cloak>
                    <input type="number" step="0.01" x-model="quantity"
                        @keydown.enter="$wire.updateStock({{ $row->id }}, quantity).then(() => editing = false)"
                        @keydown.escape="editing = false; quantity = {{ $currentStock }}"
                        class="w-24 px-2 py-1 text-sm border border-zinc-300 dark:border-zinc-600 rounded bg-white dark:bg-zinc-800 text-zinc-900 dark:text-zinc-100 focus:ring-2 focus:ring-blue-500">
                    <button @click="$wire.updateStock({{ $row->id }}, quantity).then(() => editing = false)"
                        class="p-1 text-green-600 hover:text-green-800 dark:text-green-400 dark:hover:text-green-300"
                        title="Save">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                        </svg>
                    </button>
                    <button @click="editing = false; quantity = {{ $currentStock }}"
                        class="p-1 text-red-600 hover:text-red-800 dark:text-red-400 dark:hover:text-red-300"
                        title="Cancel">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
            </div>
        @endinteract

        @interact('column_reorder_level', $row)
            <span class="text-zinc-600 dark:text-zinc-400">
                {{ $row->reorder_level ? number_format($row->reorder_level, 2) : 'N/A' }}
            </span>
        @endinteract

        @interact('column_status', $row)
            <span
                class="px-2 py-1 text-xs font-semibold rounded-full
                {{ $row->status === 'active' ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' : 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200' }}">
                {{ ucfirst($row->status) }}
            </span>
        @endinteract

        @interact('column_action', $row)
            <div class="flex items-center space-x-2">
                <button wire:click="openStockModal({{ $row->id }})"
                    class="p-2 text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300 hover:bg-blue-50 dark:hover:bg-blue-900/20 rounded-lg transition-colors"
                    title="Manage Stock">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                    </svg>
                </button>
                <button wire:click="openEditModal({{ $row->id }})"
                    class="p-2 text-yellow-600 hover:text-yellow-800 dark:text-yellow-400 dark:hover:text-yellow-300 hover:bg-yellow-50 dark:hover:bg-yellow-900/20 rounded-lg transition-colors"
                    title="Edit Item">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                    </svg>
                </button>
                <button wire:click="delete({{ $row->id }})"
                    class="p-2 text-red-600 hover:text-red-800 dark:text-red-400 dark:hover:text-red-300 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-lg transition-colors"
                    title="Delete Item">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                    </svg>
                </button>
            </div>
        @endinteract
    </x-table>

    <!-- Modals -->
    @include('livewire.super-admin.inventory.partials.item-modal')
    @include('livewire.super-admin.inventory.partials.stock-modal')

</div>
