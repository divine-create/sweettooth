<div class="space-y-6">
    <div class="bg-white rounded-lg shadow-md p-6">
        <h2 class="text-2xl font-bold mb-4">Chart of Accounts</h2>

        <!-- Filters -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Search</label>
                <input type="text" wire:model.live="search" placeholder="Account number or name..." 
                       class="w-full px-3 py-2 border border-gray-300 rounded-md">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Account Type</label>
                <select wire:model.live="filterType" class="w-full px-3 py-2 border border-gray-300 rounded-md">
                    <option value="">All Types</option>
                    @foreach ($types as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Category</label>
                <input type="text" wire:model.live="filterCategory" placeholder="Filter by category..." 
                       class="w-full px-3 py-2 border border-gray-300 rounded-md">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                <label class="flex items-center">
                    <input type="checkbox" wire:model.live="filterActive" class="form-checkbox">
                    <span class="ml-2 text-sm">Active Only</span>
                </label>
            </div>
        </div>

        <!-- Results -->
        @if ($accounts->count() > 0)
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-gray-100 border-b-2 border-gray-300">
                        <tr>
                            <th class="px-4 py-2 text-left cursor-pointer hover:bg-gray-200" wire:click="sort('account_number')">
                                Account # 
                                @if ($sortBy === 'account_number')
                                    {{ $sortDir === 'asc' ? '↑' : '↓' }}
                                @endif
                            </th>
                            <th class="px-4 py-2 text-left cursor-pointer hover:bg-gray-200" wire:click="sort('account_name')">
                                Account Name
                                @if ($sortBy === 'account_name')
                                    {{ $sortDir === 'asc' ? '↑' : '↓' }}
                                @endif
                            </th>
                            <th class="px-4 py-2 text-left">Type</th>
                            <th class="px-4 py-2 text-left">Category</th>
                            <th class="px-4 py-2 text-right">Debit Balance</th>
                            <th class="px-4 py-2 text-right">Credit Balance</th>
                            <th class="px-4 py-2 text-center">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($accounts as $account)
                            <tr class="border-b hover:bg-gray-50">
                                <td class="px-4 py-2 font-mono font-semibold">{{ $account->account_number }}</td>
                                <td class="px-4 py-2">{{ $account->account_name }}</td>
                                <td class="px-4 py-2">
                                    <span class="px-2 py-1 bg-blue-100 text-blue-800 text-xs rounded">
                                        {{ ucfirst(str_replace('_', ' ', $account->account_type)) }}
                                    </span>
                                </td>
                                <td class="px-4 py-2 text-xs text-gray-600">{{ $account->account_category ?? '-' }}</td>
                                <td class="px-4 py-2 text-right">{{ number_format($account->debit_balance, 2) }}</td>
                                <td class="px-4 py-2 text-right">{{ number_format($account->credit_balance, 2) }}</td>
                                <td class="px-4 py-2 text-center">
                                    <button wire:click="toggleStatus({{ $account->id }})" 
                                            class="px-3 py-1 rounded text-xs font-semibold 
                                            {{ $account->is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                        {{ $account->is_active ? 'Active' : 'Inactive' }}
                                    </button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div class="mt-4">
                {{ $accounts->links() }}
            </div>
        @else
            <div class="p-4 bg-gray-50 border border-gray-200 rounded text-center text-gray-500">
                No accounts found
            </div>
        @endif
    </div>
</div>
