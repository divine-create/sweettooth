<div>
    <h2 class="text-2xl font-bold mb-6 text-zinc-900 dark:text-zinc-100">Security & Access</h2>
    
    @if (session()->has('message'))
        <div class="mb-4 p-4 bg-green-100 dark:bg-green-900 text-green-700 dark:text-green-100 rounded-lg">
            {{ session('message') }}
        </div>
    @endif

    <form wire:submit.prevent="save">
        <div class="space-y-6">
            <!-- Authentication -->
            <div class="setting-group p-4 bg-zinc-50 dark:bg-zinc-700 rounded-lg transition-colors duration-300">
                <h3 class="text-lg font-semibold mb-4 text-zinc-900 dark:text-zinc-100">Authentication</h3>
                <div>
                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Authentication Method</label>
                    <select wire:model="authentication" class="w-full px-3 py-2 border border-zinc-300 dark:border-zinc-600 rounded-md bg-white dark:bg-zinc-800 text-zinc-900 dark:text-zinc-100">
                        <option value="password">Password Only</option>
                        <option value="2fa">Two-Factor Authentication</option>
                        <option value="sso">Single Sign-On (SSO)</option>
                    </select>
                </div>
            </div>
            
            <!-- Audit Logs -->
            <div class="setting-group p-4 bg-zinc-50 dark:bg-zinc-700 rounded-lg transition-colors duration-300">
                <h3 class="text-lg font-semibold mb-4 text-zinc-900 dark:text-zinc-100">Audit Logs</h3>
                <div class="flex items-center">
                    <input type="checkbox" wire:model="auditLogs" class="h-4 w-4 text-blue-600 border-zinc-300 rounded focus:ring-blue-500">
                    <span class="ml-2 text-sm text-zinc-700 dark:text-zinc-300">Enable Audit Logs (Track all user actions)</span>
                </div>
            </div>
            
            <!-- Data Isolation -->
            <div class="setting-group p-4 bg-zinc-50 dark:bg-zinc-700 rounded-lg transition-colors duration-300">
                <h3 class="text-lg font-semibold mb-4 text-zinc-900 dark:text-zinc-100">Data Isolation</h3>
                <div>
                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Isolation Level</label>
                    <select wire:model="dataIsolation" class="w-full px-3 py-2 border border-zinc-300 dark:border-zinc-600 rounded-md bg-white dark:bg-zinc-800 text-zinc-900 dark:text-zinc-100">
                        <option value="saas_company">SaaS Company Level</option>
                        <option value="branch">Branch Level</option>
                        <option value="user">User Level</option>
                    </select>
                    <p class="mt-2 text-sm text-zinc-500 dark:text-zinc-400">Controls how data is isolated between different entities in the system</p>
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
