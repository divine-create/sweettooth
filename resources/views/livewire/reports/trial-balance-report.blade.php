<div class="space-y-6">
    <div class="bg-white rounded-lg shadow-md p-6">
        <h2 class="text-2xl font-bold mb-4">Trial Balance Report</h2>

        <!-- Filters -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Accounting Period</label>
                <select wire:model="selectedPeriodId" class="w-full px-3 py-2 border border-gray-300 rounded-md">
                    <option value="">Current Period</option>
                    @foreach ($periods as $period)
                        <option value="{{ $period->id }}">{{ $period->getDisplayName() }}</option>
                    @endforeach
                </select>
            </div>

            <div class="flex items-center">
                <label class="flex items-center">
                    <input type="checkbox" wire:model="withHierarchy" class="form-checkbox">
                    <span class="ml-2 text-sm text-gray-700">Show Hierarchy</span>
                </label>
            </div>

            <div></div>
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
                <!-- Balance Check Alert -->
                @if (!$reportData['summary']['is_balanced'])
                    <div class="mb-4 p-4 bg-red-50 border border-red-200 rounded">
                        <p class="text-red-800 font-semibold">⚠️ Trial Balance NOT Balanced</p>
                        <p class="text-red-700">Difference: {{ number_format($reportData['summary']['difference'], 2) }}</p>
                    </div>
                @else
                    <div class="mb-4 p-4 bg-green-50 border border-green-200 rounded">
                        <p class="text-green-800 font-semibold">✓ Trial Balance is Balanced</p>
                    </div>
                @endif

                <table class="w-full text-sm">
                    <thead class="bg-gray-100 border-b-2 border-gray-300">
                        <tr>
                            <th class="px-4 py-2 text-left">Account Number</th>
                            <th class="px-4 py-2 text-left">Account Name</th>
                            <th class="px-4 py-2 text-right">Debit</th>
                            <th class="px-4 py-2 text-right">Credit</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($reportData['accounts'] as $account)
                            <tr class="border-b hover:bg-gray-50">
                                <td class="px-4 py-2">{{ $account['account_number'] }}</td>
                                <td class="px-4 py-2">{{ $account['account_name'] }}</td>
                                <td class="px-4 py-2 text-right">{{ number_format($account['debit'], 2) }}</td>
                                <td class="px-4 py-2 text-right">{{ number_format($account['credit'], 2) }}</td>
                            </tr>
                        @endforeach
                        <tr class="border-t-2 border-gray-300 font-bold bg-gray-50">
                            <td colspan="2" class="px-4 py-2">TOTALS</td>
                            <td class="px-4 py-2 text-right">{{ number_format($reportData['summary']['total_debits'], 2) }}</td>
                            <td class="px-4 py-2 text-right">{{ number_format($reportData['summary']['total_credits'], 2) }}</td>
                        </tr>
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
