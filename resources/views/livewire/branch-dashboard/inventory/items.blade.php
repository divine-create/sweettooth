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
        title="Items Management"
        :items="[
            ['label' => 'Dashboard', 'url' => branch_route('branch-dashboard.index')],
            ['label' => 'Inventory'],
            ['label' => 'Items Management']
        ]"
        :compact="false"
        :with-icons="true"
    />

    <!-- Low Stock Alerts -->
    @if($lowStockAlerts->isNotEmpty())
        <div x-data="{ open: true }" x-show="open" class="bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 rounded-lg p-4">
            <div class="flex items-start justify-between gap-2">
                <div class="flex items-start gap-2">
                    <svg class="w-5 h-5 text-amber-600 dark:text-amber-400 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                    </svg>
                    <div>
                        <p class="text-sm font-semibold text-amber-800 dark:text-amber-300">
                            {{ $lowStockAlerts->count() }} item(s) below reorder level
                        </p>
                        <div class="mt-2 flex flex-wrap gap-2">
                            @foreach($lowStockAlerts->take(8) as $alert)
                                @php
                                    $stock = $alert->stocks->first();
                                    $current = $stock?->quantity_available ?? 0;
                                @endphp
                                <span class="inline-flex items-center gap-1 px-2 py-1 bg-amber-100 dark:bg-amber-900/40 text-amber-800 dark:text-amber-300 text-xs rounded">
                                    <strong>{{ $alert->name }}</strong>
                                    — {{ number_format($current, 2) }} / {{ number_format($alert->reorder_level, 2) }} {{ $alert->unitOfMeasure?->symbol ?? '' }}
                                </span>
                            @endforeach
                            @if($lowStockAlerts->count() > 8)
                                <span class="text-xs text-amber-700 dark:text-amber-400 self-center">+{{ $lowStockAlerts->count() - 8 }} more</span>
                            @endif
                        </div>
                    </div>
                </div>
                <button @click="open = false" class="text-amber-500 hover:text-amber-700 dark:hover:text-amber-300 flex-shrink-0">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
        </div>
    @endif

    <!-- Header with Add Button -->
<button
    x-data="{ loadingAdd: false }"
    @click="loadingAdd = true; $wire.openCreateModal().finally(() => { loadingAdd = false })"
    :disabled="loadingAdd"
    class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg font-medium transition-all duration-200 flex items-center shadow-sm disabled:opacity-70 disabled:cursor-not-allowed">

    <!-- Plus icon (hidden when loading) -->
    <svg x-show="!loadingAdd"
         class="w-5 h-5 mr-2 transition-opacity"
         fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
    </svg>

    <!-- Spinner (shown only when loading) -->
    <svg x-show="loadingAdd"
         class="animate-spin w-5 h-5 mr-2"
         fill="none" viewBox="0 0 24 24">
        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
    </svg>

    <span>Add New Item</span>
</button>

    <!-- Export Buttons -->
    <div class="flex justify-end items-center space-x-2">
        <button wire:click="exportCSV"
            class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg font-medium transition-colors duration-200 flex items-center">
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
            </svg>
            Export CSV
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
                <button @click="selectedIds = []; $wire.$set('selectedIds', [])"
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
                    placeholder="Search by item name, SKU, or category..."
                    class="w-full px-4 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-800 dark:text-zinc-200 focus:ring-2 focus:ring-blue-500">
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
                <div>
                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Category</label>
                    <select wire:model.live="filterCategory"
                        class="w-full px-4 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-800 dark:text-zinc-200 focus:ring-2 focus:ring-blue-500">
                        <option value="">All Categories</option>
                        @foreach(\App\Models\ItemCategory::active()->forBranch(request()->query('b_id'))->orderBy('name')->get() as $cat)
                            <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                        @endforeach
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
    <x-table :$headers :$rows selectable wire:model="selectedIds" striped paginate persist
        :filter="['quantity' => 'quantity', 'search' => 'search']"
        :quantity="[10, 25, 50, 100]">

        @interact('column_sku', $row)
            <span class="font-mono text-zinc-900 dark:text-zinc-100">
                {{ $row->sku }}
            </span>
        @endinteract

        @interact('column_category', $row)
            <span class="px-2 py-1 text-xs font-semibold rounded-full
                {{ $row->category?->name === 'Raw Material' ? 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200' : '' }}
                {{ $row->category?->name === 'Packaging' ? 'bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-200' : '' }}
                {{ $row->category?->name === 'Consumable' ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' : '' }}
                {{ $row->category?->name === 'Equipment' ? 'bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-200' : '' }}">
                {{ $row->category?->name ?? 'N/A' }}
            </span>
        @endinteract

        @interact('column_uom', $row)
            <span class="text-zinc-600 dark:text-zinc-400">
                {{ $row->unitOfMeasure?->symbol ?? 'N/A' }}
            </span>
        @endinteract

        @interact('column_reorder_level', $row)
            <span class="text-zinc-600 dark:text-zinc-400">
                {{ $row->reorder_level ? number_format($row->reorder_level, 2) : 'N/A' }}
            </span>
        @endinteract

        @interact('column_status', $row)
            <span class="px-2 py-1 text-xs font-semibold rounded-full
                {{ $row->status === 'active' ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' : 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200' }}">
                {{ ucfirst($row->status) }}
            </span>
        @endinteract

        @interact('column_action', $row)
            <div class="flex items-center space-x-2" x-data="{ loadingEdit: false, loadingDelete: false }">
                <!-- Edit Button -->
                <button
                    @click="loadingEdit = true; $wire.openEditModal({{ $row->id }}).finally(() => { loadingEdit = false })"
                    :disabled="loadingEdit"
                    class="p-2 text-yellow-600 hover:text-yellow-800 dark:text-yellow-400 dark:hover:text-yellow-300 hover:bg-yellow-50 dark:hover:bg-yellow-900/20 rounded-lg transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                    title="Edit Item">
                    <svg x-show="!loadingEdit" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                    </svg>
                    <svg x-show="loadingEdit" class="w-5 h-5 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z" />
                    </svg>
                </button>

                <!-- Delete Button -->
                <button
                    @click="loadingDelete = true; $wire.delete({{ $row->id }}).finally(() => { loadingDelete = false })"
                    :disabled="loadingDelete"
                    class="p-2 text-red-600 hover:text-red-800 dark:text-red-400 dark:hover:text-red-300 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-lg transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                    title="Delete Item">
                    <svg x-show="!loadingDelete" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                    </svg>
                    <svg x-show="loadingDelete" class="w-5 h-5 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z" />
                    </svg>
                </button>
            </div>
        @endinteract
    </x-table>

    <!-- Modals -->
    @include('livewire.branch-dashboard.inventory.partials.item-modal')
    @include('livewire.branch-dashboard.inventory.partials.audit-modal')

</div>
