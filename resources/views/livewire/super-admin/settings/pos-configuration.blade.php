<div>
    <h2 class="text-2xl font-bold mb-6 text-zinc-900 dark:text-zinc-100">POS Configuration</h2>
    
    @if (session()->has('message'))
        <div class="mb-4 p-4 bg-green-100 dark:bg-green-900 text-green-700 dark:text-green-100 rounded-lg">
            {{ session('message') }}
        </div>
    @endif

    <form wire:submit.prevent="save">
        <div class="space-y-6">
            <!-- POS Interface -->
            <div class="setting-group p-4 bg-zinc-50 dark:bg-zinc-700 rounded-lg transition-colors duration-300">
                <h3 class="text-lg font-semibold mb-4 text-zinc-900 dark:text-zinc-100">POS Interface</h3>
                <div class="flex items-center">
                    <input type="checkbox" wire:model="posInterface" class="h-4 w-4 text-blue-600 border-zinc-300 rounded focus:ring-blue-500">
                    <span class="ml-2 text-sm text-zinc-700 dark:text-zinc-300">Enable POS Interface</span>
                </div>
            </div>
            
            <!-- Payment Modes -->
            <div class="setting-group p-4 bg-zinc-50 dark:bg-zinc-700 rounded-lg transition-colors duration-300">
                <h3 class="text-lg font-semibold mb-4 text-zinc-900 dark:text-zinc-100">Payment Modes</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="flex items-center">
                        <input type="checkbox" wire:model="paymentModesAdd" class="h-4 w-4 text-blue-600 border-zinc-300 rounded focus:ring-blue-500">
                        <span class="ml-2 text-sm text-zinc-700 dark:text-zinc-300">Add Payment Modes</span>
                    </div>
                    <div class="flex items-center">
                        <input type="checkbox" wire:model="paymentModesEdit" class="h-4 w-4 text-blue-600 border-zinc-300 rounded focus:ring-blue-500">
                        <span class="ml-2 text-sm text-zinc-700 dark:text-zinc-300">Edit Payment Modes</span>
                    </div>
                </div>
            </div>
            
            <!-- Receipt Template -->
            <div class="setting-group p-4 bg-zinc-50 dark:bg-zinc-700 rounded-lg transition-colors duration-300">
                <h3 class="text-lg font-semibold mb-4 text-zinc-900 dark:text-zinc-100">Receipt Template</h3>
                <div>
                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Template Type</label>
                    <select wire:model="receiptTemplate" class="w-full px-3 py-2 border border-zinc-300 dark:border-zinc-600 rounded-md bg-white dark:bg-zinc-800 text-zinc-900 dark:text-zinc-100">
                        <option value="custom">Custom</option>
                        <option value="standard">Standard</option>
                        <option value="minimal">Minimal</option>
                    </select>
                </div>
            </div>
            
            <!-- Additional Features -->
            <div class="setting-group p-4 bg-zinc-50 dark:bg-zinc-700 rounded-lg transition-colors duration-300">
                <h3 class="text-lg font-semibold mb-4 text-zinc-900 dark:text-zinc-100">Additional Features</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="flex items-center">
                        <input type="checkbox" wire:model="salesReturns" class="h-4 w-4 text-blue-600 border-zinc-300 rounded focus:ring-blue-500">
                        <span class="ml-2 text-sm text-zinc-700 dark:text-zinc-300">Sales Returns</span>
                    </div>
                    <div class="flex items-center">
                        <input type="checkbox" wire:model="offlineMode" class="h-4 w-4 text-blue-600 border-zinc-300 rounded focus:ring-blue-500">
                        <span class="ml-2 text-sm text-zinc-700 dark:text-zinc-300">Offline Mode</span>
                    </div>
                    <div class="flex items-center">
                        <input type="checkbox" wire:model="onlineShopSync" class="h-4 w-4 text-blue-600 border-zinc-300 rounded focus:ring-blue-500">
                        <span class="ml-2 text-sm text-zinc-700 dark:text-zinc-300">Online Shop Sync</span>
                    </div>
                </div>
            </div>

            <!-- Save Button -->
            <div class="flex justify-end space-x-4">
                <button type="submit" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded transition-colors duration-200">
                    Save Changes
                </button>
                <button type="button" class="bg-zinc-500 hover:bg-zinc-700 text-white font-bold py-2 px-4 rounded transition-colors duration-200">
                    Cancel
                </button>
            </div>
        </div>
    </form>
</div>
