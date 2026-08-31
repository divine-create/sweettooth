<div class="p-6">
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6">
        <div>
            <h1 class="text-2xl font-semibold text-gray-900">Wastage & Spoilage Analysis</h1>
            <p class="text-gray-500 mt-1">Monitor cost impact of production rejections and inventory losses.</p>
        </div>

        <div class="flex flex-wrap gap-4 mt-4 sm:mt-0">
            <div class="w-full sm:w-auto">
                <label class="block text-sm font-medium text-gray-700 mb-1">Unit / Branch</label>
                <select wire:model.live="selectedBranch" class="w-full sm:w-48 px-4 py-2 border border-gray-300 rounded-lg bg-white focus:ring-2 focus:ring-blue-500">
                    <option value="all">All Units (Global)</option>
                    @foreach($branches as $branch)
                        <option value="{{ $branch->id }}">{{ $branch->name }}</option>
                    @endforeach
                </select>
            </div>

            <div class="w-full sm:w-auto">
                <label class="block text-sm font-medium text-gray-700 mb-1">Timeframe</label>
                <select wire:model.live="dateFilter" class="w-full sm:w-48 px-4 py-2 border border-gray-300 rounded-lg bg-white focus:ring-2 focus:ring-blue-500">
                    <option value="daily">Today (Daily)</option>
                    <option value="weekly">This Week</option>
                    <option value="monthly">This Month</option>
                </select>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Production Wastage -->
        <div class="bg-white rounded-lg shadow border border-gray-200">
            <div class="px-6 py-4 border-b border-gray-200 flex justify-between items-center">
                <h2 class="text-lg font-medium text-gray-800">Production Wastage</h2>
                <span class="px-2 py-1 bg-red-100 text-red-700 rounded text-sm font-medium">
                    ₦{{ number_format($productionWastage->sum('cost'), 2) }} Loss
                </span>
            </div>
            <div class="overflow-x-auto max-h-96">
                <table class="w-full text-left border-collapse">
                    <thead class="sticky top-0 bg-gray-50">
                        <tr class="border-b border-gray-200 text-sm text-gray-600">
                            <th class="px-6 py-3 font-medium">Item Produced</th>
                            <th class="px-6 py-3 font-medium text-center">Qty Rejected</th>
                            <th class="px-6 py-3 font-medium">Reason</th>
                            <th class="px-6 py-3 font-medium text-right">Cost Impact</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @forelse($productionWastage as $waste)
                            <tr class="hover:bg-gray-50 text-sm">
                                <td class="px-6 py-4 font-medium">{{ $waste['item'] }}</td>
                                <td class="px-6 py-4 text-center">{{ number_format($waste['quantity']) }}</td>
                                <td class="px-6 py-4 text-gray-500">{{ $waste['reason'] }}</td>
                                <td class="px-6 py-4 text-right text-red-600 font-medium">₦{{ number_format($waste['cost'], 2) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-6 py-8 text-center text-gray-500">No production wastage recorded.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Inventory Damages & Shortages -->
        <div class="bg-white rounded-lg shadow border border-gray-200">
            <div class="px-6 py-4 border-b border-gray-200 flex justify-between items-center">
                <h2 class="text-lg font-medium text-gray-800">Inventory Damages & Shortages</h2>
                <span class="px-2 py-1 bg-red-100 text-red-700 rounded text-sm font-medium">
                    ₦{{ number_format($inventoryShortages->sum('cost'), 2) }} Loss
                </span>
            </div>
            <div class="overflow-x-auto max-h-96">
                <table class="w-full text-left border-collapse">
                    <thead class="sticky top-0 bg-gray-50">
                        <tr class="border-b border-gray-200 text-sm text-gray-600">
                            <th class="px-6 py-3 font-medium">Inventory Item</th>
                            <th class="px-6 py-3 font-medium text-center">Qty Lost</th>
                            <th class="px-6 py-3 font-medium">Reason</th>
                            <th class="px-6 py-3 font-medium text-right">Cost Impact</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @forelse($inventoryShortages as $shortage)
                            <tr class="hover:bg-gray-50 text-sm">
                                <td class="px-6 py-4 font-medium">{{ $shortage['item'] }}</td>
                                <td class="px-6 py-4 text-center">{{ number_format($shortage['quantity']) }}</td>
                                <td class="px-6 py-4 text-gray-500">{{ $shortage['reason'] }}</td>
                                <td class="px-6 py-4 text-right text-red-600 font-medium">₦{{ number_format($shortage['cost'], 2) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-6 py-8 text-center text-gray-500">No inventory shortages recorded.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
