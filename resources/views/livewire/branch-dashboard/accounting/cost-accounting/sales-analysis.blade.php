<div class="p-6">
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6">
        <div>
            <h1 class="text-2xl font-semibold text-gray-900">Cost Accounting - Sales Analysis</h1>
            <p class="text-gray-500 mt-1">Track top selling items, revenues, and cost of goods sold (COGS).</p>
        </div>

        <div class="flex flex-wrap gap-4 mt-4 sm:mt-0">
            <div class="w-full sm:w-auto">
                <label class="block text-sm font-medium text-gray-700 mb-1">Top Limits</label>
                <select wire:model.live="limit" class="w-full sm:w-32 px-4 py-2 border border-gray-300 rounded-lg bg-white focus:ring-2 focus:ring-blue-500">
                    <option value="10">Top 10</option>
                    <option value="20">Top 20</option>
                    <option value="30">Top 30</option>
                    <option value="50">Top 50</option>
                    <option value="100">Top 100</option>
                </select>
            </div>

            <div class="w-full sm:w-auto">
                <label class="block text-sm font-medium text-gray-700 mb-1">Sales Department (Unit)</label>
                <select wire:model.live="selectedDepartment" class="w-full sm:w-48 px-4 py-2 border border-gray-300 rounded-lg bg-white focus:ring-2 focus:ring-blue-500">
                    <option value="all">All Departments (Global)</option>
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
                </select>
            </div>
        </div>
    </div>

    <!-- Top Selling Items & Profitability -->
    <div class="bg-white rounded-lg shadow border border-gray-200">
        <div class="px-6 py-4 border-b border-gray-200 flex justify-between items-center">
            <h2 class="text-lg font-medium text-gray-800">Top Selling Items (Profitability)</h2>
            <span class="text-sm text-gray-500">Based on Subtotal & Line Cost</span>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-gray-50 border-b border-gray-200 text-sm text-gray-600">
                        <th class="px-6 py-3 font-medium">Product Name</th>
                        <th class="px-6 py-3 font-medium text-right">Qty Sold</th>
                        <th class="px-6 py-3 font-medium text-right">Revenue (Subtotal)</th>
                        <th class="px-6 py-3 font-medium text-right">COGS (Cost)</th>
                        <th class="px-6 py-3 font-medium text-right">Gross Profit</th>
                        <th class="px-6 py-3 font-medium text-right">Margin %</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @forelse($topItems as $item)
                        <tr class="hover:bg-gray-50 text-sm">
                            <td class="px-6 py-4 font-medium text-gray-800">{{ $item['name'] }}</td>
                            <td class="px-6 py-4 text-right">{{ number_format($item['quantity']) }}</td>
                            <td class="px-6 py-4 text-right">₦{{ number_format($item['revenue'], 2) }}</td>
                            <td class="px-6 py-4 text-right">₦{{ number_format($item['cogs'], 2) }}</td>
                            <td class="px-6 py-4 text-right font-medium {{ $item['gross_profit'] > 0 ? 'text-green-600' : 'text-red-600' }}">
                                ₦{{ number_format($item['gross_profit'], 2) }}
                            </td>
                            <td class="px-6 py-4 text-right">
                                <span class="px-2 py-1 rounded text-xs {{ $item['margin'] >= 40 ? 'bg-green-100 text-green-800' : ($item['margin'] > 15 ? 'bg-yellow-100 text-yellow-800' : 'bg-red-100 text-red-800') }}">
                                    {{ $item['margin'] }}%
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-8 text-center text-gray-500">No sales data found for this period.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
