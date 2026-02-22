<div>
    <div class="p-6 bg-gray-50 dark:bg-gray-900 min-h-screen">
        <!-- Breadcrumb -->
        <x-breadcrumb
            title="Shift Closing"
            :items="[
                ['label' => 'Dashboard', 'url' => branch_route('branch-dashboard.index')],
                ['label' => 'Inventory'],
                ['label' => 'Shift Closing']
            ]"
            :compact="false"
            :with-icons="true"
        />

        <!-- Header -->
        <div class="mb-6 mt-4">
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white">Inventory Shift Closing</h1>
            <p class="text-gray-600 dark:text-gray-400 mt-1">Close your inventory shift and reconcile stock</p>
        </div>

        @if(!$currentShiftId)
            <div class="bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-lg p-4">
                <p class="text-yellow-800 dark:text-yellow-300">No active shift found. Please clock in first.</p>
            </div>
        @else
            <!-- Shift Info -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4 mb-6">
                <div class="grid grid-cols-3 gap-4">
                    <div>
                        <span class="text-sm text-gray-600 dark:text-gray-400">Date:</span>
                        <span class="ml-2 font-semibold text-gray-900 dark:text-gray-100">{{ \Carbon\Carbon::parse($shiftDate)->format('M d, Y') }}</span>
                    </div>
                    <div>
                        <span class="text-sm text-gray-600 dark:text-gray-400">Shift:</span>
                        <span class="ml-2 font-semibold text-gray-900 dark:text-gray-100">{{ ucfirst($shiftType) }}</span>
                    </div>
                    <div>
                        <span class="text-sm text-gray-600 dark:text-gray-400">Status:</span>
                        <span class="ml-2 px-2 py-1 text-xs rounded-full {{ $isVerified ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800' }}">
                            {{ $isVerified ? 'Closed' : 'Active' }}
                        </span>
                    </div>
                </div>
            </div>

            <!-- Stock Closing Table -->
            <!-- TODO: Add functionality to:
                 - Load stock movements during shift
                 - Allow manual entry of physical counts
                 - Calculate and display variances
                 - Add reason selection for variances
                 - Support bulk entry via barcode scanner
            -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6 mb-6">
                <h3 class="text-lg font-semibold dark:text-white mb-4">Stock Closing</h3>
                <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">TODO: Full stock closing functionality will be implemented here</p>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-700">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Item</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Opening</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Expected Closing</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Actual Closing</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Variance</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                            @forelse($closingStocks as $stock)
                            <tr>
                                <td class="px-6 py-4 text-sm text-gray-900 dark:text-gray-100">{{ $stock['item_name'] }}</td>
                                <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-400">{{ number_format($stock['opening_quantity'], 2) }}</td>
                                <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-400">{{ number_format($stock['expected_closing'], 2) }}</td>
                                <td class="px-6 py-4 text-sm text-gray-900 dark:text-gray-100">{{ number_format($stock['actual_closing'], 2) }}</td>
                                <td class="px-6 py-4 text-sm {{ $stock['variance'] == 0 ? 'text-green-600' : 'text-red-600' }}">
                                    {{ number_format($stock['variance'], 2) }}
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="5" class="px-6 py-8 text-center text-gray-500 dark:text-gray-400">No stock data available</td>
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
                <button wire:click="loadClosingStockData" class="px-4 py-2 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-300">
                    Refresh Data
                </button>
                <button wire:click="saveShiftClosing" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700" {{ $isVerified ? 'disabled' : '' }}>
                    <i class="fas fa-check mr-2"></i>Close Shift
                </button>
            </div>
        @endif
    </div>
</div>
