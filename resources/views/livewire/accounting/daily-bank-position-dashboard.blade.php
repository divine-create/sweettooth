<div class="space-y-6">
    <div class="flex justify-between items-center">
        <div>
            <h1 class="text-3xl font-bold text-gray-900">Daily Bank Positions</h1>
            <p class="mt-2 text-gray-600">Track bank account balances and daily movements</p>
        </div>
    </div>

    <!-- Date Range Selection -->
    <div class="bg-white rounded-lg shadow p-6">
        <h2 class="text-lg font-semibold text-gray-900 mb-4">Filter Options</h2>
        
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <!-- Date Range Selector -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Quick Range</label>
                <select 
                    wire:change="setDateRange($event.target.value)"
                    class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                >
                    <option value="today" {{ $dateRange === 'today' ? 'selected' : '' }}>Today</option>
                    <option value="week" {{ $dateRange === 'week' ? 'selected' : '' }}>This Week</option>
                    <option value="month" {{ $dateRange === 'month' ? 'selected' : '' }}>This Month</option>
                    <option value="custom" {{ $dateRange === 'custom' ? 'selected' : '' }}>Custom</option>
                </select>
            </div>

            <!-- Start Date -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Start Date</label>
                <input 
                    type="date"
                    wire:model.live="startDate"
                    class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                />
            </div>

            <!-- End Date -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">End Date</label>
                <input 
                    type="date"
                    wire:model.live="endDate"
                    class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                />
            </div>
        </div>

        <!-- Bank Selection -->
        <div class="mt-4">
            <label class="block text-sm font-medium text-gray-700 mb-3">Filter by Bank</label>
            <div class="flex flex-wrap gap-2">
                <button
                    wire:click="selectBank(null)"
                    @class([
                        'px-4 py-2 rounded-md font-medium transition',
                        'bg-blue-600 text-white' => $selectedBankId === null,
                        'bg-gray-200 text-gray-800' => $selectedBankId !== null,
                    ])
                >
                    All Banks
                </button>
                
                @foreach ($bankAccounts as $account)
                    <button
                        wire:click="selectBank({{ $account->id }})"
                        @class([
                            'px-4 py-2 rounded-md font-medium transition',
                            'bg-blue-600 text-white' => $selectedBankId === $account->id,
                            'bg-gray-200 text-gray-800' => $selectedBankId !== $account->id,
                        ])
                    >
                        {{ $account->account_name }}
                    </button>
                @endforeach
            </div>
        </div>
    </div>

    <!-- Today's Summary -->
    @if (count($todaysSummary) > 0)
        <div class="grid grid-cols-1 md:grid-cols-{{ min(count($todaysSummary), 3) }} gap-4">
            @foreach ($todaysSummary as $summary)
                <div class="bg-white rounded-lg shadow p-6">
                    <h3 class="text-lg font-semibold text-gray-900">{{ $summary['bank'] }}</h3>
                    
                    <div class="mt-4 space-y-3">
                        <div class="flex justify-between">
                            <span class="text-gray-600">Opening Balance:</span>
                            <span class="font-semibold text-gray-900">{{ number_format($summary['opening_balance'], 2) }}</span>
                        </div>
                        
                        <div class="flex justify-between">
                            <span class="text-gray-600">Inflows:</span>
                            <span class="font-semibold text-green-600">{{ number_format($summary['total_inflows'], 2) }}</span>
                        </div>
                        
                        <div class="flex justify-between">
                            <span class="text-gray-600">Outflows:</span>
                            <span class="font-semibold text-red-600">{{ number_format($summary['total_outflows'], 2) }}</span>
                        </div>
                        
                        <div class="border-t pt-3 flex justify-between">
                            <span class="text-gray-600 font-medium">Closing Balance:</span>
                            <span class="font-bold text-lg text-gray-900">{{ number_format($summary['closing_balance'], 2) }}</span>
                        </div>
                        
                        @if ($summary['pending_items'] > 0)
                            <div class="mt-3 p-2 bg-yellow-50 rounded">
                                <span class="text-sm text-yellow-800">{{ $summary['pending_items'] }} pending items</span>
                            </div>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    @endif

    <!-- Bank Positions Table -->
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200">
            <h2 class="text-lg font-semibold text-gray-900">Bank Position History</h2>
        </div>
        
        @if (count($bankPositions) > 0)
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50 border-b border-gray-200">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Bank Account</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Opening Balance</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Inflows</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Outflows</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Closing Balance</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Pending</th>
                        </tr>
                    </thead>
                    
                    <tbody class="divide-y divide-gray-200">
                        @foreach ($bankPositions as $position)
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 text-sm text-gray-900">
                                    {{ $position->position_date->format('M d, Y') }}
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-900">
                                    {{ $position->bankAccount->account_name ?? 'N/A' }}
                                </td>
                                <td class="px-6 py-4 text-sm text-right text-gray-900">
                                    {{ number_format($position->opening_balance ?? 0, 2) }}
                                </td>
                                <td class="px-6 py-4 text-sm text-right text-green-600 font-medium">
                                    {{ number_format($position->total_inflows ?? 0, 2) }}
                                </td>
                                <td class="px-6 py-4 text-sm text-right text-red-600 font-medium">
                                    {{ number_format($position->total_outflows ?? 0, 2) }}
                                </td>
                                <td class="px-6 py-4 text-sm text-right font-semibold text-gray-900">
                                    {{ number_format($position->closing_balance ?? 0, 2) }}
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-600">
                                    {{ $position->pending_items_count ?? 0 }} items
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            
            <!-- Pagination -->
            <div class="px-6 py-4 border-t border-gray-200">
                {{ $bankPositions->links() }}
            </div>
        @else
            <div class="px-6 py-12 text-center">
                <p class="text-gray-500">No bank positions found for the selected period.</p>
            </div>
        @endif
    </div>
</div>
