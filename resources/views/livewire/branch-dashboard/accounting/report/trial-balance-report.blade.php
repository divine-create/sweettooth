<div class="space-y-6">
    <!-- Header -->
    <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-md p-6">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold text-zinc-900 dark:text-white">Trial Balance</h1>
                <p class="text-zinc-600 dark:text-zinc-400">Review account balances and verify debits equal credits</p>
            </div>
            <div class="flex items-center gap-3">
                <select wire:model.live="periodId" class="px-4 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white">
                    <option value="">All Periods</option>
                    @foreach ($periods as $period)
                        <option value="{{ $period->id }}">{{ $period->name ?? "{$period->month}/{$period->year}" }}</option>
                    @endforeach
                </select>
                <button wire:click="toggleComparative" class="px-4 py-2 {{ $isComparative ? 'bg-blue-600 text-white' : 'bg-zinc-200 dark:bg-zinc-700 text-zinc-700 dark:text-zinc-300' }} rounded-lg hover:opacity-90 transition">
                    Comparative
                </button>
                <button wire:click="exportToCsv" class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition">
                    Export CSV
                </button>
            </div>
        </div>
    </div>

    <!-- Balance Status -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-md p-6 border-l-4 border-blue-500">
            <p class="text-zinc-600 dark:text-zinc-400 text-sm font-semibold mb-2">TOTAL DEBITS</p>
            <p class="text-2xl font-bold text-blue-600 dark:text-blue-400">{{ number_format($data['total_debits'] ?? 0, 2) }}</p>
        </div>

        <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-md p-6 border-l-4 border-red-500">
            <p class="text-zinc-600 dark:text-zinc-400 text-sm font-semibold mb-2">TOTAL CREDITS</p>
            <p class="text-2xl font-bold text-red-600 dark:text-red-400">{{ number_format($data['total_credits'] ?? 0, 2) }}</p>
        </div>

        <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-md p-6 border-l-4 {{ ($data['balanced'] ?? false) ? 'border-green-500' : 'border-yellow-500' }}">
            <p class="text-zinc-600 dark:text-zinc-400 text-sm font-semibold mb-2">DIFFERENCE</p>
            <p class="text-2xl font-bold {{ ($data['balanced'] ?? false) ? 'text-green-600 dark:text-green-400' : 'text-yellow-600 dark:text-yellow-400' }}">
                {{ number_format(abs($data['difference'] ?? 0), 2) }}
            </p>
        </div>

        <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-md p-6 border-l-4 {{ ($data['balanced'] ?? false) ? 'border-green-500' : 'border-red-500' }}">
            <p class="text-zinc-600 dark:text-zinc-400 text-sm font-semibold mb-2">STATUS</p>
            <p class="text-2xl font-bold {{ ($data['balanced'] ?? false) ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                {{ ($data['balanced'] ?? false) ? 'Balanced' : 'Not Balanced' }}
            </p>
        </div>
    </div>

    <!-- Trial Balance Table -->
    <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-md overflow-hidden">
        <div class="p-4 border-b border-zinc-200 dark:border-zinc-700">
            <h3 class="text-lg font-semibold text-zinc-900 dark:text-white">Account Balances</h3>
        </div>

        @if (!empty($data['accounts']) && count($data['accounts']) > 0)
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-zinc-50 dark:bg-zinc-700/50 border-b border-zinc-200 dark:border-zinc-700">
                        <tr>
                            <th class="px-4 py-3 text-left font-semibold text-zinc-900 dark:text-white">Account Number</th>
                            <th class="px-4 py-3 text-left font-semibold text-zinc-900 dark:text-white">Account Name</th>
                            <th class="px-4 py-3 text-left font-semibold text-zinc-900 dark:text-white">Type</th>
                            <th class="px-4 py-3 text-right font-semibold text-zinc-900 dark:text-white">Debit</th>
                            <th class="px-4 py-3 text-right font-semibold text-zinc-900 dark:text-white">Credit</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                        @foreach ($data['accounts'] as $account)
                            <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-700/30 transition">
                                <td class="px-4 py-3 font-mono text-zinc-700 dark:text-zinc-300">{{ $account['account_number'] }}</td>
                                <td class="px-4 py-3 text-zinc-900 dark:text-white">{{ $account['account_name'] }}</td>
                                <td class="px-4 py-3">
                                    <span class="px-2 py-1 text-xs font-medium rounded-full
                                        @if(str_starts_with($account['account_number'], '1')) bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300
                                        @elseif(str_starts_with($account['account_number'], '2')) bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-300
                                        @elseif(str_starts_with($account['account_number'], '3')) bg-purple-100 text-purple-800 dark:bg-purple-900/30 dark:text-purple-300
                                        @elseif(str_starts_with($account['account_number'], '4')) bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300
                                        @else bg-orange-100 text-orange-800 dark:bg-orange-900/30 dark:text-orange-300
                                        @endif">
                                        {{ ucfirst($account['account_type'] ?? 'N/A') }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-right font-mono text-zinc-900 dark:text-white">
                                    {{ $account['debit'] > 0 ? number_format($account['debit'], 2) : '-' }}
                                </td>
                                <td class="px-4 py-3 text-right font-mono text-zinc-900 dark:text-white">
                                    {{ $account['credit'] > 0 ? number_format($account['credit'], 2) : '-' }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot class="bg-zinc-100 dark:bg-zinc-700 font-bold">
                        <tr>
                            <td colspan="3" class="px-4 py-3 text-zinc-900 dark:text-white">TOTALS</td>
                            <td class="px-4 py-3 text-right font-mono text-zinc-900 dark:text-white">{{ number_format($data['total_debits'] ?? 0, 2) }}</td>
                            <td class="px-4 py-3 text-right font-mono text-zinc-900 dark:text-white">{{ number_format($data['total_credits'] ?? 0, 2) }}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        @else
            <div class="p-8 text-center">
                <div class="text-zinc-400 dark:text-zinc-500 mb-4">
                    <svg class="w-16 h-16 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                    </svg>
                </div>
                <h3 class="text-lg font-semibold text-zinc-700 dark:text-zinc-300 mb-2">No Data Available</h3>
                <p class="text-zinc-500 dark:text-zinc-400">No General Ledger entries have been posted for this period. Post transactions from Sales, Purchases, or create manual journal entries.</p>
                <div class="mt-4 flex justify-center gap-3">
                    <a href="{{ route('branch-dashboard.accounting.journal-entry') }}" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition">
                        Create Journal Entry
                    </a>
                    <a href="{{ route('branch-dashboard.accounting.posting-status') }}" class="px-4 py-2 bg-zinc-200 dark:bg-zinc-700 text-zinc-700 dark:text-zinc-300 rounded-lg hover:bg-zinc-300 dark:hover:bg-zinc-600 transition">
                        View Posting Status
                    </a>
                </div>
            </div>
        @endif
    </div>

    <!-- Balancing Report -->
    @if (!empty($balancingReport))
        <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-md p-6">
            <h3 class="text-lg font-semibold text-zinc-900 dark:text-white mb-4">Balancing Report</h3>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-sm">
                <div>
                    <p class="text-zinc-600 dark:text-zinc-400">Entry Count</p>
                    <p class="font-semibold text-zinc-900 dark:text-white">{{ $balancingReport['entry_count'] ?? 0 }}</p>
                </div>
                <div>
                    <p class="text-zinc-600 dark:text-zinc-400">Accounts with Entries</p>
                    <p class="font-semibold text-zinc-900 dark:text-white">{{ $balancingReport['accounts_with_entries'] ?? 0 }}</p>
                </div>
                <div>
                    <p class="text-zinc-600 dark:text-zinc-400">Total Debits</p>
                    <p class="font-semibold text-zinc-900 dark:text-white">{{ number_format($balancingReport['total_debits'] ?? 0, 2) }}</p>
                </div>
                <div>
                    <p class="text-zinc-600 dark:text-zinc-400">Total Credits</p>
                    <p class="font-semibold text-zinc-900 dark:text-white">{{ number_format($balancingReport['total_credits'] ?? 0, 2) }}</p>
                </div>
            </div>
        </div>
    @endif
</div>
