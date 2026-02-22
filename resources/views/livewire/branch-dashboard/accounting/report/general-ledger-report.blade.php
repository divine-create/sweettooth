<div class="space-y-6">
    <!-- Header -->
    <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-md p-6">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold text-zinc-900 dark:text-white">General Ledger</h1>
                <p class="text-zinc-600 dark:text-zinc-400">Detailed view of all journal entries and transactions</p>
            </div>
            <div class="flex items-center gap-2">
                <button wire:click="exportToCsv" class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition">
                    Export CSV
                </button>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-md p-6">
        <div class="grid grid-cols-1 md:grid-cols-5 gap-4">
            <div>
                <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Start Date</label>
                <input type="date" wire:model.live="startDate" class="w-full px-4 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white">
            </div>
            <div>
                <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">End Date</label>
                <input type="date" wire:model.live="endDate" class="w-full px-4 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white">
            </div>
            <div>
                <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">General Ledger Account</label>
                <select wire:model.live="glAccountId" class="w-full px-4 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white">
                    <option value="">All Accounts</option>
                    @foreach ($glAccounts as $account)
                        <option value="{{ $account->id }}">{{ $account->account_number }} - {{ $account->account_name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Period</label>
                <select wire:model.live="periodId" class="w-full px-4 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white">
                    <option value="">All Periods</option>
                    @foreach ($periods as $period)
                        <option value="{{ $period->id }}">{{ $period->name ?? "{$period->month}/{$period->year}" }}</option>
                    @endforeach
                </select>
            </div>
            <div class="flex items-end">
                <button wire:click="resetFilters" class="w-full px-4 py-2 bg-zinc-200 dark:bg-zinc-700 text-zinc-700 dark:text-zinc-300 rounded-lg hover:bg-zinc-300 dark:hover:bg-zinc-600 transition">
                    Reset Filters
                </button>
            </div>
        </div>
    </div>

    <!-- Summary -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-md p-6 border-l-4 border-blue-500">
            <p class="text-zinc-600 dark:text-zinc-400 text-sm font-semibold mb-2">TOTAL ENTRIES</p>
            <p class="text-2xl font-bold text-blue-600 dark:text-blue-400">{{ $entries->total() }}</p>
        </div>
        <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-md p-6 border-l-4 border-green-500">
            <p class="text-zinc-600 dark:text-zinc-400 text-sm font-semibold mb-2">TOTAL DEBITS</p>
            <p class="text-2xl font-bold text-green-600 dark:text-green-400">{{ number_format($totalDebit ?? 0, 2) }}</p>
        </div>
        <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-md p-6 border-l-4 border-red-500">
            <p class="text-zinc-600 dark:text-zinc-400 text-sm font-semibold mb-2">TOTAL CREDITS</p>
            <p class="text-2xl font-bold text-red-600 dark:text-red-400">{{ number_format($totalCredit ?? 0, 2) }}</p>
        </div>
    </div>

    <!-- Entries Table -->
    <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-md overflow-hidden">
        <div class="p-4 border-b border-zinc-200 dark:border-zinc-700">
            <h3 class="text-lg font-semibold text-zinc-900 dark:text-white">Journal Entries</h3>
        </div>

        @if ($entries->count() > 0)
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-zinc-50 dark:bg-zinc-700/50 border-b border-zinc-200 dark:border-zinc-700">
                        <tr>
                            <th class="px-4 py-3 text-left font-semibold text-zinc-900 dark:text-white">Date</th>
                            <th class="px-4 py-3 text-left font-semibold text-zinc-900 dark:text-white">Account</th>
                            <th class="px-4 py-3 text-left font-semibold text-zinc-900 dark:text-white">Description</th>
                            <th class="px-4 py-3 text-left font-semibold text-zinc-900 dark:text-white">Reference</th>
                            <th class="px-4 py-3 text-left font-semibold text-zinc-900 dark:text-white">Type</th>
                            <th class="px-4 py-3 text-right font-semibold text-zinc-900 dark:text-white">Debit</th>
                            <th class="px-4 py-3 text-right font-semibold text-zinc-900 dark:text-white">Credit</th>
                            <th class="px-4 py-3 text-center font-semibold text-zinc-900 dark:text-white">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                        @foreach ($entries as $entry)
                            <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-700/30 transition">
                                <td class="px-4 py-3 text-zinc-700 dark:text-zinc-300 whitespace-nowrap">
                                    {{ $entry->entry_date ? $entry->entry_date->format('M d, Y') : 'N/A' }}
                                </td>
                                <td class="px-4 py-3">
                                    <div class="font-mono text-sm text-zinc-600 dark:text-zinc-400">{{ $entry->glAccount->account_number ?? 'N/A' }}</div>
                                    <div class="text-xs text-zinc-500 dark:text-zinc-400">{{ Str::limit($entry->glAccount->account_name ?? '', 25) }}</div>
                                </td>
                                <td class="px-4 py-3 text-zinc-700 dark:text-zinc-300 max-w-xs truncate">
                                    {{ Str::limit($entry->description, 40) }}
                                </td>
                                <td class="px-4 py-3 font-mono text-xs text-zinc-600 dark:text-zinc-400">
                                    {{ $entry->reference_number ?? '-' }}
                                </td>
                                <td class="px-4 py-3">
                                    <span class="px-2 py-1 text-xs font-medium rounded-full
                                        @if($entry->entry_type === 'sale') bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300
                                        @elseif($entry->entry_type === 'purchase') bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300
                                        @elseif($entry->entry_type === 'payment') bg-purple-100 text-purple-800 dark:bg-purple-900/30 dark:text-purple-300
                                        @elseif($entry->entry_type === 'manual') bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-300
                                        @elseif($entry->entry_type === 'reversal') bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-300
                                        @else bg-zinc-100 text-zinc-800 dark:bg-zinc-900/30 dark:text-zinc-300
                                        @endif">
                                        {{ ucfirst($entry->entry_type ?? 'N/A') }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-right font-mono text-zinc-900 dark:text-white">
                                    {{ $entry->debit > 0 ? number_format($entry->debit, 2) : '-' }}
                                </td>
                                <td class="px-4 py-3 text-right font-mono text-zinc-900 dark:text-white">
                                    {{ $entry->credit > 0 ? number_format($entry->credit, 2) : '-' }}
                                </td>
                                <td class="px-4 py-3 text-center">
                                    <span class="px-2 py-1 text-xs font-medium rounded-full
                                        @if($entry->status === 'posted') bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300
                                        @elseif($entry->status === 'draft') bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-300
                                        @else bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-300
                                        @endif">
                                        {{ ucfirst($entry->status) }}
                                    </span>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div class="p-4 border-t border-zinc-200 dark:border-zinc-700">
                {{ $entries->links() }}
            </div>
        @else
            <div class="p-8 text-center">
                <div class="text-zinc-400 dark:text-zinc-500 mb-4">
                    <svg class="w-16 h-16 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                    </svg>
                </div>
                <h3 class="text-lg font-semibold text-zinc-700 dark:text-zinc-300 mb-2">No Entries Found</h3>
                <p class="text-zinc-500 dark:text-zinc-400">No journal entries match your filter criteria. Try adjusting your filters or create new entries.</p>
                <div class="mt-4 flex justify-center gap-3">
                    <a href="{{ route('branch-dashboard.accounting.journal-entry') }}" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition">
                        Create Journal Entry
                    </a>
                    <button wire:click="resetFilters" class="px-4 py-2 bg-zinc-200 dark:bg-zinc-700 text-zinc-700 dark:text-zinc-300 rounded-lg hover:bg-zinc-300 dark:hover:bg-zinc-600 transition">
                        Reset Filters
                    </button>
                </div>
            </div>
        @endif
    </div>
</div>
