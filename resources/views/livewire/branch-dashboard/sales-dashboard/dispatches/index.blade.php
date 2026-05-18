<div class="p-3 space-y-3" x-data="{ open: false }" @if(!$showReceivingModal) wire:poll.20s="$refresh" wire:poll:keep-alive @endif>
    <x-breadcrumb title="Production Dispatches" :items="[
        ['label' => 'Dashboard', 'url' => branch_route('branch-dashboard.index')],
        ['label' => 'Sales Dashboard'],
        ['label' => 'Production Dispatches'],
    ]" :compact="false" :with-icons="true" />

    <!-- Header -->
    <div class="bg-gradient-to-r from-green-600 to-green-700 rounded-lg p-4 text-white shadow-lg">
        <div class="flex justify-between items-center">
            <div>
                <h2 class="text-xl font-bold">Production Dispatch Receiving</h2>
                <p class="text-sm opacity-90 mt-1">
                    Receive products dispatched from production departments
                </p>
            </div>
            <div class="text-right">
                <div class="bg-white/20 px-4 py-2 rounded-lg backdrop-blur-sm">
                    <div class="text-xs opacity-80">{{ $departmentName }}</div>
                    <div class="font-semibold">{{ $branchName }}</div>
                </div>
            </div>
        </div>
    </div>

    <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800 dark:border-emerald-800 dark:bg-emerald-900/20 dark:text-emerald-300">
        Receive dispatched products on this page first. Once received, items become available in POS stock for this sales department.
    </div>

    <!-- Filters Section -->
    <div x-data="{ open: true }"
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
                class="flex items-center px-2.5 py-1 rounded text-xs font-medium bg-blue-600 hover:bg-blue-700 text-white transition-all duration-200">
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
            <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                <div>
                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Search</label>
                    <input type="text" wire:model.live.debounce.300ms="search"
                        placeholder="Search by product name or SKU..."
                        class="w-full px-4 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-800 dark:text-zinc-200 focus:ring-2 focus:ring-blue-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Status</label>
                    <select wire:model.live="filterStatus"
                        class="w-full px-4 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-800 dark:text-zinc-200 focus:ring-2 focus:ring-blue-500">
                        <option value="">All Statuses</option>
                        <option value="pending_verification">Pending Verification</option>
                        <option value="accepted">Accepted</option>
                        <option value="received">Received</option>
                        <option value="rejected">Rejected</option>
                    </select>
                </div>
                <div>
                    <div class="flex items-center justify-between mb-1">
                        <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">Date</label>
                        @if($filterDate)
                            <button type="button" wire:click="$set('filterDate', null)"
                                class="text-xs text-blue-600 hover:text-blue-700 dark:text-blue-400 dark:hover:text-blue-300">
                                Clear
                            </button>
                        @endif
                    </div>
                    <input type="date" wire:model.live="filterDate"
                        class="w-full px-4 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-800 dark:text-zinc-200 focus:ring-2 focus:ring-blue-500">
                </div>
            </div>
        </div>
    </div>

    <!-- Dispatches Table -->
    <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-sm border border-zinc-200 dark:border-zinc-700 overflow-hidden">
        @if($dispatches->count() > 0)
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-zinc-50 dark:bg-zinc-900 border-b border-zinc-200 dark:border-zinc-700">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-zinc-700 dark:text-zinc-300 uppercase tracking-wider">
                                Product
                            </th>
                            <th class="px-4 py-3 text-center text-xs font-semibold text-zinc-700 dark:text-zinc-300 uppercase tracking-wider">
                                Quantity
                            </th>
                            <th class="px-4 py-3 text-center text-xs font-semibold text-zinc-700 dark:text-zinc-300 uppercase tracking-wider">
                                Dispatch Time
                            </th>
                            <th class="px-4 py-3 text-center text-xs font-semibold text-zinc-700 dark:text-zinc-300 uppercase tracking-wider">
                                From Department
                            </th>
                            <th class="px-4 py-3 text-center text-xs font-semibold text-zinc-700 dark:text-zinc-300 uppercase tracking-wider">
                                Dispatched By
                            </th>
                            <th class="px-4 py-3 text-center text-xs font-semibold text-zinc-700 dark:text-zinc-300 uppercase tracking-wider">
                                Status
                            </th>
                            <th class="px-4 py-3 text-center text-xs font-semibold text-zinc-700 dark:text-zinc-300 uppercase tracking-wider">
                                Actions
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                        @foreach($dispatches as $dispatch)
                            <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-700/50 transition-colors">
                                <td class="px-4 py-3">
                                    <div class="font-medium text-zinc-900 dark:text-zinc-100">
                                        {{ $dispatch->product?->name ?? '—' }}
                                    </div>
                                    <div class="text-xs text-zinc-500 dark:text-zinc-400">
                                        {{ $dispatch->product?->sku ?? '' }}
                                    </div>
                                </td>
                                <td class="px-4 py-3 text-center">
                                    @php
                                        $qtyDisplay = $this->getDispatchQuantityDisplay($dispatch);
                                    @endphp
                                    <div class="font-semibold text-zinc-900 dark:text-zinc-100">
                                        {{ number_format($qtyDisplay['sales_qty'], 2) }} {{ $qtyDisplay['sales_symbol'] }}
                                        @if($qtyDisplay['converted'] && $qtyDisplay['production_symbol'])
                                            <div class="text-[11px] text-zinc-500 dark:text-zinc-400">
                                                ({{ number_format($qtyDisplay['production_qty'], 2) }} {{ $qtyDisplay['production_symbol'] }})
                                            </div>
                                        @endif
                                    </div>
                                    @if($dispatch->status === 'received' && $dispatch->received_quantity != $dispatch->quantity)
                                        @php
                                            $receivedDisplay = $this->getReceivedQuantityDisplay($dispatch);
                                        @endphp
                                        <div class="text-xs mt-1">
                                            <span class="text-zinc-600 dark:text-zinc-400">Received:</span>
                                            <span class="font-medium {{ $dispatch->received_quantity > $dispatch->quantity ? 'text-orange-600' : 'text-red-600' }}">
                                                {{ number_format($receivedDisplay['sales_qty'], 2) }} {{ $receivedDisplay['sales_symbol'] }}
                                                @if($receivedDisplay['converted'] && $receivedDisplay['production_symbol'])
                                                    <span class="text-[11px] text-zinc-500 dark:text-zinc-400">
                                                        ({{ number_format($receivedDisplay['production_qty'], 2) }} {{ $receivedDisplay['production_symbol'] }})
                                                    </span>
                                                @endif
                                            </span>
                                        </div>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-center">
                                    <div class="text-sm text-zinc-700 dark:text-zinc-300">
                                        {{ $dispatch->dispatch_time ? $dispatch->dispatch_time->format('M d, Y') : 'N/A' }}
                                    </div>
                                    <div class="text-xs text-zinc-500 dark:text-zinc-400">
                                        {{ $dispatch->dispatch_time ? $dispatch->dispatch_time->format('h:i A') : '' }}
                                    </div>
                                </td>
                                <td class="px-4 py-3 text-center">
                                    <div class="text-sm text-zinc-700 dark:text-zinc-300">
                                        {{ $dispatch->productionRequest?->productionDepartment?->name ?? 'Production' }}
                                    </div>
                                </td>
                                <td class="px-4 py-3 text-center">
                                    <div class="text-sm text-zinc-700 dark:text-zinc-300">
                                        {{ $dispatch->dispatchedBy->first_name ?? 'N/A' }} {{ $dispatch->dispatchedBy->last_name ?? '' }}
                                    </div>
                                </td>
                                <td class="px-4 py-3 text-center">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $dispatch->getStatusBadgeColor() }}">
                                        {{ $dispatch->getStatusLabel() }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-center">
                                    @if(in_array($dispatch->status, ['pending_verification', 'accepted'], true))
                                        <div class="flex items-center justify-center gap-2">
                                            <button wire:click="openReceivingModal({{ $dispatch->id }})"
                                                class="inline-flex items-center px-3 py-1.5 bg-green-600 hover:bg-green-700 text-white text-xs font-medium rounded-md transition-colors">
                                                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                                </svg>
                                                Receive
                                            </button>
                                        </div>
                                    @elseif($dispatch->status === 'received')
                                        <div class="text-xs text-zinc-600 dark:text-zinc-400">
                                            Received by: <span class="font-medium">{{ $dispatch->receivedBy->first_name ?? 'N/A' }}</span>
                                        </div>
                                    @else
                                        <div class="text-xs text-zinc-500 dark:text-zinc-500">
                                            -
                                        </div>
                                    @endif
                                </td>
                            </tr>
                            @if($dispatch->notes)
                                <tr class="bg-zinc-50 dark:bg-zinc-900/50">
                                    <td colspan="7" class="px-4 py-2">
                                        <div class="text-xs text-zinc-600 dark:text-zinc-400">
                                            <span class="font-semibold">Notes:</span> {{ $dispatch->notes }}
                                        </div>
                                    </td>
                                </tr>
                            @endif
                        @endforeach
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div class="px-4 py-3 border-t border-zinc-200 dark:border-zinc-700">
                {{ $dispatches->links() }}
            </div>
        @else
            <div class="p-8 text-center">
                <svg class="mx-auto h-12 w-12 text-zinc-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" />
                </svg>
                <h3 class="mt-2 text-sm font-medium text-zinc-900 dark:text-zinc-100">No dispatches found</h3>
                <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">
                    There are no production dispatches matching your filters.
                </p>
                @if(!$departmentId || !$branchId)
                    <p class="mt-2 text-xs text-amber-600 dark:text-amber-400">
                        Context missing: Branch {{ $branchId ?? 'N/A' }} • Department {{ $departmentId ?? 'N/A' }}.
                    </p>
                @else
                    <p class="mt-2 text-xs text-zinc-500 dark:text-zinc-400">
                        Current context: Branch {{ $branchId }} • Department {{ $departmentId }}.
                    </p>
                @endif
                @if($filterDate)
                    <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">
                        Tip: clear the date filter to see dispatches from other dates.
                    </p>
                @endif
            </div>
        @endif
    </div>

    <!-- Receiving Modal -->
    @if($showReceivingModal && $selectedDispatch)
        <div class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true" wire:keydown.escape="closeReceivingModal">
            <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                <!-- Background overlay -->
                <div class="fixed inset-0 z-0 bg-zinc-900/60 transition-opacity" wire:click="closeReceivingModal"></div>

                <span class="hidden sm:inline-block sm:h-screen sm:align-middle" aria-hidden="true">&#8203;</span>

                <!-- Modal panel -->
                <div class="relative z-10 inline-block align-bottom bg-white dark:bg-zinc-800 rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                    <div class="bg-white dark:bg-zinc-800 px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                        <div class="sm:flex sm:items-start">
                            <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-green-100 dark:bg-green-900/30 sm:mx-0 sm:h-10 sm:w-10">
                                <svg class="h-6 w-6 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                </svg>
                            </div>
                            <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left flex-1">
                                <h3 class="text-lg leading-6 font-medium text-zinc-900 dark:text-zinc-100" id="modal-title">
                                    Receive Dispatch
                                </h3>
                                <div class="mt-4 space-y-4">
                                    <div class="bg-zinc-50 dark:bg-zinc-900 p-3 rounded-lg">
                                        <div class="text-sm font-medium text-zinc-900 dark:text-zinc-100">
                                            {{ $selectedDispatch->product?->name ?? 'Unknown Product' }}
                                        </div>
                                        <div class="text-xs text-zinc-500 dark:text-zinc-400 mt-1">
                                            SKU: {{ $selectedDispatch->product?->sku ?? 'N/A' }}
                                        </div>
                                        <div class="text-sm text-zinc-700 dark:text-zinc-300 mt-2">
                                            Dispatched Quantity: <span class="font-semibold">{{ number_format($selectedDispatch->quantity, 2) }} {{ $selectedDispatch->uom }}</span>
                                        </div>
                                    </div>

                                    <div>
                                        <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">
                                            Received Quantity <span class="text-red-500">*</span>
                                        </label>
                                        <input type="number" step="0.01" wire:model="receivedQuantity"
                                            class="w-full px-4 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-800 dark:text-zinc-200 focus:ring-2 focus:ring-green-500">
                                        @error('receivedQuantity')
                                            <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                                        @enderror
                                    </div>

                                    <div>
                                        <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">
                                            Notes (Optional)
                                        </label>
                                        <textarea wire:model="receivingNotes" rows="3"
                                            placeholder="Any notes about the received dispatch..."
                                            class="w-full px-4 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-800 dark:text-zinc-200 focus:ring-2 focus:ring-green-500"></textarea>
                                        @error('receivingNotes')
                                            <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                                        @enderror
                                    </div>

                                    @if($receivedQuantity !== null && (float) $receivedQuantity != (float) $selectedDispatch->quantity)
                                        <div class="bg-yellow-50 dark:bg-yellow-900/20 border-l-4 border-yellow-400 p-3 rounded">
                                            <div class="flex">
                                                <svg class="h-5 w-5 text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                        d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                                </svg>
                                                <div class="ml-3">
                                                    <p class="text-sm text-yellow-700 dark:text-yellow-200">
                                                        Variance detected: {{ number_format(abs((float) $receivedQuantity - (float) $selectedDispatch->quantity), 2) }} {{ $selectedDispatch->uom }}
                                                        {{ (float) $receivedQuantity > (float) $selectedDispatch->quantity ? 'over' : 'under' }}
                                                    </p>
                                                </div>
                                            </div>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="bg-zinc-50 dark:bg-zinc-900 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse gap-2">
                        <button type="button" wire:click="receiveDispatch"
                            class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-green-600 text-base font-medium text-white hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 sm:ml-3 sm:w-auto sm:text-sm">
                            Confirm Receipt
                        </button>
                        <button type="button" wire:click="closeReceivingModal"
                            class="mt-3 w-full inline-flex justify-center rounded-md border border-zinc-300 dark:border-zinc-600 shadow-sm px-4 py-2 bg-white dark:bg-zinc-700 text-base font-medium text-zinc-700 dark:text-zinc-300 hover:bg-zinc-50 dark:hover:bg-zinc-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                            Cancel
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
