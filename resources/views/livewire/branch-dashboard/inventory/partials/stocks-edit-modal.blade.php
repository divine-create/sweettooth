@if($showEditModal && $editingStockId)
    <div x-data="{ 
        show: @entangle('showEditModal').live,
        isLoading: false,
        init() {
            // Watch for opens/reopens
            this.$watch('show', (value) => {
                if (value) {
                    this.isLoading = true;
                    setTimeout(() => { this.isLoading = false; }, 200);
                } else {
                    this.isLoading = false;
                }
            });
        }
    }" x-show="show" x-cloak 
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0 scale-95"
        x-transition:enter-end="opacity-100 scale-100"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100 scale-100"
        x-transition:leave-end="opacity-0 scale-95"
        class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4"
        @keydown.escape.window="show = false; $wire.closeEditModal()">
        <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-xl max-w-md w-full max-h-[90vh] overflow-y-auto">
            <!-- Header -->
            <div class="sticky top-0 px-6 py-4 border-b border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800">
                <h3 class="text-lg font-semibold text-zinc-900 dark:text-zinc-100">Edit Stock</h3>
                <p class="text-sm text-zinc-600 dark:text-zinc-400 mt-1">Update stock quantities and details</p>
            </div>

            <!-- Body -->
            <form wire:submit="updateStock" class="px-6 py-4 space-y-4" x-bind:class="{ 'opacity-50 pointer-events-none': isLoading }">
                <!-- Quantity Available -->
                <div x-data="{ wire_loading: false }" @loading="wire_loading = true" @done="wire_loading = false">
                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">
                        Quantity Available <span class="text-red-500">*</span>
                    </label>
                    <div class="relative">
                        <input 
                            type="number" 
                            step="0.01"
                            min="0"
                            wire:model.live="quantity_available"
                            class="w-full px-4 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-zinc-100 focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                        />
                        <div x-show="wire_loading" class="absolute right-3 top-2.5">
                            <svg class="w-4 h-4 animate-spin text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z" />
                            </svg>
                        </div>
                    </div>
                    @error('quantity_available')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Quantity Reserved -->
                <div>
                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">
                        Quantity Reserved <span class="text-red-500">*</span>
                    </label>
                    <input 
                        type="number" 
                        step="0.01"
                        min="0"
                        wire:model="quantity_reserved"
                        class="w-full px-4 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-zinc-100 focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                    />
                    @error('quantity_reserved')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Quantity Damaged -->
                <div>
                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">
                        Quantity Damaged <span class="text-red-500">*</span>
                    </label>
                    <input 
                        type="number" 
                        step="0.01"
                        min="0"
                        wire:model="quantity_damaged"
                        class="w-full px-4 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-zinc-100 focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                    />
                    @error('quantity_damaged')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Average Cost -->
                <div>
                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">
                        Average Cost <span class="text-red-500">*</span>
                    </label>
                    <input 
                        type="number" 
                        step="0.01"
                        min="0"
                        wire:model="average_cost"
                        class="w-full px-4 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-zinc-100 focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                    />
                    @error('average_cost')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Health Status -->
                <div>
                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">
                        Health Status <span class="text-red-500">*</span>
                    </label>
                    <select 
                        wire:model="health_status"
                        class="w-full px-4 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-zinc-100 focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                    >
                        <option value="good">Good</option>
                        <option value="warning">Warning</option>
                        <option value="critical">Critical</option>
                        <option value="expired">Expired</option>
                    </select>
                    @error('health_status')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Expiry Date -->
                <div>
                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">
                        Expiry Date
                    </label>
                    <input 
                        type="date"
                        wire:model="expiry_date"
                        class="w-full px-4 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-zinc-100 focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                    />
                    @error('expiry_date')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Reorder Level -->
                <div>
                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">
                        Reorder Level
                    </label>
                    <input 
                        type="number" 
                        step="0.01"
                        min="0"
                        wire:model.live="reorder_level"
                        class="w-full px-4 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-zinc-100 focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                    />
                    @error('reorder_level')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Max Stock Level -->
                <div>
                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">
                        Max Stock Level
                    </label>
                    <input 
                        type="number" 
                        step="0.01"
                        min="0"
                        wire:model.live="max_stock_level"
                        class="w-full px-4 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-zinc-100 focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                    />
                    @error('max_stock_level')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Notes -->
                <div>
                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">
                        Notes
                    </label>
                    <textarea
                        wire:model="notes"
                        rows="2"
                        placeholder="Add any notes about this adjustment..."
                        class="w-full px-4 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-zinc-100 focus:ring-2 focus:ring-blue-500 focus:border-transparent resize-none"
                    ></textarea>
                    @error('notes')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>
            </form>

            <!-- Footer -->
            <div class="sticky bottom-0 px-6 py-4 border-t border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 flex gap-3 justify-end" x-bind:class="{ 'opacity-50': isLoading }">
                <button
                    @click="show = false; $wire.closeEditModal()"
                    type="button"
                    :disabled="isLoading"
                    class="px-4 py-2 bg-zinc-200 hover:bg-zinc-300 dark:bg-zinc-700 dark:hover:bg-zinc-600 text-zinc-800 dark:text-zinc-200 rounded-lg font-medium transition-colors duration-150 disabled:opacity-50 disabled:cursor-not-allowed"
                >
                    Cancel
                </button>
                <button x-data="{ saving: false }"
                    @click="saving = true; $wire.updateStock().finally(() => { saving = false })"
                    :disabled="saving || isLoading"
                    type="button"
                    class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg font-medium transition-colors duration-150 disabled:opacity-50 disabled:cursor-not-allowed flex items-center"
                >
                    <svg x-show="!saving" class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                    </svg>
                    <svg x-show="saving" class="w-4 h-4 mr-2 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z" />
                    </svg>
                    <span x-show="!saving && !isLoading">Save</span>
                    <span x-show="saving && !isLoading">Saving...</span>
                    <span x-show="isLoading">Loading...</span>
                </button>
            </div>
        </div>
    </div>
@endif
