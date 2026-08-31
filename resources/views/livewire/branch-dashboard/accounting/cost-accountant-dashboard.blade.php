<div>
    <div class="mb-6 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
        <div>
            <h2 class="text-2xl font-bold text-gray-800">Cost Accountant Dashboard</h2>
            <p class="text-gray-600">Cross-departmental view of sales, yields, and wastage.</p>
        </div>

        <div class="flex gap-4">
            <x-ts-select.styled wire:model.live="selectedBranch" label="Unit / Branch">
                <x-slot:options>
                    <option value="all">All Units (Global)</option>
                    @foreach($branches as $branch)
                        <option value="{{ $branch->id }}">{{ $branch->name }}</option>
                    @endforeach
                </x-slot:options>
            </x-ts-select.styled>

            <x-ts-select.styled wire:model.live="dateFilter" label="Timeframe">
                <x-slot:options>
                    <option value="daily">Today (Daily)</option>
                    <option value="weekly">This Week</option>
                    <option value="monthly">This Month</option>
                </x-slot:options>
            </x-ts-select.styled>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
        <!-- Sales & Profitability -->
        <div class="bg-white rounded-xl shadow p-6 border border-gray-100">
            <h3 class="text-lg font-bold text-gray-800 mb-4">Top Selling Items (Profitability)</h3>
            <div class="overflow-x-auto">
                <table class="w-full text-sm text-left">
                    <thead class="bg-gray-50 text-gray-600">
                        <tr>
                            <th class="px-4 py-3">Product</th>
                            <th class="px-4 py-3 text-right">Qty Sold</th>
                            <th class="px-4 py-3 text-right">Revenue</th>
                            <th class="px-4 py-3 text-right">COGS</th>
                            <th class="px-4 py-3 text-right">Gross Profit</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse($topSales as $sale)
                        <tr>
                            <td class="px-4 py-3 font-medium">{{ $sale->product->name ?? 'Unknown' }}</td>
                            <td class="px-4 py-3 text-right">{{ number_format($sale->total_quantity, 2) }}</td>
                            <td class="px-4 py-3 text-right">₦{{ number_format($sale->total_revenue, 2) }}</td>
                            <td class="px-4 py-3 text-right text-red-600">₦{{ number_format($sale->total_cogs, 2) }}</td>
                            <td class="px-4 py-3 text-right text-green-600 font-bold">
                                ₦{{ number_format($sale->total_revenue - $sale->total_cogs, 2) }}
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="5" class="px-4 py-8 text-center text-gray-500">No sales data found for this period.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="space-y-6">
            <!-- Production Wastage -->
            <div class="bg-white rounded-xl shadow p-6 border border-gray-100">
                <h3 class="text-lg font-bold text-gray-800 mb-4">Production Wastage</h3>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm text-left">
                        <thead class="bg-gray-50 text-gray-600">
                            <tr>
                                <th class="px-4 py-3">Date</th>
                                <th class="px-4 py-3">Product/Recipe</th>
                                <th class="px-4 py-3">Reason</th>
                                <th class="px-4 py-3 text-right">Cost Impact</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse($productionWastage as $waste)
                            <tr>
                                <td class="px-4 py-3 text-gray-500">{{ $waste['date'] }}</td>
                                <td class="px-4 py-3 font-medium">{{ $waste['item'] }}</td>
                                <td class="px-4 py-3"><span class="px-2 py-1 bg-red-50 text-red-600 rounded-full text-xs">{{ $waste['reason'] }}</span></td>
                                <td class="px-4 py-3 text-right text-red-600 font-bold">₦{{ number_format($waste['cost'], 2) }}</td>
                            </tr>
                            @empty
                            <tr><td colspan="4" class="px-4 py-6 text-center text-gray-500">No production wastage recorded.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Inventory Shortages -->
            <div class="bg-white rounded-xl shadow p-6 border border-gray-100">
                <h3 class="text-lg font-bold text-gray-800 mb-4">Inventory Shortages & Spoilage</h3>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm text-left">
                        <thead class="bg-gray-50 text-gray-600">
                            <tr>
                                <th class="px-4 py-3">Date</th>
                                <th class="px-4 py-3">Item</th>
                                <th class="px-4 py-3">Reason</th>
                                <th class="px-4 py-3 text-right">Cost Impact</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse($inventoryShortages as $short)
                            <tr>
                                <td class="px-4 py-3 text-gray-500">{{ $short['date'] }}</td>
                                <td class="px-4 py-3 font-medium">{{ $short['item'] }}</td>
                                <td class="px-4 py-3"><span class="px-2 py-1 bg-yellow-50 text-yellow-700 rounded-full text-xs">{{ $short['reason'] }}</span></td>
                                <td class="px-4 py-3 text-right text-red-600 font-bold">₦{{ number_format($short['cost'], 2) }}</td>
                            </tr>
                            @empty
                            <tr><td colspan="4" class="px-4 py-6 text-center text-gray-500">No inventory shortages recorded.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
