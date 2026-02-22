<div class="p-3 space-y-3">
    <x-breadcrumb title="Expiry Alerts" :items="[
        ['label' => 'Dashboard', 'url' => branch_route('branch-dashboard.index')],
        ['label' => 'Sales Dashboard'],
        ['label' => 'Expiry Alerts'],
    ]" :compact="false" :with-icons="true" />

    <!-- Expiry Alert Modal -->
    @if ($showModal && count($expiredProducts) > 0)
        <div class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                <!-- Background overlay -->
                <div class="fixed inset-0 bg-gray-500 dark:bg-gray-900 bg-opacity-75 dark:bg-opacity-75 transition-opacity"
                    aria-hidden="true"></div>

                <!-- Modal panel -->
                <div
                    class="inline-block align-bottom bg-white dark:bg-zinc-800 rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-4xl sm:w-full">
                    <!-- Header -->
                    <div class="bg-red-600 px-6 py-4">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center">
                                <svg class="w-8 h-8 text-white mr-3" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                </svg>
                                <div>
                                    <h3 class="text-xl font-bold text-white" id="modal-title">
                                        Expired Products Alert
                                    </h3>
                                    <p class="text-sm text-red-100 mt-1">
                                        {{ count($expiredProducts) }} product(s) require your attention
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Content -->
                    <div class="bg-white dark:bg-zinc-800 px-6 py-4 max-h-96 overflow-y-auto">
                        <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">
                            The following products have expired or are expiring today. Please take action:
                        </p>

                        <!-- Expired Products List -->
                        <div class="space-y-3">
                            @foreach ($expiredProducts as $product)
                                <div
                                    class="border border-red-200 dark:border-red-800 rounded-lg p-4 bg-red-50 dark:bg-red-900/20">
                                    <div class="flex items-start justify-between">
                                        <div class="flex-1">
                                            <div class="flex items-center gap-2 mb-2">
                                                <h4 class="font-semibold text-gray-900 dark:text-white">
                                                    {{ $product['product_name'] }}
                                                </h4>
                                                <span
                                                    class="px-2 py-1 text-xs font-medium rounded-full {{ $product['badge_color'] }}">
                                                    {{ $product['is_expired'] ? 'Expired' : 'Expiring Today' }}
                                                </span>
                                            </div>

                                            <div class="grid grid-cols-2 md:grid-cols-4 gap-2 text-sm text-gray-600 dark:text-gray-400">
                                                <div>
                                                    <span class="font-medium">SKU:</span>
                                                    {{ $product['product_sku'] }}
                                                </div>
                                                <div>
                                                    <span class="font-medium">Quantity:</span>
                                                    {{ $product['quantity'] }} {{ $product['uom'] }}
                                                </div>
                                                <div>
                                                    <span class="font-medium">Production:</span>
                                                    {{ $product['production_date'] ?? 'N/A' }}
                                                </div>
                                                <div>
                                                    <span class="font-medium">Expiry:</span>
                                                    {{ $product['expiry_date'] }}
                                                </div>
                                            </div>

                                            @if ($product['days_remaining'] !== null)
                                                <div class="mt-2 text-sm">
                                                    <span
                                                        class="font-medium {{ $product['days_remaining'] < 0 ? 'text-red-600 dark:text-red-400' : 'text-orange-600 dark:text-orange-400' }}">
                                                        @if ($product['days_remaining'] < 0)
                                                            Expired {{ abs($product['days_remaining']) }} day(s) ago
                                                        @elseif($product['days_remaining'] == 0)
                                                            Expires today
                                                        @else
                                                            Expires in {{ $product['days_remaining'] }} day(s)
                                                        @endif
                                                    </span>
                                                </div>
                                            @endif

                                            <!-- Callback Form (shown when selected) -->
                                            @if ($selectedProductStockId === $product['product_stock_id'])
                                                <div class="mt-3 p-3 bg-white dark:bg-zinc-900 rounded border border-gray-200 dark:border-zinc-700">
                                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                                        Callback Quantity
                                                    </label>
                                                    <input type="number" wire:model="callbackQuantity" step="0.01"
                                                        min="0" max="{{ $product['quantity'] }}"
                                                        class="w-full px-3 py-2 border border-gray-300 dark:border-zinc-600 rounded-md dark:bg-zinc-800 dark:text-white mb-2">

                                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                                        Notes (Optional)
                                                    </label>
                                                    <textarea wire:model="callbackNotes" rows="2"
                                                        class="w-full px-3 py-2 border border-gray-300 dark:border-zinc-600 rounded-md dark:bg-zinc-800 dark:text-white mb-2"
                                                        placeholder="Reason for callback..."></textarea>

                                                    <div class="flex gap-2">
                                                        <button type="button"
                                                            wire:click="markAsCallback({{ $product['product_stock_id'] }}, '{{ $product['product_name'] }}', {{ $product['quantity'] }})"
                                                            class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors text-sm font-medium">
                                                            Confirm Callback
                                                        </button>
                                                        <button type="button" wire:click="$set('selectedProductStockId', null)"
                                                            class="px-4 py-2 bg-gray-200 dark:bg-zinc-700 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-300 dark:hover:bg-zinc-600 transition-colors text-sm font-medium">
                                                            Cancel
                                                        </button>
                                                    </div>
                                                </div>
                                            @endif
                                        </div>
                                    </div>

                                    <!-- Action Buttons -->
                                    @if ($selectedProductStockId !== $product['product_stock_id'])
                                        <div class="mt-3 flex gap-2">
                                            <button type="button"
                                                wire:click="openCallbackModal({{ $product['product_stock_id'] }}, {{ $product['quantity'] }})"
                                                class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors text-sm font-medium">
                                                <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor"
                                                    viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                        d="M6 18L18 6M6 6l12 12" />
                                                </svg>
                                                Mark as Callback
                                            </button>

                                            <button type="button"
                                                wire:click="confirmStillGood({{ $product['product_stock_id'] }}, '{{ $product['product_name'] }}')"
                                                class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors text-sm font-medium">
                                                <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor"
                                                    viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                        d="M5 13l4 4L19 7" />
                                                </svg>
                                                Confirm Still Good
                                            </button>
                                        </div>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    </div>

                    <!-- Warning Section -->
                    @if (count($expiringProducts) > 0)
                        <div class="bg-yellow-50 dark:bg-yellow-900/20 px-6 py-3 border-t border-yellow-200 dark:border-yellow-800">
                            <div class="flex items-center">
                                <svg class="w-5 h-5 text-yellow-600 dark:text-yellow-400 mr-2" fill="none"
                                    stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                </svg>
                                <p class="text-sm font-medium text-yellow-800 dark:text-yellow-200">
                                    {{ count($expiringProducts) }} product(s) expiring soon - monitor closely
                                </p>
                            </div>
                        </div>
                    @endif

                    <!-- Footer -->
                    <div class="bg-gray-50 dark:bg-zinc-900 px-6 py-3 text-right">
                        <p class="text-xs text-gray-500 dark:text-gray-400 mb-2">
                            Please address all expired products before proceeding to work
                        </p>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <!-- Success State -->
    @if (!$showModal)
        <div class="bg-white dark:bg-zinc-800 rounded-lg p-6 text-center">
            <svg class="w-16 h-16 text-green-500 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-2">All Products Checked</h3>
            <p class="text-gray-600 dark:text-gray-400">
                All expired products have been addressed. You may now proceed to your dashboard.
            </p>
        </div>
    @endif
</div>
