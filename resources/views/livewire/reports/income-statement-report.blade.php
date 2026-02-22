<div class="space-y-6">
    <div class="bg-white rounded-lg shadow-md p-6">
        <h2 class="text-2xl font-bold mb-4">Income Statement (P&L) Report</h2>

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
                    <input type="checkbox" wire:model="compareWithPrevious" class="form-checkbox" @disabled(!$selectedPeriodId)>
                    <span class="ml-2 text-sm text-gray-700">Compare with Previous</span>
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
            <div class="space-y-6">
                <!-- Income Statement -->
                <div class="overflow-x-auto">
                    <h3 class="text-lg font-bold mb-4">Income Statement for {{ $reportData['period'] }}</h3>

                    <table class="w-full text-sm">
                        <tbody>
                            <!-- Revenue Section -->
                            <tr class="border-b font-semibold bg-blue-50">
                                <td colspan="2" class="px-4 py-2">REVENUE</td>
                                <td class="px-4 py-2 text-right"></td>
                            </tr>
                            @foreach ($reportData['revenue']['items'] as $item)
                                <tr class="border-b">
                                    <td class="px-6 py-2">{{ $item['account_number'] }}</td>
                                    <td class="px-4 py-2">{{ $item['account_name'] }}</td>
                                    <td class="px-4 py-2 text-right">{{ number_format($item['balance'], 2) }}</td>
                                </tr>
                            @endforeach
                            <tr class="border-b font-semibold bg-gray-50">
                                <td colspan="2" class="px-4 py-2">Total Revenue</td>
                                <td class="px-4 py-2 text-right">{{ number_format($reportData['revenue']['total'], 2) }}</td>
                            </tr>

                            <!-- COGS Section -->
                            <tr class="border-b font-semibold bg-orange-50">
                                <td colspan="2" class="px-4 py-2">COST OF GOODS SOLD</td>
                                <td class="px-4 py-2 text-right"></td>
                            </tr>
                            @foreach ($reportData['cost_of_goods_sold']['items'] as $item)
                                <tr class="border-b">
                                    <td class="px-6 py-2">{{ $item['account_number'] }}</td>
                                    <td class="px-4 py-2">{{ $item['account_name'] }}</td>
                                    <td class="px-4 py-2 text-right">{{ number_format($item['balance'], 2) }}</td>
                                </tr>
                            @endforeach
                            <tr class="border-b font-semibold bg-gray-50">
                                <td colspan="2" class="px-4 py-2">Total COGS</td>
                                <td class="px-4 py-2 text-right">{{ number_format($reportData['cost_of_goods_sold']['total'], 2) }}</td>
                            </tr>

                            <!-- Gross Profit -->
                            <tr class="border-b font-bold bg-green-50">
                                <td colspan="2" class="px-4 py-2">GROSS PROFIT</td>
                                <td class="px-4 py-2 text-right">{{ number_format($reportData['gross_profit'], 2) }}</td>
                            </tr>

                            <!-- Operating Expenses -->
                            <tr class="border-b font-semibold bg-red-50">
                                <td colspan="2" class="px-4 py-2">OPERATING EXPENSES</td>
                                <td class="px-4 py-2 text-right"></td>
                            </tr>
                            @foreach ($reportData['operating_expenses']['items'] as $item)
                                <tr class="border-b">
                                    <td class="px-6 py-2">{{ $item['account_number'] }}</td>
                                    <td class="px-4 py-2">{{ $item['account_name'] }}</td>
                                    <td class="px-4 py-2 text-right">{{ number_format($item['balance'], 2) }}</td>
                                </tr>
                            @endforeach
                            <tr class="border-b font-semibold bg-gray-50">
                                <td colspan="2" class="px-4 py-2">Total Operating Expenses</td>
                                <td class="px-4 py-2 text-right">{{ number_format($reportData['operating_expenses']['total'], 2) }}</td>
                            </tr>

                            <!-- Operating Income -->
                            <tr class="border-b font-bold bg-blue-100">
                                <td colspan="2" class="px-4 py-2">OPERATING INCOME</td>
                                <td class="px-4 py-2 text-right">{{ number_format($reportData['operating_income'], 2) }}</td>
                            </tr>

                            <!-- Other Income/Expenses -->
                            @if (!empty($reportData['other_income']['items']))
                                <tr class="border-b font-semibold bg-green-50">
                                    <td colspan="2" class="px-4 py-2">OTHER INCOME</td>
                                    <td class="px-4 py-2 text-right"></td>
                                </tr>
                                @foreach ($reportData['other_income']['items'] as $item)
                                    <tr class="border-b">
                                        <td class="px-6 py-2">{{ $item['account_number'] }}</td>
                                        <td class="px-4 py-2">{{ $item['account_name'] }}</td>
                                        <td class="px-4 py-2 text-right">{{ number_format($item['balance'], 2) }}</td>
                                    </tr>
                                @endforeach
                                <tr class="border-b font-semibold bg-gray-50">
                                    <td colspan="2" class="px-4 py-2">Total Other Income</td>
                                    <td class="px-4 py-2 text-right">{{ number_format($reportData['other_income']['total'], 2) }}</td>
                                </tr>
                            @endif

                            @if (!empty($reportData['other_expenses']['items']))
                                <tr class="border-b font-semibold bg-red-50">
                                    <td colspan="2" class="px-4 py-2">OTHER EXPENSES</td>
                                    <td class="px-4 py-2 text-right"></td>
                                </tr>
                                @foreach ($reportData['other_expenses']['items'] as $item)
                                    <tr class="border-b">
                                        <td class="px-6 py-2">{{ $item['account_number'] }}</td>
                                        <td class="px-4 py-2">{{ $item['account_name'] }}</td>
                                        <td class="px-4 py-2 text-right">{{ number_format($item['balance'], 2) }}</td>
                                    </tr>
                                @endforeach
                                <tr class="border-b font-semibold bg-gray-50">
                                    <td colspan="2" class="px-4 py-2">Total Other Expenses</td>
                                    <td class="px-4 py-2 text-right">{{ number_format($reportData['other_expenses']['total'], 2) }}</td>
                                </tr>
                            @endif

                            <!-- Net Income -->
                            <tr class="border-t-2 border-gray-300 font-bold bg-yellow-50">
                                <td colspan="2" class="px-4 py-2">NET INCOME / (LOSS)</td>
                                <td class="px-4 py-2 text-right text-lg">{{ number_format($reportData['net_income'], 2) }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Key Metrics -->
                <div class="grid grid-cols-2 md:grid-cols-5 gap-4">
                    <div class="bg-blue-50 p-4 rounded">
                        <p class="text-xs text-gray-600">Gross Margin %</p>
                        <p class="text-lg font-bold">{{ number_format($reportData['summary']['gross_margin'], 1) }}%</p>
                    </div>
                    <div class="bg-green-50 p-4 rounded">
                        <p class="text-xs text-gray-600">Operating Margin %</p>
                        <p class="text-lg font-bold">{{ number_format($reportData['summary']['operating_margin'], 1) }}%</p>
                    </div>
                    <div class="bg-purple-50 p-4 rounded">
                        <p class="text-xs text-gray-600">Net Margin %</p>
                        <p class="text-lg font-bold">{{ number_format($reportData['summary']['net_margin'], 1) }}%</p>
                    </div>
                </div>
            </div>
        @elseif ($showReport)
            <div class="p-4 bg-yellow-50 border border-yellow-200 rounded">
                No data found for the selected period.
            </div>
        @endif
    </div>
</div>
