<div class="space-y-6">
    <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-md p-6">
        <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
            <div>
                <h1 class="text-2xl font-bold text-zinc-900 dark:text-white">Bank Accounts</h1>
                <p class="text-zinc-600 dark:text-zinc-400">Create, edit, and manage bank accounts used for reconciliation and postings</p>
            </div>
            <div class="flex items-center gap-2">
                <button type="button" wire:click="exportToCsv" class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg text-sm font-semibold">Export CSV</button>
                <button wire:click="toggleForm" class="px-4 py-2 bg-zinc-900 hover:bg-zinc-800 text-white rounded-lg text-sm font-semibold">
                    {{ $showForm ? 'Close Form' : 'New Account' }}
                </button>
            </div>
        </div>
    </div>

    @if (session()->has('message'))
        <div class="bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-lg p-4 text-green-800 dark:text-green-200">
            {{ session('message') }}
        </div>
    @endif

    <!-- Form -->
    @if ($showForm)
        <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-md p-6">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-lg font-semibold text-zinc-900 dark:text-white">
                    {{ $editingId ? 'Edit Bank Account' : 'New Bank Account' }}
                </h2>
                @if ($editingId)
                    <button wire:click="resetForm" class="text-sm text-zinc-600 dark:text-zinc-300 hover:text-zinc-900 dark:hover:text-white">
                        Cancel Edit
                    </button>
                @endif
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label class="block text-sm font-medium text-zinc-900 dark:text-white mb-2">Bank Name</label>
                    <input
                        wire:model.defer="bank_name"
                        type="text"
                        class="w-full px-4 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white"
                    >
                    @error('bank_name')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-zinc-900 dark:text-white mb-2">Account Number</label>
                    <input
                        wire:model.defer="account_number"
                        type="text"
                        class="w-full px-4 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white"
                    >
                    @error('account_number')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-zinc-900 dark:text-white mb-2">Account Type</label>
                    <select
                        wire:model.defer="account_type"
                        class="w-full px-4 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white"
                    >
                        <option value="checking">Checking</option>
                        <option value="savings">Savings</option>
                        <option value="money_market">Money Market</option>
                    </select>
                    @error('account_type')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-zinc-900 dark:text-white mb-2">GL Account</label>
                    <select
                        wire:model.defer="gl_account_id"
                        class="w-full px-4 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white"
                    >
                        <option value="">-- Optional --</option>
                        @foreach ($glAccounts as $account)
                            <option value="{{ $account->id }}">
                                {{ $account->account_number }} - {{ $account->account_name }}
                            </option>
                        @endforeach
                    </select>
                    @error('gl_account_id')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-zinc-900 dark:text-white mb-2">Opening Balance</label>
                    <input
                        wire:model.defer="opening_balance"
                        type="number"
                        step="0.01"
                        class="w-full px-4 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white"
                    >
                    @error('opening_balance')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>

                <div class="flex items-center gap-2 mt-8">
                    <input wire:model.defer="is_active" type="checkbox" class="rounded border-zinc-300 dark:border-zinc-600">
                    <label class="text-sm text-zinc-700 dark:text-zinc-300">Active</label>
                </div>
            </div>

            <div class="mt-6 flex justify-end gap-2">
                <button wire:click="save" class="px-6 py-2 bg-blue-600 hover:bg-blue-500 text-white rounded-md font-medium">
                    {{ $editingId ? 'Update Bank Account' : 'Create Bank Account' }}
                </button>
                <button wire:click="resetForm" class="px-6 py-2 bg-zinc-200 hover:bg-zinc-300 text-zinc-800 rounded-md font-medium">
                    Reset
                </button>
            </div>
        </div>
    @endif

    <!-- Filters & Search -->
    <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-md p-6 space-y-4">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
                <label class="block text-sm font-medium text-zinc-900 dark:text-white mb-2">Search</label>
                <input
                    wire:model.live="search"
                    type="text"
                    placeholder="Search by name, number, or type"
                    class="w-full px-4 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white"
                >
            </div>

            <div>
                <label class="block text-sm font-medium text-zinc-900 dark:text-white mb-2">Page Size</label>
                <select
                    wire:model.live="quantity"
                    class="w-full px-4 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white"
                >
                    <option value="10">10</option>
                    <option value="25">25</option>
                    <option value="50">50</option>
                    <option value="100">100</option>
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
                        <th class="px-6 py-3 text-left text-xs font-semibold text-zinc-700 dark:text-zinc-300 uppercase tracking-wider">Bank</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-zinc-700 dark:text-zinc-300 uppercase tracking-wider">Account Number</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-zinc-700 dark:text-zinc-300 uppercase tracking-wider">Type</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-zinc-700 dark:text-zinc-300 uppercase tracking-wider">GL Account</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-zinc-700 dark:text-zinc-300 uppercase tracking-wider">Balance</th>
                        <th class="px-6 py-3 text-center text-xs font-semibold text-zinc-700 dark:text-zinc-300 uppercase tracking-wider">Status</th>
                        <th class="px-6 py-3 text-center text-xs font-semibold text-zinc-700 dark:text-zinc-300 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                    @forelse ($rows as $account)
                        <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-700/30 transition">
                            <td class="px-6 py-4 text-zinc-900 dark:text-white font-semibold">
                                {{ $account->bank_name }}
                            </td>
                            <td class="px-6 py-4 font-mono text-sm text-zinc-700 dark:text-zinc-300">
                                {{ $account->account_number }}
                            </td>
                            <td class="px-6 py-4 text-zinc-700 dark:text-zinc-300">
                                {{ ucfirst(str_replace('_', ' ', $account->account_type)) }}
                            </td>
                            <td class="px-6 py-4 text-zinc-700 dark:text-zinc-300">
                                {{ $account->glAccount?->account_number }}
                                @if ($account->glAccount)
                                    <div class="text-xs text-zinc-500 dark:text-zinc-400">{{ $account->glAccount->account_name }}</div>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-zinc-700 dark:text-zinc-300">
                                {{ number_format($account->getCurrentBalance() ?? 0, 2) }}
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
                                <button wire:click="edit({{ $account->id }})" class="text-indigo-600 dark:text-indigo-400 hover:text-indigo-900 dark:hover:text-indigo-300 font-medium text-sm">Edit</button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-8 text-center text-zinc-500 dark:text-zinc-400">
                                No bank accounts found
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="flex justify-center">
        {{ $rows->links() }}
    </div>
</div>
