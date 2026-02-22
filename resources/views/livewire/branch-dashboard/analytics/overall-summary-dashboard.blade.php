<div class="p-3 space-y-3" wire:poll.60s="refresh" wire:poll:keep-alive x-data="{ showFilters: false }">

    <x-breadcrumb title="Overall Analytics Summary" :items="[
        ['label' => 'Dashboard', 'url' => branch_route('branch-dashboard.index')],
        ['label' => 'Analytics'],
        ['label' => 'Overall Summary'],
    ]" :compact="false" :with-icons="true" />

    {{-- Header with Filter Bar --}}
    <div class="bg-gradient-to-r from-blue-600 to-blue-700 text-white rounded-lg shadow-lg p-4">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div>
                <h2 class="text-2xl font-bold">Overall Analytics Summary</h2>
                <p class="text-sm opacity-90 mt-1">Comprehensive inventory overview without charts</p>
            </div>
            <div class="flex items-center gap-2">
                <button wire:click="generateReport"
                    wire:loading.attr="disabled"
                    wire:target="generateReport"
                    class="px-4 py-2 bg-white/20 hover:bg-white/30 rounded-lg font-medium transition-colors flex items-center gap-2 disabled:opacity-60">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-3-3v6m8 4H4a1 1 0 01-1-1V6a1 1 0 011-1h10l6 6v7a1 1 0 01-1 1z"/>
                    </svg>
                    <span wire:loading.remove wire:target="generateReport">Generate Report</span>
                    <span wire:loading wire:target="generateReport">Generating...</span>
                </button>
                <button wire:click="refresh"
                    class="px-4 py-2 bg-white/20 hover:bg-white/30 rounded-lg font-medium transition-colors flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                    </svg>
                    Refresh
                </button>
                <button wire:click="exportCSV"
                    class="px-4 py-2 bg-white/20 hover:bg-white/30 rounded-lg font-medium transition-colors flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    Export CSV
                </button>
                <button wire:click="exportCSV"
                    class="px-4 py-2 bg-white/20 hover:bg-white/30 rounded-lg font-medium transition-colors flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    Export CSV
                </button>
            </div>
        </div>

        {{-- Date Filter --}}
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">
            <div>
                <label class="block text-sm font-medium opacity-90 mb-1">From Date</label>
                <input type="date" wire:model.live="dateFrom"
                    class="w-full px-4 py-2 border border-white/30 rounded-lg bg-white/10 text-white placeholder-white/60 focus:ring-2 focus:ring-white/50 focus:border-white/50">
            </div>
            <div>
                <label class="block text-sm font-medium opacity-90 mb-1">To Date</label>
                <input type="date" wire:model.live="dateTo"
                    class="w-full px-4 py-2 border border-white/30 rounded-lg bg-white/10 text-white placeholder-white/60 focus:ring-2 focus:ring-white/50 focus:border-white/50">
            </div>
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

    {{-- Quick Navigation Links --}}
    <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-sm border border-zinc-200 dark:border-zinc-700 p-4">
        <h3 class="text-sm font-semibold text-zinc-700 dark:text-zinc-300 mb-3">Quick Access</h3>
        <div class="flex flex-wrap gap-2">
            <a href="{{ branch_route('branch-dashboard.analytics.stock-level') }}"
                class="px-3 py-1.5 bg-blue-100 hover:bg-blue-200 dark:bg-blue-900/30 dark:hover:bg-blue-900/50 text-blue-800 dark:text-blue-200 rounded-lg text-sm font-medium transition-colors min-w-[120px] text-center">
                📊 Stock Level
            </a>
            <a href="{{ branch_route('branch-dashboard.analytics.stock-movement') }}"
                class="px-3 py-1.5 bg-green-100 hover:bg-green-200 dark:bg-green-900/30 dark:hover:bg-green-900/50 text-green-800 dark:text-green-200 rounded-lg text-sm font-medium transition-colors min-w-[120px] text-center">
                🔄 Stock Movement
            </a>
            <a href="{{ branch_route('branch-dashboard.analytics.purchase') }}"
                class="px-3 py-1.5 bg-purple-100 hover:bg-purple-200 dark:bg-purple-900/30 dark:hover:bg-purple-900/50 text-purple-800 dark:text-purple-200 rounded-lg text-sm font-medium transition-colors min-w-[120px] text-center">
                🛒 Purchases
            </a>
            <a href="{{ branch_route('branch-dashboard.analytics.request-dispatch') }}"
                class="px-3 py-1.5 bg-yellow-100 hover:bg-yellow-200 dark:bg-yellow-900/30 dark:hover:bg-yellow-900/50 text-yellow-800 dark:text-yellow-200 rounded-lg text-sm font-medium transition-colors min-w-[120px] text-center">
                📋 Requests
            </a>
            <a href="{{ branch_route('branch-dashboard.analytics.stock-valuation') }}"
                class="px-3 py-1.5 bg-pink-100 hover:bg-pink-200 dark:bg-pink-900/30 dark:hover:bg-pink-900/50 text-pink-800 dark:text-pink-200 rounded-lg text-sm font-medium transition-colors min-w-[120px] text-center">
                💰 Valuation
            </a>
            <a href="{{ branch_route('branch-dashboard.analytics.alerts') }}"
                class="px-3 py-1.5 bg-red-100 hover:bg-red-200 dark:bg-red-900/30 dark:hover:bg-red-900/50 text-red-800 dark:text-red-200 rounded-lg text-sm font-medium transition-colors min-w-[120px] text-center">
                🚨 Alerts
            </a>
        </div>
    </div>

    {{-- Key Metrics Cards --}}
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
        {{-- Total Stock Value --}}
        <div class="bg-gradient-to-br from-blue-500 to-blue-600 text-white rounded-lg shadow-lg p-5">
            <div class="flex items-start justify-between">
                <div class="flex-1 min-w-0">
                    <p class="text-sm opacity-90 font-medium truncate">Total Stock Value</p>
                    <p class="text-3xl font-bold mt-2">
                        {{ \App\Helpers\LocalizationHelper::formatCurrency($summary['total_stock_value'] ?? 0) }}
                    </p>
                    <p class="text-xs opacity-75 mt-2 truncate">{{ $summary['total_items'] }} items in inventory</p>
                </div>
                <div class="bg-white/20 p-3 rounded-lg flex-shrink-0">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                    </svg>
                </div>
            </div>
            @if($summary['stock_value_change_percentage'] != 0)
                <div class="mt-3 pt-3 border-t border-white/20">
                    <div class="flex items-center gap-2">
                        @if($summary['stock_value_change_percentage'] > 0)
                            <svg class="w-4 h-4 text-green-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
                            </svg>
                            <span class="text-sm font-medium text-green-300 truncate">{{ abs($summary['stock_value_change_percentage']) }}% increase</span>
                        @else
                            <svg class="w-4 h-4 text-red-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 17h8m0 0V9m0 8l-8-8-4 4-6-6" />
                            </svg>
                            <span class="text-sm font-medium text-red-300 truncate">{{ abs($summary['stock_value_change_percentage']) }}% decrease</span>
                        @endif
                    </div>
                </div>
            @endif
        </div>

        {{-- Purchase Value --}}
        <div class="bg-gradient-to-br from-green-500 to-green-600 text-white rounded-lg shadow-lg p-5">
            <div class="flex items-start justify-between">
                <div class="flex-1 min-w-0">
                    <p class="text-sm opacity-90 font-medium truncate">Purchase Value</p>
                    <p class="text-3xl font-bold mt-2">
                        {{ \App\Helpers\LocalizationHelper::formatCurrency($summary['total_purchase_value'] ?? 0) }}
                    </p>
                    <p class="text-xs opacity-75 mt-2 truncate">{{ $summary['total_purchases'] }} purchases</p>
                </div>
                <div class="bg-white/20 p-3 rounded-lg flex-shrink-0">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z" />
                    </svg>
                </div>
            </div>
        </div>

        {{-- Stock Movements --}}
        <div class="bg-gradient-to-br from-purple-500 to-purple-600 text-white rounded-lg shadow-lg p-5">
            <div class="flex items-start justify-between">
                <div class="flex-1 min-w-0">
                    <p class="text-sm opacity-90 font-medium truncate">Stock Movements</p>
                    <p class="text-3xl font-bold mt-2">{{ number_format($summary['total_movements']) }}</p>
                    <div class="flex flex-wrap items-center gap-4 mt-2 text-xs opacity-75">
                        <span class="flex items-center gap-1 truncate max-w-[60px]">
                            <span class="text-green-300">↑</span> {{ number_format($summary['stock_in'], 0) }} In
                        </span>
                        <span class="flex items-center gap-1 truncate max-w-[60px]">
                            <span class="text-red-300">↓</span> {{ number_format($summary['stock_out'], 0) }} Out
                        </span>
                    </div>
                </div>
                <div class="bg-white/20 p-3 rounded-lg flex-shrink-0">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4" />
                    </svg>
                </div>
            </div>
        </div>

        {{-- Requests --}}
        <div class="bg-gradient-to-br from-yellow-500 to-yellow-600 text-white rounded-lg shadow-lg p-5">
            <div class="flex items-start justify-between">
                <div class="flex-1 min-w-0">
                    <p class="text-sm opacity-90 font-medium truncate">Requests</p>
                    <p class="text-3xl font-bold mt-2">{{ $summary['total_requests'] }}</p>
                    <div class="flex flex-wrap items-center gap-4 mt-2 text-xs opacity-75">
                        <span class="truncate max-w-[60px]">🕐 {{ $summary['pending_requests'] }} Pending</span>
                        <span class="truncate max-w-[60px]">✓ {{ $summary['completed_requests'] }} Done</span>
                    </div>
                </div>
                <div class="bg-white/20 p-3 rounded-lg flex-shrink-0">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                    </svg>
                </div>
            </div>
        </div>
    </div>

    {{-- Alert Summary Cards --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        {{-- Low Stock Items --}}
        <div class="bg-yellow-50 dark:bg-yellow-900/20 border-l-4 border-yellow-500 rounded-lg shadow-sm p-4">
            <div class="flex items-center gap-3">
                <div class="bg-yellow-100 dark:bg-yellow-900/40 p-3 rounded-lg flex-shrink-0">
                    <svg class="w-8 h-8 text-yellow-600 dark:text-yellow-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                </div>
                <div class="flex-1 min-w-0">
                    <p class="text-sm text-zinc-600 dark:text-zinc-400 font-medium truncate">Low Stock Items</p>
                    <p class="text-3xl font-bold text-yellow-600 dark:text-yellow-500">{{ $summary['low_stock_items'] }}</p>
                    <p class="text-xs text-zinc-500 dark:text-zinc-400 mt-1 truncate">Below reorder level</p>
                </div>
            </div>
        </div>

        {{-- Critical Items --}}
        <div class="bg-red-50 dark:bg-red-900/20 border-l-4 border-red-500 rounded-lg shadow-sm p-4">
            <div class="flex items-center gap-3">
                <div class="bg-red-100 dark:bg-red-900/40 p-3 rounded-lg flex-shrink-0">
                    <svg class="w-8 h-8 text-red-600 dark:text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <div class="flex-1 min-w-0">
                    <p class="text-sm text-zinc-600 dark:text-zinc-400 font-medium truncate">Critical/Expired</p>
                    <p class="text-3xl font-bold text-red-600 dark:text-red-500">{{ $summary['critical_items'] + $summary['expired_items'] }}</p>
                    <p class="text-xs text-zinc-500 dark:text-zinc-400 mt-1 truncate">Immediate attention needed</p>
                </div>
            </div>
        </div>

        {{-- Pending Requests --}}
        <div class="bg-blue-50 dark:bg-blue-900/20 border-l-4 border-blue-500 rounded-lg shadow-sm p-4">
            <div class="flex items-center gap-3">
                <div class="bg-blue-100 dark:bg-blue-900/40 p-3 rounded-lg flex-shrink-0">
                    <svg class="w-8 h-8 text-blue-600 dark:text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <div class="flex-1 min-w-0">
                    <p class="text-sm text-zinc-600 dark:text-zinc-400 font-medium truncate">Pending Requests</p>
                    <p class="text-3xl font-bold text-blue-600 dark:text-blue-500">{{ $summary['pending_requests'] }}</p>
                    <p class="text-xs text-zinc-500 dark:text-zinc-400 mt-1 truncate">Awaiting processing</p>
                </div>
            </div>
        </div>
    </div>

    {{-- Dynamic Insights Panel --}}
    @if($insights->isNotEmpty())
        <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-sm border border-zinc-200 dark:border-zinc-700 p-4">
            <h3 class="text-lg font-semibold text-zinc-800 dark:text-zinc-100 mb-4 flex items-center gap-2">
                <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z" />
                </svg>
                Smart Insights
            </h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                @foreach($insights as $insight)
                    <div class="p-3 rounded-lg border-l-4 {{ match($insight['type']) {
                        'positive' => 'bg-green-50 dark:bg-green-900/20 border-green-500',
                        'negative' => 'bg-red-50 dark:bg-red-900/20 border-red-500',
                        'critical' => 'bg-red-50 dark:bg-red-900/20 border-red-600',
                        'warning' => 'bg-yellow-50 dark:bg-yellow-900/20 border-yellow-500',
                        default => 'bg-blue-50 dark:bg-blue-900/20 border-blue-500',
                    } }}">
                        <div class="flex items-start gap-2">
                            <span class="text-2xl">{{ $insight['icon'] }}</span>
                            <p class="text-sm {{ match($insight['type']) {
                                'positive' => 'text-green-800 dark:text-green-200',
                                'negative' => 'text-red-800 dark:text-red-200',
                                'critical' => 'text-red-900 dark:text-red-100',
                                'warning' => 'text-yellow-800 dark:text-yellow-200',
                                default => 'text-blue-800 dark:text-blue-200',
                            } }} font-medium">{{ $insight['message'] }}</p>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    {{-- Stock Health Overview Table --}}
    <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-sm border border-zinc-200 dark:border-zinc-700 p-4">
        <h3 class="text-lg font-semibold text-zinc-800 dark:text-zinc-100 mb-4 flex items-center gap-2">
            <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
            </svg>
            Stock Health Overview
        </h3>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="text-xs uppercase bg-zinc-100 dark:bg-zinc-700 text-zinc-700 dark:text-zinc-300">
                    <tr>
                        <th class="px-4 py-3 text-left">Item</th>
                        <th class="px-4 py-3 text-center">Stock Level</th>
                        <th class="px-4 py-3 text-center">Status</th>
                        <th class="px-4 py-3 text-center">Health</th>
                        <th class="px-4 py-3 text-center">Last Movement</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                    @forelse($stockHealthTable as $item)
                        <tr class="bg-white dark:bg-zinc-800 hover:bg-zinc-50 dark:hover:bg-zinc-700/50">
                            <td class="px-4 py-3">
                                <div class="font-medium text-zinc-900 dark:text-zinc-100">{{ $item['item_name'] }}</div>
                            </td>
                            <td class="px-4 py-3 text-center">
                                <span class="font-medium text-zinc-700 dark:text-zinc-300">
                                    {{ number_format($item['stock_level'], 0) }} / {{ number_format($item['reorder_level'], 0) }}
                                </span>
                                <span class="text-xs text-zinc-500"> {{ $item['uom'] }}</span>
                            </td>
                            <td class="px-4 py-3 text-center">
                                <span class="px-2.5 py-1 rounded-full text-xs font-semibold {{ match($item['status_color']) {
                                    'red' => 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200',
                                    'yellow' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200',
                                    'orange' => 'bg-orange-100 text-orange-800 dark:bg-orange-900 dark:text-orange-200',
                                    default => 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200',
                                } }}">
                                    {{ $item['status_icon'] }} {{ ucfirst($item['status']) }}
                                </span>
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex items-center justify-center gap-2">
                                    <div class="flex-1 max-w-xs">
                                        <div class="h-2 bg-zinc-200 dark:bg-zinc-700 rounded-full overflow-hidden">
                                            <div class="h-full {{ match($item['status_color']) {
                                                'red' => 'bg-red-500',
                                                'yellow' => 'bg-yellow-500',
                                                'orange' => 'bg-orange-500',
                                                default => 'bg-green-500',
                                            } }}" style="width: {{ min(100, $item['health_percentage']) }}%"></div>
                                        </div>
                                    </div>
                                    <span class="text-xs font-semibold text-zinc-700 dark:text-zinc-300 w-10 text-right">{{ $item['health_percentage'] }}%</span>
                                </div>
                            </td>
                            <td class="px-4 py-3 text-center text-zinc-700 dark:text-zinc-300">{{ $item['last_movement'] }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-4 py-8 text-center text-zinc-500 dark:text-zinc-400">No stock data available</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Two Column Layout: Alerts and Performance --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
        {{-- Top Alerts with Actions --}}
        <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-sm border border-zinc-200 dark:border-zinc-700 p-4">
            <h3 class="text-lg font-semibold text-zinc-800 dark:text-zinc-100 mb-4 flex items-center gap-2">
                <svg class="w-5 h-5 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                </svg>
                Top Alerts
            </h3>
            <div class="space-y-2 max-h-96 overflow-y-auto">
                @forelse($alerts as $alert)
                    <div class="p-3 rounded-lg border-l-4 {{ match($alert['type']) {
                        'expired' => 'bg-red-50 dark:bg-red-900/20 border-red-600',
                        'critical' => 'bg-red-50 dark:bg-red-900/20 border-red-500',
                        'warning' => 'bg-yellow-50 dark:bg-yellow-900/20 border-yellow-500',
                        default => 'bg-orange-50 dark:bg-orange-900/20 border-orange-500',
                    } }}">
                        <div class="flex items-start justify-between gap-2">
                            <div class="flex items-start gap-2 flex-1">
                                <span class="text-xl">{{ $alert['icon'] }}</span>
                                <div class="flex-1">
                                    <p class="font-medium text-sm {{ match($alert['type']) {
                                        'expired' => 'text-red-900 dark:text-red-100',
                                        'critical' => 'text-red-800 dark:text-red-200',
                                        'warning' => 'text-yellow-800 dark:text-yellow-200',
                                        default => 'text-orange-800 dark:text-orange-200',
                                    } }}">{{ $alert['message'] }}</p>
                                </div>
                            </div>
                            <a href="{{ branch_route('branch-dashboard.inventory.items') }}?item={{ $alert['item_id'] }}"
                                class="px-3 py-1 text-xs font-semibold rounded-lg {{ match($alert['type']) {
                                    'expired' => 'bg-red-600 hover:bg-red-700 text-white',
                                    'critical' => 'bg-red-500 hover:bg-red-600 text-white',
                                    'warning' => 'bg-yellow-500 hover:bg-yellow-600 text-white',
                                    default => 'bg-orange-500 hover:bg-orange-600 text-white',
                                } }} transition-colors">
                                {{ $alert['action'] }}
                            </a>
                        </div>
                    </div>
                @empty
                    <p class="text-center text-zinc-500 dark:text-zinc-400 py-8">🎉 No alerts - everything looks good!</p>
                @endforelse
            </div>
        </div>

        {{-- Performance Insights --}}
        <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-sm border border-zinc-200 dark:border-zinc-700 p-4">
            <h3 class="text-lg font-semibold text-zinc-800 dark:text-zinc-100 mb-4 flex items-center gap-2">
                <svg class="w-5 h-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                </svg>
                Performance Metrics
            </h3>
            <div class="space-y-4">
                <div class="p-4 bg-blue-50 dark:bg-blue-900/20 rounded-lg">
                    <p class="text-sm text-zinc-600 dark:text-zinc-400 mb-1">Average Turnover Rate</p>
                    <p class="text-2xl font-bold text-blue-600 dark:text-blue-400">{{ $performanceMetrics['average_turnover_rate'] }}</p>
                    <p class="text-xs text-zinc-500 dark:text-zinc-400 mt-1">units per day</p>
                </div>

                @if($performanceMetrics['fastest_moving'])
                    <div class="p-4 bg-green-50 dark:bg-green-900/20 rounded-lg border-l-4 border-green-500">
                        <div class="flex items-center gap-2 mb-2">
                            <span class="text-2xl">🏆</span>
                            <p class="text-sm font-semibold text-green-800 dark:text-green-200">Fastest-Moving Item</p>
                        </div>
                        <p class="font-medium text-zinc-900 dark:text-zinc-100">{{ $performanceMetrics['fastest_moving']['name'] }}</p>
                        <p class="text-sm text-zinc-600 dark:text-zinc-400">{{ number_format($performanceMetrics['fastest_moving']['quantity'], 0) }} units moved</p>
                    </div>
                @endif

                @if($performanceMetrics['slowest_moving'])
                    <div class="p-4 bg-orange-50 dark:bg-orange-900/20 rounded-lg border-l-4 border-orange-500">
                        <div class="flex items-center gap-2 mb-2">
                            <span class="text-2xl">🐢</span>
                            <p class="text-sm font-semibold text-orange-800 dark:text-orange-200">Slowest-Moving Item</p>
                        </div>
                        <p class="font-medium text-zinc-900 dark:text-zinc-100">{{ $performanceMetrics['slowest_moving']['name'] }}</p>
                        <p class="text-sm text-zinc-600 dark:text-zinc-400">{{ number_format($performanceMetrics['slowest_moving']['quantity'], 0) }} units moved</p>
                    </div>
                @endif

                @if($performanceMetrics['most_requested'])
                    <div class="p-4 bg-purple-50 dark:bg-purple-900/20 rounded-lg border-l-4 border-purple-500">
                        <div class="flex items-center gap-2 mb-2">
                            <span class="text-2xl">📋</span>
                            <p class="text-sm font-semibold text-purple-800 dark:text-purple-200">Most Requested Item</p>
                        </div>
                        <p class="font-medium text-zinc-900 dark:text-zinc-100">{{ $performanceMetrics['most_requested']['name'] }}</p>
                        <p class="text-sm text-zinc-600 dark:text-zinc-400">{{ number_format($performanceMetrics['most_requested']['quantity'], 0) }} units requested</p>
                    </div>
                @endif

                <div class="p-4 bg-zinc-50 dark:bg-zinc-900/20 rounded-lg">
                    <p class="text-sm text-zinc-600 dark:text-zinc-400 mb-1">Expired vs Active Items</p>
                    <div class="flex items-center gap-3">
                        <div class="flex-1">
                            <div class="h-3 bg-zinc-200 dark:bg-zinc-700 rounded-full overflow-hidden">
                                <div class="h-full bg-red-500" style="width: {{ $performanceMetrics['expired_vs_active_percentage'] }}%"></div>
                            </div>
                        </div>
                        <span class="text-lg font-bold text-red-600 dark:text-red-400">{{ $performanceMetrics['expired_vs_active_percentage'] }}%</span>
                    </div>
                    <p class="text-xs text-zinc-500 dark:text-zinc-400 mt-1">expired or expiring soon</p>
                </div>
            </div>
        </div>
    </div>

    {{-- Department/Category Breakdown --}}
    @if($departmentBreakdown->isNotEmpty())
        <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-sm border border-zinc-200 dark:border-zinc-700 p-4">
            <h3 class="text-lg font-semibold text-zinc-800 dark:text-zinc-100 mb-4 flex items-center gap-2">
                <svg class="w-5 h-5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                </svg>
                Category Breakdown
            </h3>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="text-xs uppercase bg-zinc-100 dark:bg-zinc-700 text-zinc-700 dark:text-zinc-300">
                        <tr>
                            <th class="px-4 py-3 text-left">Category</th>
                            <th class="px-4 py-3 text-right">Stock Value</th>
                            <th class="px-4 py-3 text-center">Stock In</th>
                            <th class="px-4 py-3 text-center">Stock Out</th>
                            <th class="px-4 py-3 text-center">Low Items</th>
                            <th class="px-4 py-3 text-center">Requests</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                        @foreach($departmentBreakdown as $dept)
                            <tr class="bg-white dark:bg-zinc-800 hover:bg-zinc-50 dark:hover:bg-zinc-700/50">
                                <td class="px-4 py-3">
                                    <div class="font-medium text-zinc-900 dark:text-zinc-100">{{ $dept['category'] }}</div>
                                    <div class="text-xs text-zinc-500">{{ $dept['item_count'] }} items</div>
                                </td>
                                <td class="px-4 py-3 text-right font-semibold text-zinc-900 dark:text-zinc-100">
                                    {{ \App\Helpers\LocalizationHelper::formatCurrency($dept['stock_value'] ?? 0) }}
                                </td>
                                <td class="px-4 py-3 text-center">
                                    <span class="px-2 py-1 bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-200 rounded text-xs font-medium">
                                        +{{ number_format($dept['stock_in'], 0) }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-center">
                                    <span class="px-2 py-1 bg-red-100 dark:bg-red-900/30 text-red-800 dark:text-red-200 rounded text-xs font-medium">
                                        -{{ number_format($dept['stock_out'], 0) }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-center">
                                    @if($dept['low_items'] > 0)
                                        <span class="px-2 py-1 bg-yellow-100 dark:bg-yellow-900/30 text-yellow-800 dark:text-yellow-200 rounded text-xs font-semibold">
                                            {{ $dept['low_items'] }}
                                        </span>
                                    @else
                                        <span class="text-zinc-400">-</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-center text-zinc-700 dark:text-zinc-300 font-medium">{{ $dept['requests'] }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    {{-- Recent Activity Feed --}}
    <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-sm border border-zinc-200 dark:border-zinc-700 p-4">
        <h3 class="text-lg font-semibold text-zinc-800 dark:text-zinc-100 mb-4 flex items-center gap-2">
            <svg class="w-5 h-5 text-cyan-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            Recent Stock Activity
        </h3>
        <div class="space-y-3 max-h-96 overflow-y-auto">
            @forelse($recentActivity as $activity)
                <div class="flex items-start gap-3 p-3 rounded-lg hover:bg-zinc-50 dark:hover:bg-zinc-700/30 transition-colors">
                    <div class="p-2 rounded-lg {{ match($activity->type) {
                        'in' => 'bg-green-100 dark:bg-green-900/30 text-green-600',
                        'out' => 'bg-red-100 dark:bg-red-900/30 text-red-600',
                        'transfer' => 'bg-blue-100 dark:bg-blue-900/30 text-blue-600',
                        'damaged' => 'bg-orange-100 dark:bg-orange-900/30 text-orange-600',
                        default => 'bg-zinc-100 dark:bg-zinc-700 text-zinc-600',
                    } }}">
                        @if($activity->type === 'in')
                            📥
                        @elseif($activity->type === 'out')
                            📤
                        @elseif($activity->type === 'transfer')
                            🔄
                        @elseif($activity->type === 'damaged')
                            ⚠️
                        @else
                            📦
                        @endif
                    </div>
                    <div class="flex-1 min-w-0">
                        <div class="flex items-start justify-between gap-2">
                            <div class="flex-1">
                                <p class="font-medium text-zinc-900 dark:text-zinc-100">{{ $activity->stock->item->name }}</p>
                                <p class="text-sm text-zinc-600 dark:text-zinc-400">
                                    {{ ucfirst($activity->type) }} movement by {{ $activity->mover->name ?? 'System' }}
                                </p>
                            </div>
                            <div class="text-right">
                                <p class="font-semibold {{ $activity->type === 'in' ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                                    {{ $activity->type === 'in' ? '+' : '-' }}{{ number_format(abs($activity->quantity), 2) }}
                                </p>
                                <p class="text-xs text-zinc-500">{{ \Carbon\Carbon::parse($activity->movement_date)->format('M d, H:i') }}</p>
                            </div>
                        </div>
                        @if($activity->notes)
                            <p class="text-xs text-zinc-500 dark:text-zinc-400 mt-1 italic">{{ $activity->notes }}</p>
                        @endif
                    </div>
                </div>
            @empty
                <p class="text-center text-zinc-500 dark:text-zinc-400 py-8">No recent activity in the selected period</p>
            @endforelse
        </div>
    </div>
</div>
