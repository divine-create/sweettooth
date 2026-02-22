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
        title="Inventory Approvals"
        :items="[
            ['label' => 'Dashboard', 'url' => branch_route('branch-dashboard.index')],
            ['label' => 'Audit Management'],
            ['label' => 'Inventory Approvals']
        ]"
        :compact="false"
        :with-icons="true"
    />

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
            <!-- Search -->
            <div>
                <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Search</label>
                <input type="text" wire:model.live.debounce.300ms="search"
                    placeholder="Search by requester name or action..."
                    class="w-full px-4 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-800 dark:text-zinc-200 focus:ring-2 focus:ring-blue-500">
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
                <div>
                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Status</label>
                    <select wire:model.live="filterStatus"
                        class="w-full px-4 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-800 dark:text-zinc-200 focus:ring-2 focus:ring-blue-500">
                        <option value="">All Statuses</option>
                        <option value="pending">Pending</option>
                        <option value="approved">Approved</option>
                        <option value="rejected">Rejected</option>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Action Type</label>
                    <select wire:model.live="filterAction"
                        class="w-full px-4 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-800 dark:text-zinc-200 focus:ring-2 focus:ring-blue-500">
                        <option value="">All Actions</option>
                        <option value="stock_adjustment">Stock Adjustment</option>
                        <option value="create_item">Create Item</option>
                        <option value="update_item">Update Item</option>
                        <option value="delete_item">Delete Item</option>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Items Per Page</label>
                    <select wire:model.live="quantity"
                        class="w-full px-4 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-800 dark:text-zinc-200 focus:ring-2 focus:ring-blue-500">
                        <option value="10">10</option>
                        <option value="15">15</option>
                        <option value="25">25</option>
                        <option value="50">50</option>
                    </select>
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

    <!-- Empty State -->
    @if($requests->isEmpty())
        <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-sm border border-zinc-200 dark:border-zinc-700 p-12">
            <div class="text-center">
                <svg class="mx-auto h-12 w-12 text-zinc-400 dark:text-zinc-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" />
                </svg>
                <h3 class="mt-4 text-lg font-medium text-zinc-900 dark:text-zinc-100">No requests found</h3>
                <p class="mt-2 text-sm text-zinc-600 dark:text-zinc-400">
                    @if($filterStatus !== 'pending')
                        No {{ $filterStatus }} requests at this time.
                    @else
                        All pending approval requests have been processed.
                    @endif
                </p>
            </div>
        </div>
    @else
        <!-- Requests Table -->
        <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-sm border border-zinc-200 dark:border-zinc-700 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full divide-y divide-zinc-200 dark:divide-zinc-700">
                    <thead class="bg-zinc-50 dark:bg-zinc-900">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-zinc-700 dark:text-zinc-300 uppercase tracking-wider">Requester</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-zinc-700 dark:text-zinc-300 uppercase tracking-wider">Action</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-zinc-700 dark:text-zinc-300 uppercase tracking-wider">Description</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-zinc-700 dark:text-zinc-300 uppercase tracking-wider">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-zinc-700 dark:text-zinc-300 uppercase tracking-wider">Date</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-zinc-700 dark:text-zinc-300 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                        @foreach($requests as $request)
                            <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-700/50 transition-colors">
                                <td class="px-6 py-4 whitespace-nowrap text-sm">
                                    <div class="flex items-center">
                                        <div class="h-8 w-8 rounded-full bg-blue-100 dark:bg-blue-900 flex items-center justify-center">
                                            <span class="text-xs font-semibold text-blue-600 dark:text-blue-300">
                                                {{ strtoupper(substr($request->requester->first_name ?? 'U', 0, 1)) }}
                                            </span>
                                        </div>
                                        <div class="ml-3">
                                            <p class="font-medium text-zinc-900 dark:text-zinc-100">
                                                {{ $request->requester->first_name ?? 'Unknown' }} {{ $request->requester->last_name ?? '' }}
                                            </p>
                                            <p class="text-xs text-zinc-600 dark:text-zinc-400">
                                                {{ $request->requester?->email ?? 'N/A' }}
                                            </p>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm">
                                    @php
                                        $actionColors = [
                                            'stock_adjustment' => 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200',
                                            'create_item' => 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200',
                                            'update_item' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200',
                                            'delete_item' => 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200',
                                        ];
                                        $actionLabel = [
                                            'stock_adjustment' => 'Stock Adjustment',
                                            'create_item' => 'Create Item',
                                            'update_item' => 'Update Item',
                                            'delete_item' => 'Delete Item',
                                        ];
                                    @endphp
                                    <span class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full {{ $actionColors[$request->action] ?? '' }}">
                                        {{ $actionLabel[$request->action] ?? ucfirst(str_replace('_', ' ', $request->action)) }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-sm text-zinc-900 dark:text-zinc-100 max-w-xs truncate">
                                    {{ $request->description }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm">
                                    @php
                                        $statusColors = [
                                            'pending' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200',
                                            'approved' => 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200',
                                            'rejected' => 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200',
                                        ];
                                    @endphp
                                    <span class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full {{ $statusColors[$request->status] ?? '' }}">
                                        {{ ucfirst($request->status) }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-zinc-600 dark:text-zinc-400">
                                    <div>
                                        <p>{{ $request->created_at->format('M d, Y') }}</p>
                                        <p class="text-xs">{{ $request->created_at->format('H:i') }}</p>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm space-x-2">
                                    <button wire:click="openDetailsModal({{ $request->id }})"
                                        class="text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300 font-medium transition-colors">
                                        View Details
                                    </button>
                                    @if($request->status === 'pending')
                                        <button wire:click="approveRequest({{ $request->id }})" 
                                            wire:loading.attr="disabled"
                                            class="text-green-600 hover:text-green-800 dark:text-green-400 dark:hover:text-green-300 font-medium transition-colors">
                                            Approve
                                        </button>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div class="bg-white dark:bg-zinc-800 px-4 py-3 flex items-center justify-between border-t border-zinc-200 dark:border-zinc-700 sm:px-6">
                {{ $requests->links() }}
            </div>
        </div>
    @endif

    <!-- Details Modal -->
    @if($showDetailsModal && $selectedRequestId)
        @php
            $selectedRequest = \App\Models\ApprovalAuditRequest::find($selectedRequestId);
        @endphp
        @if($selectedRequest)
            <div x-data="{ show: @entangle('showDetailsModal').live, showReject: false }" x-show="show" x-cloak
                x-transition:enter="transition ease-out duration-200"
                x-transition:enter-start="opacity-0 scale-95"
                x-transition:enter-end="opacity-100 scale-100"
                x-transition:leave="transition ease-in duration-150"
                x-transition:leave-start="opacity-100 scale-100"
                x-transition:leave-end="opacity-0 scale-95"
                class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4"
                @keydown.escape.window="show = false; $wire.closeDetailsModal()">
                <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-xl max-w-2xl w-full max-h-96 overflow-y-auto">
                    <!-- Header -->
                    <div class="px-6 py-4 border-b border-zinc-200 dark:border-zinc-700 sticky top-0 bg-white dark:bg-zinc-800">
                        <div class="flex justify-between items-start">
                            <div>
                                <h3 class="text-lg font-semibold text-zinc-900 dark:text-zinc-100">Request Details</h3>
                                <p class="text-sm text-zinc-600 dark:text-zinc-400 mt-1">
                                    {{ $selectedRequest->created_at->format('F d, Y H:i') }}
                                </p>
                            </div>
                            <button @click="show = false; $wire.closeDetailsModal()"
                                class="text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-300">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>
                    </div>

                    <!-- Body -->
                    <div class="px-6 py-4 space-y-4">
                        <!-- Requester Info -->
                        <div>
                            <h4 class="text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2">Requester</h4>
                            <p class="text-sm text-zinc-900 dark:text-zinc-100">
                                {{ $selectedRequest->requester->first_name ?? 'Unknown' }} {{ $selectedRequest->requester->last_name ?? '' }}
                            </p>
                            <p class="text-xs text-zinc-600 dark:text-zinc-400">{{ $selectedRequest->requester?->email ?? 'N/A' }}</p>
                        </div>

                        <!-- Action & Status -->
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <h4 class="text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2">Action Type</h4>
                                <p class="text-sm text-zinc-900 dark:text-zinc-100">{{ ucfirst(str_replace('_', ' ', $selectedRequest->action)) }}</p>
                            </div>
                            <div>
                                <h4 class="text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2">Status</h4>
                                @php
                                    $statusColors = [
                                        'pending' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200',
                                        'approved' => 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200',
                                        'rejected' => 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200',
                                    ];
                                @endphp
                                <span class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full {{ $statusColors[$selectedRequest->status] ?? '' }}">
                                    {{ ucfirst($selectedRequest->status) }}
                                </span>
                            </div>
                        </div>

                        <!-- Description -->
                        <div>
                            <h4 class="text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2">Reason/Description</h4>
                            <p class="text-sm text-zinc-900 dark:text-zinc-100 bg-zinc-50 dark:bg-zinc-900 p-3 rounded-lg">
                                {{ $selectedRequest->description }}
                            </p>
                        </div>

                        <!-- Related Data to Delete (for delete_item requests) -->
                        @php
                            $relatedData = $selectedRequest->payload['related_data'] ?? null;
                        @endphp
                        @if(strpos($selectedRequest->action, 'delete_item') !== false && !empty($relatedData))
                            <div class="bg-red-50 dark:bg-red-900/30 border border-red-200 dark:border-red-800 rounded-lg p-4">
                                <div class="flex gap-2 mb-3">
                                    <svg class="w-5 h-5 text-red-600 dark:text-red-400 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                                    </svg>
                                    <h4 class="text-sm font-semibold text-red-900 dark:text-red-100">
                                        Related Data Will Be Deleted
                                    </h4>
                                </div>

                                <ul class="space-y-2 text-sm text-red-800 dark:text-red-200">
                                    @if(!empty($relatedData['recipes']))
                                        <li class="flex items-start gap-2">
                                            <svg class="w-4 h-4 text-red-600 dark:text-red-400 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                            </svg>
                                            <div>
                                                <p><strong>{{ $relatedData['recipes']['count'] }}</strong> Recipe(s)</p>
                                                @if(!empty($relatedData['recipes']['items']) && count($relatedData['recipes']['items']) <= 5)
                                                    <ul class="ml-4 mt-1 text-xs space-y-0.5">
                                                        @foreach($relatedData['recipes']['items'] as $recipe)
                                                            <li>• {{ $recipe['product_name'] }} ({{ $recipe['quantity'] }} {{ $recipe['uom'] }})</li>
                                                        @endforeach
                                                    </ul>
                                                @endif
                                            </div>
                                        </li>
                                    @endif

                                    @if(!empty($relatedData['products']))
                                        <li class="flex items-start gap-2">
                                            <svg class="w-4 h-4 text-orange-600 dark:text-orange-400 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                            </svg>
                                            <p><strong>{{ $relatedData['products']['count'] }}</strong> Product(s) affected: 
                                                {{ implode(', ', array_column($relatedData['products']['items'], 'name')) }}
                                            </p>
                                        </li>
                                    @endif

                                    @if(!empty($relatedData['stocks']))
                                        <li class="flex items-start gap-2">
                                            <svg class="w-4 h-4 text-red-600 dark:text-red-400 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                            </svg>
                                            <div>
                                                <p><strong>{{ $relatedData['stocks']['count'] }}</strong> Stock Record(s)</p>
                                                @if(!empty($relatedData['stocks']['items']))
                                                    <ul class="ml-4 mt-1 text-xs space-y-0.5">
                                                        @foreach($relatedData['stocks']['items'] as $stock)
                                                            <li>• {{ $stock['branch_name'] }}: {{ $stock['quantity_available'] }} available, {{ $stock['quantity_reserved'] }} reserved, {{ $stock['quantity_damaged'] }} damaged</li>
                                                        @endforeach
                                                    </ul>
                                                @endif
                                            </div>
                                        </li>
                                    @endif

                                    @if(!empty($relatedData['purchases']))
                                        <li class="flex items-start gap-2">
                                            <svg class="w-4 h-4 text-red-600 dark:text-red-400 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                            </svg>
                                            <div>
                                                <p><strong>{{ $relatedData['purchases']['count'] }}</strong> Purchase Order(s)</p>
                                                @if(!empty($relatedData['purchases']['items']) && count($relatedData['purchases']['items']) <= 5)
                                                    <ul class="ml-4 mt-1 text-xs space-y-0.5">
                                                        @foreach($relatedData['purchases']['items'] as $purchase)
                                                            <li>• PO {{ $purchase['purchase_number'] }}: {{ $purchase['quantity'] }} units ({{ $purchase['status'] }})</li>
                                                        @endforeach
                                                    </ul>
                                                @endif
                                            </div>
                                        </li>
                                    @endif

                                    @if(!empty($relatedData['stock_movements']))
                                        <li class="flex items-start gap-2">
                                            <svg class="w-4 h-4 text-red-600 dark:text-red-400 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                            </svg>
                                            <p><strong>{{ $relatedData['stock_movements']['count'] }}</strong> Stock Movement Record(s)</p>
                                        </li>
                                    @endif
                                </ul>
                            </div>
                        @endif

                        <!-- Payload -->
                        <div>
                            <h4 class="text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2">Request Data</h4>
                            <div class="bg-zinc-50 dark:bg-zinc-900 p-3 rounded-lg text-xs font-mono text-zinc-900 dark:text-zinc-100 overflow-x-auto max-h-40 overflow-y-auto">
                                <pre>{{ json_encode($selectedRequest->payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
                            </div>
                        </div>

                        <!-- Approval Info (if approved/rejected) -->
                        @if($selectedRequest->status !== 'pending')
                            <div class="border-t border-zinc-200 dark:border-zinc-700 pt-4">
                                <h4 class="text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2">
                                    {{ $selectedRequest->status === 'approved' ? 'Approved By' : 'Rejected By' }}
                                </h4>
                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <p class="text-xs text-zinc-600 dark:text-zinc-400">Name</p>
                                        <p class="text-sm font-medium text-zinc-900 dark:text-zinc-100">
                                            {{ $selectedRequest->approver?->first_name ?? 'N/A' }} {{ $selectedRequest->approver?->last_name ?? '' }}
                                        </p>
                                    </div>
                                    <div>
                                        <p class="text-xs text-zinc-600 dark:text-zinc-400">Date</p>
                                        <p class="text-sm text-zinc-900 dark:text-zinc-100">
                                            {{ $selectedRequest->approved_at?->format('F d, Y H:i') ?? 'N/A' }}
                                        </p>
                                    </div>
                                </div>
                                @if($selectedRequest->status === 'rejected' && $selectedRequest->comment)
                                    <div class="mt-3">
                                        <p class="text-xs text-zinc-600 dark:text-zinc-400">Rejection Reason</p>
                                        <p class="text-sm text-red-600 dark:text-red-400 bg-red-50 dark:bg-red-900/20 p-2 rounded">
                                            {{ $selectedRequest->comment }}
                                        </p>
                                    </div>
                                @endif
                            </div>
                        @endif
                    </div>

                    <!-- Footer -->
                    @if($selectedRequest->status === 'pending')
                        <div class="px-6 py-4 border-t border-zinc-200 dark:border-zinc-700 flex gap-3 justify-end bg-zinc-50 dark:bg-zinc-900">
                            <button @click="show = false; $wire.closeDetailsModal()"
                                class="px-4 py-2 bg-zinc-200 hover:bg-zinc-300 dark:bg-zinc-700 dark:hover:bg-zinc-600 text-zinc-800 dark:text-zinc-200 rounded-lg font-medium transition-colors">
                                Close
                            </button>
                            <button x-data="{ rejecting: false }" @click="rejecting = !rejecting"
                                class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-lg font-medium transition-colors">
                                Reject
                            </button>
                            <button wire:click="approveRequest({{ $selectedRequest->id }})"
                                wire:loading.attr="disabled" wire:target="approveRequest"
                                class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg font-medium transition-colors disabled:opacity-50 disabled:cursor-not-allowed">
                                <span wire:loading.remove wire:target="approveRequest">Approve</span>
                                <span wire:loading wire:target="approveRequest">Approving...</span>
                            </button>
                        </div>

                            <!-- Rejection Comment Section -->
                        <div x-show="showReject" x-cloak
                            class="px-6 py-4 border-t border-zinc-200 dark:border-zinc-700 bg-red-50 dark:bg-red-900/20">
                            <h4 class="text-sm font-medium text-zinc-900 dark:text-zinc-100 mb-3">Rejection Reason</h4>
                            <textarea wire:model="rejectionComment"
                                placeholder="Enter reason for rejection..."
                                rows="3"
                                class="w-full px-4 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-zinc-100 placeholder-zinc-400 dark:placeholder-zinc-500 focus:ring-2 focus:ring-red-500 focus:border-transparent resize-none mb-3">
                            </textarea>
                            @error('rejectionComment')
                                <p class="text-red-500 text-xs mb-3">{{ $message }}</p>
                            @enderror
                            <div class="flex gap-2 justify-end">
                                <button @click="showReject = false"
                                    class="px-3 py-1 text-sm bg-zinc-200 hover:bg-zinc-300 dark:bg-zinc-700 dark:hover:bg-zinc-600 text-zinc-800 dark:text-zinc-200 rounded font-medium transition-colors">
                                    Cancel
                                </button>
                                <button wire:click="rejectRequest({{ $selectedRequest->id }})"
                                    wire:loading.attr="disabled" wire:target="rejectRequest"
                                    class="px-3 py-1 text-sm bg-red-600 hover:bg-red-700 text-white rounded font-medium transition-colors disabled:opacity-50 disabled:cursor-not-allowed">
                                    <span wire:loading.remove wire:target="rejectRequest">Confirm Rejection</span>
                                    <span wire:loading wire:target="rejectRequest">Rejecting...</span>
                                </button>
                            </div>
                        </div>
                    @else
                        <div class="px-6 py-4 border-t border-zinc-200 dark:border-zinc-700 flex gap-3 justify-end bg-zinc-50 dark:bg-zinc-900">
                            <button @click="show = false; $wire.closeDetailsModal()"
                                class="px-4 py-2 bg-zinc-200 hover:bg-zinc-300 dark:bg-zinc-700 dark:hover:bg-zinc-600 text-zinc-800 dark:text-zinc-200 rounded-lg font-medium transition-colors">
                                Close
                            </button>
                        </div>
                    @endif
                </div>
            </div>
        @endif
    @endif

</div>
