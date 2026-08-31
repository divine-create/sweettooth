<div class="p-6">
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6">
        <div>
            <h1 class="text-2xl font-semibold text-gray-900">Cost Accounting Overview</h1>
            <p class="text-gray-500 mt-1">High-level summary of gross margins, production losses, and inventory value.</p>
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

    <!-- Summary Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <div class="bg-white p-6 rounded-lg shadow border border-gray-200">
            <p class="text-sm font-medium text-gray-500">Total Revenue (Sales)</p>
            <p class="mt-2 text-3xl font-bold text-gray-900">₦{{ number_format($totalRevenue, 2) }}</p>
        </div>
        
        <div class="bg-white p-6 rounded-lg shadow border border-gray-200">
            <p class="text-sm font-medium text-gray-500">Total COGS (Cost of Sales)</p>
            <p class="mt-2 text-3xl font-bold text-gray-900">₦{{ number_format($totalCogs, 2) }}</p>
        </div>
        
        <div class="bg-white p-6 rounded-lg shadow border border-gray-200">
            <p class="text-sm font-medium text-gray-500">Gross Profit</p>
            <p class="mt-2 text-3xl font-bold {{ $grossProfit >= 0 ? 'text-green-600' : 'text-red-600' }}">
                ₦{{ number_format($grossProfit, 2) }}
            </p>
        </div>

        <div class="bg-white p-6 rounded-lg shadow border border-red-200 bg-red-50">
            <p class="text-sm font-medium text-red-600">Total Material Lost (Wastage)</p>
            <p class="mt-2 text-3xl font-bold text-red-700">
                ₦{{ number_format($productionWastageTotal + $inventoryDamagesTotal, 2) }}
            </p>
        </div>
    </div>
    
    <div class="mt-6 flex gap-4">
        <a href="{{ branch_route('branch-dashboard.accounting.cost-accounting.sales-analysis') }}" class="px-4 py-2 bg-blue-600 text-white rounded shadow hover:bg-blue-700 transition">View Detailed Sales Analysis</a>
        <a href="{{ branch_route('branch-dashboard.accounting.cost-accounting.wastage-analysis') }}" class="px-4 py-2 bg-white border border-gray-300 text-gray-700 rounded shadow hover:bg-gray-50 transition">View Detailed Wastage Report</a>
    </div>
</div>
