<div class="p-6">
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6">
        <div>
            <h1 class="text-2xl font-semibold text-gray-900">Cost Accounting - Production Analysis</h1>
            <p class="text-gray-500 mt-1">Track production output, efficiency, and cost of goods manufactured.</p>
        </div>

        <div class="flex flex-wrap gap-4 mt-4 sm:mt-0">
            <div class="w-full sm:w-auto">
                <label class="block text-sm font-medium text-gray-700 mb-1">Production Unit</label>
                <select wire:model.live="selectedDepartment" class="w-full sm:w-48 px-4 py-2 border border-gray-300 rounded-lg bg-white focus:ring-2 focus:ring-blue-500">
                    <option value="all">All Units (Global)</option>
                    @foreach($departments as $dept)
                        <option value="{{ $dept->id }}">{{ $dept->name }}</option>
                    @endforeach
                </select>
            </div>

            <div class="w-full sm:w-auto">
                <label class="block text-sm font-medium text-gray-700 mb-1">Timeframe</label>
                <select wire:model.live="dateFilter" class="w-full sm:w-40 px-4 py-2 border border-gray-300 rounded-lg bg-white focus:ring-2 focus:ring-blue-500">
                    <option value="daily">Today (Daily)</option>
                    <option value="weekly">This Week</option>
                    <option value="monthly">This Month</option>
                    <option value="custom">Custom Range</option>
                </select>
            </div>

            @if($dateFilter === 'custom')
                <div class="w-full sm:w-auto flex items-end gap-2">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Start Date</label>
                        <input type="date" wire:model.live="customStartDate" class="w-full px-4 py-2 border border-gray-300 rounded-lg bg-white focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">End Date</label>
                        <input type="date" wire:model.live="customEndDate" class="w-full px-4 py-2 border border-gray-300 rounded-lg bg-white focus:ring-2 focus:ring-blue-500">
                    </div>
                </div>
            @endif
        </div>
    </div>

    <!-- Summary Metrics -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
        <div class="bg-white p-6 rounded-lg shadow border border-gray-200">
            <p class="text-sm font-medium text-gray-600">Total Production Cost</p>
            <p class="mt-2 text-3xl font-bold text-gray-800">
                ₦{{ number_format($totalCost, 2) }}
            </p>
        </div>
        <div class="bg-white p-6 rounded-lg shadow border border-gray-200">
            <p class="text-sm font-medium text-gray-600">Total Items Produced</p>
            <p class="mt-2 text-3xl font-bold text-gray-800">
                {{ number_format($totalProduced) }}
            </p>
        </div>
        <div class="bg-white p-6 rounded-lg shadow border border-red-200 bg-red-50">
            <p class="text-sm font-medium text-red-600">Total Items Rejected</p>
            <p class="mt-2 text-3xl font-bold text-red-700">
                {{ number_format($totalRejected) }}
            </p>
        </div>
    </div>

    <!-- Detailed Production Records -->
    <div class="bg-white rounded-lg shadow border border-gray-200">
        <div class="px-6 py-4 border-b border-gray-200 flex justify-between items-center">
            <h2 class="text-lg font-medium text-gray-800">Production Log</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-gray-50 border-b border-gray-200 text-sm text-gray-600">
                        <th class="px-6 py-3 font-medium">Date</th>
                        <th class="px-6 py-3 font-medium">Department</th>
                        <th class="px-6 py-3 font-medium">Product</th>
                        <th class="px-6 py-3 font-medium text-right">Qty Produced</th>
                        <th class="px-6 py-3 font-medium text-right">Qty Rejected</th>
                        <th class="px-6 py-3 font-medium text-right">Unit Cost</th>
                        <th class="px-6 py-3 font-medium text-right">Total Cost</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @forelse($productionData as $row)
                        <tr class="hover:bg-gray-50 text-sm">
                            <td class="px-6 py-4 text-gray-500">{{ $row['date'] }}</td>
                            <td class="px-6 py-4 font-medium text-gray-800">{{ $row['department'] }}</td>
                            <td class="px-6 py-4 font-medium">{{ $row['product'] }}</td>
                            <td class="px-6 py-4 text-right">{{ number_format($row['quantity_produced']) }}</td>
                            <td class="px-6 py-4 text-right {{ $row['quantity_rejected'] > 0 ? 'text-red-600 font-medium' : '' }}">{{ number_format($row['quantity_rejected']) }}</td>
                            <td class="px-6 py-4 text-right">₦{{ number_format($row['unit_cost'], 2) }}</td>
                            <td class="px-6 py-4 text-right font-medium">₦{{ number_format($row['total_cost'], 2) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-8 text-center text-gray-500">No production records found for this period.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
