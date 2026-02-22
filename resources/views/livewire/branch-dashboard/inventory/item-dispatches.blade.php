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
        title="Item Dispatches"
        :items="[
            ['label' => 'Dashboard', 'url' => branch_route('branch-dashboard.index')],
            ['label' => 'Inventory'],
            ['label' => 'Item Dispatches']
        ]"
        :compact="false"
        :with-icons="true"
    />

    <!-- Flash Messages -->
    @if (session()->has('success'))
    <div class="bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-lg p-4 flex items-center justify-between">
        <div class="flex items-center gap-3">
            <svg class="w-5 h-5 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
            </svg>
            <p class="text-sm font-medium text-green-900 dark:text-green-100">{{ session('success') }}</p>
        </div>
    </div>
    @endif

    @if (session()->has('error'))
    <div class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg p-4 flex items-center justify-between">
        <div class="flex items-center gap-3">
            <svg class="w-5 h-5 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
            <p class="text-sm font-medium text-red-900 dark:text-red-100">{{ session('error') }}</p>
        </div>
    </div>
    @endif

    <!-- Pending Requests Accordion -->
    @if($pendingRequests->count() > 0)
    <div x-data="{ open: false }" class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg">
        <div class="flex justify-between items-center p-4 cursor-pointer" @click="open = !open">
            <div>
                <h3 class="text-sm font-semibold text-blue-900 dark:text-blue-100">
                    Pending Dispatch Requests ({{ $pendingRequests->count() }})
                </h3>
                <p class="text-xs text-blue-700 dark:text-blue-300 mt-1">
                    {{ $pendingRequests->count() }} {{ Str::plural('request', $pendingRequests->count()) }} awaiting dispatch
                </p>
            </div>
            <svg class="w-5 h-5 text-blue-900 dark:text-blue-100 transition-transform duration-200"
                :class="{ 'rotate-180': open }"
                fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
            </svg>
        </div>

        <div x-show="open" x-collapse class="px-4 pb-4" x-cloak>
            <div class="space-y-2 max-h-96 overflow-y-auto scrollbar-thin">
                @foreach($pendingRequests as $request)
                <div class="flex justify-between items-center bg-white dark:bg-zinc-800 p-3 rounded-lg shadow-sm">
                    <div class="flex-1">
                        <p class="font-medium text-zinc-900 dark:text-zinc-100">{{ $request->request_number }}</p>
                        <p class="text-sm text-zinc-600 dark:text-zinc-400">{{ $request->department->name ?? 'N/A' }}</p>
                        <p class="text-xs text-zinc-500 dark:text-zinc-500 mt-1">
                            {{ $request->created_at->diffForHumans() }}
                        </p>
                    </div>
                    <button wire:click="openDispatchModal('{{ $request->id }}')"
                        class="px-3 py-1.5 bg-blue-600 hover:bg-blue-700 text-white rounded-lg text-sm font-medium transition-colors flex items-center gap-1">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6" />
                        </svg>
                        Dispatch
                    </button>
                </div>
                @endforeach
            </div>
        </div>
    </div>
    @endif

    <!-- Header with Export Buttons -->
    <div class="flex justify-end gap-2 mb-3">
       <button wire:click="exportCSV" 
           class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg font-medium transition-colors duration-200 flex items-center shadow-sm">
           <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
               <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
           </svg>
           Export CSV
       </button>
       <button wire:click="exportCSV" 
           class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg font-medium transition-colors duration-200 flex items-center shadow-sm">
           <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
               <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
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
                    placeholder="Search by request number, item name/SKU, or dispatcher name..."
                    class="w-full px-4 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-800 dark:text-zinc-200 focus:ring-2 focus:ring-blue-500">
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
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

    <!-- Table -->
    <x-table
        :headers="[
            ['index' => 'request_number', 'label' => 'Request #'],
            ['index' => 'department', 'label' => 'Department'],
            ['index' => 'requested_by', 'label' => 'Requested By'],
            ['index' => 'items_count', 'label' => 'Items'],
            ['index' => 'shift', 'label' => 'Shift'],
            ['index' => 'status', 'label' => 'Status'],
            ['index' => 'created_at', 'label' => 'Date'],
            ['index' => 'actions', 'label' => 'Actions'],
        ]"
        :rows="$requests"
        striped
        paginate
        persist
        :filter="['quantity' => 'quantity', 'search' => 'search']"
        :quantity="[10, 25, 50, 100]">

        @interact('column_request_number', $row)
            <span class="font-mono font-semibold text-zinc-900 dark:text-zinc-100">
                {{ $row->request_number }}
            </span>
        @endinteract

        @interact('column_department', $row)
            <span class="text-zinc-900 dark:text-zinc-100">
                {{ $row->department->name ?? 'N/A' }}
            </span>
        @endinteract

        @interact('column_requested_by', $row)
            <span class="text-zinc-900 dark:text-zinc-100">
                {{ $row->requester->name ?? $row->requestedBy->name ?? 'N/A' }}
            </span>
        @endinteract

        @interact('column_items_count', $row)
            <span class="text-zinc-900 dark:text-zinc-100">
                {{ $row->requestDetails->count() }} {{ Str::plural('item', $row->requestDetails->count()) }}
            </span>
        @endinteract

        @interact('column_shift', $row)
            @php
                $shiftColors = [
                    'morning' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200',
                    'afternoon' => 'bg-orange-100 text-orange-800 dark:bg-orange-900 dark:text-orange-200',
                    'night' => 'bg-indigo-100 text-indigo-800 dark:bg-indigo-900 dark:text-indigo-200',
                ];
            @endphp
            <span class="px-2 py-1 text-xs font-semibold rounded-full {{ $shiftColors[$row->shift] ?? 'bg-zinc-100 text-zinc-800 dark:bg-zinc-700 dark:text-zinc-200' }}">
                {{ $row->shift ? ucfirst($row->shift) : 'N/A' }}
            </span>
        @endinteract

        @interact('column_status', $row)
            @php
                // Determine actual status based on quantities
                if ($row->status === 'cancelled') {
                    $actualStatus = 'cancelled';
                } else {
                    $totalRequested = 0;
                    $totalApproved = 0;
                    $totalDispatched = 0;

                    foreach($row->requestDetails as $detail) {
                        $totalRequested += $detail->quantity_requested;
                        $totalApproved += $detail->quantity_approved;
                        $totalDispatched += $detail->quantity_dispatched;
                    }

                    // Determine status based on quantities
                    if ($totalDispatched == 0 && $totalApproved == 0) {
                        $actualStatus = 'pending';
                    } elseif ($totalRequested == $totalApproved && $totalApproved == $totalDispatched) {
                        $actualStatus = 'completed';
                    } elseif ($totalDispatched > 0) {
                        $actualStatus = 'partially_dispatched';
                    } elseif ($totalApproved > 0 && $totalDispatched == 0) {
                        $actualStatus = 'approved';
                    } else {
                        $actualStatus = 'pending';
                    }
                }

                $statusConfig = [
                    'pending' => ['bg' => 'bg-blue-100 dark:bg-blue-900/20', 'text' => 'text-blue-800 dark:text-blue-200', 'label' => 'Pending'],
                    'approved' => ['bg' => 'bg-yellow-100 dark:bg-yellow-900/20', 'text' => 'text-yellow-800 dark:text-yellow-200', 'label' => 'Approved'],
                    'partially_dispatched' => ['bg' => 'bg-orange-100 dark:bg-orange-900/20', 'text' => 'text-orange-800 dark:text-orange-200', 'label' => 'Partial'],
                    'completed' => ['bg' => 'bg-green-100 dark:bg-green-900/20', 'text' => 'text-green-800 dark:text-green-200', 'label' => 'Completed'],
                    'cancelled' => ['bg' => 'bg-red-100 dark:bg-red-900/20', 'text' => 'text-red-800 dark:text-red-200', 'label' => 'Cancelled'],
                ];
                $config = $statusConfig[$actualStatus] ?? ['bg' => 'bg-zinc-100', 'text' => 'text-zinc-800', 'label' => ucfirst($actualStatus)];
            @endphp
            <span class="px-2 py-1 text-xs font-semibold rounded-full {{ $config['bg'] }} {{ $config['text'] }}">
                {{ $config['label'] }}
            </span>
        @endinteract

        @interact('column_created_at', $row)
            <div class="text-zinc-900 dark:text-zinc-100">
                {{ $row->created_at->format('Y-m-d') }}
            </div>
            <div class="text-xs text-zinc-500 dark:text-zinc-400">
                {{ $row->created_at->format('H:i') }}
            </div>
        @endinteract

        @interact('column_actions', $row)
            @php
                // Check if request is truly complete by comparing quantities
                $hasIncompleteItems = false;
                foreach($row->requestDetails as $detail) {
                    // Check if any item is not fully approved from requested
                    if ($detail->quantity_requested > $detail->quantity_approved) {
                        $hasIncompleteItems = true;
                        break;
                    }
                    // Check if any item is not fully dispatched from approved
                    if ($detail->quantity_approved > $detail->quantity_dispatched) {
                        $hasIncompleteItems = true;
                        break;
                    }
                }

                $isComplete = !$hasIncompleteItems && $row->status === 'completed';
            @endphp

            <div class="flex items-center gap-2">
                @if(!$isComplete)
                    <button wire:click="openDispatchModal('{{ $row->id }}')"
                        class="px-3 py-1.5 bg-blue-600 hover:bg-blue-700 text-white rounded text-xs font-medium transition-colors flex items-center gap-1">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                        </svg>
                        {{ in_array($row->status, ['partially_dispatched', 'approved']) ? 'Continue' : 'Process' }}
                    </button>
                @else
                    <button wire:click="openDispatchModal('{{ $row->id }}')"
                        class="px-3 py-1.5 bg-zinc-200 hover:bg-zinc-300 dark:bg-zinc-700 dark:hover:bg-zinc-600 text-zinc-800 dark:text-zinc-200 rounded text-xs font-medium transition-colors flex items-center gap-1">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                        </svg>
                        View
                    </button>
                @endif
            </div>
        @endinteract
    </x-table>

    <!-- Dispatch Modal -->
    <div x-data="{ show: @entangle('showDispatchModal') }" x-show="show" x-cloak class="fixed inset-0 z-50 overflow-hidden"
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
            class="fixed inset-y-0 right-0 w-full md:w-1/2 lg:w-1/2 bg-white dark:bg-zinc-900 shadow-xl flex flex-col">

            <div class="px-6 py-4 border-b border-zinc-200 dark:border-zinc-700 flex items-center justify-between">
                <h2 class="text-xl font-bold text-zinc-900 dark:text-zinc-100">Approve & Dispatch Items</h2>
                <button wire:click="closeModal"
                    class="p-2 hover:bg-zinc-100 dark:hover:bg-zinc-800 rounded-lg transition-colors">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <div class="flex-1 overflow-y-auto px-6 py-4 scrollbar-thin">
                <div class="space-y-4">
                    @if(!empty($modalError))
                        <div class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 text-red-700 dark:text-red-200 text-sm px-4 py-3 rounded">
                            {{ $modalError }}
                        </div>
                    @endif
                    @if(!empty($modalWarning))
                        <div class="bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 text-yellow-800 dark:text-yellow-200 text-sm px-4 py-3 rounded">
                            {{ $modalWarning }}
                        </div>
                    @endif
                    @if(!empty($modalSuccess))
                        <div class="bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 text-green-700 dark:text-green-200 text-sm px-4 py-3 rounded">
                            {{ $modalSuccess }}
                        </div>
                    @endif
                    @if(!empty($dispatchedItems))
                    <div class="border border-zinc-200 dark:border-zinc-700 rounded-lg overflow-hidden">
                        <div class="overflow-x-auto">
                            <table class="w-full text-sm">
                                <thead class="bg-zinc-50 dark:bg-zinc-800">
                                    <tr>
                                        <th class="px-3 py-2 text-left text-xs font-medium text-zinc-700 dark:text-zinc-300">Item</th>
                                        <th class="px-3 py-2 text-center text-xs font-medium text-zinc-700 dark:text-zinc-300">Stock</th>
                                        <th class="px-3 py-2 text-center text-xs font-medium text-zinc-700 dark:text-zinc-300">Requested</th>
                                        <th class="px-3 py-2 text-center text-xs font-medium text-zinc-700 dark:text-zinc-300">Approved</th>
                                        <th class="px-3 py-2 text-center text-xs font-medium text-zinc-700 dark:text-zinc-300">Dispatched</th>
                                        <th class="px-3 py-2 text-center text-xs font-medium text-zinc-700 dark:text-zinc-300">Status</th>
                                        <th class="px-3 py-2 text-center text-xs font-medium text-zinc-700 dark:text-zinc-300">Approve Qty</th>
                                        <th class="px-3 py-2 text-center text-xs font-medium text-zinc-700 dark:text-zinc-300">Dispatch Qty</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($dispatchedItems as $index => $item)
                                    <tr class="border-t border-zinc-200 dark:border-zinc-700
                                        {{ $item['is_fully_dispatched'] && $item['is_fully_approved'] ? 'bg-green-50 dark:bg-green-900/10' : '' }}
                                        {{ !empty($approvalBlockers[$item['detail_id']] ?? false) ? 'bg-red-50 dark:bg-red-900/20' : '' }}
                                        {{ !empty($dispatchBlockers[$item['detail_id']] ?? false) ? 'bg-red-50 dark:bg-red-900/20' : '' }}">
                                        <!-- Item Name -->
                                        <td class="px-3 py-3">
                                            <div class="font-medium text-zinc-900 dark:text-zinc-100">{{ $item['item_name'] }}</div>
                                            <div class="text-xs text-zinc-500 dark:text-zinc-400">{{ $item['uom'] }}</div>
                                        </td>

                                        <!-- Stock Available -->
                                        <td class="px-3 py-3 text-center">
                                            <div class="font-semibold {{ $item['has_sufficient_stock'] ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                                                {{ number_format($item['stock_available'], 2) }}
                                            </div>
                                            <div class="text-xs text-zinc-500">
                                                @if($item['has_sufficient_stock'])
                                                    Available
                                                @else
                                                    Low Stock!
                                                @endif
                                            </div>
                                        </td>

                                        <!-- Requested (Cannot be changed) -->
                                        <td class="px-3 py-3 text-center">
                                            <div class="font-semibold text-zinc-900 dark:text-zinc-100">
                                                {{ number_format($item['quantity_requested'], 2) }}
                                            </div>
                                            <div class="text-xs text-zinc-500">Total</div>
                                        </td>

                                        <!-- Approved -->
                                        <td class="px-3 py-3 text-center">
                                            <div class="text-zinc-900 dark:text-zinc-100">
                                                {{ number_format($item['quantity_approved'], 2) }}
                                            </div>
                                            @if($item['remaining_to_approve'] > 0)
                                            <div class="text-xs text-blue-600 dark:text-blue-400">
                                                {{ number_format($item['remaining_to_approve'], 2) }} left
                                            </div>
                                            @else
                                            <div class="text-xs text-green-600 dark:text-green-400">Fully approved</div>
                                            @endif
                                        </td>

                                        <!-- Dispatched -->
                                        <td class="px-3 py-3 text-center">
                                            <div class="text-zinc-900 dark:text-zinc-100">
                                                {{ number_format($item['quantity_dispatched'], 2) }}
                                            </div>
                                            @if($item['remaining_to_dispatch'] > 0)
                                            <div class="text-xs text-orange-600 dark:text-orange-400">
                                                {{ number_format($item['remaining_to_dispatch'], 2) }} left
                                            </div>
                                            @elseif($item['quantity_approved'] > 0)
                                            <div class="text-xs text-green-600 dark:text-green-400">Fully dispatched</div>
                                            @endif
                                        </td>

                                        <!-- Status -->
                                        <td class="px-3 py-3 text-center">
                                            @if($item['is_fully_approved'] && $item['is_fully_dispatched'])
                                                <span class="px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">
                                                    Complete
                                                </span>
                                            @elseif($item['is_partially_approved'] || $item['is_partially_dispatched'])
                                                <span class="px-2 py-1 text-xs font-semibold rounded-full bg-orange-100 text-orange-800 dark:bg-orange-900 dark:text-orange-200">
                                                    Partial
                                                </span>
                                            @elseif($item['quantity_approved'] == 0)
                                                <span class="px-2 py-1 text-xs font-semibold rounded-full bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200">
                                                    Pending
                                                </span>
                                            @else
                                                <span class="px-2 py-1 text-xs font-semibold rounded-full bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200">
                                                    Ready
                                                </span>
                                            @endif
                                        </td>

                                        <!-- Approve Quantity Input -->
                                        <td class="px-3 py-3 text-center">
                                            @if($item['remaining_to_approve'] > 0)
                                                <input type="number" min="0" max="{{ $item['remaining_to_approve'] }}"
                                                    wire:model="dispatchedItems.{{ $index }}.approve_quantity"
                                                    placeholder="0" step="1"
                                                    class="w-20 px-2 py-1 text-sm text-center border border-blue-300 dark:border-blue-600 rounded bg-white dark:bg-zinc-800 text-zinc-900 dark:text-zinc-100 focus:ring-2 focus:ring-blue-500">
                                                <div class="text-xs text-zinc-500 mt-1">of {{ number_format($item['remaining_to_approve'], 2) }}</div>
                                            @else
                                                <span class="text-xs text-zinc-400 dark:text-zinc-500">-</span>
                                            @endif
                                        </td>

                                        <!-- Dispatch Quantity Input -->
                                        <td class="px-3 py-3 text-center">
                                            @if($item['remaining_to_dispatch'] > 0)
                                                <input type="number" min="0" max="{{ $item['remaining_to_dispatch'] }}"
                                                    wire:model="dispatchedItems.{{ $index }}.dispatch_quantity"
                                                    placeholder="0" step="1"
                                                    class="w-20 px-2 py-1 text-sm text-center border border-green-300 dark:border-green-600 rounded bg-white dark:bg-zinc-800 text-zinc-900 dark:text-zinc-100 focus:ring-2 focus:ring-green-500">
                                                <div class="text-xs text-zinc-500 mt-1">of {{ number_format($item['remaining_to_dispatch'], 2) }}</div>
                                            @else
                                                <span class="text-xs text-zinc-400 dark:text-zinc-500">-</span>
                                            @endif
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <!-- Info & Legend -->
                         <div class="px-4 py-3 bg-zinc-50 dark:bg-zinc-800/50 border-t border-zinc-200 dark:border-zinc-700 space-y-2">
                             <div class="flex items-center justify-between">
                                 <div class="space-y-1">
                                     <div class="text-xs text-zinc-600 dark:text-zinc-400">
                                         <span class="font-semibold">Workflow:</span>
                                         1️⃣ Enter quantities to approve → 2️⃣ Click "Approve Items" → 3️⃣ Enter quantities to dispatch → 4️⃣ Click "Dispatch Items"
                                     </div>
                                     <div class="text-xs text-zinc-600 dark:text-zinc-400">
                                         <span class="font-semibold">Note:</span>
                                         <span class="text-green-600 dark:text-green-400">Green</span> = Sufficient stock |
                                         <span class="text-red-600 dark:text-red-400">Red</span> = Low/insufficient stock
                                     </div>
                                 </div>
                                 <div class="flex items-center gap-2 ml-4">
                                     <button wire:click="approveAll" type="button"
                                         class="px-3 py-1.5 bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-medium rounded-lg transition-colors whitespace-nowrap">
                                         ✓ Approve All
                                     </button>
                                     <button wire:click="dispatchAll" type="button"
                                         class="px-3 py-1.5 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-medium rounded-lg transition-colors whitespace-nowrap">
                                         ✓ Dispatch All
                                     </button>
                                 </div>
                             </div>
                         </div>
                    </div>
                    @endif
                </div>
            </div>

            <div class="px-6 py-4 border-t border-zinc-200 dark:border-zinc-700 bg-zinc-50 dark:bg-zinc-800 flex items-center justify-between gap-4">
                <button wire:click="closeModal" type="button"
                    class="px-4 py-2.5 bg-zinc-300 hover:bg-zinc-400 dark:bg-zinc-700 dark:hover:bg-zinc-600 text-zinc-800 dark:text-zinc-200 rounded-lg font-medium transition-colors">
                    Cancel
                </button>

                <div class="flex items-center gap-3">
                    <!-- Approve Button -->
                    <button wire:click="approveItems()" type="button"
                        wire:loading.attr="disabled"
                        wire:loading.class="opacity-50 cursor-not-allowed"
                        class="px-4 py-2.5 bg-blue-600 hover:bg-blue-700 dark:bg-blue-700 dark:hover:bg-blue-600 text-white rounded-lg font-medium transition-colors inline-flex items-center gap-2 whitespace-nowrap">
                        <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" wire:loading.remove wire:target="approveItems">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <span wire:loading.remove wire:target="approveItems">Approve Items</span>
                        <span wire:loading wire:target="approveItems" class="inline-flex items-center gap-2">
                            <svg class="animate-spin h-4 w-4 flex-shrink-0" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            Approving...
                        </span>
                    </button>

                    <!-- Dispatch Button -->
                    <button wire:click="dispatchItems()" type="button"
                        wire:loading.attr="disabled"
                        wire:loading.class="opacity-50 cursor-not-allowed"
                        class="px-4 py-2.5 bg-green-600 hover:bg-green-700 dark:bg-green-700 dark:hover:bg-green-600 text-white rounded-lg font-medium transition-colors inline-flex items-center gap-2 whitespace-nowrap">
                        <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" wire:loading.remove wire:target="dispatchItems">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6" />
                        </svg>
                        <span wire:loading.remove wire:target="dispatchItems">Dispatch Items</span>
                        <span wire:loading wire:target="dispatchItems" class="inline-flex items-center gap-2">
                            <svg class="animate-spin h-4 w-4 flex-shrink-0" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            Dispatching...
                        </span>
                    </button>
                </div>
            </div>
        </div>
    </div>

</div>
