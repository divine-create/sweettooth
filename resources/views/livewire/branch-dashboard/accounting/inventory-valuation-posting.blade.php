<div class="space-y-6">
    <!-- Header -->
    <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-md p-6">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold text-zinc-900 dark:text-white">Inventory Valuation to GL</h1>
                <p class="text-zinc-600 dark:text-zinc-400">Convert current stock values to accounting entries (Assets)</p>
            </div>
            <div class="flex items-center gap-3">
                <select wire:model.live="selectedBranchId" wire:change="changeBranch($event.target.value)" class="px-4 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white">
                    <option value="">All Branches</option>
                    @foreach ($branches as $branch)
                        <option value="{{ $branch->id }}">{{ $branch->name }}</option>
                    @endforeach
                </select>
            </div>
        </div>
    </div>

    <!-- Flash Messages -->
    @if (session()->has('message'))
        <div class="bg-green-100 dark:bg-green-900/30 border border-green-400 dark:border-green-700 text-green-700 dark:text-green-300 px-4 py-3 rounded-lg">
            {{ session('message') }}
        </div>
    @endif

    @if (session()->has('error'))
        <div class="bg-red-100 dark:bg-red-900/30 border border-red-400 dark:border-red-700 text-red-700 dark:text-red-300 px-4 py-3 rounded-lg">
            {{ session('error') }}
        </div>
    @endif

    <!-- Period Status -->
    @if ($currentPeriod)
        <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-4">
            <div class="flex items-center justify-between">
                <div>
                    <p class="font-semibold text-blue-800 dark:text-blue-300">Current Accounting Period</p>
                    <p class="text-blue-600 dark:text-blue-400">{{ $currentPeriod->name ?? "{$currentPeriod->month}/{$currentPeriod->year}" }} ({{ ucfirst($currentPeriod->status) }})</p>
                </div>
                @if ($alreadyPosted)
                    <span class="px-3 py-1 bg-yellow-100 dark:bg-yellow-900/30 text-yellow-800 dark:text-yellow-300 rounded-full text-sm font-medium">
                        Inventory Already Posted
                    </span>
                @endif
            </div>
        </div>
    @else
        <div class="bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-lg p-4">
            <p class="text-yellow-800 dark:text-yellow-300 font-medium">No open accounting period. Please create one before posting inventory values.</p>
        </div>
    @endif

    <!-- Summary Cards -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-md p-6 border-l-4 border-blue-500">
            <p class="text-zinc-600 dark:text-zinc-400 text-sm font-semibold mb-2">TOTAL INVENTORY VALUE</p>
            <p class="text-2xl font-bold text-blue-600 dark:text-blue-400">{{ $this->formatCurrency($inventoryValuation['grand_total']) }}</p>
        </div>

        <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-md p-6 border-l-4 border-green-500">
            <p class="text-zinc-600 dark:text-zinc-400 text-sm font-semibold mb-2">STOCK ITEMS</p>
            <p class="text-2xl font-bold text-green-600 dark:text-green-400">{{ number_format($inventoryValuation['stock_count']) }}</p>
        </div>

        <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-md p-6 border-l-4 border-purple-500">
            <p class="text-zinc-600 dark:text-zinc-400 text-sm font-semibold mb-2">CATEGORIES</p>
            <p class="text-2xl font-bold text-purple-600 dark:text-purple-400">{{ count(array_filter($inventoryValuation['categories'], fn($c) => $c['total_value'] > 0)) }}</p>
        </div>

        <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-md p-6 border-l-4 border-orange-500">
            <p class="text-zinc-600 dark:text-zinc-400 text-sm font-semibold mb-2">SELECTED TO POST</p>
            <p class="text-2xl font-bold text-orange-600 dark:text-orange-400">
                {{ $this->formatCurrency(collect($selectedCategories)->sum(fn($c) => $categoryTotals[$c] ?? 0)) }}
            </p>
        </div>
    </div>

    <!-- Inventory Categories -->
    <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-md overflow-hidden">
        <div class="p-4 border-b border-zinc-200 dark:border-zinc-700 flex items-center justify-between">
            <h3 class="text-lg font-semibold text-zinc-900 dark:text-white">Inventory by Category</h3>
            <div class="flex gap-2">
                <button wire:click="selectAllCategories" class="px-3 py-1 text-sm bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300 rounded hover:bg-blue-200 dark:hover:bg-blue-900/50 transition">
                    Select All
                </button>
                <button wire:click="deselectAllCategories" class="px-3 py-1 text-sm bg-zinc-100 dark:bg-zinc-700 text-zinc-700 dark:text-zinc-300 rounded hover:bg-zinc-200 dark:hover:bg-zinc-600 transition">
                    Deselect All
                </button>
            </div>
        </div>

        <div class="divide-y divide-zinc-200 dark:divide-zinc-700">
            @foreach ($inventoryValuation['categories'] as $categoryKey => $category)
                @if ($category['total_value'] > 0)
                    <div class="p-4 hover:bg-zinc-50 dark:hover:bg-zinc-700/30 transition">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-4">
                                <input
                                    type="checkbox"
                                    wire:click="toggleCategory('{{ $categoryKey }}')"
                                    {{ in_array($categoryKey, $selectedCategories) ? 'checked' : '' }}
                                    class="w-5 h-5 text-blue-600 bg-zinc-100 border-zinc-300 rounded focus:ring-blue-500 dark:focus:ring-blue-600 dark:ring-offset-zinc-800 focus:ring-2 dark:bg-zinc-700 dark:border-zinc-600"
                                >
                                <div>
                                    <h4 class="font-semibold text-zinc-900 dark:text-white">{{ $category['name'] }}</h4>
                                    <p class="text-sm text-zinc-500 dark:text-zinc-400">
                                        General Ledger Account: {{ $category['gl_account'] }} | Items: {{ count($category['items']) }} | Qty: {{ number_format($category['total_qty'], 2) }}
                                    </p>
                                </div>
                            </div>
                            <div class="text-right">
                                <p class="text-xl font-bold text-zinc-900 dark:text-white">{{ $this->formatCurrency($category['total_value']) }}</p>
                                <p class="text-xs text-zinc-500 dark:text-zinc-400">Total Value</p>
                            </div>
                        </div>

                        <!-- Expandable Item List -->
                        @if (count($category['items']) > 0)
                            <details class="mt-3">
                                <summary class="cursor-pointer text-sm text-blue-600 dark:text-blue-400 hover:underline">
                                    View {{ count($category['items']) }} items
                                </summary>
                                <div class="mt-2 overflow-x-auto">
                                    <table class="w-full text-sm">
                                        <thead class="bg-zinc-100 dark:bg-zinc-700/50">
                                            <tr>
                                                <th class="px-3 py-2 text-left text-zinc-700 dark:text-zinc-300">Item</th>
                                                <th class="px-3 py-2 text-left text-zinc-700 dark:text-zinc-300">SKU</th>
                                                <th class="px-3 py-2 text-left text-zinc-700 dark:text-zinc-300">Branch</th>
                                                <th class="px-3 py-2 text-right text-zinc-700 dark:text-zinc-300">Qty</th>
                                                <th class="px-3 py-2 text-right text-zinc-700 dark:text-zinc-300">Avg Cost</th>
                                                <th class="px-3 py-2 text-right text-zinc-700 dark:text-zinc-300">Value</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                                            @foreach ($category['items'] as $item)
                                                <tr>
                                                    <td class="px-3 py-2 text-zinc-900 dark:text-white">{{ $item['item_name'] }}</td>
                                                    <td class="px-3 py-2 font-mono text-xs text-zinc-600 dark:text-zinc-400">{{ $item['item_sku'] }}</td>
                                                    <td class="px-3 py-2 text-zinc-600 dark:text-zinc-400">{{ $item['branch_name'] }}</td>
                                                    <td class="px-3 py-2 text-right text-zinc-900 dark:text-white">{{ number_format($item['quantity'], 2) }}</td>
                                                    <td class="px-3 py-2 text-right font-mono text-zinc-900 dark:text-white">{{ $this->formatCurrency($item['average_cost']) }}</td>
                                                    <td class="px-3 py-2 text-right font-mono font-semibold text-zinc-900 dark:text-white">{{ $this->formatCurrency($item['value']) }}</td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </details>
                        @endif
                    </div>
                @endif
            @endforeach
        </div>

        @if ($inventoryValuation['grand_total'] == 0)
            <div class="p-8 text-center">
                <div class="text-zinc-400 dark:text-zinc-500 mb-4">
                    <svg class="w-16 h-16 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"></path>
                    </svg>
                </div>
                <h3 class="text-lg font-semibold text-zinc-700 dark:text-zinc-300 mb-2">No Inventory Found</h3>
                <p class="text-zinc-500 dark:text-zinc-400">No stock items with available quantity found for the selected criteria.</p>
            </div>
        @endif
    </div>

    <!-- Posting Description -->
    @if (count($selectedCategories) > 0)
        <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-md p-6">
            <h3 class="text-lg font-semibold text-zinc-900 dark:text-white mb-4">Posting Details</h3>
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Description</label>
                    <input type="text" wire:model="description" class="w-full px-4 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white" placeholder="Enter posting description">
                </div>

                <div class="bg-zinc-50 dark:bg-zinc-700/30 rounded-lg p-4">
                    <h4 class="font-semibold text-zinc-900 dark:text-white mb-2">Journal Entry Preview</h4>
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b border-zinc-200 dark:border-zinc-600">
                                <th class="py-2 text-left text-zinc-700 dark:text-zinc-300">Account</th>
                                <th class="py-2 text-right text-zinc-700 dark:text-zinc-300">Debit</th>
                                <th class="py-2 text-right text-zinc-700 dark:text-zinc-300">Credit</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($selectedCategories as $catKey)
                                @if (isset($categoryTotals[$catKey]) && $categoryTotals[$catKey] > 0)
                                    <tr class="border-b border-zinc-100 dark:border-zinc-700">
                                        <td class="py-2 text-zinc-900 dark:text-white">
                                            {{ $glAccountMappings[$catKey] ?? '1300' }} - {{ $inventoryValuation['categories'][$catKey]['name'] ?? 'Inventory' }}
                                        </td>
                                        <td class="py-2 text-right font-mono text-green-600 dark:text-green-400">{{ $this->formatCurrency($categoryTotals[$catKey]) }}</td>
                                        <td class="py-2 text-right">-</td>
                                    </tr>
                                @endif
                            @endforeach
                            <tr class="border-b border-zinc-100 dark:border-zinc-700">
                                <td class="py-2 text-zinc-900 dark:text-white">3010 - Opening Balance Equity</td>
                                <td class="py-2 text-right">-</td>
                                <td class="py-2 text-right font-mono text-red-600 dark:text-red-400">
                                    {{ $this->formatCurrency(collect($selectedCategories)->sum(fn($c) => $categoryTotals[$c] ?? 0)) }}
                                </td>
                            </tr>
                        </tbody>
                        <tfoot class="font-bold">
                            <tr>
                                <td class="py-2 text-zinc-900 dark:text-white">TOTAL</td>
                                <td class="py-2 text-right font-mono text-zinc-900 dark:text-white">
                                    {{ $this->formatCurrency(collect($selectedCategories)->sum(fn($c) => $categoryTotals[$c] ?? 0)) }}
                                </td>
                                <td class="py-2 text-right font-mono text-zinc-900 dark:text-white">
                                    {{ $this->formatCurrency(collect($selectedCategories)->sum(fn($c) => $categoryTotals[$c] ?? 0)) }}
                                </td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    @endif

    <!-- Posting History -->
    @if (!empty($postingHistory))
        <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-md overflow-hidden">
            <div class="p-4 border-b border-zinc-200 dark:border-zinc-700 bg-yellow-50 dark:bg-yellow-900/20">
                <h3 class="text-lg font-semibold text-yellow-800 dark:text-yellow-300">Previous Postings for This Period</h3>
                <p class="text-sm text-yellow-600 dark:text-yellow-400">Inventory has already been posted. Review before posting again.</p>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-zinc-50 dark:bg-zinc-700/50">
                        <tr>
                            <th class="px-4 py-3 text-left text-zinc-700 dark:text-zinc-300">Date</th>
                            <th class="px-4 py-3 text-left text-zinc-700 dark:text-zinc-300">Reference</th>
                            <th class="px-4 py-3 text-left text-zinc-700 dark:text-zinc-300">Account</th>
                            <th class="px-4 py-3 text-left text-zinc-700 dark:text-zinc-300">Description</th>
                            <th class="px-4 py-3 text-right text-zinc-700 dark:text-zinc-300">Amount</th>
                            <th class="px-4 py-3 text-center text-zinc-700 dark:text-zinc-300">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                        @foreach ($postingHistory as $posting)
                            <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-700/30">
                                <td class="px-4 py-3 text-zinc-600 dark:text-zinc-400">{{ $posting['date'] }}</td>
                                <td class="px-4 py-3 font-mono text-xs text-zinc-600 dark:text-zinc-400">{{ $posting['reference'] }}</td>
                                <td class="px-4 py-3 text-zinc-900 dark:text-white">
                                    <span class="font-mono text-xs">{{ $posting['account'] }}</span> - {{ $posting['account_name'] }}
                                </td>
                                <td class="px-4 py-3 text-zinc-600 dark:text-zinc-400">{{ Str::limit($posting['description'], 30) }}</td>
                                <td class="px-4 py-3 text-right font-mono font-semibold text-zinc-900 dark:text-white">{{ $this->formatCurrency($posting['amount']) }}</td>
                                <td class="px-4 py-3 text-center">
                                    <button wire:click="reversePosting({{ $posting['id'] }})" wire:confirm="Are you sure you want to reverse this entry?" class="px-2 py-1 text-xs bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-300 rounded hover:bg-red-200 dark:hover:bg-red-900/50 transition">
                                        Reverse
                                    </button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot class="bg-zinc-100 dark:bg-zinc-700">
                        <tr>
                            <td colspan="4" class="px-4 py-3 font-semibold text-zinc-900 dark:text-white">Total Previously Posted</td>
                            <td class="px-4 py-3 text-right font-mono font-bold text-zinc-900 dark:text-white">{{ $this->formatCurrency(collect($postingHistory)->sum('amount')) }}</td>
                            <td></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    @endif

    <!-- Post Button -->
    <div class="flex justify-end gap-3">
        <a href="{{ route('branch-dashboard.accounting.dashboard') }}" class="px-6 py-3 bg-zinc-200 dark:bg-zinc-700 text-zinc-700 dark:text-zinc-300 rounded-lg hover:bg-zinc-300 dark:hover:bg-zinc-600 transition">
            Cancel
        </a>
        <button
            wire:click="confirmPosting"
            {{ count($selectedCategories) == 0 || !$currentPeriod ? 'disabled' : '' }}
            class="px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition disabled:opacity-50 disabled:cursor-not-allowed"
        >
            {{ $alreadyPosted ? 'Post Additional Entries' : 'Post to General Ledger' }}
        </button>
    </div>

    <!-- Confirmation Modal -->
    @if ($showConfirmModal)
        <div class="fixed inset-0 bg-black/50 flex items-center justify-center z-50" wire:key="confirm-modal">
            <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-xl max-w-md w-full mx-4 p-6">
                <h3 class="text-lg font-bold text-zinc-900 dark:text-white mb-4">Confirm Posting</h3>
                <p class="text-zinc-600 dark:text-zinc-400 mb-4">
                    You are about to post inventory valuation of
                    <span class="font-bold text-zinc-900 dark:text-white">{{ $this->formatCurrency(collect($selectedCategories)->sum(fn($c) => $categoryTotals[$c] ?? 0)) }}</span>
                    to the General Ledger.
                </p>
                <p class="text-sm text-yellow-600 dark:text-yellow-400 mb-4">
                    This will create journal entries that affect your financial statements.
                </p>
                <div class="flex justify-end gap-3">
                    <button wire:click="cancelPosting" wire:loading.attr="disabled" wire:target="cancelPosting" class="px-4 py-2 bg-zinc-200 dark:bg-zinc-700 text-zinc-700 dark:text-zinc-300 rounded-lg hover:bg-zinc-300 dark:hover:bg-zinc-600 transition disabled:opacity-50">
                        Cancel
                    </button>
                    <button wire:click="postToGL" wire:loading.attr="disabled" wire:target="postToGL" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition disabled:opacity-50 flex items-center gap-2">
                        <span wire:loading wire:target="postToGL">
                            <svg class="animate-spin h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                        </span>
                        <span wire:loading.remove wire:target="postToGL">Confirm & Post</span>
                        <span wire:loading wire:target="postToGL">Posting...</span>
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>
