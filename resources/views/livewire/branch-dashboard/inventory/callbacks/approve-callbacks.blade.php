<div class="p-3 space-y-3">
    <x-breadcrumb title="Inventory Callbacks" :items="[
        ['label' => 'Dashboard', 'url' => branch_route('branch-dashboard.index')],
        ['label' => 'Inventory'],
        ['label' => 'Callbacks'],
    ]" :compact="false" :with-icons="true" />

    <!-- Header -->
    <div class="bg-gradient-to-r from-green-600 to-green-700 rounded-lg p-4 text-white shadow-lg">
        <div class="flex justify-between items-center">
            <div>
                <h2 class="text-xl font-bold">Inventory Callbacks Management</h2>
                <p class="text-sm opacity-90 mt-1">
                    Review and approve callbacks from production departments
                </p>
            </div>
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="grid grid-cols-1 md:grid-cols-5 gap-3">
        <div class="bg-white dark:bg-zinc-800 rounded-lg p-4 shadow-sm border border-zinc-200 dark:border-zinc-700">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-zinc-600 dark:text-zinc-400">Total Callbacks</p>
                    <p class="text-2xl font-bold text-zinc-900 dark:text-zinc-100">{{ $stats['total'] }}</p>
                </div>
                <div class="p-3 bg-green-100 dark:bg-green-900/30 rounded-lg">
                    <svg class="w-6 h-6 text-green-600 dark:text-green-400" fill="none" stroke="currentColor"
                        viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                    </svg>
                </div>
            </div>
        </div>

        <div class="bg-white dark:bg-zinc-800 rounded-lg p-4 shadow-sm border border-zinc-200 dark:border-zinc-700">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-zinc-600 dark:text-zinc-400">Pending Approval</p>
                    <p class="text-2xl font-bold text-yellow-600 dark:text-yellow-400">{{ $stats['pending'] }}</p>
                </div>
                <div class="p-3 bg-yellow-100 dark:bg-yellow-900/30 rounded-lg">
                    <svg class="w-6 h-6 text-yellow-600 dark:text-yellow-400" fill="none" stroke="currentColor"
                        viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
            </div>
        </div>

        <div class="bg-white dark:bg-zinc-800 rounded-lg p-4 shadow-sm border border-zinc-200 dark:border-zinc-700">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-zinc-600 dark:text-zinc-400">Approved</p>
                    <p class="text-2xl font-bold text-blue-600 dark:text-blue-400">{{ $stats['approved'] }}</p>
                </div>
                <div class="p-3 bg-blue-100 dark:bg-blue-900/30 rounded-lg">
                    <svg class="w-6 h-6 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor"
                        viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
            </div>
        </div>

        <div class="bg-white dark:bg-zinc-800 rounded-lg p-4 shadow-sm border border-zinc-200 dark:border-zinc-700">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-zinc-600 dark:text-zinc-400">Completed</p>
                    <p class="text-2xl font-bold text-green-600 dark:text-green-400">{{ $stats['completed'] }}</p>
                </div>
                <div class="p-3 bg-green-100 dark:bg-green-900/30 rounded-lg">
                    <svg class="w-6 h-6 text-green-600 dark:text-green-400" fill="none" stroke="currentColor"
                        viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M5 13l4 4L19 7" />
                    </svg>
                </div>
            </div>
        </div>

        <div class="bg-white dark:bg-zinc-800 rounded-lg p-4 shadow-sm border border-zinc-200 dark:border-zinc-700">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-zinc-600 dark:text-zinc-400">Rejected</p>
                    <p class="text-2xl font-bold text-red-600 dark:text-red-400">{{ $stats['rejected'] }}</p>
                </div>
                <div class="p-3 bg-red-100 dark:bg-red-900/30 rounded-lg">
                    <svg class="w-6 h-6 text-red-600 dark:text-red-400" fill="none" stroke="currentColor"
                        viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters Section -->
    <div x-data="{ open: false }"
        class="bg-white dark:bg-zinc-800 rounded-lg shadow-sm border border-zinc-200 dark:border-zinc-700">
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
                class="flex items-center px-2.5 py-1 rounded text-xs font-medium bg-green-600 hover:bg-green-700 text-white transition-all duration-200">
                <svg class="w-3.5 h-3.5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path x-show="!open" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M4 8h16M4 16h16" />
                    <path x-show="open" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M6 18L18 6M6 6l12 12" />
                </svg>
                <span x-text="open ? 'Close' : 'Show Filters'"></span>
            </button>
        </div>

        <div x-show="open" x-collapse class="p-3 space-y-3">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-3">
                <div>
                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Search</label>
                    <input type="text" wire:model.live.debounce.300ms="search"
                        placeholder="Search by item or employee..."
                        class="w-full px-3 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-800 dark:text-zinc-200 focus:ring-2 focus:ring-green-500 text-sm">
                </div>

                <div>
                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Status</label>
                    <select wire:model.live="filterStatus"
                        class="w-full px-3 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-800 dark:text-zinc-200 focus:ring-2 focus:ring-green-500 text-sm">
                        <option value="">All Statuses</option>
                        @foreach ($statusOptions as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Source Type</label>
                    <select wire:model.live="filterSourceType"
                        class="w-full px-3 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-800 dark:text-zinc-200 focus:ring-2 focus:ring-green-500 text-sm">
                        <option value="">All Types</option>
                        @foreach ($sourceTypeOptions as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Start Date</label>
                    <input type="date" wire:model.live="startDate"
                        class="w-full px-3 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-800 dark:text-zinc-200 focus:ring-2 focus:ring-green-500 text-sm">
                </div>

                <div>
                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">End Date</label>
                    <input type="date" wire:model.live="endDate"
                        class="w-full px-3 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-800 dark:text-zinc-200 focus:ring-2 focus:ring-green-500 text-sm">
                </div>
            </div>
        </div>
    </div>

    <!-- Callbacks Table -->
    <x-table :$headers :$rows striped paginate persist collapsible
        :filter="['quantity' => 'quantity', 'search' => 'search', 'filterStatus' => 'filterStatus', 'filterSourceType' => 'filterSourceType', 'startDate' => 'startDate', 'endDate' => 'endDate']"
        :quantity="[10, 20, 50, 100]">

        @interact('column_callback_id', $row)
            <div class="text-center">
                <span class="font-mono text-sm text-zinc-600 dark:text-zinc-400">#{{ $row->id }}</span>
            </div>
        @endinteract

        @interact('column_source_type', $row)
            <div class="text-center">
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                    {{ $row->source_type === 'raw_material_from_stock' ? 'bg-purple-100 text-purple-800 dark:bg-purple-900/30 dark:text-purple-400' : 'bg-orange-100 text-orange-800 dark:bg-orange-900/30 dark:text-orange-400' }}">
                    {{ $row->formatted_source_type }}
                </span>
            </div>
        @endinteract

        @interact('column_item_product', $row)
            <div>
                @if($row->source_type === 'raw_material_from_stock' && $row->item)
                    <div class="font-medium text-zinc-900 dark:text-zinc-100">{{ $row->item->name }}</div>
                    <div class="text-xs text-zinc-500 dark:text-zinc-400">SKU: {{ $row->item->sku }}</div>
                @elseif($row->source_type === 'finished_product_reject' && $row->product)
                    <div class="font-medium text-zinc-900 dark:text-zinc-100">{{ $row->product->name }}</div>
                    <div class="text-xs text-zinc-500 dark:text-zinc-400">SKU: {{ $row->product->sku }}</div>
                @else
                    <span class="text-sm text-zinc-500">N/A</span>
                @endif
            </div>
        @endinteract

        @interact('column_quantity', $row)
            <div class="text-center">
                <span class="font-semibold text-orange-600 dark:text-orange-400">
                    {{ number_format($row->quantity, 2) }} {{ $row->uom }}
                </span>
            </div>
        @endinteract

        @interact('column_reason', $row)
            <div class="text-center">
                <span class="text-sm text-zinc-700 dark:text-zinc-300">
                    {{ $row->formatted_reason }}
                </span>
            </div>
        @endinteract

        @interact('column_status', $row)
            <div class="text-center">
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $this->getStatusBadgeClass($row->status) }}">
                    {{ $row->formatted_status }}
                </span>
            </div>
        @endinteract

        @interact('column_shift', $row)
            <div class="text-sm text-zinc-700 dark:text-zinc-300">
                @if($row->shift)
                    <div>{{ $row->shift->shift_type ?? 'N/A' }}</div>
                    <div class="text-xs text-zinc-500">{{ optional($row->shift->shift_date)->format('M d, Y') }}</div>
                @else
                    N/A
                @endif
            </div>
        @endinteract

        @interact('column_callback_time', $row)
            <div class="text-center text-sm text-zinc-600 dark:text-zinc-400">
                {{ optional($row->callback_time)->format('M d, Y H:i') }}
            </div>
        @endinteract

        @interact('column_action', $row)
            <div class="flex justify-center gap-2 flex-wrap">
                @if($row->status === 'pending')
                    <button wire:click="approveCallback({{ $row->id }})"
                        wire:confirm="Are you sure you want to approve this callback?"
                        class="px-3 py-1.5 bg-green-600 hover:bg-green-700 text-white rounded text-xs font-medium transition-colors">
                        Approve
                    </button>
                    <button wire:click="openRejectModal({{ $row->id }})"
                        class="px-3 py-1.5 bg-red-600 hover:bg-red-700 text-white rounded text-xs font-medium transition-colors">
                        Reject
                    </button>
                @elseif($row->status === 'approved_by_inventory')
                    <button wire:click="completeCallback({{ $row->id }})"
                        wire:confirm="Are you sure you want to complete this callback?"
                        class="px-3 py-1.5 bg-blue-600 hover:bg-blue-700 text-white rounded text-xs font-medium transition-colors">
                        Complete
                    </button>
                @endif

                <button wire:click="viewDetails({{ $row->id }})"
                    class="px-3 py-1.5 bg-zinc-600 hover:bg-zinc-700 text-white rounded text-xs font-medium transition-colors">
                    View Details
                </button>
            </div>
        @endinteract

    </x-table>

    <!-- Details Modal -->
    @if($showDetailsModal && $selectedCallback)
        <div x-data="{ show: @entangle('showDetailsModal') }" x-show="show" x-cloak
            class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                <!-- Background overlay -->
                <div x-show="show" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0"
                    x-transition:enter-end="opacity-100" x-transition:leave="ease-in duration-200"
                    x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
                    class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true"></div>

                <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

                <!-- Modal panel -->
                <div x-show="show" x-transition:enter="ease-out duration-300"
                    x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                    x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                    x-transition:leave="ease-in duration-200"
                    x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                    x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                    class="inline-block align-bottom bg-white dark:bg-zinc-800 rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-3xl sm:w-full">
                    <div class="bg-white dark:bg-zinc-800 px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                        <div class="flex justify-between items-start mb-4">
                            <h3 class="text-lg font-medium text-zinc-900 dark:text-zinc-100">
                                Callback Details #{{ $selectedCallback->id }}
                            </h3>
                            <button wire:click="closeDetailsModal" class="text-zinc-400 hover:text-zinc-500">
                                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>

                        <div class="space-y-4">
                            <!-- Source Type & Item Info -->
                            <div class="border-b border-zinc-200 dark:border-zinc-700 pb-3">
                                <h4 class="text-sm font-semibold text-zinc-700 dark:text-zinc-300 mb-2">Item Information</h4>
                                <div class="grid grid-cols-2 gap-3">
                                    <div>
                                        <p class="text-xs text-zinc-500 dark:text-zinc-400">Source Type</p>
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                            {{ $selectedCallback->source_type === 'raw_material_from_stock' ? 'bg-purple-100 text-purple-800 dark:bg-purple-900/30 dark:text-purple-400' : 'bg-orange-100 text-orange-800 dark:bg-orange-900/30 dark:text-orange-400' }}">
                                            {{ $selectedCallback->formatted_source_type }}
                                        </span>
                                    </div>
                                    @if($selectedCallback->source_type === 'raw_material_from_stock' && $selectedCallback->item)
                                        <div>
                                            <p class="text-xs text-zinc-500 dark:text-zinc-400">Item Name</p>
                                            <p class="text-sm font-medium text-zinc-900 dark:text-zinc-100">
                                                {{ $selectedCallback->item->name }}</p>
                                        </div>
                                        <div>
                                            <p class="text-xs text-zinc-500 dark:text-zinc-400">SKU</p>
                                            <p class="text-sm font-medium text-zinc-900 dark:text-zinc-100">
                                                {{ $selectedCallback->item->sku }}</p>
                                        </div>
                                    @elseif($selectedCallback->source_type === 'finished_product_reject' && $selectedCallback->product)
                                        <div>
                                            <p class="text-xs text-zinc-500 dark:text-zinc-400">Product Name</p>
                                            <p class="text-sm font-medium text-zinc-900 dark:text-zinc-100">
                                                {{ $selectedCallback->product->name }}</p>
                                        </div>
                                        <div>
                                            <p class="text-xs text-zinc-500 dark:text-zinc-400">SKU</p>
                                            <p class="text-sm font-medium text-zinc-900 dark:text-zinc-100">
                                                {{ $selectedCallback->product->sku }}</p>
                                        </div>
                                    @endif
                                    <div>
                                        <p class="text-xs text-zinc-500 dark:text-zinc-400">Quantity</p>
                                        <p class="text-sm font-medium text-orange-600 dark:text-orange-400">
                                            {{ number_format($selectedCallback->quantity, 2) }}
                                            {{ $selectedCallback->uom }}</p>
                                    </div>
                                    <div>
                                        <p class="text-xs text-zinc-500 dark:text-zinc-400">Reason</p>
                                        <p class="text-sm font-medium text-zinc-900 dark:text-zinc-100">
                                            {{ $selectedCallback->formatted_reason }}</p>
                                    </div>
                                </div>
                            </div>

                            <!-- Status & Tracking -->
                            <div class="border-b border-zinc-200 dark:border-zinc-700 pb-3">
                                <h4 class="text-sm font-semibold text-zinc-700 dark:text-zinc-300 mb-2">Status & Tracking</h4>
                                <div class="grid grid-cols-2 gap-3">
                                    <div>
                                        <p class="text-xs text-zinc-500 dark:text-zinc-400">Current Status</p>
                                        <span
                                            class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $this->getStatusBadgeClass($selectedCallback->status) }}">
                                            {{ $selectedCallback->formatted_status }}
                                        </span>
                                    </div>
                                    <div>
                                        <p class="text-xs text-zinc-500 dark:text-zinc-400">Callback Time</p>
                                        <p class="text-sm font-medium text-zinc-900 dark:text-zinc-100">
                                            {{ optional($selectedCallback->callback_time)->format('M d, Y H:i') }}</p>
                                    </div>
                                    <div>
                                        <p class="text-xs text-zinc-500 dark:text-zinc-400">Recorded By</p>
                                        <p class="text-sm font-medium text-zinc-900 dark:text-zinc-100">
                                            {{ $selectedCallback->recordedBy->name ?? 'N/A' }}</p>
                                    </div>
                                    @if($selectedCallback->approved_by)
                                        <div>
                                            <p class="text-xs text-zinc-500 dark:text-zinc-400">Approved By</p>
                                            <p class="text-sm font-medium text-zinc-900 dark:text-zinc-100">
                                                {{ $selectedCallback->approvedBy->name ?? 'N/A' }}</p>
                                            <p class="text-xs text-zinc-500 dark:text-zinc-400">
                                                {{ optional($selectedCallback->approved_at)->format('M d, Y H:i') }}</p>
                                        </div>
                                    @endif
                                </div>
                            </div>

                            <!-- Shift Info -->
                            @if($selectedCallback->shift)
                                <div class="border-b border-zinc-200 dark:border-zinc-700 pb-3">
                                    <h4 class="text-sm font-semibold text-zinc-700 dark:text-zinc-300 mb-2">Production Shift</h4>
                                    <div class="grid grid-cols-2 gap-3">
                                        <div>
                                            <p class="text-xs text-zinc-500 dark:text-zinc-400">Shift Type</p>
                                            <p class="text-sm font-medium text-zinc-900 dark:text-zinc-100">
                                                {{ $selectedCallback->shift->shift_type ?? 'N/A' }}</p>
                                        </div>
                                        <div>
                                            <p class="text-xs text-zinc-500 dark:text-zinc-400">Shift Date</p>
                                            <p class="text-sm font-medium text-zinc-900 dark:text-zinc-100">
                                                {{ optional($selectedCallback->shift->shift_date)->format('M d, Y') }}</p>
                                        </div>
                                    </div>
                                </div>
                            @endif

                            <!-- Notes -->
                            @if($selectedCallback->notes)
                                <div>
                                    <h4 class="text-sm font-semibold text-zinc-700 dark:text-zinc-300 mb-2">Notes</h4>
                                    <p class="text-sm text-zinc-600 dark:text-zinc-400">{{ $selectedCallback->notes }}
                                    </p>
                                </div>
                            @endif

                            <!-- Rejection Reason -->
                            @if($selectedCallback->status === 'rejected' && $selectedCallback->notes)
                                <div class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg p-3">
                                    <h4 class="text-sm font-semibold text-red-700 dark:text-red-300 mb-2">Rejection Reason</h4>
                                    <p class="text-sm text-red-600 dark:text-red-400">{{ $selectedCallback->notes }}</p>
                                </div>
                            @endif
                        </div>
                    </div>

                    <!-- Modal Actions -->
                    <div class="bg-zinc-50 dark:bg-zinc-900 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse gap-2">
                        @if($selectedCallback->status === 'pending')
                            <button wire:click="approveCallback({{ $selectedCallback->id }})"
                                wire:confirm="Are you sure you want to approve this callback?"
                                class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-green-600 text-base font-medium text-white hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 sm:ml-3 sm:w-auto sm:text-sm">
                                Approve Callback
                            </button>
                            <button wire:click="openRejectModal({{ $selectedCallback->id }})"
                                class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-red-600 text-base font-medium text-white hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 sm:ml-3 sm:w-auto sm:text-sm">
                                Reject Callback
                            </button>
                        @elseif($selectedCallback->status === 'approved_by_inventory')
                            <button wire:click="completeCallback({{ $selectedCallback->id }})"
                                wire:confirm="Are you sure you want to complete this callback?"
                                class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-blue-600 text-base font-medium text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:ml-3 sm:w-auto sm:text-sm">
                                Complete Callback
                            </button>
                        @endif
                        <button wire:click="closeDetailsModal" type="button"
                            class="mt-3 w-full inline-flex justify-center rounded-md border border-zinc-300 dark:border-zinc-600 shadow-sm px-4 py-2 bg-white dark:bg-zinc-700 text-base font-medium text-zinc-700 dark:text-zinc-300 hover:bg-zinc-50 dark:hover:bg-zinc-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                            Close
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <!-- Rejection Modal -->
    @if($showRejectModal)
        <div x-data="{ show: @entangle('showRejectModal') }" x-show="show" x-cloak
            class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                <!-- Background overlay -->
                <div x-show="show" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0"
                    x-transition:enter-end="opacity-100" x-transition:leave="ease-in duration-200"
                    x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
                    class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true"></div>

                <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

                <!-- Modal panel -->
                <div x-show="show" x-transition:enter="ease-out duration-300"
                    x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                    x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                    x-transition:leave="ease-in duration-200"
                    x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                    x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                    class="inline-block align-bottom bg-white dark:bg-zinc-800 rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                    <div class="bg-white dark:bg-zinc-800 px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                        <div class="flex items-start">
                            <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-red-100 dark:bg-red-900/30 sm:mx-0 sm:h-10 sm:w-10">
                                <svg class="h-6 w-6 text-red-600 dark:text-red-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </div>
                            <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left flex-1">
                                <h3 class="text-lg leading-6 font-medium text-zinc-900 dark:text-zinc-100" id="modal-title">
                                    Reject Callback
                                </h3>
                                <div class="mt-2">
                                    <p class="text-sm text-zinc-500 dark:text-zinc-400">
                                        Please provide a reason for rejecting this callback. This will be visible to the production department.
                                    </p>
                                </div>
                                <div class="mt-4">
                                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2">Rejection Reason</label>
                                    <textarea wire:model="rejectReason" rows="4"
                                        class="w-full px-3 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-800 dark:text-zinc-200 focus:ring-2 focus:ring-red-500 text-sm"
                                        placeholder="Enter the reason for rejection..."></textarea>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="bg-zinc-50 dark:bg-zinc-900 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse gap-2">
                        <button wire:click="rejectCallback" type="button"
                            class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-red-600 text-base font-medium text-white hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 sm:ml-3 sm:w-auto sm:text-sm">
                            Reject Callback
                        </button>
                        <button wire:click="closeRejectModal" type="button"
                            class="mt-3 w-full inline-flex justify-center rounded-md border border-zinc-300 dark:border-zinc-600 shadow-sm px-4 py-2 bg-white dark:bg-zinc-700 text-base font-medium text-zinc-700 dark:text-zinc-300 hover:bg-zinc-50 dark:hover:bg-zinc-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 sm:mt-0 sm:w-auto sm:text-sm">
                            Cancel
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
