<div class="space-y-6">
    <div class="flex justify-between items-center">
        <div>
            <h1 class="text-3xl font-bold text-gray-900">Cash Position Management</h1>
            <p class="mt-2 text-gray-600">Record and track daily cash counts</p>
        </div>
        
        <button
            wire:click="toggleCreateForm"
            @class([
                'px-4 py-2 rounded-md font-medium transition',
                'bg-red-600 text-white hover:bg-red-700' => $showCreateForm,
                'bg-blue-600 text-white hover:bg-blue-700' => !$showCreateForm,
            ])
        >
            {{ $showCreateForm ? 'Cancel' : 'Record Cash Count' }}
        </button>
    </div>

    <!-- Flash Messages -->
    @if (session()->has('success'))
        <div class="bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-lg">
            {{ session('success') }}
        </div>
    @endif

    @if (session()->has('error'))
        <div class="bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-lg">
            {{ session('error') }}
        </div>
    @endif

    <!-- Create Cash Count Form -->
    @if ($showCreateForm)
        <div class="bg-white rounded-lg shadow p-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-6">Record Cash Count</h2>
            
            <form wire:submit="saveCashCount" class="space-y-4">
                <!-- Cash Type -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Cash Type</label>
                    <select 
                        wire:model="cashType"
                        class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                    >
                        <option value="sales_cash">Sales Cash</option>
                        <option value="petty_cash">Petty Cash</option>
                        <option value="other">Other</option>
                    </select>
                    @error('cashType') <span class="text-red-600 text-sm mt-1">{{ $message }}</span> @enderror
                </div>

                <!-- Cash Count -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Physical Cash Count</label>
                    <input 
                        type="number"
                        wire:model="cashCount"
                        step="0.01"
                        placeholder="0.00"
                        class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                    />
                    @error('cashCount') <span class="text-red-600 text-sm mt-1">{{ $message }}</span> @enderror
                </div>

                <!-- Notes -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Notes (Optional)</label>
                    <textarea 
                        wire:model="notes"
                        rows="3"
                        placeholder="Add any notes or discrepancies..."
                        class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                    ></textarea>
                </div>

                <!-- Submit Button -->
                <div class="flex gap-3">
                    <button
                        type="submit"
                        class="px-4 py-2 bg-blue-600 text-white rounded-md font-medium hover:bg-blue-700 transition"
                    >
                        Save Cash Count
                    </button>
                    
                    <button
                        type="button"
                        wire:click="toggleCreateForm"
                        class="px-4 py-2 bg-gray-200 text-gray-800 rounded-md font-medium hover:bg-gray-300 transition"
                    >
                        Cancel
                    </button>
                </div>
            </form>
        </div>
    @endif

    <!-- Today's Summary -->
    @if (count($todaysCashSummary) > 0)
        <div class="bg-white rounded-lg shadow p-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">Today's Cash Summary</h2>
            
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                @foreach ($todaysCashSummary as $cash)
                    <div class="border rounded-lg p-4">
                        <h3 class="font-semibold text-gray-900">{{ $cash['type'] }}</h3>
                        
                        <div class="mt-3 space-y-2">
                            <div class="flex justify-between">
                                <span class="text-sm text-gray-600">Physical Count:</span>
                                <span class="font-semibold text-gray-900">{{ number_format($cash['physical_count'], 2) }}</span>
                            </div>
                            
                            <div class="flex justify-between">
                                <span class="text-sm text-gray-600">Variance:</span>
                                <span @class([
                                    'font-semibold',
                                    'text-green-600' => $cash['variance'] >= 0,
                                    'text-red-600' => $cash['variance'] < 0,
                                ])>
                                    {{ $cash['variance'] >= 0 ? '+' : '' }}{{ number_format($cash['variance'], 2) }}
                                </span>
                            </div>
                            
                            <div class="text-xs text-gray-500 pt-2 border-t">
                                Last updated: {{ $cash['last_updated'] }}
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    <!-- Date Range Selection -->
    <div class="bg-white rounded-lg shadow p-6">
        <h2 class="text-lg font-semibold text-gray-900 mb-4">Filter History</h2>
        
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Quick Range</label>
                <select 
                    wire:change="setDateRange($event.target.value)"
                    class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                >
                    <option value="today" {{ $dateRange === 'today' ? 'selected' : '' }}>Today</option>
                    <option value="week" {{ $dateRange === 'week' ? 'selected' : '' }}>This Week</option>
                    <option value="month" {{ $dateRange === 'month' ? 'selected' : '' }}>This Month</option>
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Start Date</label>
                <input 
                    type="date"
                    wire:model.live="startDate"
                    class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                />
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">End Date</label>
                <input 
                    type="date"
                    wire:model.live="endDate"
                    class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                />
            </div>
        </div>
    </div>

    <!-- Cash Positions History -->
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200">
            <h2 class="text-lg font-semibold text-gray-900">Cash Count History</h2>
        </div>
        
        @if (count($cashPositions) > 0)
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50 border-b border-gray-200">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date & Time</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Cash Type</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Physical Count</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Book Balance</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Variance</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Recorded By</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Notes</th>
                        </tr>
                    </thead>
                    
                    <tbody class="divide-y divide-gray-200">
                        @foreach ($cashPositions as $position)
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 text-sm text-gray-900 whitespace-nowrap">
                                    {{ $position->position_date->format('M d, Y H:i') }}
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-900">
                                    {{ ucwords(str_replace('_', ' ', $position->cash_type)) }}
                                </td>
                                <td class="px-6 py-4 text-sm text-right text-gray-900 font-semibold">
                                    {{ number_format($position->physical_count ?? 0, 2) }}
                                </td>
                                <td class="px-6 py-4 text-sm text-right text-gray-900">
                                    {{ number_format($position->book_balance ?? 0, 2) }}
                                </td>
                                <td class="px-6 py-4 text-sm text-right font-medium">
                                    @php
                                        $variance = ($position->book_balance ?? 0) - ($position->physical_count ?? 0);
                                    @endphp
                                    <span @class([
                                        'text-green-600' => $variance >= 0,
                                        'text-red-600' => $variance < 0,
                                    ])>
                                        {{ $variance >= 0 ? '+' : '' }}{{ number_format($variance, 2) }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-900">
                                    {{ $position->recordedBy?->name ?? 'System' }}
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-600 max-w-xs truncate">
                                    {{ $position->notes ?? '-' }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            
            <!-- Pagination -->
            <div class="px-6 py-4 border-t border-gray-200">
                {{ $cashPositions->links() }}
            </div>
        @else
            <div class="px-6 py-12 text-center">
                <p class="text-gray-500">No cash count records found for the selected period.</p>
            </div>
        @endif
    </div>
</div>
