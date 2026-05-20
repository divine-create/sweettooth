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
        title="Health Checks"
        :items="[
            ['label' => 'Dashboard', 'url' => branch_route('branch-dashboard.index')],
            ['label' => 'Inventory'],
            ['label' => 'Health Checks']
        ]"
        :compact="false"
        :with-icons="true"
    />

    <!-- Batch Expiry Alerts -->
    @if($expiredBatchesWithStock->isNotEmpty())
        <div class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg p-4">
            <h3 class="text-sm font-semibold text-red-800 dark:text-red-300 mb-2 flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/>
                </svg>
                Expired Batches with Remaining Stock ({{ $expiredBatchesWithStock->count() }})
            </h3>
            <div class="overflow-x-auto">
                <table class="w-full text-xs">
                    <thead>
                        <tr class="border-b border-red-200 dark:border-red-700">
                            <th class="text-left py-1.5 pr-3 font-semibold text-red-700 dark:text-red-300">Item</th>
                            <th class="text-left py-1.5 pr-3 font-semibold text-red-700 dark:text-red-300">Batch No.</th>
                            <th class="text-left py-1.5 pr-3 font-semibold text-red-700 dark:text-red-300">Expired</th>
                            <th class="text-right py-1.5 font-semibold text-red-700 dark:text-red-300">Qty Remaining</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-red-100 dark:divide-red-900/30">
                        @foreach($expiredBatchesWithStock as $batch)
                            <tr>
                                <td class="py-1.5 pr-3 text-red-900 dark:text-red-200">{{ $batch->stock?->item?->name ?? '—' }}</td>
                                <td class="py-1.5 pr-3 text-red-700 dark:text-red-300">{{ $batch->batch_number ?? '—' }}</td>
                                <td class="py-1.5 pr-3 text-red-700 dark:text-red-300">{{ $batch->expiry_date?->format('Y-m-d') }}</td>
                                <td class="py-1.5 text-right font-semibold text-red-900 dark:text-red-200">{{ number_format($batch->quantity_remaining, 2) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <p class="mt-2 text-xs text-red-600 dark:text-red-400">Action required: dispose, quarantine, or write off these batches.</p>
        </div>
    @endif

    @if($expiringBatches->isNotEmpty())
        <div class="bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 rounded-lg p-4">
            <h3 class="text-sm font-semibold text-amber-800 dark:text-amber-300 mb-2 flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                Batches Expiring Within {{ $expiryWarningDays }} Days ({{ $expiringBatches->count() }})
            </h3>
            <div class="overflow-x-auto">
                <table class="w-full text-xs">
                    <thead>
                        <tr class="border-b border-amber-200 dark:border-amber-700">
                            <th class="text-left py-1.5 pr-3 font-semibold text-amber-700 dark:text-amber-300">Item</th>
                            <th class="text-left py-1.5 pr-3 font-semibold text-amber-700 dark:text-amber-300">Batch No.</th>
                            <th class="text-left py-1.5 pr-3 font-semibold text-amber-700 dark:text-amber-300">Expires</th>
                            <th class="text-right py-1.5 pr-3 font-semibold text-amber-700 dark:text-amber-300">Days Left</th>
                            <th class="text-right py-1.5 font-semibold text-amber-700 dark:text-amber-300">Qty Remaining</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-amber-100 dark:divide-amber-900/30">
                        @foreach($expiringBatches as $batch)
                            <tr>
                                <td class="py-1.5 pr-3 text-amber-900 dark:text-amber-200">{{ $batch->stock?->item?->name ?? '—' }}</td>
                                <td class="py-1.5 pr-3 text-amber-700 dark:text-amber-300">{{ $batch->batch_number ?? '—' }}</td>
                                <td class="py-1.5 pr-3 text-amber-700 dark:text-amber-300">{{ $batch->expiry_date?->format('Y-m-d') }}</td>
                                <td class="py-1.5 pr-3 text-right font-semibold text-amber-900 dark:text-amber-200">{{ $batch->daysUntilExpiry() }}</td>
                                <td class="py-1.5 text-right text-amber-900 dark:text-amber-200">{{ number_format($batch->quantity_remaining, 2) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    <!-- Header with Action Buttons -->
    <div class="flex justify-end gap-2 mb-3">
        <button wire:click="exportCSV" 
            class="inline-flex items-center gap-2 px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg font-medium transition-all duration-200 hover:shadow-lg active:scale-95 shadow-sm">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
            </svg>
            Export CSV
        </button>
        <button wire:click="openCreateModal"
            class="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg font-medium transition-all duration-200 hover:shadow-lg active:scale-95 shadow-sm">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
            </svg>
            Record Health Check
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
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
                <div>
                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Condition</label>
                    <select wire:model.live="filterCondition"
                        class="w-full px-4 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-800 dark:text-zinc-200 focus:ring-2 focus:ring-blue-500">
                        <option value="">All Conditions</option>
                        <option value="good">Good</option>
                        <option value="fair">Fair</option>
                        <option value="poor">Poor</option>
                        <option value="damaged">Damaged</option>
                        <option value="expired">Expired</option>
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

                <div>
                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Action Taken</label>
                    <select wire:model.live="filterActionTaken"
                        class="w-full px-4 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-800 dark:text-zinc-200 focus:ring-2 focus:ring-blue-500">
                        <option value="">All</option>
                        <option value="1">Action Taken</option>
                        <option value="0">No Action</option>
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
            ['index' => 'check_date', 'label' => 'Check Date'],
            ['index' => 'item_name', 'label' => 'Item'],
            ['index' => 'condition', 'label' => 'Condition'],
            ['index' => 'quantity_affected', 'label' => 'Qty Affected'],
            ['index' => 'observations', 'label' => 'Observations'],
            ['index' => 'action_taken', 'label' => 'Action Taken'],
            ['index' => 'checker', 'label' => 'Checked By'],
            ['index' => 'actions', 'label' => 'Actions', 'display' => true],
        ]"
        :rows="$healthChecks"
        striped
        paginate
        persist
        :filter="['quantity' => 'quantity', 'search' => 'search']"
        :quantity="[10, 25, 50, 100]">

        @interact('column_check_date', $row)
            <span class="text-zinc-900 dark:text-zinc-100">
                {{ $row->check_date ? $row->check_date->format('Y-m-d') : 'N/A' }}
            </span>
        @endinteract

        @interact('column_item_name', $row)
            <span class="font-medium text-zinc-900 dark:text-zinc-100">
                {{ $row->stock->item?->name ?? 'N/A' }}
            </span>
        @endinteract

        @interact('column_condition', $row)
            @php
                $conditionColors = [
                    'good' => 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200',
                    'fair' => 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200',
                    'poor' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200',
                    'damaged' => 'bg-orange-100 text-orange-800 dark:bg-orange-900 dark:text-orange-200',
                    'expired' => 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200',
                ];
            @endphp
            <span class="px-2 py-1 text-xs font-semibold rounded-full {{ $conditionColors[$row->condition] ?? '' }}">
                {{ ucfirst($row->condition) }}
            </span>
        @endinteract

        @interact('column_quantity_affected', $row)
            <span class="text-zinc-900 dark:text-zinc-100">
                {{ $row->quantity_affected ? number_format($row->quantity_affected, 2) : 'N/A' }}
            </span>
        @endinteract

        @interact('column_observations', $row)
            <span class="text-zinc-600 dark:text-zinc-400 text-sm">
                {{ $row->observations ? Str::limit($row->observations, 50) : 'N/A' }}
            </span>
        @endinteract

        @interact('column_action_taken', $row)
            @if($row->action_taken)
            <span class="text-zinc-600 dark:text-zinc-400 text-sm">
                {{ Str::limit($row->action_taken, 50) }}
            </span>
            @else
            <span class="text-zinc-400 dark:text-zinc-500 italic">No action</span>
            @endif
        @endinteract

        @interact('column_checker', $row)
            <span class="text-zinc-900 dark:text-zinc-100">
                {{ $row->checker?->name ?? 'N/A' }}
            </span>
        @endinteract

        @interact('column_actions', $row)
            <div class="flex items-center space-x-1">
                <button wire:click="editHealthCheck({{ $row->id }})"
                    class="p-1.5 text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300 hover:bg-blue-50 dark:hover:bg-blue-900/20 rounded-lg transition-colors"
                    title="Edit">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                    </svg>
                </button>
                <button wire:click="deleteHealthCheck({{ $row->id }})"
                    wire:confirm="Are you sure you want to delete this health check? This action cannot be undone."
                    class="p-1.5 text-red-600 hover:text-red-800 dark:text-red-400 dark:hover:text-red-300 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-lg transition-colors"
                    title="Delete">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                    </svg>
                </button>
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
            class="fixed inset-y-0 right-0 w-full md:w-1/2 lg:w-1/3 bg-white dark:bg-zinc-900 shadow-xl flex flex-col">

            <div class="px-6 py-4 border-b border-zinc-200 dark:border-zinc-700 flex items-center justify-between">
                <h2 class="text-xl font-bold text-zinc-900 dark:text-zinc-100">{{ $isEditing ? 'Edit Health Check' : 'Record Health Check' }}</h2>
                <button wire:click="closeModal"
                    class="p-2 hover:bg-zinc-100 dark:hover:bg-zinc-800 rounded-lg transition-colors">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <div class="flex-1 overflow-y-auto px-6 py-4 scrollbar-thin">
                <form wire:submit.prevent="save" class="space-y-4">
                    <div>
                         <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2">Stock Item *</label>
                         <x-select.styled
                             wire:model.live="stock_id"
                             :options="$stocks"
                             select="label:label|value:value"
                             placeholder="Search and select stock item"
                             searchable
                         />
                         @error('stock_id')
                             <span class="text-red-500 text-sm">{{ $message }}</span>
                         @enderror
                     </div>

                    <div>
                        <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2">Check Date *</label>
                        <input type="date" wire:model="check_date"
                            class="w-full px-4 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-800 text-zinc-900 dark:text-zinc-100 focus:ring-2 focus:ring-blue-500">
                        @error('check_date')
                            <span class="text-red-500 text-sm">{{ $message }}</span>
                        @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2">Condition *</label>
                        <select wire:model="condition"
                            class="w-full px-4 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-800 text-zinc-900 dark:text-zinc-100 focus:ring-2 focus:ring-blue-500">
                            <option value="">Select Condition</option>
                            <option value="good">Good</option>
                            <option value="fair">Fair</option>
                            <option value="poor">Poor</option>
                            <option value="damaged">Damaged</option>
                            <option value="expired">Expired</option>
                        </select>
                        @error('condition')
                            <span class="text-red-500 text-sm">{{ $message }}</span>
                        @enderror
                    </div>

                    <div>
                         <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2">
                             Quantity Affected
                             @php
                                 $selectedStock = collect($stocks)->firstWhere('value', (string) $this->stock_id);
                             @endphp
                             @if($selectedStock)
                                 <span class="text-xs font-normal text-zinc-500 dark:text-zinc-400">(Max: {{ number_format($selectedStock['quantity_available'], 2) }} {{ $selectedStock['uom'] }})</span>
                             @endif
                         </label>
                         <input type="number" 
                             step="0.01" 
                             min="0.01"
                             @if($selectedStock) max="{{ $selectedStock['quantity_available'] }}" @endif
                             wire:model="quantity_affected"
                             class="w-full px-4 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-800 text-zinc-900 dark:text-zinc-100 focus:ring-2 focus:ring-blue-500"
                             placeholder="Enter quantity affected">
                         @error('quantity_affected')
                             <span class="text-red-500 text-sm">{{ $message }}</span>
                         @enderror
                     </div>

                    <div>
                        <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2">Observations</label>
                        <textarea wire:model="observations" rows="3"
                            class="w-full px-4 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-800 text-zinc-900 dark:text-zinc-100 focus:ring-2 focus:ring-blue-500"
                            placeholder="Enter observations"></textarea>
                        @error('observations')
                            <span class="text-red-500 text-sm">{{ $message }}</span>
                        @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2">Action Taken</label>
                        <textarea wire:model="action_taken" rows="3"
                            class="w-full px-4 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-800 text-zinc-900 dark:text-zinc-100 focus:ring-2 focus:ring-blue-500"
                            placeholder="Enter action taken"></textarea>
                        @error('action_taken')
                            <span class="text-red-500 text-sm">{{ $message }}</span>
                        @enderror
                    </div>
                </form>
            </div>

            <div class="px-6 py-4 border-t border-zinc-200 dark:border-zinc-700 flex items-center justify-end space-x-3">
                <button wire:click="closeModal"
                    class="px-4 py-2 bg-zinc-200 hover:bg-zinc-300 dark:bg-zinc-700 dark:hover:bg-zinc-600 text-zinc-800 dark:text-zinc-200 rounded-lg font-medium transition-colors">
                    Cancel
                </button>
                <button wire:click="save"
                    class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg font-medium transition-colors">
                    {{ $isEditing ? 'Update Health Check' : 'Save Health Check' }}
                </button>
            </div>
        </div>
    </div>

</div>
