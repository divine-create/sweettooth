<div>
    <div class="p-6 bg-gray-50 dark:bg-gray-900 min-h-screen">
        <!-- Header -->
        <div class="mb-6">
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white">Production Shift Closing - {{ $departmentName }}</h1>
            <p class="text-gray-600 dark:text-gray-400 mt-1">Close your production shift and finalize records</p>
        </div>

        @if(!$currentShiftId)
            <div class="bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-lg p-4">
                <p class="text-yellow-800 dark:text-yellow-300">No active shift found. Please clock in first.</p>
            </div>
        @else
            <!-- Shift Info -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4 mb-6">
                <div class="grid grid-cols-4 gap-4">
                    <div>
                        <span class="text-sm text-gray-600 dark:text-gray-400">Date:</span>
                        <span class="ml-2 font-semibold text-gray-900 dark:text-gray-100">{{ \Carbon\Carbon::parse($shiftDate)->format('M d, Y') }}</span>
                    </div>
                    <div>
                        <span class="text-sm text-gray-600 dark:text-gray-400">Shift:</span>
                        <span class="ml-2 font-semibold text-gray-900 dark:text-gray-100">{{ ucfirst($shiftType) }}</span>
                    </div>
                    <div>
                        <span class="text-sm text-gray-600 dark:text-gray-400">Department:</span>
                        <span class="ml-2 font-semibold text-gray-900 dark:text-gray-100">{{ $departmentName }}</span>
                    </div>
                    <div>
                        <span class="text-sm text-gray-600 dark:text-gray-400">Status:</span>
                        <span class="ml-2 px-2 py-1 text-xs rounded-full {{ $isVerified ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800' }}">
                            {{ $isVerified ? 'Closed' : 'Active' }}
                        </span>
                    </div>
                </div>
            </div>

            <!-- Material Efficiency Overview -->
            @if(!empty($rawMaterialUsage))
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                <div class="bg-gradient-to-br from-blue-50 to-blue-100 dark:from-blue-900/20 dark:to-blue-800/20 rounded-lg shadow p-6">
                    <p class="text-sm text-gray-600 dark:text-gray-400">Material Efficiency</p>
                    <p class="text-3xl font-bold mt-2 {{ $totalMaterialEfficiency <= 105 ? 'text-green-600' : 'text-red-600' }}">
                        {{ number_format($totalMaterialEfficiency, 1) }}%
                    </p>
                    <p class="text-xs text-gray-500 mt-1">100% = Perfect | &lt;100% = Under-use | &gt;100% = Overuse</p>
                </div>
                <div class="bg-gradient-to-br from-yellow-50 to-amber-100 dark:from-yellow-900/20 dark:to-amber-800/20 rounded-lg shadow p-6">
                    <p class="text-sm text-gray-600 dark:text-gray-400">Materials Tracked</p>
                    <p class="text-3xl font-bold text-gray-900 dark:text-white mt-2">{{ count($rawMaterialUsage) }}</p>
                    <p class="text-xs text-gray-500 mt-1">Ingredients monitored</p>
                </div>
                <div class="bg-gradient-to-br from-red-50 to-orange-100 dark:from-red-900/20 dark:to-orange-800/20 rounded-lg shadow p-6">
                    <p class="text-sm text-gray-600 dark:text-gray-400">Variance Alerts</p>
                    <p class="text-3xl font-bold text-red-600 mt-2">{{ count($materialVariances) }}</p>
                    <p class="text-xs text-gray-500 mt-1">Items with significant variance</p>
                </div>
            </div>
            @endif

            <!-- Variance Alerts -->
            @if(!empty($materialVariances))
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6 mb-6">
                <h3 class="text-lg font-semibold dark:text-white mb-4 flex items-center">
                    <svg class="w-5 h-5 text-red-600 mr-2" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                    </svg>
                    Material Variance Alerts
                </h3>
                <div class="grid gap-3">
                    @foreach($materialVariances as $variance)
                    <div class="border-l-4 {{ $variance['severity'] === 'high' ? 'border-red-500 bg-red-50 dark:bg-red-900/20' : 'border-yellow-500 bg-yellow-50 dark:bg-yellow-900/20' }} p-4 rounded">
                        <div class="flex justify-between items-start">
                            <div>
                                <p class="font-semibold text-gray-900 dark:text-white">{{ $variance['item_name'] }} ({{ $variance['item_code'] }})</p>
                                <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                                    <span class="{{ $variance['type'] === 'overuse' ? 'text-red-600' : 'text-blue-600' }}">
                                        {{ $variance['type'] === 'overuse' ? 'Overused' : 'Underused' }} by {{ number_format(abs($variance['variance_quantity']), 2) }}
                                    </span>
                                    ({{ number_format($variance['variance_percentage'], 1) }}% variance)
                                </p>
                            </div>
                            <div class="text-right">
                                <p class="font-bold {{ $variance['cost_variance'] > 0 ? 'text-red-600' : 'text-green-600' }}">
                                    {{ $variance['cost_variance'] > 0 ? '+' : '' }}
                                    {{ \App\Helpers\LocalizationHelper::formatCurrency(abs($variance['cost_variance'] ?? 0)) }}
                                </p>
                                <span class="px-2 py-1 text-xs rounded-full {{ $variance['severity'] === 'high' ? 'bg-red-100 text-red-800' : 'bg-yellow-100 text-yellow-800' }}">
                                    {{ ucfirst($variance['severity']) }}
                                </span>
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
            @endif

            <!-- Raw Material Usage -->
            @if(!empty($rawMaterialUsage))
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6 mb-6">
                <h3 class="text-lg font-semibold dark:text-white mb-4">Raw Material Usage Analysis</h3>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-700">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Material</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Planned</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Actual</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Variance</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Efficiency</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Cost Impact</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                            @foreach($rawMaterialUsage as $usage)
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700">
                                <td class="px-6 py-4">
                                    <div>
                                        <p class="text-sm font-medium text-gray-900 dark:text-white">{{ $usage['item_name'] }}</p>
                                        <p class="text-xs text-gray-500">{{ $usage['item_code'] }}</p>
                                    </div>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-400 text-right">
                                    {{ number_format($usage['planned_quantity'], 2) }} {{ $usage['uom'] }}
                                </td>
                                <td class="px-6 py-4 text-sm font-semibold text-gray-900 dark:text-white text-right">
                                    {{ number_format($usage['actual_quantity'], 2) }} {{ $usage['uom'] }}
                                </td>
                                <td class="px-6 py-4 text-sm text-right">
                                    <span class="{{ $usage['variance'] > 0 ? 'text-red-600' : ($usage['variance'] < 0 ? 'text-blue-600' : 'text-gray-600') }}">
                                        {{ $usage['variance'] > 0 ? '+' : '' }}{{ number_format($usage['variance'], 2) }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-sm text-right">
                                    <span class="px-2 py-1 text-xs font-semibold rounded-full
                                        {{ $usage['efficiency_percentage'] <= 105 ? 'bg-green-100 text-green-800' : ($usage['efficiency_percentage'] <= 115 ? 'bg-yellow-100 text-yellow-800' : 'bg-red-100 text-red-800') }}">
                                        {{ number_format($usage['efficiency_percentage'], 1) }}%
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-sm font-semibold text-right {{ $usage['cost_variance'] > 0 ? 'text-red-600' : ($usage['cost_variance'] < 0 ? 'text-green-600' : 'text-gray-600') }}">
                                    {{ $usage['cost_variance'] > 0 ? '+' : '' }}
                                    {{ \App\Helpers\LocalizationHelper::formatCurrency(abs($usage['cost_variance'] ?? 0)) }}
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            @endif

            <!-- Production Summary -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6 mb-6">
                <h3 class="text-lg font-semibold dark:text-white mb-4">Production Summary</h3>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-700">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Recipe</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Batches Planned</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Batches Produced</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Total Approved</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Yield %</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                            @forelse($productionSummary as $item)
                            <tr>
                                <td class="px-6 py-4 text-sm font-medium text-gray-900 dark:text-gray-100">{{ $item['recipe_name'] }}</td>
                                <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-400">{{ $item['batches_planned'] }}</td>
                                <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-400">{{ $item['batches_produced'] }}</td>
                                <td class="px-6 py-4 text-sm font-semibold text-gray-900 dark:text-gray-100">{{ number_format($item['total_approved'], 2) }}</td>
                                <td class="px-6 py-4 text-sm {{ $item['yield_percentage'] >= 90 ? 'text-green-600' : 'text-yellow-600' }}">
                                    {{ number_format($item['yield_percentage'], 1) }}%
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="5" class="px-6 py-8 text-center text-gray-500 dark:text-gray-400">No production data for this shift</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Notes -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6 mb-6">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Closing Notes</label>
                <textarea wire:model="notes" rows="3" class="w-full px-3 py-2 border dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white" placeholder="Add any notes about this shift..."></textarea>
            </div>

            <!-- Actions -->
            <div class="flex justify-end gap-4">
                <button wire:click="loadProductionClosingData" class="px-4 py-2 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-300">
                    Refresh Data
                </button>
                <button wire:click="saveShiftClosing" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700" {{ $isVerified ? 'disabled' : '' }}>
                    <i class="fas fa-check mr-2"></i>Close Shift
                </button>
            </div>
        @endif
    </div>
</div>
