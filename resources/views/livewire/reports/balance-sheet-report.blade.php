<div class="space-y-6">
    <div class="bg-white rounded-lg shadow-md p-6">
        <h2 class="text-2xl font-bold mb-4">Balance Sheet (Statement of Financial Position)</h2>

        <!-- Filters -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Accounting Period</label>
                <select wire:model="selectedPeriodId" class="w-full px-3 py-2 border border-gray-300 rounded-md">
                    <option value="">Current Date</option>
                    @foreach ($periods as $period)
                        <option value="{{ $period->id }}">{{ $period->getDisplayName() }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">As Of Date</label>
                <input type="date" wire:model="asOfDate" class="w-full px-3 py-2 border border-gray-300 rounded-md">
            </div>

            <div class="flex items-center">
                <label class="flex items-center">
                    <input type="checkbox" wire:model="compareWithPrevious" class="form-checkbox" @disabled(!$selectedPeriodId)>
                    <span class="ml-2 text-sm text-gray-700">Compare with Previous</span>
                </label>
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
            <div class="space-y-6">
                <!-- Balance Check Alert -->
                @if (!$reportData['balance_check']['is_balanced'])
                    <div class="p-4 bg-red-50 border border-red-200 rounded">
                        <p class="text-red-800 font-semibold">⚠️ Balance Sheet NOT Balanced</p>
                        <p class="text-red-700">Difference: {{ number_format($reportData['balance_check']['difference'], 2) }}</p>
                    </div>
                @else
                    <div class="p-4 bg-green-50 border border-green-200 rounded">
                        <p class="text-green-800 font-semibold">✓ Balance Sheet is Balanced</p>
                    </div>
                @endif

                <!-- Balance Sheet -->
                <div class="overflow-x-auto">
                    <h3 class="text-lg font-bold mb-4">As of {{ $reportData['as_of_date'] ?? now()->format('Y-m-d') }}</h3>

                    <table class="w-full text-sm">
                        <tbody>
                            <!-- Assets -->
                            <tr class="border-b font-bold bg-blue-50">
                                <td colspan="2" class="px-4 py-3">ASSETS</td>
                                <td class="px-4 py-3 text-right"></td>
                            </tr>
                            @foreach ($reportData['assets']['items'] as $asset)
                                <tr class="border-b">
                                    <td class="px-6 py-2">{{ $asset['account_number'] }}</td>
                                    <td class="px-4 py-2">{{ $asset['account_name'] }}</td>
                                    <td class="px-4 py-2 text-right">{{ number_format($asset['balance'], 2) }}</td>
                                </tr>
                            @endforeach
                            <tr class="border-b font-bold bg-gray-100">
                                <td colspan="2" class="px-4 py-2">Total Assets</td>
                                <td class="px-4 py-2 text-right text-lg">{{ number_format($reportData['totals']['total_assets'], 2) }}</td>
                            </tr>

                            <!-- Liabilities -->
                            <tr class="border-b font-bold bg-red-50 mt-4">
                                <td colspan="2" class="px-4 py-3">LIABILITIES</td>
                                <td class="px-4 py-3 text-right"></td>
                            </tr>
                            @foreach ($reportData['liabilities']['items'] as $liability)
                                <tr class="border-b">
                                    <td class="px-6 py-2">{{ $liability['account_number'] }}</td>
                                    <td class="px-4 py-2">{{ $liability['account_name'] }}</td>
                                    <td class="px-4 py-2 text-right">{{ number_format($liability['balance'], 2) }}</td>
                                </tr>
                            @endforeach
                            <tr class="border-b font-bold bg-gray-100">
                                <td colspan="2" class="px-4 py-2">Total Liabilities</td>
                                <td class="px-4 py-2 text-right">{{ number_format($reportData['totals']['total_liabilities'], 2) }}</td>
                            </tr>

                            <!-- Equity -->
                            <tr class="border-b font-bold bg-green-50">
                                <td colspan="2" class="px-4 py-3">EQUITY</td>
                                <td class="px-4 py-3 text-right"></td>
                            </tr>
                            @foreach ($reportData['equity']['items'] as $equity)
                                <tr class="border-b">
                                    <td class="px-6 py-2">{{ $equity['account_number'] }}</td>
                                    <td class="px-4 py-2">{{ $equity['account_name'] }}</td>
                                    <td class="px-4 py-2 text-right">{{ number_format($equity['balance'], 2) }}</td>
                                </tr>
                            @endforeach
                            <tr class="border-b font-bold bg-gray-100">
                                <td colspan="2" class="px-4 py-2">Total Equity</td>
                                <td class="px-4 py-2 text-right">{{ number_format($reportData['totals']['total_equity'], 2) }}</td>
                            </tr>

                            <!-- Total Liabilities & Equity -->
                            <tr class="border-t-2 border-gray-300 font-bold bg-yellow-50">
                                <td colspan="2" class="px-4 py-2">Total Liabilities & Equity</td>
                                <td class="px-4 py-2 text-right text-lg">{{ number_format($reportData['totals']['total_liabilities_equity'], 2) }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Key Ratios -->
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                    <div class="bg-blue-50 p-4 rounded">
                        <p class="text-xs text-gray-600">Current Ratio</p>
                        <p class="text-lg font-bold">-</p>
                    </div>
                    <div class="bg-green-50 p-4 rounded">
                        <p class="text-xs text-gray-600">Debt-to-Equity</p>
                        <p class="text-lg font-bold">{{ number_format($reportData['totals']['total_liabilities'] > 0 ? $reportData['totals']['total_liabilities'] / max(1, $reportData['totals']['total_equity']) : 0, 2) }}</p>
                    </div>
                    <div class="bg-purple-50 p-4 rounded">
                        <p class="text-xs text-gray-600">Equity Ratio</p>
                        <p class="text-lg font-bold">{{ number_format(($reportData['totals']['total_assets'] > 0 ? ($reportData['totals']['total_equity'] / $reportData['totals']['total_assets']) * 100 : 0), 1) }}%</p>
                    </div>
                    <div class="bg-yellow-50 p-4 rounded">
                        <p class="text-xs text-gray-600">Asset Turnover</p>
                        <p class="text-lg font-bold">-</p>
                    </div>
                </div>
            </div>
        @elseif ($showReport)
            <div class="p-4 bg-yellow-50 border border-yellow-200 rounded">
                No data found for the selected criteria.
            </div>
        @endif
    </div>
</div>
