<div>
    <div class="p-6 bg-gray-50 dark:bg-gray-900 min-h-screen">
        <!-- Header -->
        <div class="mb-6">
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white">Shift Closing - {{ $departmentName }}</h1>
            <p class="text-gray-600 dark:text-gray-400 mt-1">Close your shift, reconcile stock and cash</p>
        </div>

        @if(!$currentShiftId)
            <div class="bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-lg p-4">
                <p class="text-yellow-800 dark:text-yellow-300">No active shift found. Please clock in first.</p>
            </div>
        @else
            <!-- Shift Info -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4 mb-6">
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
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

            <!-- Sales Summary Grid -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
                <!-- Basic Sales Summary -->
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                    <h3 class="text-lg font-semibold dark:text-white mb-4">Sales Summary</h3>
                    <div class="space-y-3">
                        <div class="flex justify-between">
                            <span class="text-gray-600 dark:text-gray-400">Total Sales:</span>
                            <span class="font-semibold text-green-600 dark:text-green-400">
                                {{ \App\Helpers\LocalizationHelper::formatCurrency($salesSummary['total_sales'] ?? 0) }}
                            </span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-600 dark:text-gray-400">Total Orders:</span>
                            <span class="font-semibold text-gray-900 dark:text-gray-100">{{ number_format($salesSummary['total_orders'] ?? 0) }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-600 dark:text-gray-400">Avg Order Value:</span>
                            <span class="font-semibold text-gray-900 dark:text-gray-100">
                                {{ \App\Helpers\LocalizationHelper::formatCurrency($salesSummary['avg_order_value'] ?? 0) }}
                            </span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-600 dark:text-gray-400">Total Discount:</span>
                            <span class="font-semibold text-red-600 dark:text-red-400">
                                {{ \App\Helpers\LocalizationHelper::formatCurrency($salesSummary['total_discount'] ?? 0) }}
                            </span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-600 dark:text-gray-400">Cancelled Orders:</span>
                            <span class="font-semibold text-gray-900 dark:text-gray-100">{{ $salesSummary['cancelled_orders'] ?? 0 }}</span>
                        </div>
                    </div>
                </div>

                <!-- Payment Breakdown -->
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                    <h3 class="text-lg font-semibold dark:text-white mb-4">Payment Methods</h3>
                    <div class="space-y-3">
                        @forelse(($salesSummary['payment_breakdown'] ?? []) as $method => $amount)
                        <div class="flex justify-between">
                            <span class="text-gray-600 dark:text-gray-400 capitalize">{{ $method }}:</span>
                            <span class="font-semibold text-gray-900 dark:text-gray-100">
                                {{ \App\Helpers\LocalizationHelper::formatCurrency($amount ?? 0) }}
                            </span>
                        </div>
                        @empty
                        <p class="text-sm text-gray-500 dark:text-gray-400">No payment data</p>
                        @endforelse
                    </div>
                </div>

                <!-- Top Products -->
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                    <h3 class="text-lg font-semibold dark:text-white mb-4">Top Selling Products</h3>
                    <div class="space-y-3">
                        @forelse(($salesSummary['top_products'] ?? []) as $product)
                        <div class="flex justify-between items-center">
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-medium text-gray-900 dark:text-gray-100 truncate">{{ $product['product_name'] }}</p>
                                <p class="text-xs text-gray-500 dark:text-gray-400">Qty: {{ number_format($product['quantity'], 0) }}</p>
                            </div>
                            <span class="ml-2 text-sm font-semibold text-gray-900 dark:text-gray-100">
                                {{ \App\Helpers\LocalizationHelper::formatCurrency($product['revenue'] ?? 0) }}
                            </span>
                        </div>
                        @empty
                        <p class="text-sm text-gray-500 dark:text-gray-400">No sales data</p>
                        @endforelse
                    </div>
                </div>
            </div>

            <!-- Cash Reconciliation -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6 mb-6">
                <h3 class="text-lg font-semibold dark:text-white mb-4">Cash Reconciliation</h3>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-4">
                    <!-- Cash -->
                    <div class="border dark:border-gray-700 rounded-lg p-4">
                        <h4 class="font-medium text-gray-900 dark:text-white mb-3">Cash</h4>
                        <div class="space-y-2">
                            <div class="flex justify-between text-sm">
                                <span class="text-gray-600 dark:text-gray-400">Expected:</span>
                                <span class="font-medium">
                                    {{ \App\Helpers\LocalizationHelper::formatCurrency($cashReconciliation['expected_cash'] ?? 0) }}
                                </span>
                            </div>
                            <div>
                                <label class="block text-xs text-gray-600 dark:text-gray-400 mb-1">Actual Count:</label>
                                <input type="number" wire:model.live="actualCash" step="0.01"
                                    class="w-full px-3 py-2 text-sm border dark:border-gray-600 rounded bg-white dark:bg-gray-700 text-gray-900 dark:text-white"
                                    placeholder="0.00" {{ $isVerified ? 'disabled' : '' }}>
                            </div>
                            <div class="flex justify-between text-sm pt-2 border-t dark:border-gray-700">
                                <span class="text-gray-600 dark:text-gray-400">Variance:</span>
                                <span class="font-semibold {{ abs($cashReconciliation['cash_variance'] ?? 0) > 0 ? 'text-red-600 dark:text-red-400' : 'text-green-600 dark:text-green-400' }}">
                                    {{ \App\Helpers\LocalizationHelper::formatCurrency($cashReconciliation['cash_variance'] ?? 0) }}
                                </span>
                            </div>
                            @if(($cashReconciliation['cash_requires_reason'] ?? false))
                            <div>
                                <label class="block text-xs text-gray-600 dark:text-gray-400 mb-1">Reason Required:</label>
                                <textarea wire:model="cashVarianceReason" rows="2"
                                    class="w-full px-3 py-2 text-sm border dark:border-gray-600 rounded bg-white dark:bg-gray-700 text-gray-900 dark:text-white"
                                    placeholder="Explain variance..." {{ $isVerified ? 'disabled' : '' }}></textarea>
                            </div>
                            @endif
                        </div>
                    </div>

                    <!-- POS -->
                    <div class="border dark:border-gray-700 rounded-lg p-4">
                        <h4 class="font-medium text-gray-900 dark:text-white mb-3">POS/Card</h4>
                        <div class="space-y-2">
                            <div class="flex justify-between text-sm">
                                <span class="text-gray-600 dark:text-gray-400">Expected:</span>
                                <span class="font-medium">
                                    {{ \App\Helpers\LocalizationHelper::formatCurrency($cashReconciliation['expected_pos'] ?? 0) }}
                                </span>
                            </div>
                            <div>
                                <label class="block text-xs text-gray-600 dark:text-gray-400 mb-1">Actual Count:</label>
                                <input type="number" wire:model.live="actualPos" step="0.01"
                                    class="w-full px-3 py-2 text-sm border dark:border-gray-600 rounded bg-white dark:bg-gray-700 text-gray-900 dark:text-white"
                                    placeholder="0.00" {{ $isVerified ? 'disabled' : '' }}>
                            </div>
                            <div class="flex justify-between text-sm pt-2 border-t dark:border-gray-700">
                                <span class="text-gray-600 dark:text-gray-400">Variance:</span>
                                <span class="font-semibold {{ abs($cashReconciliation['pos_variance'] ?? 0) > 0 ? 'text-red-600 dark:text-red-400' : 'text-green-600 dark:text-green-400' }}">
                                    {{ \App\Helpers\LocalizationHelper::formatCurrency($cashReconciliation['pos_variance'] ?? 0) }}
                                </span>
                            </div>
                            @if(($cashReconciliation['pos_requires_reason'] ?? false))
                            <div>
                                <label class="block text-xs text-gray-600 dark:text-gray-400 mb-1">Reason Required:</label>
                                <textarea wire:model="posVarianceReason" rows="2"
                                    class="w-full px-3 py-2 text-sm border dark:border-gray-600 rounded bg-white dark:bg-gray-700 text-gray-900 dark:text-white"
                                    placeholder="Explain variance..." {{ $isVerified ? 'disabled' : '' }}></textarea>
                            </div>
                            @endif
                        </div>
                    </div>

                    <!-- Transfer -->
                    <div class="border dark:border-gray-700 rounded-lg p-4">
                        <h4 class="font-medium text-gray-900 dark:text-white mb-3">Transfer</h4>
                        <div class="space-y-2">
                            <div class="flex justify-between text-sm">
                                <span class="text-gray-600 dark:text-gray-400">Expected:</span>
                                <span class="font-medium">
                                    {{ \App\Helpers\LocalizationHelper::formatCurrency($cashReconciliation['expected_transfer'] ?? 0) }}
                                </span>
                            </div>
                            <div>
                                <label class="block text-xs text-gray-600 dark:text-gray-400 mb-1">Actual Count:</label>
                                <input type="number" wire:model.live="actualTransfer" step="0.01"
                                    class="w-full px-3 py-2 text-sm border dark:border-gray-600 rounded bg-white dark:bg-gray-700 text-gray-900 dark:text-white"
                                    placeholder="0.00" {{ $isVerified ? 'disabled' : '' }}>
                            </div>
                            <div class="flex justify-between text-sm pt-2 border-t dark:border-gray-700">
                                <span class="text-gray-600 dark:text-gray-400">Variance:</span>
                                <span class="font-semibold {{ abs($cashReconciliation['transfer_variance'] ?? 0) > 0 ? 'text-red-600 dark:text-red-400' : 'text-green-600 dark:text-green-400' }}">
                                    {{ \App\Helpers\LocalizationHelper::formatCurrency($cashReconciliation['transfer_variance'] ?? 0) }}
                                </span>
                            </div>
                            @if(($cashReconciliation['transfer_requires_reason'] ?? false))
                            <div>
                                <label class="block text-xs text-gray-600 dark:text-gray-400 mb-1">Reason Required:</label>
                                <textarea wire:model="transferVarianceReason" rows="2"
                                    class="w-full px-3 py-2 text-sm border dark:border-gray-600 rounded bg-white dark:bg-gray-700 text-gray-900 dark:text-white"
                                    placeholder="Explain variance..." {{ $isVerified ? 'disabled' : '' }}></textarea>
                            </div>
                            @endif
                        </div>
                    </div>
                </div>

                <!-- Total Summary -->
                <div class="border-t dark:border-gray-700 pt-4">
                    <div class="flex justify-between items-center">
                        <span class="text-lg font-semibold text-gray-900 dark:text-white">Total Variance:</span>
                        <span class="text-xl font-bold {{ abs($cashReconciliation['total_variance'] ?? 0) > 0 ? 'text-red-600 dark:text-red-400' : 'text-green-600 dark:text-green-400' }}">
                            {{ \App\Helpers\LocalizationHelper::formatCurrency($cashReconciliation['total_variance'] ?? 0) }}
                        </span>
                    </div>
                </div>
            </div>

            <!-- Stock Closing -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6 mb-6">
                <h3 class="text-lg font-semibold dark:text-white mb-4">Stock Closing</h3>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-700">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Product</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Opening</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Additions</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Sold</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Expected</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Actual</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Variance</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Status</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                            @forelse($closingStocks as $index => $stock)
                            @php
                                $hasVariance = $stock['variance'] != 0;
                                $isShortage = $stock['variance'] < 0;
                                $rowBg = $stock['is_expired'] ? 'bg-red-50 dark:bg-red-900/20' : ($hasVariance && $isShortage ? 'bg-red-50/50 dark:bg-red-900/10' : ($hasVariance ? 'bg-amber-50/50 dark:bg-amber-900/10' : ''));
                                $varianceClass = $stock['variance'] == 0
                                    ? 'text-green-600 dark:text-green-400'
                                    : ($isShortage ? 'text-red-600 dark:text-red-400' : 'text-amber-600 dark:text-amber-400');
                            @endphp
                            <tr class="{{ $rowBg }}">
                                <td class="px-4 py-3 text-sm">
                                    <div class="font-medium text-gray-900 dark:text-gray-100">{{ $stock['product_name'] }}</div>
                                    @if($stock['is_expired'])
                                    <span class="text-xs text-red-600 dark:text-red-400">Expired: {{ $stock['expiry_date'] }}</span>
                                    @elseif($stock['days_to_expiry'] !== null && $stock['days_to_expiry'] <= 3)
                                    <span class="text-xs text-orange-600 dark:text-orange-400">Expires in {{ $stock['days_to_expiry'] }} days</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-400">{{ number_format($stock['opening_quantity'], 2) }}</td>
                                <td class="px-4 py-3 text-sm text-blue-600 dark:text-blue-400">+{{ number_format($stock['addition_quantity'], 2) }}</td>
                                <td class="px-4 py-3 text-sm text-red-600 dark:text-red-400">-{{ number_format($stock['sold_quantity'], 2) }}</td>
                                <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-400">{{ number_format($stock['expected_closing'], 2) }}</td>
                                <td class="px-4 py-3 text-sm">
                                    <input type="number"
                                        wire:model.live="closingStocks.{{ $index }}.actual_closing"
                                        wire:change="updateActualClosing({{ $stock['product_id'] }}, $event.target.value)"
                                        step="0.01"
                                        class="w-20 px-2 py-1 text-sm border {{ $hasVariance && $isShortage ? 'border-red-400' : 'dark:border-gray-600' }} rounded bg-white dark:bg-gray-700 text-gray-900 dark:text-white"
                                        {{ $isVerified ? 'disabled' : '' }}>
                                </td>
                                <td class="px-4 py-3 text-sm font-semibold {{ $varianceClass }}">
                                    <span class="flex items-center gap-1">
                                        @if($stock['variance'] > 0)
                                            <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M3.293 9.707a1 1 0 010-1.414l6-6a1 1 0 011.414 0l6 6a1 1 0 01-1.414 1.414L11 5.414V17a1 1 0 11-2 0V5.414L4.707 9.707a1 1 0 01-1.414 0z" clip-rule="evenodd"/></svg>
                                        @elseif($stock['variance'] < 0)
                                            <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 10.293a1 1 0 010 1.414l-6 6a1 1 0 01-1.414 0l-6-6a1 1 0 111.414-1.414L9 14.586V3a1 1 0 012 0v11.586l4.293-4.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                                        @endif
                                        {{ number_format(abs($stock['variance']), 2) }}
                                        <span class="text-xs font-normal">{{ $stock['product_uom'] }}</span>
                                    </span>
                                    @if($hasVariance)
                                        <span class="text-xs font-normal {{ $isShortage ? 'text-red-500' : 'text-amber-500' }}">
                                            {{ $isShortage ? 'shortage' : 'surplus' }}
                                        </span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-sm">
                                    @if($stock['is_expired'])
                                    <span class="px-2 py-1 text-xs rounded-full bg-red-100 text-red-800 dark:bg-red-900/50 dark:text-red-300">Expired</span>
                                    @elseif($hasVariance && $isShortage)
                                    <div class="space-y-1">
                                        <span class="px-2 py-1 text-xs rounded-full bg-red-100 text-red-800 dark:bg-red-900/50 dark:text-red-300">Shortage ↓</span>
                                        @if(!$isVerified)
                                        <input type="text"
                                            wire:model.live="closingStocks.{{ $index }}.notes"
                                            class="w-full px-2 py-1 text-xs border border-red-300 dark:border-red-700 rounded bg-white dark:bg-gray-700 text-gray-900 dark:text-white placeholder-red-300"
                                            placeholder="Reason for shortage…">
                                        @endif
                                    </div>
                                    @elseif($hasVariance)
                                    <div class="space-y-1">
                                        <span class="px-2 py-1 text-xs rounded-full bg-amber-100 text-amber-800 dark:bg-amber-900/50 dark:text-amber-300">Surplus ↑</span>
                                        @if(!$isVerified)
                                        <input type="text"
                                            wire:model.live="closingStocks.{{ $index }}.notes"
                                            class="w-full px-2 py-1 text-xs border border-amber-300 dark:border-amber-700 rounded bg-white dark:bg-gray-700 text-gray-900 dark:text-white placeholder-amber-300"
                                            placeholder="Reason for surplus…">
                                        @endif
                                    </div>
                                    @else
                                    <span class="px-2 py-1 text-xs rounded-full bg-green-100 text-green-800 dark:bg-green-900/50 dark:text-green-300">OK ✓</span>
                                    @endif
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="8" class="px-4 py-8 text-center text-gray-500 dark:text-gray-400">No stock data available</td>
                            </tr>
                            @endforelse
                        </tbody>
                        @php
                            $totalVarianceItems = collect($closingStocks)->where('variance', '!=', 0)->count();
                            $totalShortage = collect($closingStocks)->where('variance', '<', 0)->sum('variance');
                            $totalSurplus = collect($closingStocks)->where('variance', '>', 0)->sum('variance');
                        @endphp
                        @if(count($closingStocks) > 0 && $totalVarianceItems > 0)
                        <tfoot class="bg-gray-50 dark:bg-gray-700 border-t-2 border-gray-300 dark:border-gray-600">
                            <tr>
                                <td colspan="6" class="px-4 py-2 text-xs text-gray-500 font-semibold">
                                    {{ $totalVarianceItems }} item(s) with variance
                                </td>
                                <td class="px-4 py-2 text-xs font-semibold">
                                    @if($totalShortage < 0)
                                        <span class="text-red-600">{{ number_format($totalShortage, 2) }} shortage</span>
                                    @endif
                                    @if($totalSurplus > 0)
                                        <span class="text-amber-600 ml-2">+{{ number_format($totalSurplus, 2) }} surplus</span>
                                    @endif
                                </td>
                                <td class="px-4 py-2"></td>
                            </tr>
                        </tfoot>
                        @endif
                    </table>
                </div>
            </div>

            <!-- Notes -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6 mb-6">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Closing Notes</label>
                <textarea wire:model="notes" rows="3"
                    class="w-full px-3 py-2 border dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white"
                    placeholder="Add any notes about this shift..." {{ $isVerified ? 'disabled' : '' }}></textarea>
            </div>

            <!-- Actions -->
            <div class="flex justify-end gap-4">
                <button wire:click="loadClosingData"
                    class="px-4 py-2 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-300 dark:hover:bg-gray-600"
                    {{ $isVerified ? 'disabled' : '' }}>
                    <i class="fas fa-sync mr-2"></i>Refresh Data
                </button>
                <button wire:click="saveShiftClosing"
                    class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed"
                    {{ $isVerified ? 'disabled' : '' }}>
                    <i class="fas fa-check mr-2"></i>Close Shift
                </button>
            </div>
        @endif
    </div>
</div>
