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
    <div class="grid grid-cols-1 md:grid-cols-4 gap-3">
        <div class="bg-white dark:bg-zinc-800 rounded-lg p-4 shadow-sm border border-zinc-200 dark:border-zinc-700">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-zinc-600 dark:text-zinc-400">Total Returns</p>
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
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-3">
                <div>
                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Search</label>
                    <input type="text" wire:model.live.debounce.300ms="search"
                        placeholder="Search item or SKU..."
                        class="w-full px-3 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-800 dark:text-zinc-200 focus:ring-2 focus:ring-green-500 text-sm">
                </div>

                <div>
                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Status</label>
                    <select wire:model.live="filterStatus"
                        class="w-full px-3 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-800 dark:text-zinc-200 focus:ring-2 focus:ring-green-500 text-sm">
                        <option value="">All Statuses</option>
                        <option value="pending_approval">Pending Approval</option>
                        <option value="approved">Approved</option>
                        <option value="rejected">Rejected</option>
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
    @php
        $headers = [
            ['index' => 'date', 'label' => 'Date'],
            ['index' => 'dept', 'label' => 'Department'],
            ['index' => 'item', 'label' => 'Raw Material'],
            ['index' => 'qty', 'label' => 'Quantity'],
            ['index' => 'reason', 'label' => 'Reason'],
            ['index' => 'status', 'label' => 'Status'],
            ['index' => 'action', 'label' => 'Action'],
        ];
    @endphp
    <x-table :$headers :$rows striped paginate persist collapsible
        :filter="['quantity' => 'quantity', 'search' => 'search', 'filterStatus' => 'filterStatus', 'startDate' => 'startDate', 'endDate' => 'endDate']"
        :quantity="[10, 20, 50, 100]">

        @interact('column_date', $row)
            <div class="text-sm text-zinc-600 dark:text-zinc-400">
                {{ $row->created_at->format('M d, Y') }}
                <div class="text-xs">{{ $row->created_at->format('H:i') }}</div>
            </div>
        @endinteract

        @interact('column_dept', $row)
            <div class="text-sm font-medium text-zinc-900 dark:text-zinc-100">
                {{ $row->department->name }}
            </div>
        @endinteract

        @interact('column_item', $row)
            <div>
                <div class="font-medium text-zinc-900 dark:text-zinc-100">{{ $row->item->name }}</div>
                <div class="text-xs text-zinc-500 dark:text-zinc-400">SKU: {{ $row->item->sku }}</div>
            </div>
        @endinteract

        @interact('column_qty', $row)
            <div class="font-semibold text-orange-600 dark:text-orange-400">
                {{ number_format($row->quantity, 2) }} {{ $row->uom }}
            </div>
        @endinteract

        @interact('column_reason', $row)
            <div class="text-sm text-zinc-700 dark:text-zinc-300 capitalize">
                {{ str_replace('_', ' ', $row->reason) }}
            </div>
        @endinteract

        @interact('column_status', $row)
            @php
                $statusClass = match($row->status) {
                    'pending_approval' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400',
                    'approved' => 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400',
                    'rejected' => 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400',
                    default => 'bg-zinc-100 text-zinc-800 dark:bg-zinc-900/30 dark:text-zinc-400',
                };
            @endphp
            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $statusClass }}">
                {{ str_replace('_', ' ', ucfirst($row->status)) }}
            </span>
        @endinteract

        @interact('column_action', $row)
            <div class="flex justify-end gap-2">
                @if($row->status === 'pending_approval')
                    <button wire:click="approveReturn('{{ $row->id }}')"
                        wire:confirm="Are you sure you want to approve this return?"
                        class="px-3 py-1.5 bg-green-600 hover:bg-green-700 text-white rounded text-xs font-medium transition-colors">
                        Approve
                    </button>
                    <button wire:click="openRejectModal('{{ $row->id }}')"
                        class="px-3 py-1.5 bg-red-600 hover:bg-red-700 text-white rounded text-xs font-medium transition-colors">
                        Reject
                    </button>
                @endif

                <button wire:click="viewDetails('{{ $row->id }}')"
                    class="px-3 py-1.5 bg-zinc-600 hover:bg-zinc-700 text-white rounded text-xs font-medium transition-colors">
                    View
                </button>
            </div>
        @endinteract

    </x-table>

    <!-- Details Modal (Slide-in) -->
    <div x-data="{ show: @entangle('showDetailsModal') }" x-show="show" x-cloak class="fixed inset-0 z-50 overflow-hidden"
        @keydown.escape.window="show = false; $wire.closeDetailsModal()">
        <!-- Backdrop -->
        <div x-show="show" x-transition:enter="transition-opacity ease-linear duration-150"
            x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
            x-transition:leave="transition-opacity ease-linear duration-150" x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0" class="fixed inset-0 bg-black bg-opacity-50"
            @click="show = false; $wire.closeDetailsModal()">
        </div>

        <!-- Slide-in Panel -->
        <div x-show="show" x-transition:enter="transform transition ease-in-out duration-200"
            x-transition:enter-start="translate-x-full" x-transition:enter-end="translate-x-0"
            x-transition:leave="transform transition ease-in-out duration-200"
            x-transition:leave-start="translate-x-0" x-transition:leave-end="translate-x-full"
            class="fixed inset-y-0 right-0 w-full md:w-1/2 lg:w-1/2 bg-white dark:bg-zinc-900 shadow-xl flex flex-col">

            <!-- Header -->
            <div class="px-6 py-4 border-b border-zinc-200 dark:border-zinc-700 flex items-center justify-between">
                <h2 class="text-xl font-bold text-zinc-900 dark:text-zinc-100">Return Details</h2>
                <button @click="show = false; $wire.closeDetailsModal()"
                    class="p-2 hover:bg-zinc-100 dark:hover:bg-zinc-800 rounded-lg transition-colors duration-150">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <!-- Scrollable Content -->
            <div class="flex-1 overflow-y-auto px-6 py-4 scrollbar-thin scrollbar-thumb-zinc-300 dark:scrollbar-thumb-zinc-700 scrollbar-track-transparent">
                @if($selectedReturn)
                    <div class="space-y-6">
                        <!-- Item Information -->
                        <div>
                            <h4 class="text-xs font-bold text-zinc-500 uppercase tracking-wider mb-2">Item Information</h4>
                            <div class="bg-zinc-50 dark:bg-zinc-800/50 rounded-lg p-4 border border-zinc-200 dark:border-zinc-700">
                                <p class="text-base font-bold text-zinc-900 dark:text-zinc-100">{{ $selectedReturn->item->name }}</p>
                                <p class="text-sm text-zinc-500">SKU: {{ $selectedReturn->item->sku }}</p>
                                <div class="mt-3 flex items-center gap-4">
                                    <div>
                                        <p class="text-xs text-zinc-500 uppercase">Quantity</p>
                                        <p class="text-lg font-bold text-orange-600">
                                            {{ number_format($selectedReturn->quantity, 2) }} {{ $selectedReturn->uom }}
                                        </p>
                                    </div>
                                    <div>
                                        <p class="text-xs text-zinc-500 uppercase">Status</p>
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium 
                                            {{ $selectedReturn->status === 'pending_approval' ? 'bg-yellow-100 text-yellow-800' : ($selectedReturn->status === 'approved' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800') }}">
                                            {{ str_replace('_', ' ', ucfirst($selectedReturn->status)) }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Return Reason -->
                        <div>
                            <h4 class="text-xs font-bold text-zinc-500 uppercase tracking-wider mb-2">Return Details</h4>
                            <div class="bg-zinc-50 dark:bg-zinc-800/50 rounded-lg p-4 border border-zinc-200 dark:border-zinc-700">
                                <div class="space-y-3">
                                    <div>
                                        <p class="text-xs text-zinc-500 uppercase">Reason</p>
                                        <p class="text-sm font-medium capitalize">{{ str_replace('_', ' ', $selectedReturn->reason) }}</p>
                                    </div>
                                    @if($selectedReturn->notes)
                                        <div>
                                            <p class="text-xs text-zinc-500 uppercase">Staff Notes</p>
                                            <p class="text-sm italic text-zinc-700 dark:text-zinc-300">"{{ $selectedReturn->notes }}"</p>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>

                        <!-- Source Information -->
                        <div>
                            <h4 class="text-xs font-bold text-zinc-500 uppercase tracking-wider mb-2">Source & Traceability</h4>
                            <div class="bg-zinc-50 dark:bg-zinc-800/50 rounded-lg p-4 border border-zinc-200 dark:border-zinc-700">
                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <p class="text-xs text-zinc-500 uppercase">Department</p>
                                        <p class="text-sm font-medium">{{ $selectedReturn->department->name }}</p>
                                    </div>
                                    <div>
                                        <p class="text-xs text-zinc-500 uppercase">Shift</p>
                                        <p class="text-sm font-medium">#{{ $selectedReturn->shift->shift_number ?? 'N/A' }}</p>
                                    </div>
                                    <div>
                                        <p class="text-xs text-zinc-500 uppercase">Initiated By</p>
                                        <p class="text-sm font-medium">{{ $selectedReturn->returnedBy->name ?? 'System' }}</p>
                                    </div>
                                    <div>
                                        <p class="text-xs text-zinc-500 uppercase">Date</p>
                                        <p class="text-sm font-medium">{{ $selectedReturn->created_at->format('M d, Y H:i') }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Approval History -->
                        @if($selectedReturn->approved_at)
                            <div>
                                <h4 class="text-xs font-bold text-zinc-500 uppercase tracking-wider mb-2">Processing History</h4>
                                <div class="bg-zinc-50 dark:bg-zinc-800/50 rounded-lg p-4 border border-zinc-200 dark:border-zinc-700">
                                    <div class="flex items-center gap-4">
                                        <div class="h-10 w-10 rounded-full bg-zinc-200 dark:bg-zinc-700 flex items-center justify-center">
                                            <svg class="w-6 h-6 text-zinc-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                            </svg>
                                        </div>
                                        <div>
                                            <p class="text-sm font-bold text-zinc-900 dark:text-zinc-100">
                                                Processed by {{ $selectedReturn->approvedBy->name ?? 'N/A' }}
                                            </p>
                                            <p class="text-xs text-zinc-500">
                                                {{ $selectedReturn->approved_at->format('M d, Y @ H:i') }}
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endif
                    </div>
                @else
                    <div class="flex flex-col items-center justify-center h-64 text-zinc-400">
                        <svg class="animate-spin h-8 w-8 mb-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        <p>Loading details...</p>
                    </div>
                @endif
            </div>

            <!-- Footer -->
            <div class="px-6 py-4 border-t border-zinc-200 dark:border-zinc-700 flex items-center justify-end space-x-3">
                @if($selectedReturn && $selectedReturn->status === 'pending_approval')
                    <button wire:click="openRejectModal('{{ $selectedReturn->id }}')"
                        class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-lg font-medium transition-colors duration-150">
                        Reject
                    </button>
                    <button wire:click="approveReturn('{{ $selectedReturn->id }}')"
                        wire:confirm="Are you sure you want to approve this return?"
                        class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg font-medium transition-colors duration-150">
                        Approve Return
                    </button>
                @endif
                <button @click="show = false; $wire.closeDetailsModal()"
                    class="px-4 py-2 bg-zinc-200 hover:bg-zinc-300 dark:bg-zinc-700 dark:hover:bg-zinc-600 text-zinc-800 dark:text-zinc-200 rounded-lg font-medium transition-colors duration-150">
                    Close
                </button>
            </div>
        </div>
    </div>

    <!-- Reject Modal -->
    <div x-data="{ show: @entangle('showRejectModal') }" x-show="show" x-cloak class="fixed inset-0 z-[60] overflow-hidden"
        @keydown.escape.window="show = false; $wire.closeRejectModal()">
        <!-- Backdrop -->
        <div x-show="show" x-transition:enter="transition-opacity ease-linear duration-150"
            x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
            x-transition:leave="transition-opacity ease-linear duration-150" x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0" class="fixed inset-0 bg-black bg-opacity-50"
            @click="show = false; $wire.closeRejectModal()">
        </div>

        <!-- Modal Panel -->
        <div class="fixed inset-0 flex items-center justify-center p-4">
            <div x-show="show" x-transition:enter="transform transition ease-out duration-200"
                x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
                x-transition:leave="transform transition ease-in duration-150"
                x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-95"
                class="bg-white dark:bg-zinc-900 rounded-xl shadow-2xl max-w-md w-full overflow-hidden">
                
                <div class="px-6 py-4 border-b border-zinc-200 dark:border-zinc-700">
                    <h3 class="text-lg font-bold text-zinc-900 dark:text-zinc-100">Reject Return Request</h3>
                </div>

                <div class="p-6">
                    <p class="text-sm text-zinc-600 dark:text-zinc-400 mb-4">
                        Please provide a reason for rejecting this material return. This will be visible to the production department.
                    </p>
                    <textarea wire:model="rejectReason" rows="4" 
                        class="w-full px-4 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-800 text-zinc-900 dark:text-zinc-100 focus:ring-2 focus:ring-red-500 text-sm"
                        placeholder="Explain why this return is being rejected..."></textarea>
                    @error('rejectReason') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
                </div>

                <div class="px-6 py-4 border-t border-zinc-200 dark:border-zinc-700 bg-zinc-50 dark:bg-zinc-800/50 flex justify-end gap-3">
                    <button @click="show = false; $wire.closeRejectModal()"
                        class="px-4 py-2 text-sm font-medium text-zinc-700 dark:text-zinc-300 hover:text-zinc-900">
                        Cancel
                    </button>
                    <button wire:click="rejectReturn"
                        class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-lg text-sm font-bold transition-all shadow-sm">
                        Confirm Rejection
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
