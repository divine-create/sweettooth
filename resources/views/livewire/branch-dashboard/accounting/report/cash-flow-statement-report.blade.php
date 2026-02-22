<div>
    <div class="mb-8">
        <div class="flex items-center justify-between mb-6">
            <div>
                <h1 class="text-3xl font-bold text-gray-900 dark:text-white">Cash Flow Statement</h1>
                <p class="text-gray-600 dark:text-gray-400 mt-1">Track cash movements across operating, investing, and financing activities</p>
            </div>
            <div class="flex items-center gap-3">
                <x-button wire:click="exportToCsv" class="inline-flex items-center gap-2 px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg font-medium transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                    Export CSV
                </x-button>
            </div>
        </div>

        <!-- Filters Section -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6 mb-8">
            <div class="flex flex-col sm:flex-row gap-4 items-end">
                <div class="flex-1">
                    <label for="startDate" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Start Date</label>
                    <input id="startDate" type="date" wire:model.live="startDate"
                           class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:text-white transition-colors">
                </div>
                <div class="flex-1">
                    <label for="endDate" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">End Date</label>
                    <input id="endDate" type="date" wire:model.live="endDate"
                           class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:text-white transition-colors">
                </div>
                <div class="flex-shrink-0">
                    <button wire:click="resetFilters" class="px-4 py-2 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300 rounded-lg font-medium transition-colors">
                        Reset
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Cash Flow Activities Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
        <!-- Operating Activities -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
            <div class="bg-gradient-to-r from-blue-500 to-blue-600 p-6">
                <div class="flex items-center gap-3">
                    <div class="p-2 bg-white/20 rounded-lg">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/>
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-lg font-semibold text-white">Operating Activities</h3>
                        <p class="text-blue-100 text-sm">Core business operations</p>
                    </div>
                </div>
            </div>
            <div class="p-6">
                <div class="space-y-3 mb-6">
                    @foreach($cfs['operating_activities'] ?? [] as $item => $amount)
                        <div class="flex justify-between items-center py-2 border-b border-gray-100 dark:border-gray-700 last:border-b-0">
                            <span class="text-sm text-gray-600 dark:text-gray-400">{{ $item }}</span>
                            <span class="font-medium text-gray-900 dark:text-white">{{ number_format($amount, 2) }}</span>
                        </div>
                    @endforeach
                </div>
                <div class="bg-blue-50 dark:bg-blue-900/20 rounded-lg p-4 border border-blue-200 dark:border-blue-800">
                    <div class="flex justify-between items-center">
                        <span class="text-sm font-medium text-blue-800 dark:text-blue-300">Net Cash Flow</span>
                        <span class="text-lg font-bold text-blue-900 dark:text-blue-200">{{ number_format($cfs['operating']['total'], 2) }}</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Investing Activities -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
            <div class="bg-gradient-to-r from-green-500 to-green-600 p-6">
                <div class="flex items-center gap-3">
                    <div class="p-2 bg-white/20 rounded-lg">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-lg font-semibold text-white">Investing Activities</h3>
                        <p class="text-green-100 text-sm">Asset purchases & sales</p>
                    </div>
                </div>
            </div>
            <div class="p-6">
                <div class="space-y-3 mb-6">
                    @foreach($cfs['investing_activities'] ?? [] as $item => $amount)
                        <div class="flex justify-between items-center py-2 border-b border-gray-100 dark:border-gray-700 last:border-b-0">
                            <span class="text-sm text-gray-600 dark:text-gray-400">{{ $item }}</span>
                            <span class="font-medium text-gray-900 dark:text-white">{{ number_format($amount, 2) }}</span>
                        </div>
                    @endforeach
                </div>
                <div class="bg-green-50 dark:bg-green-900/20 rounded-lg p-4 border border-green-200 dark:border-green-800">
                    <div class="flex justify-between items-center">
                        <span class="text-sm font-medium text-green-800 dark:text-green-300">Net Cash Flow</span>
                        <span class="text-lg font-bold text-green-900 dark:text-green-200">{{ number_format($cfs['investing']['total'], 2) }}</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Financing Activities -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
            <div class="bg-gradient-to-r from-purple-500 to-purple-600 p-6">
                <div class="flex items-center gap-3">
                    <div class="p-2 bg-white/20 rounded-lg">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"/>
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-lg font-semibold text-white">Financing Activities</h3>
                        <p class="text-purple-100 text-sm">Debt & equity transactions</p>
                    </div>
                </div>
            </div>
            <div class="p-6">
                <div class="space-y-3 mb-6">
                    @foreach($cfs['financing_activities'] ?? [] as $item => $amount)
                        <div class="flex justify-between items-center py-2 border-b border-gray-100 dark:border-gray-700 last:border-b-0">
                            <span class="text-sm text-gray-600 dark:text-gray-400">{{ $item }}</span>
                            <span class="font-medium text-gray-900 dark:text-white">{{ number_format($amount, 2) }}</span>
                        </div>
                    @endforeach
                </div>
                <div class="bg-purple-50 dark:bg-purple-900/20 rounded-lg p-4 border border-purple-200 dark:border-purple-800">
                    <div class="flex justify-between items-center">
                        <span class="text-sm font-medium text-purple-800 dark:text-purple-300">Net Cash Flow</span>
                        <span class="text-lg font-bold text-purple-900 dark:text-purple-200">{{ number_format($cfs['financing']['total'], 2) }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Cash Flow Summary -->
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-8 mb-8">
        <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-6 flex items-center gap-3">
            <svg class="w-6 h-6 text-gray-600 dark:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
            </svg>
            Cash Flow Summary
        </h2>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div class="text-center p-6 bg-gradient-to-br from-blue-50 to-blue-100 dark:from-blue-900/20 dark:to-blue-800/20 rounded-lg border border-blue-200 dark:border-blue-800">
                <div class="text-3xl font-bold text-blue-900 dark:text-blue-200 mb-2">{{ number_format($cfs['opening_cash'], 2) }}</div>
                <div class="text-sm text-blue-700 dark:text-blue-300">Opening Cash Balance</div>
            </div>

            <div class="text-center p-6 bg-gradient-to-br from-{{ $cfs['net_change'] >= 0 ? 'green' : 'red' }}-50 to-{{ $cfs['net_change'] >= 0 ? 'green' : 'red' }}-100 dark:from-{{ $cfs['net_change'] >= 0 ? 'green' : 'red' }}-900/20 dark:to-{{ $cfs['net_change'] >= 0 ? 'green' : 'red' }}-800/20 rounded-lg border border-{{ $cfs['net_change'] >= 0 ? 'green' : 'red' }}-200 dark:border-{{ $cfs['net_change'] >= 0 ? 'green' : 'red' }}-800">
                <div class="text-3xl font-bold {{ $cfs['net_change'] >= 0 ? 'text-green-900 dark:text-green-200' : 'text-red-900 dark:text-red-200' }} mb-2">{{ number_format($cfs['net_change'], 2) }}</div>
                <div class="text-sm {{ $cfs['net_change'] >= 0 ? 'text-green-700 dark:text-green-300' : 'text-red-700 dark:text-red-300' }}">Net Cash Change</div>
            </div>

            <div class="text-center p-6 bg-gradient-to-br from-indigo-50 to-indigo-100 dark:from-indigo-900/20 dark:to-indigo-800/20 rounded-lg border border-indigo-200 dark:border-indigo-800">
                <div class="text-3xl font-bold text-indigo-900 dark:text-indigo-200 mb-2">{{ number_format($cfs['closing_cash'], 2) }}</div>
                <div class="text-sm text-indigo-700 dark:text-indigo-300">Closing Cash Balance</div>
            </div>
        </div>
    </div>

    <!-- Detailed Tables Section -->
    <div class="grid grid-cols-1 xl:grid-cols-2 gap-8">
        <!-- Bank Positions -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
            <div class="bg-gradient-to-r from-slate-600 to-slate-700 p-6">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <div class="p-2 bg-white/20 rounded-lg">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/>
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-lg font-semibold text-white">Bank Positions</h3>
                            <p class="text-slate-200 text-sm">Account balances & transactions</p>
                        </div>
                    </div>
                    <button wire:click="toggleBankSummary" class="px-3 py-1 bg-white/20 hover:bg-white/30 text-white rounded-lg text-sm font-medium transition-colors">
                        {{ $showBankSummary ? 'Hide' : 'Show' }}
                    </button>
                </div>
            </div>

            @if ($showBankSummary)
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-gray-50 dark:bg-gray-700">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider">Account</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider">Opening</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider">Deposits</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider">Withdrawals</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider">Closing</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                            @foreach($bankPositions ?? [] as $position)
                                @if(is_array($position) && isset($position['opening_balance']))
                                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white">{{ $position['bank_account'] ?? 'N/A' }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600 dark:text-gray-400">{{ number_format($position['opening_balance'], 2) }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-green-600 dark:text-green-400 font-medium">{{ number_format($position['total_deposits'], 2) }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-red-600 dark:text-red-400 font-medium">{{ number_format($position['total_withdrawals'], 2) }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-semibold text-gray-900 dark:text-white">{{ number_format($position['closing_balance'], 2) }}</td>
                                </tr>
                                @endif
                            @endforeach
                            @if(empty($bankPositions))
                                <tr>
                                    <td colspan="5" class="px-6 py-8 text-center text-sm text-gray-500 dark:text-gray-400">
                                        <svg class="mx-auto h-8 w-8 text-gray-400 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                        </svg>
                                        No bank positions available for the selected period.
                                    </td>
                                </tr>
                            @endif
                        </tbody>
                    </table>
                </div>
            @else
                <div class="p-8 text-center text-gray-500 dark:text-gray-400">
                    <svg class="mx-auto h-12 w-12 text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/>
                    </svg>
                    <p>Click "Show" to view detailed bank positions</p>
                </div>
            @endif
        </div>

        <!-- Cash Positions -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
            <div class="bg-gradient-to-r from-slate-600 to-slate-700 p-6">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <div class="p-2 bg-white/20 rounded-lg">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/>
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-lg font-semibold text-white">Cash Positions</h3>
                            <p class="text-slate-200 text-sm">Cash flow by location</p>
                        </div>
                    </div>
                    <button wire:click="toggleCashSummary" class="px-3 py-1 bg-white/20 hover:bg-white/30 text-white rounded-lg text-sm font-medium transition-colors">
                        {{ $showCashSummary ? 'Hide' : 'Show' }}
                    </button>
                </div>
            </div>

            @if ($showCashSummary)
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-gray-50 dark:bg-gray-700">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider">Location</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider">Opening</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider">Inflows</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider">Outflows</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider">Closing</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                            @foreach($cashPositions ?? [] as $position)
                                @if(is_array($position) && isset($position['opening_balance']))
                                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white">{{ $position['location'] ?? 'N/A' }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600 dark:text-gray-400">{{ number_format($position['opening_balance'], 2) }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-green-600 dark:text-green-400 font-medium">{{ number_format($position['total_inflows'], 2) }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-red-600 dark:text-red-400 font-medium">{{ number_format($position['total_outflows'], 2) }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-semibold text-gray-900 dark:text-white">{{ number_format($position['closing_balance'], 2) }}</td>
                                </tr>
                                @endif
                            @endforeach
                            @if(empty($cashPositions))
                                <tr>
                                    <td colspan="5" class="px-6 py-8 text-center text-sm text-gray-500 dark:text-gray-400">
                                        <svg class="mx-auto h-8 w-8 text-gray-400 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/>
                                        </svg>
                                        No cash positions available for the selected period.
                                    </td>
                                </tr>
                            @endif
                        </tbody>
                    </table>
                </div>
            @else
                <div class="p-8 text-center text-gray-500 dark:text-gray-400">
                    <svg class="mx-auto h-12 w-12 text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/>
                    </svg>
                    <p>Click "Show" to view detailed cash positions</p>
                </div>
            @endif
        </div>
    </div>
</div>