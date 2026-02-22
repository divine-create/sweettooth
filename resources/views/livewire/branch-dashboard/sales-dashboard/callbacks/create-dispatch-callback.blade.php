<div class="p-3 space-y-3">
    <x-breadcrumb title="Dispatch Callbacks" :items="[
        ['label' => 'Dashboard', 'url' => branch_route('branch-dashboard.index')],
        ['label' => 'Sales Dashboard'],
        ['label' => 'Dispatch Callbacks'],
    ]" :compact="false" :with-icons="true" />

    <!-- Header -->
    <div class="bg-gradient-to-r from-orange-600 to-orange-700 rounded-lg p-4 text-white shadow-lg">
        <div class="flex justify-between items-center">
            <div>
                <h2 class="text-xl font-bold">Product Dispatch Callbacks</h2>
                <p class="text-sm opacity-90 mt-1">
                    Return products back to production from dispatches
                </p>
            </div>
        </div>
    </div>

    <!-- Shift Selector and Info -->
    @if (!$isSuperAdmin && count($availableShifts) === 0)
        <div class="bg-yellow-50 dark:bg-yellow-900/20 border-l-4 border-yellow-400 p-4 rounded">
            <div class="flex items-center">
                <svg class="w-5 h-5 text-yellow-400 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                </svg>
                <div>
                    <h3 class="text-sm font-medium text-yellow-800 dark:text-yellow-200">No Sales Shifts Found</h3>
                    <p class="text-sm text-yellow-700 dark:text-yellow-300 mt-1">No sales shifts available in the last 30 days.</p>
                </div>
            </div>
        </div>
    @elseif(!$isSuperAdmin)
        <!-- Shift Selector -->
        <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-sm border border-zinc-200 dark:border-zinc-700 p-4">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2">
                        Select Shift
                    </label>
                    <select wire:model.live="selectedSalesShiftId"
                        class="w-full px-3 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-zinc-100 focus:ring-2 focus:ring-orange-500">
                        @foreach($availableShifts as $shift)
                            <option value="{{ $shift->id }}">
                                {{ $shift->shift_date }} - {{ ucfirst($shift->shift_type) }} - {{ $shift->department->name ?? 'N/A' }}
                                @if($currentSalesShift && $shift->id === $currentSalesShift->id) (Active) @endif
                            </option>
                        @endforeach
                    </select>
                </div>
                @if($selectedSalesShift)
                    <div class="flex items-end">
                        <div class="flex-1">
                            <h3 class="text-sm font-medium text-zinc-700 dark:text-zinc-300">Selected Shift Info</h3>
                            <p class="text-lg font-semibold text-zinc-900 dark:text-zinc-100 mt-1">
                                {{ $selectedSalesShift->shift_date }} - {{ $selectedSalesShift->department->name ?? 'N/A' }}
                            </p>
                        </div>
                        @if($currentSalesShift && $selectedSalesShift->id === $currentSalesShift->id)
                            <span class="inline-flex items-center px-3 py-1.5 rounded-full text-sm font-medium bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400">
                                <svg class="w-4 h-4 mr-1.5" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                                </svg>
                                Active
                            </span>
                        @endif
                    </div>
                @endif
            </div>
        </div>
    @endif

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
            <div>
                <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Search</label>
                <input type="text" wire:model.live.debounce.300ms="search"
                    placeholder="Search by product name or SKU..."
                    class="w-full px-4 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-800 dark:text-zinc-200 focus:ring-2 focus:ring-blue-500">
            </div>
        </div>
    </div>

    <!-- Dispatches Table -->
    <x-table :$headers :$rows striped paginate persist collapsible
        :filter="['quantity' => 'quantity', 'search' => 'search']"
        :quantity="[10, 20, 50, 100]">

        @interact('column_product', $row)
            <div>
                <div class="font-medium text-zinc-900 dark:text-zinc-100">{{ $row->product->name }}</div>
                <div class="text-xs text-zinc-500 dark:text-zinc-400">{{ $row->product->sku }}</div>
            </div>
        @endinteract

        @interact('column_dispatch_date', $row)
            <div class="text-center text-sm text-zinc-600 dark:text-zinc-400">
                {{ $row->dispatch_date ? $row->dispatch_date->format('M d, Y H:i') : 'N/A' }}
            </div>
        @endinteract

        @interact('column_received_qty', $row)
            <div class="text-center">
                <span class="font-semibold text-zinc-900 dark:text-zinc-100">
                    {{ number_format($row->received_quantity, 2) }} {{ $row->uom }}
                </span>
            </div>
        @endinteract

        @interact('column_returned_qty', $row)
            @php
                $totalReturned = $row->productDispatchCallbacks()
                    ->whereIn('status', ['pending', 'approved_by_production', 'received_by_production', 'completed'])
                    ->sum('quantity');
            @endphp
            <div class="text-center">
                <span class="font-medium text-red-600 dark:text-red-400">
                    {{ number_format($totalReturned, 2) }} {{ $row->uom }}
                </span>
            </div>
        @endinteract

        @interact('column_available_to_return', $row)
            @php
                $availableToReturn = $this->getAvailableQuantity($row);
            @endphp
            <div class="text-center">
                <span class="font-semibold text-green-600 dark:text-green-400">
                    {{ number_format($availableToReturn, 2) }} {{ $row->uom }}
                </span>
            </div>
        @endinteract

        @interact('column_status', $row)
            <div class="text-center">
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400">
                    {{ ucfirst($row->status) }}
                </span>
            </div>
        @endinteract

        @interact('column_action', $row)
            <div class="flex justify-center">
                @if($this->getAvailableQuantity($row) > 0)
                    <button wire:click="openCallbackModal({{ $row->id }})"
                        class="px-3 py-1.5 bg-orange-600 hover:bg-orange-700 text-white rounded text-sm font-medium transition-colors">
                        Create Callback
                    </button>
                @else
                    <span class="text-xs text-zinc-400 dark:text-zinc-500">Fully Returned</span>
                @endif
            </div>
        @endinteract

    </x-table>

    <!-- Callback Modal -->
    @if($showCallbackModal && $selectedDispatch)
        <div x-data="{ open: @entangle('showCallbackModal') }" x-show="open" x-cloak
            class="fixed inset-0 z-50 overflow-y-auto" @keydown.escape.window="$wire.closeCallbackModal()">
            <div class="flex items-center justify-center min-h-screen px-4">
                <div class="fixed inset-0 bg-black/50 dark:bg-black/70" @click="$wire.closeCallbackModal()"></div>

                <div class="relative bg-white dark:bg-zinc-800 rounded-lg shadow-xl max-w-md w-full p-6">
                    <!-- Modal Header -->
                    <div class="flex justify-between items-start mb-4">
                        <div>
                            <h3 class="text-lg font-semibold text-zinc-900 dark:text-zinc-100">Create Dispatch Callback</h3>
                            <p class="text-sm text-zinc-600 dark:text-zinc-400 mt-1">
                                {{ $selectedDispatch->product->name }}
                            </p>
                        </div>
                        <button @click="$wire.closeCallbackModal()"
                            class="text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-300">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    <!-- Dispatch Info -->
                    <div class="bg-zinc-100 dark:bg-zinc-700/50 rounded-lg p-3 mb-4">
                        <div class="grid grid-cols-2 gap-2 text-sm">
                            <div>
                                <span class="text-zinc-600 dark:text-zinc-400">Received:</span>
                                <span class="font-semibold text-zinc-900 dark:text-zinc-100 ml-1">
                                    {{ number_format($selectedDispatch->received_quantity, 2) }}
                                    {{ $selectedDispatch->uom }}
                                </span>
                            </div>
                            <div>
                                <span class="text-zinc-600 dark:text-zinc-400">Available to Return:</span>
                                <span class="font-semibold text-green-600 dark:text-green-400 ml-1">
                                    {{ number_format($this->getAvailableQuantity($selectedDispatch), 2) }}
                                    {{ $selectedDispatch->uom }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Form -->
                    <form wire:submit.prevent="submitCallback" class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">
                                Callback Quantity *
                            </label>
                            <input type="number" step="0.01" min="0" wire:model="callbackQuantity"
                                class="w-full px-3 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-zinc-100 focus:ring-2 focus:ring-orange-500"
                                required>
                            @error('callbackQuantity')
                                <span class="text-red-500 text-xs mt-1">{{ $message }}</span>
                            @enderror
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">
                                Reason *
                            </label>
                            <select wire:model="callbackReason"
                                class="w-full px-3 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-zinc-100 focus:ring-2 focus:ring-orange-500"
                                required>
                                <option value="">Select reason</option>
                                @foreach($reasonOptions as $value => $label)
                                    <option value="{{ $value }}">{{ $label }}</option>
                                @endforeach
                            </select>
                            @error('callbackReason')
                                <span class="text-red-500 text-xs mt-1">{{ $message }}</span>
                            @enderror
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">
                                Notes (Optional)
                            </label>
                            <textarea wire:model="callbackNotes" rows="3"
                                class="w-full px-3 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-zinc-100 focus:ring-2 focus:ring-orange-500"
                                placeholder="Additional details..."></textarea>
                        </div>

                        <!-- Actions -->
                        <div class="flex justify-end gap-3 pt-2">
                            <button type="button" @click="$wire.closeCallbackModal()"
                                class="px-4 py-2 bg-zinc-200 hover:bg-zinc-300 dark:bg-zinc-700 dark:hover:bg-zinc-600 text-zinc-800 dark:text-zinc-200 rounded-lg font-medium transition-colors">
                                Cancel
                            </button>
                            <button type="submit"
                                class="px-4 py-2 bg-orange-600 hover:bg-orange-700 text-white rounded-lg font-medium transition-colors">
                                Create Callback
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif

    <!-- Help Panel -->
    <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-4">
        <div class="flex items-start">
            <svg class="w-5 h-5 text-blue-600 dark:text-blue-400 mt-0.5 mr-3 flex-shrink-0" fill="none"
                stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <div class="text-sm text-blue-800 dark:text-blue-200">
                <h4 class="font-semibold mb-1">Dispatch Callback Instructions:</h4>
                <ul class="list-disc list-inside space-y-1">
                    <li><strong>Dispatch-Based Returns:</strong> Return products based on specific production dispatches</li>
                    <li><strong>Production Approval:</strong> All callbacks must be approved by production department</li>
                    <li><strong>Quality Issues:</strong> Use this for products that don't meet quality standards</li>
                    <li><strong>Tracking:</strong> Each callback is tracked separately for accountability</li>
                </ul>
            </div>
        </div>
    </div>
</div>
