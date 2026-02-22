<div>
    <h2 class="text-2xl font-bold mb-6 text-zinc-900 dark:text-zinc-100">Accounting & Cash</h2>
    
    @if (session()->has('message'))
        <div class="mb-4 p-4 bg-green-100 dark:bg-green-900 text-green-700 dark:text-green-100 rounded-lg">
            {{ session('message') }}
        </div>
    @endif

    <form wire:submit.prevent="save">
        <div class="space-y-6">
            <!-- Expenses Categories -->
            <div class="setting-group p-4 bg-zinc-50 dark:bg-zinc-700 rounded-lg transition-colors duration-300">
                <h3 class="text-lg font-semibold mb-4 text-zinc-900 dark:text-zinc-100">Expenses Categories</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="flex items-center">
                        <input type="checkbox" wire:model="expensesAdd" class="h-4 w-4 text-blue-600 border-zinc-300 rounded focus:ring-blue-500">
                        <span class="ml-2 text-sm text-zinc-700 dark:text-zinc-300">Add Expense Categories</span>
                    </div>
                    <div class="flex items-center">
                        <input type="checkbox" wire:model="expensesEdit" class="h-4 w-4 text-blue-600 border-zinc-300 rounded focus:ring-blue-500">
                        <span class="ml-2 text-sm text-zinc-700 dark:text-zinc-300">Edit Expense Categories</span>
                    </div>
                </div>
            </div>
            
            <!-- Cash & Bank Management -->
            <div class="setting-group p-4 bg-zinc-50 dark:bg-zinc-700 rounded-lg transition-colors duration-300">
                <h3 class="text-lg font-semibold mb-4 text-zinc-900 dark:text-zinc-100">Cash & Bank Management</h3>
                <div class="flex items-center">
                    <input type="checkbox" wire:model="cashBank" class="h-4 w-4 text-blue-600 border-zinc-300 rounded focus:ring-blue-500">
                    <span class="ml-2 text-sm text-zinc-700 dark:text-zinc-300">Enable Cash & Bank Account Management</span>
                </div>
            </div>
            
            <!-- Profit & Loss Reports -->
            <div class="setting-group p-4 bg-zinc-50 dark:bg-zinc-700 rounded-lg transition-colors duration-300">
                <h3 class="text-lg font-semibold mb-4 text-zinc-900 dark:text-zinc-100">Profit & Loss Reports</h3>
                <div>
                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Report Type</label>
                    <select wire:model="profitLossReports" class="w-full px-3 py-2 border border-zinc-300 dark:border-zinc-600 rounded-md bg-white dark:bg-zinc-800 text-zinc-900 dark:text-zinc-100">
                        <option value="by_date">By Date</option>
                        <option value="by_month">By Month</option>
                        <option value="by_quarter">By Quarter</option>
                        <option value="by_year">By Year</option>
                    </select>
                </div>
            </div>
            
            <!-- Accounting Entries -->
            <div class="setting-group p-4 bg-zinc-50 dark:bg-zinc-700 rounded-lg transition-colors duration-300">
                <h3 class="text-lg font-semibold mb-4 text-zinc-900 dark:text-zinc-100">Accounting Entries</h3>
                <div>
                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Entry Mode</label>
                    <select wire:model="accountingEntries" class="w-full px-3 py-2 border border-zinc-300 dark:border-zinc-600 rounded-md bg-white dark:bg-zinc-800 text-zinc-900 dark:text-zinc-100">
                        <option value="auto">Automatic</option>
                        <option value="manual">Manual</option>
                    </select>
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
