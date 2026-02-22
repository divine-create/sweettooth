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
        title="Item Requests"
        :items="[
            ['label' => 'Dashboard', 'url' => branch_route('branch-dashboard.index')],
            ['label' => 'Inventory'],
            ['label' => 'Item Requests']
        ]"
        :compact="false"
        :with-icons="true"
    />

    <!-- Header with Action Buttons -->
    <div class="flex justify-end gap-2 mb-3">
        <button wire:click="exportCSV" 
            class="inline-flex items-center gap-2 px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg font-medium transition-all duration-200 hover:shadow-lg active:scale-95 shadow-sm">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2m0 0v-8m0 8H3m15 0h3"/>
            </svg>
            CSV
        </button>
        <button wire:click="exportCSV" 
            class="inline-flex items-center gap-2 px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg font-medium transition-all duration-200 hover:shadow-lg active:scale-95 shadow-sm">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
            </svg>
            CSV
        </button>
        <!-- New Request button removed: inventory/store users should not request from themselves -->
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
                    placeholder="Search by request number, requester name, or department..."
                    class="w-full px-4 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-800 dark:text-zinc-200 focus:ring-2 focus:ring-blue-500">
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
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
                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Status</label>
                    <select wire:model.live="filterStatus"
                        class="w-full px-4 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-800 dark:text-zinc-200 focus:ring-2 focus:ring-blue-500">
                        <option value="">All Status</option>
                        <option value="pending">Pending</option>
                        <option value="approved">Approved</option>
                        <option value="partially_dispatched">Partially Dispatched</option>
                        <option value="completed">Completed</option>
                        <option value="rejected">Rejected</option>
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
            ['index' => 'request_number', 'label' => 'Request #'],
            ['index' => 'department', 'label' => 'Department'],
            ['index' => 'requester', 'label' => 'Requested By'],
            ['index' => 'request_date', 'label' => 'Request Date'],
            ['index' => 'status', 'label' => 'Status'],
            ['index' => 'items_count', 'label' => 'Items'],
        ]"
        :rows="$requests"
        striped
        paginate
        persist
        :filter="['quantity' => 'table_quantity', 'search' => 'search']"
        :quantity="[2, 10, 25, 50, 100]">

        @interact('column_request_number', $row)
            <button wire:click="viewRequest({{ $row->id }})" class="font-mono text-blue-600 dark:text-blue-400 hover:underline">
                {{ $row->request_number }}
            </button>
        @endinteract

        @interact('column_department', $row)
            <span class="text-zinc-900 dark:text-zinc-100">
                {{ $row->department->name ?? 'N/A' }}
            </span>
        @endinteract

        @interact('column_requester', $row)
            <span class="text-zinc-900 dark:text-zinc-100">
                {{ $row->requester->name ?? 'N/A' }}
            </span>
        @endinteract

        @interact('column_request_date', $row)
            <span class="text-zinc-900 dark:text-zinc-100">
                {{ $row->request_date ? $row->request_date->format('Y-m-d') : 'N/A' }}
            </span>
        @endinteract

        @interact('column_status', $row)
            @php
                $statusColors = [
                    'pending' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200',
                    'approved' => 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200',
                    'partially_dispatched' => 'bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-200',
                    'completed' => 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200',
                    'rejected' => 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200',
                ];
            @endphp
            <span class="px-2 py-1 text-xs font-semibold rounded-full {{ $statusColors[$row->status] ?? '' }}">
                {{ ucfirst(str_replace('_', ' ', $row->status)) }}
            </span>
        @endinteract

        @interact('column_items_count', $row)
            <span class="text-zinc-600 dark:text-zinc-400">
                {{ $row->requestDetails->count() }}
            </span>
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
                <h2 class="text-xl font-bold text-zinc-900 dark:text-zinc-100">New Item Request</h2>
                <button wire:click="closeModal"
                    class="p-2 hover:bg-zinc-100 dark:hover:bg-zinc-800 rounded-lg transition-colors">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <div class="flex-1 overflow-y-auto px-6 py-4 scrollbar-thin">
                <form wire:submit.prevent="save" class="space-y-4">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2">Department *</label>
                            <select wire:model="department_id"
                                class="w-full px-4 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-800 text-zinc-900 dark:text-zinc-100 focus:ring-2 focus:ring-blue-500">
                                <option value="">Select Department</option>
                                @foreach($departments as $dept)
                                    <option value="{{ $dept->id }}">{{ $dept->name }}</option>
                                @endforeach
                            </select>
                            @error('department_id')
                                <span class="text-red-500 text-sm">{{ $message }}</span>
                            @enderror
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2">Request Date *</label>
                            <input type="date" wire:model="request_date"
                                class="w-full px-4 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-800 text-zinc-900 dark:text-zinc-100 focus:ring-2 focus:ring-blue-500">
                            @error('request_date')
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

                    <div>
                        <div class="flex justify-between items-center mb-2">
                            <h3 class="text-sm font-semibold text-zinc-800 dark:text-zinc-200">Request Items</h3>
                            <button type="button" wire:click="addRequestItem"
                                class="px-3 py-1.5 bg-green-600 hover:bg-green-700 text-white rounded-lg text-sm font-medium transition-colors">
                                Add Item
                            </button>
                        </div>
                        <div class="border border-zinc-200 dark:border-zinc-700 rounded-lg overflow-hidden">
                            <table class="w-full">
                                <thead class="bg-zinc-50 dark:bg-zinc-800">
                                    <tr>
                                        <th class="px-4 py-2 text-left text-xs font-medium text-zinc-700 dark:text-zinc-300">Item</th>
                                        <th class="px-4 py-2 text-left text-xs font-medium text-zinc-700 dark:text-zinc-300">Quantity</th>
                                        <th class="px-4 py-2 text-left text-xs font-medium text-zinc-700 dark:text-zinc-300">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($requestItems as $index => $item)
                                    <tr class="border-t border-zinc-200 dark:border-zinc-700">
                                        <td class="px-4 py-2">
                                            <select wire:model="requestItems.{{ $index }}.item_id"
                                                class="w-full px-2 py-1 text-sm border border-zinc-300 dark:border-zinc-600 rounded bg-white dark:bg-zinc-800 text-zinc-900 dark:text-zinc-100">
                                                <option value="">Select Item</option>
                                                @foreach($items as $availableItem)
                                                    <option value="{{ $availableItem->id }}">{{ $availableItem->name }}</option>
                                                @endforeach
                                            </select>
                                            @error("requestItems.{$index}.item_id")
                                                <span class="text-red-500 text-xs">{{ $message }}</span>
                                            @enderror
                                        </td>
                                        <td class="px-4 py-2">
                                            <input type="number" step="0.01" wire:model="requestItems.{{ $index }}.quantity_requested"
                                                class="w-full px-2 py-1 text-sm border border-zinc-300 dark:border-zinc-600 rounded bg-white dark:bg-zinc-800 text-zinc-900 dark:text-zinc-100">
                                            @error("requestItems.{$index}.quantity_requested")
                                                <span class="text-red-500 text-xs">{{ $message }}</span>
                                            @enderror
                                        </td>
                                        <td class="px-4 py-2">
                                            <button type="button" wire:click="removeRequestItem({{ $index }})"
                                                class="p-1 text-red-600 hover:text-red-800">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                                </svg>
                                            </button>
                                        </td>
                                    </tr>
                                    @empty
                                    <tr>
                                        <td colspan="3" class="px-4 py-8 text-center text-zinc-500">No items added. Click "Add Item" to begin.</td>
                                    </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
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
                    Create Request
                </button>
            </div>
        </div>
    </div>

    <!-- Detail Modal -->
    <div x-data="{ show: @entangle('showDetailModal') }" x-show="show" x-cloak class="fixed inset-0 z-50 overflow-hidden"
        @keydown.escape.window="show = false">
        <div x-show="show" x-transition:enter="transition-opacity ease-linear duration-300"
            x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
            x-transition:leave="transition-opacity ease-linear duration-300" x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0" class="fixed inset-0 bg-black bg-opacity-50"
            @click="$wire.closeDetailModal()">
        </div>

        <div x-show="show" x-transition:enter="transform transition ease-in-out duration-300"
            x-transition:enter-start="translate-x-full" x-transition:enter-end="translate-x-0"
            x-transition:leave="transform transition ease-in-out duration-300"
            x-transition:leave-start="translate-x-0" x-transition:leave-end="translate-x-full"
            class="fixed inset-y-0 right-0 w-full md:w-2/3 lg:w-1/2 bg-white dark:bg-zinc-900 shadow-xl flex flex-col">

            <div class="px-6 py-4 border-b border-zinc-200 dark:border-zinc-700 flex items-center justify-between">
                <h2 class="text-xl font-bold text-zinc-900 dark:text-zinc-100">Request Details</h2>
                <button wire:click="closeDetailModal"
                    class="p-2 hover:bg-zinc-100 dark:hover:bg-zinc-800 rounded-lg transition-colors">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            @if($selectedRequest)
            <div class="flex-1 overflow-y-auto px-6 py-4 scrollbar-thin space-y-6">
                <!-- Request Information -->
                <div class="bg-zinc-50 dark:bg-zinc-800 rounded-lg p-4">
                    <h3 class="text-lg font-semibold text-zinc-900 dark:text-zinc-100 mb-3">Request Information</h3>
                    <div class="grid grid-cols-2 gap-4 text-sm">
                        <div>
                            <p class="text-zinc-500 dark:text-zinc-400">Request Number</p>
                            <p class="font-mono font-semibold text-zinc-900 dark:text-zinc-100">{{ $selectedRequest->request_number }}</p>
                        </div>
                        <div>
                            <p class="text-zinc-500 dark:text-zinc-400">Status</p>
                            @php
                                $statusColors = [
                                    'pending' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200',
                                    'approved' => 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200',
                                    'partially_dispatched' => 'bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-200',
                                    'completed' => 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200',
                                    'cancelled' => 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200',
                                ];
                            @endphp
                            <span class="inline-block px-2 py-1 text-xs font-semibold rounded-full {{ $statusColors[$selectedRequest->status] ?? '' }}">
                                {{ ucfirst(str_replace('_', ' ', $selectedRequest->status)) }}
                            </span>
                        </div>
                        <div>
                            <p class="text-zinc-500 dark:text-zinc-400">Department</p>
                            <p class="font-semibold text-zinc-900 dark:text-zinc-100">{{ $selectedRequest->department->name ?? 'N/A' }}</p>
                        </div>
                        <div>
                            <p class="text-zinc-500 dark:text-zinc-400">Branch</p>
                            <p class="font-semibold text-zinc-900 dark:text-zinc-100">{{ $selectedRequest->branch->name ?? 'N/A' }}</p>
                        </div>
                        <div>
                            <p class="text-zinc-500 dark:text-zinc-400">Requested By</p>
                            <p class="font-semibold text-zinc-900 dark:text-zinc-100">{{ $selectedRequest->requester->name ?? 'N/A' }}</p>
                        </div>
                        <div>
                            <p class="text-zinc-500 dark:text-zinc-400">Request Date</p>
                            <p class="font-semibold text-zinc-900 dark:text-zinc-100">{{ $selectedRequest->request_date ? $selectedRequest->request_date->format('Y-m-d') : 'N/A' }}</p>
                        </div>
                        @if($selectedRequest->approved_by)
                        <div>
                            <p class="text-zinc-500 dark:text-zinc-400">Approved By</p>
                            <p class="font-semibold text-zinc-900 dark:text-zinc-100">{{ $selectedRequest->approver->name ?? 'N/A' }}</p>
                        </div>
                        <div>
                            <p class="text-zinc-500 dark:text-zinc-400">Approved At</p>
                            <p class="font-semibold text-zinc-900 dark:text-zinc-100">{{ $selectedRequest->approved_at ? $selectedRequest->approved_at->format('Y-m-d H:i') : 'N/A' }}</p>
                        </div>
                        @endif
                    </div>
                    @if($selectedRequest->notes)
                    <div class="mt-4">
                        <p class="text-zinc-500 dark:text-zinc-400">Notes</p>
                        <p class="text-zinc-900 dark:text-zinc-100 mt-1">{{ $selectedRequest->notes }}</p>
                    </div>
                    @endif
                </div>

                <!-- Request Items -->
                <div>
                    <h3 class="text-lg font-semibold text-zinc-900 dark:text-zinc-100 mb-3">Requested Items</h3>
                    <div class="border border-zinc-200 dark:border-zinc-700 rounded-lg overflow-hidden">
                        <table class="w-full">
                            <thead class="bg-zinc-50 dark:bg-zinc-800">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-zinc-700 dark:text-zinc-300">Item</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-zinc-700 dark:text-zinc-300">UOM</th>
                                    <th class="px-4 py-3 text-right text-xs font-medium text-zinc-700 dark:text-zinc-300">Requested</th>
                                    <th class="px-4 py-3 text-right text-xs font-medium text-zinc-700 dark:text-zinc-300">Approved</th>
                                    <th class="px-4 py-3 text-right text-xs font-medium text-zinc-700 dark:text-zinc-300">Dispatched</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($selectedRequest->requestDetails as $detail)
                                <tr class="border-t border-zinc-200 dark:border-zinc-700">
                                    <td class="px-4 py-3 text-sm text-zinc-900 dark:text-zinc-100">{{ $detail->item->name ?? 'N/A' }}</td>
                                    <td class="px-4 py-3 text-sm text-zinc-900 dark:text-zinc-100">{{ $detail->unitOfMeasure?->symbol ?? 'N/A' }}</td>
                                    <td class="px-4 py-3 text-sm text-right text-zinc-900 dark:text-zinc-100">{{ number_format($detail->quantity_requested, 2) }}</td>
                                    <td class="px-4 py-3 text-sm text-right text-zinc-900 dark:text-zinc-100">{{ number_format($detail->quantity_approved, 2) }}</td>
                                    <td class="px-4 py-3 text-sm text-right text-zinc-900 dark:text-zinc-100">{{ number_format($detail->quantity_dispatched, 2) }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            @endif

            <div class="px-6 py-4 border-t border-zinc-200 dark:border-zinc-700 flex items-center justify-end">
                <button wire:click="closeDetailModal"
                    class="px-4 py-2 bg-zinc-200 hover:bg-zinc-300 dark:bg-zinc-700 dark:hover:bg-zinc-600 text-zinc-800 dark:text-zinc-200 rounded-lg font-medium transition-colors">
                    Close
                </button>
            </div>
        </div>
    </div>

</div>
