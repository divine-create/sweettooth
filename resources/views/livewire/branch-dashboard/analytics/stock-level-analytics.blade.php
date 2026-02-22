<div class="p-3 space-y-3">

    <style>
        .scrollbar-thin::-webkit-scrollbar {
            width: 8px;
        }
        .scrollbar-thin::-webkit-scrollbar-track {
            background: transparent;
        }
        .scrollbar-thin::-webkit-scrollbar-thumb {
            @apply bg-zinc-300 dark:bg-zinc-700 rounded-full;
        }
        .scrollbar-thin::-webkit-scrollbar-thumb:hover {
            @apply bg-zinc-400 dark:bg-zinc-600;
        }
    </style>

    <x-breadcrumb
        title="Stock Level Analytics"
        :items="[
            ['label' => 'Dashboard', 'url' => branch_route('branch-dashboard.index')],
            ['label' => 'Analytics'],
            ['label' => 'Stock Level Analytics']
        ]"
        :compact="false"
        :with-icons="true"
    />

    <!-- Actions -->
    <div class="flex flex-wrap justify-end gap-2 mb-3">
        <button wire:click="generateReport"
            wire:loading.attr="disabled"
            wire:target="generateReport"
            class="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg font-medium transition-all duration-200 hover:shadow-lg active:scale-95 shadow-sm disabled:opacity-60">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-3-3v6m8 4H4a1 1 0 01-1-1V6a1 1 0 011-1h10l6 6v7a1 1 0 01-1 1z"/>
            </svg>
            <span wire:loading.remove wire:target="generateReport">Generate Report</span>
            <span wire:loading wire:target="generateReport">Generating...</span>
        </button>
        <button wire:click="exportCSV" 
            class="inline-flex items-center gap-2 px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg font-medium transition-all duration-200 hover:shadow-lg active:scale-95 shadow-sm">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2m0 0v-8m0 8H3m15 0h3"/>
            </svg>
            CSV
        </button>
        <button wire:click="exportCSV" 
            class="inline-flex items-center gap-2 px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg font-medium transition-all duration-200 hover:shadow-lg active:scale-95 shadow-sm">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
            </svg>
            CSV
        </button>
    </div>

    @if (session('success'))
        <div class="p-3 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-700 rounded text-xs text-green-800 dark:text-green-200 mb-3">
            <div class="flex flex-wrap items-center gap-2">
                <span>{{ session('success') }}</span>
                @if($generatedReportId)
                    <a href="{{ branch_route('branch-dashboard.reporting.report.view', ['id' => $generatedReportId]) }}"
                       class="inline-flex items-center px-2 py-1 rounded bg-green-700 text-white hover:bg-green-800">
                        View Report
                    </a>
                @endif
            </div>
        </div>
    @endif

    @if (session('error'))
        <div class="p-3 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-700 rounded text-xs text-red-800 dark:text-red-200 mb-3">
            {{ session('error') }}
        </div>
    @endif

    {{-- Smart Insights Panel --}}
    @if(count($smartInsights) > 0)
        <div class="bg-gradient-to-r from-blue-50 to-indigo-50 dark:from-zinc-800 dark:to-zinc-700 rounded-lg shadow-sm border border-blue-200 dark:border-zinc-600 p-4">
            <h3 class="text-sm font-bold text-zinc-800 dark:text-zinc-100 mb-3 flex items-center">
                <svg class="w-4 h-4 mr-1.5 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                Smart Insights
            </h3>
            <div class="space-y-2">
                @foreach($smartInsights as $insight)
                    @php
                        $bgColor = match($insight['type']) {
                            'success' => 'bg-green-50 dark:bg-green-900/20 border-green-200 dark:border-green-800',
                            'warning' => 'bg-yellow-50 dark:bg-yellow-900/20 border-yellow-200 dark:border-yellow-800',
                            'critical' => 'bg-red-50 dark:bg-red-900/20 border-red-200 dark:border-red-800',
                            default => 'bg-blue-50 dark:bg-blue-900/20 border-blue-200 dark:border-blue-800'
                        };
                    @endphp
                    <div class="flex items-start gap-3 p-3 {{ $bgColor }} border rounded-lg">
                        <span class="text-2xl">{{ $insight['icon'] }}</span>
                        <p class="text-sm text-zinc-800 dark:text-zinc-200 flex-1">{{ $insight['message'] }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    {{-- Summary Cards --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3">
        <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-sm border border-zinc-200 dark:border-zinc-700 p-3">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs font-medium text-zinc-500 dark:text-zinc-400 mb-1">Total Items</p>
                    <p class="text-xl font-bold text-blue-600 dark:text-blue-400">
                        {{ number_format($summary['total_items']) }}
                    </p>
                    <p class="text-xs text-zinc-500 dark:text-zinc-400 mt-1">
                        In inventory
                    </p>
                </div>
                <div class="text-3xl text-blue-500 opacity-20">📦</div>
            </div>
        </div>

        <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-sm border border-zinc-200 dark:border-zinc-700 p-3">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs font-medium text-zinc-500 dark:text-zinc-400 mb-1">Available Stock</p>
                    <p class="text-xl font-bold text-green-600 dark:text-green-400">
                        {{ number_format($summary['total_available'], 0) }}
                    </p>
                    <p class="text-xs text-zinc-500 dark:text-zinc-400 mt-1">
                        Units ready
                    </p>
                </div>
                <div class="text-3xl text-green-500 opacity-20">✓</div>
            </div>
        </div>

        <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-sm border border-zinc-200 dark:border-zinc-700 p-3">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs font-medium text-zinc-500 dark:text-zinc-400 mb-1">Low Stock</p>
                    <p class="text-xl font-bold text-yellow-600 dark:text-yellow-400">
                        {{ number_format($summary['low_stock_count']) }}
                    </p>
                    <p class="text-xs text-zinc-500 dark:text-zinc-400 mt-1">
                        Need reorder
                    </p>
                </div>
                <div class="text-3xl text-yellow-500 opacity-20">⚠</div>
            </div>
        </div>

        <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-sm border border-zinc-200 dark:border-zinc-700 p-3">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs font-medium text-zinc-500 dark:text-zinc-400 mb-1">Critical/Expired</p>
                    <p class="text-xl font-bold text-red-600 dark:text-red-400">
                        {{ number_format($summary['critical_items'] + $summary['expired_items']) }}
                    </p>
                    <p class="text-xs text-zinc-500 dark:text-zinc-400 mt-1">
                        Needs attention
                    </p>
                </div>
                <div class="text-3xl text-red-500 opacity-20">🔴</div>
            </div>
        </div>

        <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-sm border border-zinc-200 dark:border-zinc-700 p-3">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs font-medium text-zinc-500 dark:text-zinc-400 mb-1">Out of Stock</p>
                    <p class="text-xl font-bold text-gray-600 dark:text-gray-400">
                        {{ number_format($summary['out_of_stock']) }}
                    </p>
                    <p class="text-xs text-zinc-500 dark:text-zinc-400 mt-1">
                        Items depleted
                    </p>
                </div>
                <div class="text-3xl text-gray-500 opacity-20">❌</div>
            </div>
        </div>
    </div>

    {{-- Filters Section --}}
    <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-sm border border-zinc-200 dark:border-zinc-700 p-3">
        <div class="flex justify-between items-center mb-3">
            <h2 class="text-sm font-semibold text-zinc-800 dark:text-zinc-100 flex items-center">
                <svg class="w-4 h-4 mr-1.5 text-zinc-600 dark:text-zinc-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707L14.293 13H10v5l-4-4v-3.586L3.293 7.293A1 1 0 013 6.586V4z" />
                </svg>
                Filters & Search
            </h2>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3">
            <div>
                <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Search Item</label>
                <input type="text" wire:model.live.debounce.300ms="searchTerm"
                    placeholder="Search by name or SKU..."
                    class="w-full px-4 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-800 dark:text-zinc-200 focus:ring-2 focus:ring-blue-500">
            </div>

            <div>
                <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Category</label>
                <select wire:model.live="selectedCategory"
                    class="w-full px-4 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-800 dark:text-zinc-200 focus:ring-2 focus:ring-blue-500">
                    <option value="">All Categories</option>
                    @foreach($categories as $category)
                        <option value="{{ $category }}">{{ str_replace('_', ' ', ucfirst($category)) }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Health Status</label>
                <select wire:model.live="healthFilter"
                    class="w-full px-4 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-800 dark:text-zinc-200 focus:ring-2 focus:ring-blue-500">
                    <option value="">All Status</option>
                    @foreach($healthStatuses as $status)
                        <option value="{{ $status }}">{{ ucfirst($status) }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">From</label>
                <input type="date" wire:model.live="dateFrom"
                    class="w-full px-4 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-800 dark:text-zinc-200 focus:ring-2 focus:ring-blue-500">
            </div>

            <div>
                <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">To</label>
                <input type="date" wire:model.live="dateTo"
                    class="w-full px-4 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-800 dark:text-zinc-200 focus:ring-2 focus:ring-blue-500">
            </div>
        </div>
    </div>

    {{-- Period Comparison --}}
    <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-sm border border-zinc-200 dark:border-zinc-700 p-4">
        <h3 class="text-sm font-bold text-zinc-800 dark:text-zinc-100 mb-3 flex items-center">
            <svg class="w-4 h-4 mr-1.5 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
            </svg>
            Period Comparison
            <span class="ml-2 text-xs font-normal text-zinc-600 dark:text-zinc-400">
                ({{ \Carbon\Carbon::parse($dateFrom)->format('M d') }} - {{ \Carbon\Carbon::parse($dateTo)->format('M d, Y') }})
            </span>
        </h3>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div class="bg-zinc-50 dark:bg-zinc-700/50 rounded-lg p-4">
                <p class="text-xs text-zinc-500 dark:text-zinc-400 mb-1">Total Quantity Moved</p>
                <div class="flex items-end gap-3">
                    <div>
                        <p class="text-xs text-zinc-500 dark:text-zinc-400">Current Period</p>
                        <p class="text-2xl font-bold text-blue-600 dark:text-blue-400">
                            {{ number_format($periodComparison['current_qty'], 0) }}
                        </p>
                    </div>
                    <div>
                        <p class="text-xs text-zinc-500 dark:text-zinc-400">Previous Period</p>
                        <p class="text-lg font-semibold text-zinc-600 dark:text-zinc-400">
                            {{ number_format($periodComparison['previous_qty'], 0) }}
                        </p>
                    </div>
                    <div class="flex-1 text-right">
                        @php
                            $qtyPercent = $periodComparison['qty_change_percent'];
                        @endphp
                        <p class="text-xs text-zinc-500 dark:text-zinc-400">Change</p>
                        <p class="text-lg font-bold {{ $qtyPercent >= 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                            {{ $qtyPercent >= 0 ? '↑' : '↓' }} {{ number_format(abs($qtyPercent), 1) }}%
                        </p>
                    </div>
                </div>
            </div>

            <div class="bg-zinc-50 dark:bg-zinc-700/50 rounded-lg p-4">
                <p class="text-xs text-zinc-500 dark:text-zinc-400 mb-1">Total Movements</p>
                <div class="flex items-end gap-3">
                    <div>
                        <p class="text-xs text-zinc-500 dark:text-zinc-400">Current Period</p>
                        <p class="text-2xl font-bold text-purple-600 dark:text-purple-400">
                            {{ number_format($periodComparison['current_movements']) }}
                        </p>
                    </div>
                    <div>
                        <p class="text-xs text-zinc-500 dark:text-zinc-400">Previous Period</p>
                        <p class="text-lg font-semibold text-zinc-600 dark:text-zinc-400">
                            {{ number_format($periodComparison['previous_movements']) }}
                        </p>
                    </div>
                    <div class="flex-1 text-right">
                        @php
                            $movementPercent = $periodComparison['movement_change_percent'];
                        @endphp
                        <p class="text-xs text-zinc-500 dark:text-zinc-400">Change</p>
                        <p class="text-lg font-bold {{ $movementPercent >= 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                            {{ $movementPercent >= 0 ? '↑' : '↓' }} {{ number_format(abs($movementPercent), 1) }}%
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Analytics Grid --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-3">
        {{-- Health Status Breakdown --}}
        <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-sm border border-zinc-200 dark:border-zinc-700 p-4">
            <h3 class="text-base font-semibold text-zinc-800 dark:text-zinc-100 mb-4">Health Status Distribution</h3>
            <div class="space-y-2">
                @forelse($healthBreakdown as $health)
                    @php
                        $statusColors = [
                            'good' => 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200',
                            'warning' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200',
                            'critical' => 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200',
                            'expired' => 'bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-200',
                        ];
                        $percentage = $summary['total_items'] > 0 ? ($health['count'] / $summary['total_items']) * 100 : 0;
                    @endphp
                    <div class="flex items-center justify-between p-3 bg-gray-50 dark:bg-zinc-700/50 rounded">
                        <div class="flex items-center flex-1">
                            <span class="px-2 py-1 text-xs font-semibold rounded {{ $statusColors[$health['status']] ?? '' }} mr-3">
                                {{ ucfirst($health['status']) }}
                            </span>
                            <div class="flex-1">
                                <div class="w-full bg-zinc-200 dark:bg-zinc-600 rounded-full h-2">
                                    <div class="h-2 rounded-full {{ str_replace(['100', '800'], ['600', '600'], $statusColors[$health['status']] ?? '') }}"
                                        style="width: {{ $percentage }}%"></div>
                                </div>
                            </div>
                        </div>
                        <div class="text-right ml-4">
                            <p class="font-bold text-zinc-900 dark:text-zinc-100">{{ number_format($health['count']) }}</p>
                            <p class="text-xs text-zinc-500 dark:text-zinc-400">{{ number_format($percentage, 1) }}%</p>
                        </div>
                    </div>
                @empty
                    <p class="text-center text-zinc-500 dark:text-zinc-400 py-8">No data available</p>
                @endforelse
            </div>
        </div>

        {{-- Category Breakdown --}}
        <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-sm border border-zinc-200 dark:border-zinc-700 p-4">
            <h3 class="text-base font-semibold text-zinc-800 dark:text-zinc-100 mb-4">Category Distribution</h3>
            <div class="space-y-3 max-h-[400px] overflow-y-auto scrollbar-thin">
                @forelse($categoryBreakdown as $index => $cat)
                    <div class="flex items-center justify-between p-3 bg-gray-50 dark:bg-zinc-700/50 rounded hover:bg-gray-100 dark:hover:bg-zinc-700 transition-colors">
                        <div class="flex items-center flex-1">
                            <div class="flex items-center justify-center w-8 h-8 rounded-full bg-blue-100 dark:bg-blue-900 text-blue-600 dark:text-blue-400 font-bold text-sm mr-3">
                                {{ $index + 1 }}
                            </div>
                            <div>
                                <p class="font-medium text-zinc-900 dark:text-zinc-100">{{ str_replace('_', ' ', ucfirst($cat['category'])) }}</p>
                                <p class="text-xs text-zinc-500 dark:text-zinc-400">
                                    {{ number_format($cat['total_available'], 0) }} units available
                                    @if($cat['low_stock'] > 0)
                                        • <span class="text-yellow-600">{{ $cat['low_stock'] }} low</span>
                                    @endif
                                </p>
                            </div>
                        </div>
                        <div class="text-right">
                            <p class="font-bold text-blue-600 dark:text-blue-400">{{ number_format($cat['count']) }}</p>
                            <p class="text-xs text-zinc-500 dark:text-zinc-400">items</p>
                        </div>
                    </div>
                @empty
                    <p class="text-center text-zinc-500 dark:text-zinc-400 py-8">No data available</p>
                @endforelse
            </div>
        </div>
    </div>

    {{-- Stock Level Trend Table --}}
    @if($stockLevelTrend->isNotEmpty())
        <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-sm border border-zinc-200 dark:border-zinc-700 p-4">
            <h3 class="text-base font-semibold text-zinc-800 dark:text-zinc-100 mb-4">Stock Level Trend (Last 14 Days)</h3>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-zinc-100 dark:bg-zinc-700">
                        <tr>
                            <th class="px-4 py-2 text-left text-zinc-700 dark:text-zinc-300">Date</th>
                            <th class="px-4 py-2 text-right text-zinc-700 dark:text-zinc-300">Stock In</th>
                            <th class="px-4 py-2 text-right text-zinc-700 dark:text-zinc-300">Stock Out</th>
                            <th class="px-4 py-2 text-right text-zinc-700 dark:text-zinc-300">Net Change</th>
                            <th class="px-4 py-2 text-right text-zinc-700 dark:text-zinc-300">Movements</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                        @foreach($stockLevelTrend as $trend)
                            <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-700/50">
                                <td class="px-4 py-2 font-medium text-zinc-900 dark:text-zinc-100">{{ $trend['date'] }}</td>
                                <td class="px-4 py-2 text-right text-green-600 dark:text-green-400">
                                    +{{ number_format($trend['stock_in'], 2) }}
                                </td>
                                <td class="px-4 py-2 text-right text-red-600 dark:text-red-400">
                                    -{{ number_format($trend['stock_out'], 2) }}
                                </td>
                                <td class="px-4 py-2 text-right font-semibold {{ $trend['net_change'] >= 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                                    {{ $trend['net_change'] >= 0 ? '+' : '' }}{{ number_format($trend['net_change'], 2) }}
                                </td>
                                <td class="px-4 py-2 text-right text-zinc-900 dark:text-zinc-100">
                                    {{ number_format($trend['movements']) }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    {{-- Two Column Layout --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-3">
        {{-- Reorder Recommendations --}}
        <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-sm border border-zinc-200 dark:border-zinc-700 p-4">
            <h3 class="text-base font-semibold text-zinc-800 dark:text-zinc-100 mb-4">Reorder Recommendations</h3>
            <div class="space-y-2 max-h-[500px] overflow-y-auto scrollbar-thin">
                @forelse($reorderRecommendations as $rec)
                    @php
                        $urgencyColors = [
                            'Critical' => 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200',
                            'High' => 'bg-orange-100 text-orange-800 dark:bg-orange-900 dark:text-orange-200',
                            'Medium' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200',
                        ];
                    @endphp
                    <div class="flex items-start justify-between p-3 bg-gray-50 dark:bg-zinc-700/50 rounded hover:bg-gray-100 dark:hover:bg-zinc-700 transition-colors">
                        <div class="flex-1">
                            <div class="flex items-center gap-2 mb-1">
                                <p class="font-medium text-zinc-900 dark:text-zinc-100">{{ $rec['item']->name }}</p>
                                <span class="px-2 py-0.5 text-xs font-semibold rounded {{ $urgencyColors[$rec['urgency']] ?? '' }}">
                                    {{ $rec['urgency'] }}
                                </span>
                            </div>
                            <p class="text-xs text-zinc-500 dark:text-zinc-400">SKU: {{ $rec['item']->sku }}</p>
                            <div class="mt-2 text-xs">
                                <span class="text-zinc-600 dark:text-zinc-400">Current: </span>
                                <span class="font-semibold text-red-600 dark:text-red-400">{{ number_format($rec['current_qty'], 2) }}</span>
                                <span class="text-zinc-500 mx-1">|</span>
                                <span class="text-zinc-600 dark:text-zinc-400">Reorder Level: </span>
                                <span class="font-semibold">{{ number_format($rec['reorder_level'], 2) }}</span>
                            </div>
                        </div>
                        <div class="text-right ml-3">
                            <p class="text-xs text-zinc-500 dark:text-zinc-400">Suggested</p>
                            <p class="text-lg font-bold text-blue-600 dark:text-blue-400">{{ number_format($rec['suggested_qty'], 0) }}</p>
                            <p class="text-xs text-zinc-500 dark:text-zinc-400">{{ $rec['item']->uom }}</p>
                        </div>
                    </div>
                @empty
                    <div class="text-center py-8 text-zinc-500 dark:text-zinc-400">
                        <p class="text-2xl mb-2">✅</p>
                        <p class="text-sm">All items are well-stocked!</p>
                    </div>
                @endforelse
            </div>
        </div>

        {{-- Turnover Analysis --}}
        <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-sm border border-zinc-200 dark:border-zinc-700 p-4">
            <h3 class="text-base font-semibold text-zinc-800 dark:text-zinc-100 mb-4">Stock Turnover Analysis</h3>
            <div class="space-y-2 max-h-[500px] overflow-y-auto scrollbar-thin">
                @forelse($turnoverAnalysis as $index => $item)
                    @php
                        $velocityColors = [
                            'High' => 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200',
                            'Moderate' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200',
                            'Low' => 'bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-200',
                        ];
                    @endphp
                    <div class="flex items-center justify-between p-3 bg-gray-50 dark:bg-zinc-700/50 rounded">
                        <div class="flex items-center flex-1">
                            <div class="flex items-center justify-center w-8 h-8 rounded-full bg-purple-100 dark:bg-purple-900 text-purple-600 dark:text-purple-400 font-bold text-sm mr-3">
                                {{ $index + 1 }}
                            </div>
                            <div>
                                <p class="font-medium text-zinc-900 dark:text-zinc-100">{{ $item['item_name'] }}</p>
                                <p class="text-xs text-zinc-500 dark:text-zinc-400">
                                    {{ $item['sku'] }} • {{ number_format($item['movement_count']) }} movements
                                </p>
                                <div class="mt-1">
                                    <span class="px-2 py-0.5 text-xs font-semibold rounded {{ $velocityColors[$item['velocity']] ?? '' }}">
                                        {{ $item['velocity'] }} Velocity
                                    </span>
                                </div>
                            </div>
                        </div>
                        <div class="text-right ml-3">
                            <p class="font-bold text-purple-600 dark:text-purple-400">{{ number_format($item['total_moved'], 0) }}</p>
                            <p class="text-xs text-zinc-500 dark:text-zinc-400">units moved</p>
                            <p class="text-xs text-zinc-500 dark:text-zinc-400 mt-0.5">Rate: {{ number_format($item['turnover_rate'], 2) }}</p>
                        </div>
                    </div>
                @empty
                    <p class="text-center text-zinc-500 dark:text-zinc-400 py-8">No turnover data available</p>
                @endforelse
            </div>
        </div>
    </div>

    {{-- Stock Items Table --}}
    <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-sm border border-zinc-200 dark:border-zinc-700 p-4">
        <h3 class="text-base font-semibold text-zinc-800 dark:text-zinc-100 mb-4">Stock Items Details</h3>
        <div class="overflow-x-auto" wire:loading.class="opacity-50" wire:target="updatedSearchTerm, updatedSelectedCategory, updatedHealthFilter, updatedDateFrom, updatedDateTo">
            <table class="w-full text-sm text-left">
                <thead class="text-xs uppercase bg-zinc-100 dark:bg-zinc-700 text-zinc-700 dark:text-zinc-300">
                    <tr>
                        <th class="px-4 py-3">Item</th>
                        <th class="px-4 py-3">Category</th>
                        <th class="px-4 py-3">Available</th>
                        <th class="px-4 py-3">Reserved</th>
                        <th class="px-4 py-3">Damaged</th>
                        <th class="px-4 py-3">Reorder Level</th>
                        <th class="px-4 py-3">Health Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                    @forelse($stocks as $stock)
                        <tr class="bg-white dark:bg-zinc-800 hover:bg-zinc-50 dark:hover:bg-zinc-700/50">
                            <td class="px-4 py-3">
                                <div class="font-medium text-zinc-900 dark:text-zinc-100">{{ $stock->item->name }}</div>
                                <div class="text-xs text-gray-500 dark:text-gray-400">{{ $stock->item->sku }}</div>
                            </td>
                            <td class="px-4 py-3">
                                <span class="px-2 py-1 text-xs font-semibold rounded bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200">
                                    {{ str_replace('_', ' ', ucfirst($stock->item->category)) }}
                                </span>
                            </td>
                            <td class="px-4 py-3">
                                <span class="font-semibold {{ $stock->quantity_available <= 0 ? 'text-red-600 dark:text-red-400' : 'text-zinc-900 dark:text-zinc-100' }}">
                                    {{ number_format($stock->quantity_available, 2) }}
                                </span>
                                <span class="text-xs text-zinc-500 ml-1">{{ $stock->item->uom }}</span>
                            </td>
                            <td class="px-4 py-3 text-zinc-900 dark:text-zinc-100">
                                {{ number_format($stock->quantity_reserved, 2) }} {{ $stock->item->uom }}
                            </td>
                            <td class="px-4 py-3">
                                <span class="{{ $stock->quantity_damaged > 0 ? 'text-orange-600 dark:text-orange-400 font-semibold' : 'text-zinc-900 dark:text-zinc-100' }}">
                                    {{ number_format($stock->quantity_damaged, 2) }}
                                </span>
                                <span class="text-xs text-zinc-500 ml-1">{{ $stock->item->uom }}</span>
                            </td>
                            <td class="px-4 py-3 text-zinc-900 dark:text-zinc-100">
                                @if($stock->item->reorder_level)
                                    {{ number_format($stock->item->reorder_level, 2) }}
                                @else
                                    <span class="text-gray-400 dark:text-gray-500">Not set</span>
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                @php
                                    $badgeColors = match($stock->health_status) {
                                        'good' => 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200',
                                        'warning' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200',
                                        'critical' => 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200',
                                        'expired' => 'bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-200',
                                        default => 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200'
                                    };
                                @endphp
                                <span class="px-2 py-1 text-xs font-semibold rounded {{ $badgeColors }}">
                                    {{ ucfirst($stock->health_status) }}
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-8 text-center text-gray-500 dark:text-gray-400">
                                No stock items found
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-4">
            {{ $stocks->links() }}
        </div>
    </div>

</div>
