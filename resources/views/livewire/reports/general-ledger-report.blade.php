<div class="space-y-6">
    <div class="bg-white rounded-lg shadow-md p-6">
        <h2 class="text-2xl font-bold mb-4">General Ledger Report</h2>

        <!-- Filters -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">General Ledger Account</label>
                <select wire:model="selectedAccountId" class="w-full px-3 py-2 border border-gray-300 rounded-md">
                    <option value="">All Accounts</option>
                    @foreach ($accounts as $account)
                        <option value="{{ $account->id }}">{{ $account->account_number }} - {{ $account->account_name }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Accounting Period</label>
                <select wire:model="selectedPeriodId" class="w-full px-3 py-2 border border-gray-300 rounded-md">
                    <option value="">All Periods</option>
                    @foreach ($periods as $period)
                        <option value="{{ $period->id }}">{{ $period->getDisplayName() }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Start Date</label>
                <input type="date" wire:model="startDate" class="w-full px-3 py-2 border border-gray-300 rounded-md">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">End Date</label>
                <input type="date" wire:model="endDate" class="w-full px-3 py-2 border border-gray-300 rounded-md">
            </div>
        </div>

        <!-- Action Buttons -->
        <div class="flex gap-2 mb-6">
            <button wire:click="generateReport" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">
                Generate Report
            </button>
            <button wire:click="exportToCsv" class="px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700">
                Export CSV
            </button>
            <button wire:click="resetFilters" class="px-4 py-2 bg-gray-400 text-white rounded-md hover:bg-gray-500">
                Reset
            </button>
        </div>

        <!-- Report Output -->
        @if ($showReport && !empty($reportData))
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-gray-100 border-b-2 border-gray-300">
                        <tr>
                            <th class="px-4 py-2 text-left">Account Number</th>
                            <th class="px-4 py-2 text-left">Account Name</th>
                            <th class="px-4 py-2 text-left">Type</th>
                            <th class="px-4 py-2 text-right">Debit</th>
                            <th class="px-4 py-2 text-right">Credit</th>
                            <th class="px-4 py-2 text-right">Balance</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($reportData as $account)
                            <tr class="border-b hover:bg-gray-50">
                                <td class="px-4 py-2">{{ $account['account_number'] }}</td>
                                <td class="px-4 py-2">{{ $account['account_name'] }}</td>
                                <td class="px-4 py-2">{{ ucfirst($account['account_type']) }}</td>
                                <td class="px-4 py-2 text-right">{{ number_format($account['total_debit'], 2) }}</td>
                                <td class="px-4 py-2 text-right">{{ number_format($account['total_credit'], 2) }}</td>
                                <td class="px-4 py-2 text-right font-semibold">{{ number_format($account['balance'], 2) }}</td>
                            </tr>
                            @foreach ($account['entries'] as $entry)
                                <tr class="border-b bg-gray-50 text-xs">
                                    <td colspan="2" class="px-6 py-1">{{ $entry['date']->format('Y-m-d') }} - {{ $entry['reference'] }}</td>
                                    <td class="px-4 py-1">{{ substr($entry['description'], 0, 20) }}...</td>
                                    <td class="px-4 py-1 text-right">{{ number_format($entry['debit'], 2) }}</td>
                                    <td class="px-4 py-1 text-right">{{ number_format($entry['credit'], 2) }}</td>
                                    <td class="px-4 py-1 text-right">{{ number_format($entry['balance'], 2) }}</td>
                                </tr>
                            @endforeach
                        @endforeach
                    </tbody>
                </table>
            </div>
        @elseif ($showReport)
            <div class="p-4 bg-yellow-50 border border-yellow-200 rounded">
                No data found for the selected criteria.
            </div>
        @endif
    </div>
</div>
