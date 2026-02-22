<div>
    <h2 class="text-2xl font-bold mb-6 text-zinc-900 dark:text-zinc-100">Notifications & Alerts</h2>
    
    @if (session()->has('message'))
        <div class="mb-4 p-4 bg-green-100 dark:bg-green-900 text-green-700 dark:text-green-100 rounded-lg">
            {{ session('message') }}
        </div>
    @endif

    <form wire:submit.prevent="save">
        <div class="space-y-6">
            <!-- Alert Channels -->
            <div class="setting-group p-4 bg-zinc-50 dark:bg-zinc-700 rounded-lg transition-colors duration-300">
                <h3 class="text-lg font-semibold mb-4 text-zinc-900 dark:text-zinc-100">Alert Channels</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="flex items-center">
                        <input type="checkbox" wire:model="alertsEmail" class="h-4 w-4 text-blue-600 border-zinc-300 rounded focus:ring-blue-500">
                        <span class="ml-2 text-sm text-zinc-700 dark:text-zinc-300">Email Alerts</span>
                    </div>
                    <div class="flex items-center">
                        <input type="checkbox" wire:model="alertsSms" class="h-4 w-4 text-blue-600 border-zinc-300 rounded focus:ring-blue-500">
                        <span class="ml-2 text-sm text-zinc-700 dark:text-zinc-300">SMS Alerts</span>
                    </div>
                </div>
                <p class="mt-2 text-sm text-zinc-500 dark:text-zinc-400">Configure notification channels for system alerts (low stock, expiry warnings, etc.)</p>
            </div>
            
            <!-- Task & To-Do -->
            <div class="setting-group p-4 bg-zinc-50 dark:bg-zinc-700 rounded-lg transition-colors duration-300">
                <h3 class="text-lg font-semibold mb-4 text-zinc-900 dark:text-zinc-100">Task & To-Do System</h3>
                <div class="flex items-center">
                    <input type="checkbox" wire:model="taskTodo" class="h-4 w-4 text-blue-600 border-zinc-300 rounded focus:ring-blue-500">
                    <span class="ml-2 text-sm text-zinc-700 dark:text-zinc-300">Enable Task & To-Do Management</span>
                </div>
                <p class="mt-2 text-sm text-zinc-500 dark:text-zinc-400">Allow users to create and manage tasks within the system</p>
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
