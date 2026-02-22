<div class="space-y-6">
    <!-- Header -->
    <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-md p-6">
        <h1 class="text-3xl font-bold text-zinc-900 dark:text-white mb-2">Chart of Accounts</h1>
        <p class="text-zinc-600 dark:text-zinc-400">Manage General Ledger accounts and their statuses</p>
    </div>

    <!-- Filters & Search -->
    <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-md p-6 space-y-4">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div>
                <label class="block text-sm font-medium text-zinc-900 dark:text-white mb-2">Search by Code/Name</label>
                <input 
                    wire:model.live="search" 
                    type="text" 
                    placeholder="Search accounts..."
                    class="w-full px-4 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white placeholder-zinc-500 dark:placeholder-zinc-400"
                >
            </div>

            <div>
                <label class="block text-sm font-medium text-zinc-900 dark:text-white mb-2">Account Type</label>
                <select 
                    wire:model.live="filterType"
                    class="w-full px-4 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white"
                >
                    <option value="">All Types</option>
                    <option value="asset">Asset</option>
                    <option value="liability">Liability</option>
                    <option value="equity">Equity</option>
                    <option value="revenue">Revenue</option>
                    <option value="cost_of_goods_sold">COGS</option>
                    <option value="expense">Expense</option>
                    <option value="other_income">Other Income</option>
                    <option value="tax">Tax</option>
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-zinc-900 dark:text-white mb-2">Status</label>
                <select 
                    wire:model.live="filterStatus"
                    class="w-full px-4 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white"
                >
                    <option value="">All Status</option>
                    <option value="active">Active</option>
                    <option value="inactive">Inactive</option>
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-zinc-900 dark:text-white mb-2">Sort By</label>
                <select 
                    wire:model.live="sortBy"
                    class="w-full px-4 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white"
                >
                    <option value="account_number">Account Number</option>
                    <option value="account_name">Account Name</option>
                    <option value="account_type">Type</option>
                </select>
            </div>
        </div>
    </div>

    <!-- Accounts Table -->
    <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-md overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-zinc-50 dark:bg-zinc-700/50 border-b border-zinc-200 dark:border-zinc-700">
                    <tr>
                        <th class="px-6 py-3 text-left">
                            <button wire:click="setSortBy('account_number')" class="font-semibold text-zinc-900 dark:text-white hover:text-indigo-600 dark:hover:text-indigo-400">
                                Code
                                @if ($sortBy === 'account_number')
                                    {{ $sortDirection === 'asc' ? '↑' : '↓' }}
                                @endif
                            </button>
                        </th>
                        <th class="px-6 py-3 text-left">
                            <button wire:click="setSortBy('account_name')" class="font-semibold text-zinc-900 dark:text-white hover:text-indigo-600 dark:hover:text-indigo-400">
                                Name
                                @if ($sortBy === 'account_name')
                                    {{ $sortDirection === 'asc' ? '↑' : '↓' }}
                                @endif
                            </button>
                        </th>
                        <th class="px-6 py-3 text-left">
                            <button wire:click="setSortBy('account_type')" class="font-semibold text-zinc-900 dark:text-white hover:text-indigo-600 dark:hover:text-indigo-400">
                                Type
                                @if ($sortBy === 'account_type')
                                    {{ $sortDirection === 'asc' ? '↑' : '↓' }}
                                @endif
                            </button>
                        </th>
                        <th class="px-6 py-3 text-center">
                            <span class="font-semibold text-zinc-900 dark:text-white">Status</span>
                        </th>
                        <th class="px-6 py-3 text-center">
                            <span class="font-semibold text-zinc-900 dark:text-white">Actions</span>
                        </th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                    @forelse ($this->accounts as $account)
                        <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-700/30 transition">
                            <td class="px-6 py-4 font-mono text-sm font-semibold text-zinc-900 dark:text-white">{{ $account->account_number }}</td>
                            <td class="px-6 py-4 text-zinc-700 dark:text-zinc-300">{{ $account->account_name }}</td>
                            <td class="px-6 py-4">
                                <span class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full 
                                    @if ($account->account_type === 'asset') bg-blue-100 dark:bg-blue-900/30 text-blue-800 dark:text-blue-300
                                    @elseif ($account->account_type === 'liability') bg-red-100 dark:bg-red-900/30 text-red-800 dark:text-red-300
                                    @elseif ($account->account_type === 'equity') bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-300
                                    @elseif ($account->account_type === 'revenue') bg-emerald-100 dark:bg-emerald-900/30 text-emerald-800 dark:text-emerald-300
                                    @elseif ($account->account_type === 'cost_of_goods_sold') bg-purple-100 dark:bg-purple-900/30 text-purple-800 dark:text-purple-300
                                    @else bg-orange-100 dark:bg-orange-900/30 text-orange-800 dark:text-orange-300
                                    @endif
                                ">
                                    {{ ucfirst(str_replace('_', ' ', $account->account_type)) }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-center">
                                @if ($account->is_active)
                                    <span class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-300">
                                        Active
                                    </span>
                                @else
                                    <span class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-zinc-100 dark:bg-zinc-700 text-zinc-800 dark:text-zinc-300">
                                        Inactive
                                    </span>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-center">
                                <button 
                                    wire:click="toggleActive({{ $account->id }})"
                                    class="text-indigo-600 dark:text-indigo-400 hover:text-indigo-900 dark:hover:text-indigo-300 font-medium text-sm"
                                >
                                    {{ $account->is_active ? 'Deactivate' : 'Activate' }}
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-8 text-center text-zinc-500 dark:text-zinc-400">
                                No accounts found matching your criteria
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Pagination -->
    <div class="flex justify-center">
        {{ $this->accounts->links() }}
    </div>
</div>
