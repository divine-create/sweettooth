<div class="p-6">
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6">
        <div>
            <h1 class="text-2xl font-semibold text-gray-900">Wastage & Adjustments Analysis</h1>
            <p class="text-gray-500 mt-1">Monitor cost impact of production rejections and inventory adjustments.</p>
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

    <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">
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
                            <th class="px-6 py-3 font-medium text-right">Cost Impact</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @forelse($productionWastage as $waste)
                            <tr class="hover:bg-gray-50 text-sm">
                                <td class="px-6 py-4 font-medium">{{ $waste['item'] }} <br><span class="text-xs text-gray-500">{{ $waste['reason'] }}</span></td>
                                <td class="px-6 py-4 text-center">{{ number_format($waste['quantity']) }}</td>
                                <td class="px-6 py-4 text-right text-red-600 font-medium">₦{{ number_format($waste['cost'], 2) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="px-6 py-8 text-center text-gray-500">No production wastage recorded.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Inventory Wastage -->
        <div class="bg-white rounded-lg shadow border border-gray-200">
            <div class="px-6 py-4 border-b border-gray-200 flex justify-between items-center">
                <h2 class="text-lg font-medium text-gray-800">Inventory Wastage</h2>
                <span class="px-2 py-1 bg-orange-100 text-orange-700 rounded text-sm font-medium">
                    ₦{{ number_format($inventoryWastage->sum('cost'), 2) }} Loss
                </span>
            </div>
            <div class="overflow-x-auto max-h-96">
                <table class="w-full text-left border-collapse">
                    <thead class="sticky top-0 bg-gray-50">
                        <tr class="border-b border-gray-200 text-sm text-gray-600">
                            <th class="px-6 py-3 font-medium">Inventory Item</th>
                            <th class="px-6 py-3 font-medium text-center">Qty Lost</th>
                            <th class="px-6 py-3 font-medium text-right">Cost Impact</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @forelse($inventoryWastage as $waste)
                            <tr class="hover:bg-gray-50 text-sm">
                                <td class="px-6 py-4 font-medium">{{ $waste['item'] }} <br><span class="text-xs text-gray-500">{{ $waste['reason'] }}</span></td>
                                <td class="px-6 py-4 text-center">{{ number_format($waste['quantity']) }}</td>
                                <td class="px-6 py-4 text-right text-orange-600 font-medium">₦{{ number_format($waste['cost'], 2) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="px-6 py-8 text-center text-gray-500">No inventory wastage recorded.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Inventory Adjustments -->
        <div class="bg-white rounded-lg shadow border border-gray-200">
            <div class="px-6 py-4 border-b border-gray-200 flex justify-between items-center">
                <h2 class="text-lg font-medium text-gray-800">Inventory Adjustments</h2>
                <span class="px-2 py-1 bg-gray-100 text-gray-700 rounded text-sm font-medium">
                    ₦{{ number_format($inventoryAdjustments->sum('cost'), 2) }}
                </span>
            </div>
            <div class="overflow-x-auto max-h-96">
                <table class="w-full text-left border-collapse">
                    <thead class="sticky top-0 bg-gray-50">
                        <tr class="border-b border-gray-200 text-sm text-gray-600">
                            <th class="px-6 py-3 font-medium">Inventory Item</th>
                            <th class="px-6 py-3 font-medium text-center">Qty Adjusted</th>
                            <th class="px-6 py-3 font-medium text-right">Cost Impact</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @forelse($inventoryAdjustments as $adj)
                            <tr class="hover:bg-gray-50 text-sm">
                                <td class="px-6 py-4 font-medium">{{ $adj['item'] }} <br><span class="text-xs text-gray-500">{{ $adj['reason'] }}</span></td>
                                <td class="px-6 py-4 text-center">{{ number_format($adj['quantity']) }}</td>
                                <td class="px-6 py-4 text-right text-gray-800 font-medium">₦{{ number_format($adj['cost'], 2) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="px-6 py-8 text-center text-gray-500">No inventory adjustments recorded.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
