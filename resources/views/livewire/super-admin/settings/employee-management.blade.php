<div>
    <h2 class="text-2xl font-bold mb-6 text-zinc-900 dark:text-zinc-100">Employee Management</h2>
    
    @if (session()->has('message'))
        <div class="mb-4 p-4 bg-green-100 dark:bg-green-900 text-green-700 dark:text-green-100 rounded-lg">
            {{ session('message') }}
        </div>
    @endif

    <form wire:submit.prevent="save">
        <div class="space-y-6">
            <!-- Roles & Permissions -->
            <div class="setting-group p-4 bg-zinc-50 dark:bg-zinc-700 rounded-lg transition-colors duration-300">
                <h3 class="text-lg font-semibold mb-4 text-zinc-900 dark:text-zinc-100">Roles & Permissions</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="flex items-center">
                        <input type="checkbox" wire:model="rolesCreate" class="h-4 w-4 text-blue-600 border-zinc-300 rounded focus:ring-blue-500">
                        <span class="ml-2 text-sm text-zinc-700 dark:text-zinc-300">Create Roles</span>
                    </div>
                    <div class="flex items-center">
                        <input type="checkbox" wire:model="rolesEdit" class="h-4 w-4 text-blue-600 border-zinc-300 rounded focus:ring-blue-500">
                        <span class="ml-2 text-sm text-zinc-700 dark:text-zinc-300">Edit Roles</span>
                    </div>
                    <div class="flex items-center">
                        <input type="checkbox" wire:model="permissionsPos" class="h-4 w-4 text-blue-600 border-zinc-300 rounded focus:ring-blue-500">
                        <span class="ml-2 text-sm text-zinc-700 dark:text-zinc-300">POS Permissions</span>
                    </div>
                    <div class="flex items-center">
                        <input type="checkbox" wire:model="permissionsInventory" class="h-4 w-4 text-blue-600 border-zinc-300 rounded focus:ring-blue-500">
                        <span class="ml-2 text-sm text-zinc-700 dark:text-zinc-300">Inventory Permissions</span>
                    </div>
                    <div class="flex items-center">
                        <input type="checkbox" wire:model="permissionsReports" class="h-4 w-4 text-blue-600 border-zinc-300 rounded focus:ring-blue-500">
                        <span class="ml-2 text-sm text-zinc-700 dark:text-zinc-300">Reports Permissions</span>
                    </div>
                    <div class="flex items-center">
                        <input type="checkbox" wire:model="pinLogin" class="h-4 w-4 text-blue-600 border-zinc-300 rounded focus:ring-blue-500">
                        <span class="ml-2 text-sm text-zinc-700 dark:text-zinc-300">PIN Login for POS</span>
                    </div>
                </div>
            </div>
            
            <!-- Employee Profiles -->
            <div class="setting-group p-4 bg-zinc-50 dark:bg-zinc-700 rounded-lg transition-colors duration-300">
                <h3 class="text-lg font-semibold mb-4 text-zinc-900 dark:text-zinc-100">Employee Profiles</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="flex items-center">
                        <input type="checkbox" wire:model="staffAdd" class="h-4 w-4 text-blue-600 border-zinc-300 rounded focus:ring-blue-500">
                        <span class="ml-2 text-sm text-zinc-700 dark:text-zinc-300">Add Staff</span>
                    </div>
                    <div class="flex items-center">
                        <input type="checkbox" wire:model="staffEdit" class="h-4 w-4 text-blue-600 border-zinc-300 rounded focus:ring-blue-500">
                        <span class="ml-2 text-sm text-zinc-700 dark:text-zinc-300">Edit Staff</span>
                    </div>
                    <div class="flex items-center">
                        <input type="checkbox" wire:model="staffDelete" class="h-4 w-4 text-blue-600 border-zinc-300 rounded focus:ring-blue-500">
                        <span class="ml-2 text-sm text-zinc-700 dark:text-zinc-300">Delete Staff</span>
                    </div>
                    <div class="flex items-center">
                        <input type="checkbox" wire:model="shiftScheduling" class="h-4 w-4 text-blue-600 border-zinc-300 rounded focus:ring-blue-500">
                        <span class="ml-2 text-sm text-zinc-700 dark:text-zinc-300">Shift Scheduling</span>
                    </div>
                </div>
            </div>
            
            <!-- Departments -->
            <div class="setting-group p-4 bg-zinc-50 dark:bg-zinc-700 rounded-lg transition-colors duration-300">
                <h3 class="text-lg font-semibold mb-4 text-zinc-900 dark:text-zinc-100">Departments</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="flex items-center">
                        <input type="checkbox" wire:model="departmentSales" class="h-4 w-4 text-blue-600 border-zinc-300 rounded focus:ring-blue-500">
                        <span class="ml-2 text-sm text-zinc-700 dark:text-zinc-300">Sales Department</span>
                    </div>
                    <div class="flex items-center">
                        <input type="checkbox" wire:model="departmentWarehouse" class="h-4 w-4 text-blue-600 border-zinc-300 rounded focus:ring-blue-500">
                        <span class="ml-2 text-sm text-zinc-700 dark:text-zinc-300">Warehouse Department</span>
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
