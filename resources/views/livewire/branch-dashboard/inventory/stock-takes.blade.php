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
        title="Stock Takes"
        :items="[
            ['label' => 'Dashboard', 'url' => branch_route('branch-dashboard.index')],
            ['label' => 'Inventory'],
            ['label' => 'Stock Takes']
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
        <button wire:click="openCreateModal"
            class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg font-medium transition-colors duration-200 flex items-center shadow-sm">
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
            </svg>
            New Stock Take
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
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
                <div>
                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Type</label>
                    <select wire:model.live="filterType"
                        class="w-full px-4 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-800 dark:text-zinc-200 focus:ring-2 focus:ring-blue-500">
                        <option value="">All Types</option>
                        <option value="daily">Daily</option>
                        <option value="weekly">Weekly</option>
                        <option value="monthly">Monthly</option>
                        <option value="annual">Annual</option>
                        <option value="ad_hoc">Ad Hoc</option>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Status</label>
                    <select wire:model.live="filterStatus"
                        class="w-full px-4 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-800 dark:text-zinc-200 focus:ring-2 focus:ring-blue-500">
                        <option value="">All Status</option>
                        <option value="in_progress">In Progress</option>
                        <option value="completed">Completed</option>
                        <option value="verified">Verified</option>
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
            ['index' => 'stock_take_number', 'label' => 'Stock Take #'],
            ['index' => 'stock_take_date', 'label' => 'Date'],
            ['index' => 'type', 'label' => 'Type'],
            ['index' => 'status', 'label' => 'Status'],
            ['index' => 'conductor', 'label' => 'Conducted By'],
            ['index' => 'verifier', 'label' => 'Verified By'],
            ['index' => 'items_count', 'label' => 'Items'],
            ['index' => 'action', 'label' => 'Actions', 'display' => true],
        ]"
        :rows="$stockTakes"
        striped
        paginate
        persist
        :filter="['quantity' => 'quantity', 'search' => 'search']"
        :quantity="[10, 25, 50, 100]">

        @interact('column_stock_take_number', $row)
            <span class="font-mono text-zinc-900 dark:text-zinc-100">
                {{ $row->stock_take_number }}
            </span>
        @endinteract

        @interact('column_stock_take_date', $row)
            <span class="text-zinc-900 dark:text-zinc-100">
                {{ $row->stock_take_date ? $row->stock_take_date->format('Y-m-d') : 'N/A' }}
            </span>
        @endinteract

        @interact('column_type', $row)
            @php
                $typeColors = [
                    'daily' => 'bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-200',
                    'weekly' => 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200',
                    'monthly' => 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200',
                    'annual' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200',
                    'ad_hoc' => 'bg-pink-100 text-pink-800 dark:bg-pink-900 dark:text-pink-200',
                ];
            @endphp
            <span class="px-2 py-1 text-xs font-semibold rounded-full {{ $typeColors[$row->type] ?? '' }}">
                {{ ucfirst($row->type) }}
            </span>
        @endinteract

        @interact('column_status', $row)
            @php
                $statusColors = [
                    'in_progress' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200',
                    'completed' => 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200',
                    'verified' => 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200',
                ];
            @endphp
            <span class="px-2 py-1 text-xs font-semibold rounded-full {{ $statusColors[$row->status] ?? '' }}">
                {{ ucfirst(str_replace('_', ' ', $row->status)) }}
            </span>
        @endinteract

        @interact('column_conductor', $row)
            <span class="text-zinc-900 dark:text-zinc-100">
                {{ $row->conductor->name ?? 'N/A' }}
            </span>
        @endinteract

        @interact('column_verifier', $row)
            <span class="text-zinc-900 dark:text-zinc-100">
                {{ $row->verifier->name ?? 'N/A' }}
            </span>
        @endinteract

        @interact('column_items_count', $row)
            <span class="text-zinc-600 dark:text-zinc-400">
                {{ $row->stockTakeDetails->count() }}
            </span>
        @endinteract

        @interact('column_action', $row)
            <div class="flex items-center space-x-1">
                <button wire:click="viewStockTake({{ $row->id }})"
                    class="p-1.5 text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300 hover:bg-blue-50 dark:hover:bg-blue-900/20 rounded-lg transition-colors"
                    title="View Details">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                    </svg>
                </button>
                @if($row->status === 'in_progress')
                <button wire:click="completeStockTake({{ $row->id }})"
                    class="p-1.5 text-green-600 hover:text-green-800 dark:text-green-400 dark:hover:text-green-300 hover:bg-green-50 dark:hover:bg-green-900/20 rounded-lg transition-colors"
                    title="Mark Complete">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                    </svg>
                </button>
                @endif
            </div>
        @endinteract
    </x-table>

    <!-- Create Modal -->
    <div x-data="{ show: @entangle('showModal') }" x-show="show" x-cloak class="fixed inset-0 z-50 overflow-hidden"
        @keydown.escape.window="show = false">
        <div x-show="show" x-transition:enter="transition-opacity ease-linear duration-300"
            x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
            x-transition:leave="transition-opacity ease-linear duration-300" x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0" class="fixed inset-0 bg-black bg-opacity-50"
            @click="$wire.closeModal()">
        </div>

        <div x-show="show" x-transition:enter="transform transition ease-in-out duration-300"
            x-transition:enter-start="translate-x-full" x-transition:enter-end="translate-x-0"
            x-transition:leave="transform transition ease-in-out duration-300"
            x-transition:leave-start="translate-x-0" x-transition:leave-end="translate-x-full"
            class="fixed inset-y-0 right-0 w-full md:w-2/3 lg:w-1/2 bg-white dark:bg-zinc-900 shadow-xl flex flex-col">

            <div class="px-6 py-4 border-b border-zinc-200 dark:border-zinc-700 flex items-center justify-between">
                <h2 class="text-xl font-bold text-zinc-900 dark:text-zinc-100">New Stock Take</h2>
                <button wire:click="closeModal"
                    class="p-2 hover:bg-zinc-100 dark:hover:bg-zinc-800 rounded-lg transition-colors">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <div class="flex-1 overflow-y-auto px-6 py-4 scrollbar-thin">
                <form wire:submit.prevent="save" class="space-y-4">
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2">Stock Take Date *</label>
                            <input type="date" wire:model="stock_take_date"
                                class="w-full px-4 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-800 text-zinc-900 dark:text-zinc-100 focus:ring-2 focus:ring-blue-500">
                            @error('stock_take_date')
                                <span class="text-red-500 text-sm">{{ $message }}</span>
                            @enderror
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2">Type *</label>
                            <select wire:model="type"
                                class="w-full px-4 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-800 text-zinc-900 dark:text-zinc-100 focus:ring-2 focus:ring-blue-500">
                                <option value="daily">Daily</option>
                                <option value="weekly">Weekly</option>
                                <option value="monthly">Monthly</option>
                                <option value="annual">Annual</option>
                                <option value="ad_hoc">Ad Hoc</option>
                            </select>
                            @error('type')
                                <span class="text-red-500 text-sm">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2">Notes</label>
                        <textarea wire:model="notes" rows="3"
                            class="w-full px-4 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-800 text-zinc-900 dark:text-zinc-100 focus:ring-2 focus:ring-blue-500"
                            placeholder="Enter notes"></textarea>
                        @error('notes')
                            <span class="text-red-500 text-sm">{{ $message }}</span>
                        @enderror
                    </div>

                    @if(!empty($stockTakeItems))
                    <div>
                        <h3 class="text-sm font-semibold text-zinc-800 dark:text-zinc-200 mb-2">Stock Items</h3>
                        <div class="border border-zinc-200 dark:border-zinc-700 rounded-lg overflow-hidden">
                            <table class="w-full">
                                <thead class="bg-zinc-50 dark:bg-zinc-800">
                                    <tr>
                                        <th class="px-4 py-2 text-left text-xs font-medium text-zinc-700 dark:text-zinc-300">Item</th>
                                        <th class="px-4 py-2 text-left text-xs font-medium text-zinc-700 dark:text-zinc-300">System Qty</th>
                                        <th class="px-4 py-2 text-left text-xs font-medium text-zinc-700 dark:text-zinc-300">Physical Qty</th>
                                    </tr>
                                </thead>
                                <tbody class="max-h-96 overflow-y-auto">
                                    @foreach($stockTakeItems as $index => $item)
                                    <tr class="border-t border-zinc-200 dark:border-zinc-700">
                                        <td class="px-4 py-2 text-sm text-zinc-900 dark:text-zinc-100">{{ $item['item_name'] }}</td>
                                        <td class="px-4 py-2 text-sm text-zinc-600 dark:text-zinc-400">{{ number_format($item['system_quantity'], 2) }} {{ $item['uom'] }}</td>
                                        <td class="px-4 py-2">
                                            <input type="number" step="0.01" wire:model="stockTakeItems.{{ $index }}.physical_quantity"
                                                class="w-full px-2 py-1 text-sm border border-zinc-300 dark:border-zinc-600 rounded bg-white dark:bg-zinc-800 text-zinc-900 dark:text-zinc-100"
                                                placeholder="Enter physical qty">
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                    @endif
                </form>
            </div>

            <div class="px-6 py-4 border-t border-zinc-200 dark:border-zinc-700 flex items-center justify-end space-x-3">
                <button wire:click="closeModal"
                    class="px-4 py-2 bg-zinc-200 hover:bg-zinc-300 dark:bg-zinc-700 dark:hover:bg-zinc-600 text-zinc-800 dark:text-zinc-200 rounded-lg font-medium transition-colors">
                    Cancel
                </button>
                <button wire:click="save"
                    class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg font-medium transition-colors">
                    Create Stock Take
                </button>
            </div>
        </div>
    </div>

    <!-- View Details Modal -->
    <div x-data="{ show: @entangle('showViewModal') }" x-show="show" x-cloak class="fixed inset-0 z-50 overflow-hidden"
        @keydown.escape.window="show = false">
        <div x-show="show" x-transition:enter="transition-opacity ease-linear duration-300"
            x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
            x-transition:leave="transition-opacity ease-linear duration-300" x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0" class="fixed inset-0 bg-black bg-opacity-50"
            @click="$wire.closeViewModal()">
        </div>

        <div x-show="show" x-transition:enter="transform transition ease-in-out duration-300"
            x-transition:enter-start="translate-x-full" x-transition:enter-end="translate-x-0"
            x-transition:leave="transform transition ease-in-out duration-300"
            x-transition:leave-start="translate-x-0" x-transition:leave-end="translate-x-full"
            class="fixed inset-y-0 right-0 w-full md:w-2/3 lg:w-1/2 bg-white dark:bg-zinc-900 shadow-xl flex flex-col">

            @if($viewingStockTake)
            <div class="px-6 py-4 border-b border-zinc-200 dark:border-zinc-700 flex items-center justify-between">
                <div>
                    <h2 class="text-xl font-bold text-zinc-900 dark:text-zinc-100">Stock Take Details</h2>
                    <p class="text-sm text-zinc-500 dark:text-zinc-400 font-mono mt-0.5">{{ $viewingStockTake->stock_take_number }}</p>
                </div>
                <button wire:click="closeViewModal"
                    class="p-2 hover:bg-zinc-100 dark:hover:bg-zinc-800 rounded-lg transition-colors">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <div class="flex-1 overflow-y-auto px-6 py-4 scrollbar-thin space-y-5">
                <!-- Meta info -->
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <p class="text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wide mb-1">Date</p>
                        <p class="text-sm text-zinc-900 dark:text-zinc-100">{{ $viewingStockTake->stock_take_date?->format('Y-m-d') ?? 'N/A' }}</p>
                    </div>
                    <div>
                        <p class="text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wide mb-1">Type</p>
                        @php
                            $typeColors = [
                                'daily' => 'bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-200',
                                'weekly' => 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200',
                                'monthly' => 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200',
                                'annual' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200',
                                'ad_hoc' => 'bg-pink-100 text-pink-800 dark:bg-pink-900 dark:text-pink-200',
                            ];
                        @endphp
                        <span class="px-2 py-1 text-xs font-semibold rounded-full {{ $typeColors[$viewingStockTake->type] ?? '' }}">
                            {{ ucfirst($viewingStockTake->type) }}
                        </span>
                    </div>
                    <div>
                        <p class="text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wide mb-1">Status</p>
                        @php
                            $statusColors = [
                                'in_progress' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200',
                                'completed' => 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200',
                                'verified' => 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200',
                            ];
                        @endphp
                        <span class="px-2 py-1 text-xs font-semibold rounded-full {{ $statusColors[$viewingStockTake->status] ?? '' }}">
                            {{ ucfirst(str_replace('_', ' ', $viewingStockTake->status)) }}
                        </span>
                    </div>
                    <div>
                        <p class="text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wide mb-1">Conducted By</p>
                        <p class="text-sm text-zinc-900 dark:text-zinc-100">{{ $viewingStockTake->conductor?->name ?? 'N/A' }}</p>
                    </div>
                    @if($viewingStockTake->verifier)
                    <div>
                        <p class="text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wide mb-1">Verified By</p>
                        <p class="text-sm text-zinc-900 dark:text-zinc-100">{{ $viewingStockTake->verifier->name }}</p>
                    </div>
                    @endif
                    @if($viewingStockTake->notes)
                    <div class="col-span-2">
                        <p class="text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wide mb-1">Notes</p>
                        <p class="text-sm text-zinc-700 dark:text-zinc-300">{{ $viewingStockTake->notes }}</p>
                    </div>
                    @endif
                </div>

                <!-- Summary badges -->
                @php
                    $details = $viewingStockTake->stockTakeDetails;
                    $matches = $details->where('variance_type', 'match')->count();
                    $surpluses = $details->where('variance_type', 'surplus')->count();
                    $shortages = $details->where('variance_type', 'shortage')->count();
                @endphp
                <div class="flex gap-3">
                    <div class="flex-1 bg-green-50 dark:bg-green-900/20 rounded-lg p-3 text-center">
                        <p class="text-2xl font-bold text-green-700 dark:text-green-300">{{ $matches }}</p>
                        <p class="text-xs text-green-600 dark:text-green-400 mt-0.5">Matches</p>
                    </div>
                    <div class="flex-1 bg-blue-50 dark:bg-blue-900/20 rounded-lg p-3 text-center">
                        <p class="text-2xl font-bold text-blue-700 dark:text-blue-300">{{ $surpluses }}</p>
                        <p class="text-xs text-blue-600 dark:text-blue-400 mt-0.5">Surpluses</p>
                    </div>
                    <div class="flex-1 bg-red-50 dark:bg-red-900/20 rounded-lg p-3 text-center">
                        <p class="text-2xl font-bold text-red-700 dark:text-red-300">{{ $shortages }}</p>
                        <p class="text-xs text-red-600 dark:text-red-400 mt-0.5">Shortages</p>
                    </div>
                </div>

                <!-- Items table -->
                @if($details->count() > 0)
                <div>
                    <h3 class="text-sm font-semibold text-zinc-800 dark:text-zinc-200 mb-2">Item Details ({{ $details->count() }})</h3>
                    <div class="border border-zinc-200 dark:border-zinc-700 rounded-lg overflow-hidden">
                        <table class="w-full text-sm">
                            <thead class="bg-zinc-50 dark:bg-zinc-800">
                                <tr>
                                    <th class="px-3 py-2 text-left text-xs font-medium text-zinc-600 dark:text-zinc-400">Item</th>
                                    <th class="px-3 py-2 text-right text-xs font-medium text-zinc-600 dark:text-zinc-400">System</th>
                                    <th class="px-3 py-2 text-right text-xs font-medium text-zinc-600 dark:text-zinc-400">Physical</th>
                                    <th class="px-3 py-2 text-right text-xs font-medium text-zinc-600 dark:text-zinc-400">Variance</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                                @foreach($details as $detail)
                                @php
                                    $varianceClass = match($detail->variance_type) {
                                        'surplus' => 'text-blue-600 dark:text-blue-400',
                                        'shortage' => 'text-red-600 dark:text-red-400',
                                        default => 'text-green-600 dark:text-green-400',
                                    };
                                    $uom = $detail->stock?->item?->unitOfMeasure?->symbol ?? '';
                                @endphp
                                <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-800/50">
                                    <td class="px-3 py-2 text-zinc-900 dark:text-zinc-100">
                                        {{ $detail->stock?->item?->name ?? 'Unknown' }}
                                    </td>
                                    <td class="px-3 py-2 text-right text-zinc-600 dark:text-zinc-400">
                                        {{ number_format($detail->system_quantity, 2) }} {{ $uom }}
                                    </td>
                                    <td class="px-3 py-2 text-right text-zinc-900 dark:text-zinc-100">
                                        {{ number_format($detail->physical_quantity, 2) }} {{ $uom }}
                                    </td>
                                    <td class="px-3 py-2 text-right font-medium {{ $varianceClass }}">
                                        {{ $detail->variance_quantity > 0 ? '+' : '' }}{{ number_format($detail->variance_quantity, 2) }} {{ $uom }}
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
                @else
                <div class="text-center py-8 text-zinc-400 dark:text-zinc-500">
                    <svg class="w-10 h-10 mx-auto mb-2 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                    </svg>
                    <p class="text-sm">No items recorded for this stock take.</p>
                </div>
                @endif
            </div>

            <div class="px-6 py-4 border-t border-zinc-200 dark:border-zinc-700 flex items-center justify-end">
                <button wire:click="closeViewModal"
                    class="px-4 py-2 bg-zinc-200 hover:bg-zinc-300 dark:bg-zinc-700 dark:hover:bg-zinc-600 text-zinc-800 dark:text-zinc-200 rounded-lg font-medium transition-colors">
                    Close
                </button>
            </div>
            @endif
        </div>
    </div>

</div>
