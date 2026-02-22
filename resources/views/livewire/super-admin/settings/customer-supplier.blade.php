<div>
    <h2 class="text-2xl font-bold mb-6 text-zinc-900 dark:text-zinc-100">Customer & Supplier Management</h2>
    
    @if (session()->has('message'))
        <div class="mb-4 p-4 bg-green-100 dark:bg-green-900 text-green-700 dark:text-green-100 rounded-lg">
            {{ session('message') }}
        </div>
    @endif

    <form wire:submit.prevent="save">
        <div class="space-y-6">
            <!-- Customer Management -->
            <div class="setting-group p-4 bg-zinc-50 dark:bg-zinc-700 rounded-lg transition-colors duration-300">
                <h3 class="text-lg font-semibold mb-4 text-zinc-900 dark:text-zinc-100">Customer Management</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="flex items-center">
                        <input type="checkbox" wire:model="customersAdd" class="h-4 w-4 text-blue-600 border-zinc-300 rounded focus:ring-blue-500">
                        <span class="ml-2 text-sm text-zinc-700 dark:text-zinc-300">Add Customers</span>
                    </div>
                    <div class="flex items-center">
                        <input type="checkbox" wire:model="customersEdit" class="h-4 w-4 text-blue-600 border-zinc-300 rounded focus:ring-blue-500">
                        <span class="ml-2 text-sm text-zinc-700 dark:text-zinc-300">Edit Customers</span>
                    </div>
                    <div class="flex items-center">
                        <input type="checkbox" wire:model="customersGroups" class="h-4 w-4 text-blue-600 border-zinc-300 rounded focus:ring-blue-500">
                        <span class="ml-2 text-sm text-zinc-700 dark:text-zinc-300">Customer Groups</span>
                    </div>
                </div>
            </div>
            
            <!-- Supplier Management -->
            <div class="setting-group p-4 bg-zinc-50 dark:bg-zinc-700 rounded-lg transition-colors duration-300">
                <h3 class="text-lg font-semibold mb-4 text-zinc-900 dark:text-zinc-100">Supplier Management</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="flex items-center">
                        <input type="checkbox" wire:model="suppliersAdd" class="h-4 w-4 text-blue-600 border-zinc-300 rounded focus:ring-blue-500">
                        <span class="ml-2 text-sm text-zinc-700 dark:text-zinc-300">Add Suppliers</span>
                    </div>
                    <div class="flex items-center">
                        <input type="checkbox" wire:model="suppliersEdit" class="h-4 w-4 text-blue-600 border-zinc-300 rounded focus:ring-blue-500">
                        <span class="ml-2 text-sm text-zinc-700 dark:text-zinc-300">Edit Suppliers</span>
                    </div>
                </div>
            </div>
            
            <!-- Import/Export -->
            <div class="setting-group p-4 bg-zinc-50 dark:bg-zinc-700 rounded-lg transition-colors duration-300">
                <h3 class="text-lg font-semibold mb-4 text-zinc-900 dark:text-zinc-100">Import/Export</h3>
                <div class="flex items-center">
                    <input type="checkbox" wire:model="partyImport" class="h-4 w-4 text-blue-600 border-zinc-300 rounded focus:ring-blue-500">
                    <span class="ml-2 text-sm text-zinc-700 dark:text-zinc-300">Enable Party Import (Customers & Suppliers)</span>
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
