<div class="p-3 space-y-3">
    <!-- Header -->
    <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-sm border border-zinc-200 dark:border-zinc-700 p-4">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <h2 class="text-lg font-semibold text-zinc-800 dark:text-zinc-100">Purchase Analytics</h2>
            <button wire:click="generateReport"
                    wire:loading.attr="disabled"
                    wire:target="generateReport"
                    class="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg font-medium text-sm disabled:opacity-60">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-3-3v6m8 4H4a1 1 0 01-1-1V6a1 1 0 011-1h10l6 6v7a1 1 0 01-1 1z"/>
                </svg>
                <span wire:loading.remove wire:target="generateReport">Generate Report</span>
                <span wire:loading wire:target="generateReport">Generating...</span>
            </button>
        </div>
    </div>

    @if (session('success'))
        <div class="p-3 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-700 rounded text-sm text-green-800 dark:text-green-200">
            <div class="flex flex-wrap items-center gap-2">
                <span>{{ session('success') }}</span>
                @if($generatedReportId)
                    <a href="{{ branch_route('branch-dashboard.reporting.report.view', ['id' => $generatedReportId]) }}"
                       class="inline-flex items-center px-2 py-1 rounded bg-green-700 text-white hover:bg-green-800 text-xs">
                        View Report
                    </a>
                @endif
            </div>
        </div>
    @endif

    @if (session('warning'))
        <div class="p-3 bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-700 rounded text-sm text-yellow-800 dark:text-yellow-200">
            {{ session('warning') }}
        </div>
    @endif

    <!-- Summary Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-blue-50 dark:bg-blue-900/20 rounded-lg shadow-sm p-4">
            <p class="text-sm text-gray-600 dark:text-gray-400">Total Purchases</p>
            <p class="text-2xl font-bold text-blue-600 dark:text-blue-500">{{ number_format($summary['total_purchases']) }}</p>
        </div>
        <div class="bg-green-50 dark:bg-green-900/20 rounded-lg shadow-sm p-4">
            <p class="text-sm text-gray-600 dark:text-gray-400">Total Spent</p>
            <p class="text-2xl font-bold text-green-600 dark:text-green-500">
                {{ \App\Helpers\LocalizationHelper::formatCurrency($summary['total_spent'] ?? 0) }}
            </p>
        </div>
        <div class="bg-yellow-50 dark:bg-yellow-900/20 rounded-lg shadow-sm p-4">
            <p class="text-sm text-gray-600 dark:text-gray-400">Avg Purchase Value</p>
            <p class="text-2xl font-bold text-yellow-600 dark:text-yellow-500">
                {{ \App\Helpers\LocalizationHelper::formatCurrency($summary['avg_purchase_value'] ?? 0) }}
            </p>
        </div>
        <div class="bg-purple-50 dark:bg-purple-900/20 rounded-lg shadow-sm p-4">
            <p class="text-sm text-gray-600 dark:text-gray-400">Total Items</p>
            <p class="text-2xl font-bold text-purple-600 dark:text-purple-500">{{ number_format($summary['total_items'], 2) }}</p>
        </div>
    </div>

    <!-- Filters -->
    <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-sm border border-zinc-200 dark:border-zinc-700 p-4">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <input type="text" wire:model.live="supplierFilter" placeholder="Search supplier..."
                   class="border border-zinc-300 dark:border-zinc-600 rounded-lg px-4 py-2 bg-white dark:bg-zinc-700 text-zinc-800 dark:text-zinc-200 focus:outline-none focus:ring-2 focus:ring-blue-500">
            <select wire:model.live="paymentStatus"
                    class="border border-zinc-300 dark:border-zinc-600 rounded-lg px-4 py-2 bg-white dark:bg-zinc-700 text-zinc-800 dark:text-zinc-200 focus:outline-none focus:ring-2 focus:ring-blue-500">
                <option value="">All Status</option>
                <option value="paid">Paid</option>
                <option value="partial">Partial</option>
                <option value="pending">Pending</option>
            </select>
            <div>
                <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">From</label>
                <input type="date" wire:model.live="dateFrom"
                       class="border border-zinc-300 dark:border-zinc-600 rounded-lg px-4 py-2 bg-white dark:bg-zinc-700 text-zinc-800 dark:text-zinc-200 focus:outline-none focus:ring-2 focus:ring-blue-500 w-full">
            </div>
            <div>
                <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">To</label>
                <input type="date" wire:model.live="dateTo"
                       class="border border-zinc-300 dark:border-zinc-600 rounded-lg px-4 py-2 bg-white dark:bg-zinc-700 text-zinc-800 dark:text-zinc-200 focus:outline-none focus:ring-2 focus:ring-blue-500 w-full">
            </div>
        </div>
        <div class="mt-4 flex justify-end">
            <button wire:click="resetFilters" class="px-4 py-2 bg-zinc-200 hover:bg-zinc-300 dark:bg-zinc-700 dark:hover:bg-zinc-600 text-zinc-800 dark:text-zinc-200 rounded-lg font-medium text-sm">
                Reset Filters
            </button>
        </div>
    </div>

    <!-- View Mode Tabs -->
    <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-sm border border-zinc-200 dark:border-zinc-700 p-2 mb-4">
        <div class="flex flex-wrap gap-1 bg-zinc-100 dark:bg-zinc-700 p-1 rounded-lg">
            <button wire:click="setViewMode('overview')" class="px-3 py-2 text-xs font-medium rounded transition-all whitespace-nowrap {{ $viewMode === 'overview' ? 'bg-white dark:bg-zinc-600 text-blue-600 dark:text-blue-400 shadow-sm' : 'text-zinc-600 dark:text-zinc-400 hover:text-zinc-900 dark:hover:text-zinc-200' }}">
                📊 Overview
            </button>
            <button wire:click="setViewMode('breakdown')" class="px-3 py-2 text-xs font-medium rounded transition-all whitespace-nowrap {{ $viewMode === 'breakdown' ? 'bg-white dark:bg-zinc-600 text-blue-600 dark:text-blue-400 shadow-sm' : 'text-zinc-600 dark:text-zinc-400 hover:text-zinc-900 dark:hover:text-zinc-200' }}">
                📈 Breakdown
            </button>
            <button wire:click="setViewMode('suppliers')" class="px-3 py-2 text-xs font-medium rounded transition-all whitespace-nowrap {{ $viewMode === 'suppliers' ? 'bg-white dark:bg-zinc-600 text-blue-600 dark:text-blue-400 shadow-sm' : 'text-zinc-600 dark:text-zinc-400 hover:text-zinc-900 dark:hover:text-zinc-200' }}">
                🏢 Suppliers
            </button>
            <button wire:click="setViewMode('items')" class="px-3 py-2 text-xs font-medium rounded transition-all whitespace-nowrap {{ $viewMode === 'items' ? 'bg-white dark:bg-zinc-600 text-blue-600 dark:text-blue-400 shadow-sm' : 'text-zinc-600 dark:text-zinc-400 hover:text-zinc-900 dark:hover:text-zinc-200' }}">
                📦 Items
            </button>
            <button wire:click="setViewMode('table')" class="px-3 py-2 text-xs font-medium rounded transition-all whitespace-nowrap {{ $viewMode === 'table' ? 'bg-white dark:bg-zinc-600 text-blue-600 dark:text-blue-400 shadow-sm' : 'text-zinc-600 dark:text-zinc-400 hover:text-zinc-900 dark:hover:text-zinc-200' }}">
                📋 Table
            </button>
        </div>
    </div>

    <!-- OVERVIEW VIEW -->
    @if($viewMode === 'overview')
    <div class="space-y-4">
        <!-- Payment Status Breakdown with Percentages -->
        <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-sm border border-zinc-200 dark:border-zinc-700 p-4">
            <h3 class="text-base font-semibold text-zinc-800 dark:text-zinc-100 mb-4">Payment Status Overview</h3>
            <div class="space-y-4">
                <div>
                    <div class="flex justify-between items-center mb-2">
                        <span class="font-medium text-zinc-900 dark:text-zinc-100">Paid</span>
                        <span class="text-sm font-bold text-green-600 dark:text-green-400">{{ $summary['paid_count'] }} ({{ $summary['paid_percentage'] }}%)</span>
                    </div>
                    <div class="w-full bg-zinc-200 dark:bg-zinc-700 rounded-full h-3">
                        <div class="bg-green-500 h-3 rounded-full" style="width: {{ $summary['paid_percentage'] }}%"></div>
                    </div>
                </div>

                <div>
                    <div class="flex justify-between items-center mb-2">
                        <span class="font-medium text-zinc-900 dark:text-zinc-100">Partial</span>
                        <span class="text-sm font-bold text-yellow-600 dark:text-yellow-400">{{ $summary['partial_count'] }} ({{ $summary['partial_percentage'] }}%)</span>
                    </div>
                    <div class="w-full bg-zinc-200 dark:bg-zinc-700 rounded-full h-3">
                        <div class="bg-yellow-500 h-3 rounded-full" style="width: {{ $summary['partial_percentage'] }}%"></div>
                    </div>
                </div>

                <div>
                    <div class="flex justify-between items-center mb-2">
                        <span class="font-medium text-zinc-900 dark:text-zinc-100">Pending</span>
                        <span class="text-sm font-bold text-red-600 dark:text-red-400">{{ $summary['pending_count'] }} ({{ $summary['pending_percentage'] }}%)</span>
                    </div>
                    <div class="w-full bg-zinc-200 dark:bg-zinc-700 rounded-full h-3">
                        <div class="bg-red-500 h-3 rounded-full" style="width: {{ $summary['pending_percentage'] }}%"></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Cost Breakdown -->
        <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-sm border border-zinc-200 dark:border-zinc-700 p-4">
            <h3 class="text-base font-semibold text-zinc-800 dark:text-zinc-100 mb-4">Cost Breakdown</h3>
            <div class="space-y-2">
                <div class="flex justify-between items-center p-3 bg-zinc-50 dark:bg-zinc-700/50 rounded">
                    <span class="text-zinc-700 dark:text-zinc-300">FOB Cost (NGN)</span>
                    <span class="font-bold text-lg text-zinc-900 dark:text-zinc-100">
                        {{ \App\Helpers\LocalizationHelper::formatCurrency($costBreakdown['series'][0] ?? 0) }}
                    </span>
                </div>
                <div class="flex justify-between items-center p-3 bg-zinc-50 dark:bg-zinc-700/50 rounded">
                    <span class="text-zinc-700 dark:text-zinc-300">Other Costs</span>
                    <span class="font-bold text-lg text-zinc-900 dark:text-zinc-100">
                        {{ \App\Helpers\LocalizationHelper::formatCurrency($costBreakdown['series'][1] ?? 0) }}
                    </span>
                </div>
                <div class="flex justify-between items-center p-3 bg-green-50 dark:bg-green-900/20 rounded border border-green-200 dark:border-green-800">
                    <span class="font-medium text-green-900 dark:text-green-100">Total Landing Cost</span>
                    <span class="font-bold text-lg text-green-600 dark:text-green-400">
                        {{ \App\Helpers\LocalizationHelper::formatCurrency($costBreakdown['series'][2] ?? 0) }}
                    </span>
                </div>
            </div>
        </div>
    </div>
    @endif

    <!-- BREAKDOWN VIEW -->
    @if($viewMode === 'breakdown')
    <div class="space-y-4">
        <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-sm border border-zinc-200 dark:border-zinc-700 p-4">
            <h3 class="text-base font-semibold text-zinc-800 dark:text-zinc-100 mb-4">Detailed Payment Status Analysis</h3>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="bg-green-50 dark:bg-green-900/20 rounded-lg p-4 border border-green-200 dark:border-green-700">
                    <p class="text-sm text-green-700 dark:text-green-300">Paid Purchases</p>
                    <p class="text-3xl font-bold text-green-600 dark:text-green-400">{{ $summary['paid_count'] }}</p>
                    <p class="text-xs text-green-600 dark:text-green-400 mt-1">{{ $summary['paid_percentage'] }}% of total</p>
                </div>
                <div class="bg-yellow-50 dark:bg-yellow-900/20 rounded-lg p-4 border border-yellow-200 dark:border-yellow-700">
                    <p class="text-sm text-yellow-700 dark:text-yellow-300">Partial Purchases</p>
                    <p class="text-3xl font-bold text-yellow-600 dark:text-yellow-400">{{ $summary['partial_count'] }}</p>
                    <p class="text-xs text-yellow-600 dark:text-yellow-400 mt-1">{{ $summary['partial_percentage'] }}% of total</p>
                </div>
                <div class="bg-red-50 dark:bg-red-900/20 rounded-lg p-4 border border-red-200 dark:border-red-700">
                    <p class="text-sm text-red-700 dark:text-red-300">Pending Purchases</p>
                    <p class="text-3xl font-bold text-red-600 dark:text-red-400">{{ $summary['pending_count'] }}</p>
                    <p class="text-xs text-red-600 dark:text-red-400 mt-1">{{ $summary['pending_percentage'] }}% of total</p>
                </div>
            </div>
        </div>

        <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-sm border border-zinc-200 dark:border-zinc-700 p-4">
            <h3 class="text-base font-semibold text-zinc-800 dark:text-zinc-100 mb-4">Cost Analysis</h3>
            <div class="space-y-3">
                @php
                    $totalCost = $costBreakdown['series'][2] ?? 0;
                    $fobCost = $costBreakdown['series'][0] ?? 0;
                    $otherCosts = $costBreakdown['series'][1] ?? 0;
                    $fobPct = $totalCost > 0 ? round(($fobCost / $totalCost) * 100, 1) : 0;
                    $otherPct = $totalCost > 0 ? round(($otherCosts / $totalCost) * 100, 1) : 0;
                @endphp
                <div>
                    <div class="flex justify-between mb-2">
                        <span class="font-medium">FOB Cost</span>
                        <span class="font-bold text-blue-600 dark:text-blue-400">{{ $fobPct }}%</span>
                    </div>
                    <div class="w-full bg-zinc-200 dark:bg-zinc-700 rounded-full h-3">
                        <div class="bg-blue-500 h-3 rounded-full" style="width: {{ $fobPct }}%"></div>
                    </div>
                </div>
                <div>
                    <div class="flex justify-between mb-2">
                        <span class="font-medium">Other Costs</span>
                        <span class="font-bold text-orange-600 dark:text-orange-400">{{ $otherPct }}%</span>
                    </div>
                    <div class="w-full bg-zinc-200 dark:bg-zinc-700 rounded-full h-3">
                        <div class="bg-orange-500 h-3 rounded-full" style="width: {{ $otherPct }}%"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif

    <!-- SUPPLIERS VIEW -->
    @if($viewMode === 'suppliers')
    <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-sm border border-zinc-200 dark:border-zinc-700 p-4">
        <h3 class="text-base font-semibold text-zinc-800 dark:text-zinc-100 mb-4">Top Suppliers by Spending</h3>
        <div class="space-y-3">
            @forelse($supplierAnalysis as $supplier)
                @php
                    $percentage = ($summary['total_spent'] > 0) ? (($supplier->total_spent / $summary['total_spent']) * 100) : 0;
                @endphp
                <div>
                    <div class="flex justify-between items-center mb-2">
                        <div>
                            <span class="font-medium text-zinc-900 dark:text-zinc-100">{{ $supplier->supplier_name }}</span>
                            <p class="text-xs text-zinc-600 dark:text-zinc-400">{{ $supplier->purchase_count }} purchases</p>
                        </div>
                        <span class="text-sm font-bold text-blue-600 dark:text-blue-400">
                            {{ \App\Helpers\LocalizationHelper::formatCurrency($supplier->total_spent ?? 0) }}
                            ({{ round($percentage, 1) }}%)
                        </span>
                    </div>
                    <div class="w-full bg-zinc-200 dark:bg-zinc-700 rounded-full h-2">
                        <div class="bg-blue-500 h-2 rounded-full" style="width: {{ $percentage }}%"></div>
                    </div>
                </div>
            @empty
                <p class="text-center text-zinc-600 dark:text-zinc-400 py-4">No supplier data available</p>
            @endforelse
        </div>
    </div>
    @endif

    <!-- ITEMS VIEW -->
    @if($viewMode === 'items')
    <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-sm border border-zinc-200 dark:border-zinc-700 p-4">
        <h3 class="text-base font-semibold text-zinc-800 dark:text-zinc-100 mb-4">Top 10 Purchased Items</h3>
        <div class="space-y-3">
            @php
                $totalItemsCost = $topItems->sum('total_cost');
            @endphp
            @forelse($topItems as $item)
                @php
                    $percentage = ($totalItemsCost > 0) ? (($item->total_cost / $totalItemsCost) * 100) : 0;
                @endphp
                <div class="border-l-4 border-blue-500 pl-3 py-2">
                    <div class="flex justify-between items-start mb-2">
                        <div>
                            <p class="font-medium text-zinc-900 dark:text-zinc-100">{{ $item->item->name ?? 'N/A' }}</p>
                            <p class="text-xs text-zinc-600 dark:text-zinc-400">Qty: {{ number_format($item->total_quantity, 2) }} {{ $item->item->uom ?? 'units' }}</p>
                        </div>
                        <span class="text-sm font-bold text-blue-600 dark:text-blue-400">
                            {{ \App\Helpers\LocalizationHelper::formatCurrency($item->total_cost ?? 0) }}
                            ({{ round($percentage, 1) }}%)
                        </span>
                    </div>
                    <div class="w-full bg-zinc-200 dark:bg-zinc-700 rounded-full h-1.5">
                        <div class="bg-blue-500 h-1.5 rounded-full" style="width: {{ $percentage }}%"></div>
                    </div>
                </div>
            @empty
                <p class="text-center text-zinc-600 dark:text-zinc-400 py-4">No item data available</p>
            @endforelse
        </div>
    </div>
    @endif

    <!-- TABLE VIEW -->
    @if($viewMode === 'table')
    <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-sm border border-zinc-200 dark:border-zinc-700 overflow-hidden">
        <div class="px-4 py-3 border-b border-zinc-200 dark:border-zinc-700">
            <h3 class="text-base font-semibold text-zinc-800 dark:text-zinc-100">Purchase Details</h3>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-zinc-50 dark:bg-zinc-700">
                    <tr>
                        <th class="px-4 py-2 text-left text-xs font-medium text-zinc-700 dark:text-zinc-300">Purchase #</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-zinc-700 dark:text-zinc-300">Date</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-zinc-700 dark:text-zinc-300">Supplier</th>
                        <th class="px-4 py-2 text-right text-xs font-medium text-zinc-700 dark:text-zinc-300">Landing Cost</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-zinc-700 dark:text-zinc-300">Payment</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-zinc-700 dark:text-zinc-300">Recorded By</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                    @forelse($purchases as $purchase)
                        <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-700/50">
                            <td class="px-4 py-2 text-sm text-zinc-900 dark:text-zinc-100 font-mono">{{ $purchase->purchase_number }}</td>
                            <td class="px-4 py-2 text-sm text-zinc-600 dark:text-zinc-400">{{ $purchase->purchase_date->format('d M Y') }}</td>
                            <td class="px-4 py-2 text-sm text-zinc-900 dark:text-zinc-100">{{ $purchase->supplier_name }}</td>
                            <td class="px-4 py-2 text-sm text-right font-bold text-zinc-900 dark:text-zinc-100">
                                {{ \App\Helpers\LocalizationHelper::formatCurrency($purchase->landing_cost ?? 0) }}
                            </td>
                            <td class="px-4 py-2 text-sm">
                                <span class="px-2 py-1 text-xs font-semibold rounded 
                                    @if($purchase->payment_status === 'paid') bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400
                                    @elseif($purchase->payment_status === 'partial') bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400
                                    @else bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400
                                    @endif">
                                    {{ ucfirst($purchase->payment_status) }}
                                </span>
                            </td>
                            <td class="px-4 py-2 text-sm text-zinc-600 dark:text-zinc-400">
                                @if($purchase->recorder && isset($purchase->recorder->name))
                                    {{ $purchase->recorder->name }}
                                @elseif($purchase->recorder && isset($purchase->recorder->employee_name))
                                    {{ $purchase->recorder->employee_name }}
                                @else
                                    System
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-8 text-center text-zinc-500 dark:text-zinc-400">No purchases found</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="px-4 py-3 border-t border-zinc-200 dark:border-zinc-700">
            {{ $purchases->links() }}
        </div>
    </div>
    @endif
</div>
