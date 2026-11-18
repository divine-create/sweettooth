<div class="p-6 bg-gray-50 dark:bg-gray-900 min-h-screen space-y-6">
    <!-- Breadcrumb & Header -->
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 print-hide">
        <div>
            <div class="flex items-center gap-2">
                <span class="px-2.5 py-1 text-xs font-semibold rounded-md bg-indigo-100 text-indigo-800 dark:bg-indigo-900/40 dark:text-indigo-300">
                    Executive & Inventory Reports
                </span>
                <span class="text-xs text-gray-500 dark:text-gray-400">
                    {{ $currentBranch?->name ?? 'All Branches' }}
                </span>
            </div>
            <h1 class="text-2xl md:text-3xl font-bold text-gray-900 dark:text-white mt-1">
                Supply & Purchase Report
            </h1>
            <p class="text-sm text-gray-600 dark:text-gray-400">
                Audit supplier procurement expenditure, vendor debt balances, raw material costs, and purchase orders.
            </p>
        </div>

        <div class="flex items-center gap-3">
            <button type="button" onclick="window.print()"
                class="px-4 py-2 text-sm font-semibold rounded-lg bg-gray-700 hover:bg-gray-800 text-white shadow-sm transition flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                </svg>
                <span>Print Report</span>
            </button>

            <button type="button" wire:click="exportToCsv"
                class="px-4 py-2 text-sm font-semibold rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white shadow-sm transition flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
                <span>Export {{ ucfirst($activeTab) }} CSV</span>
            </button>
        </div>
    </div>

    <!-- Printable Header (Visible only when printing) -->
    <div class="print-header hidden print:block mb-4 pb-3 border-b-2 border-gray-800">
        <div class="flex justify-between items-start">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Sweet Tooth — Supply & Purchase Report</h1>
                <p class="text-xs text-gray-600 mt-1">
                    Branch: {{ $currentBranch?->name ?? 'All Branches' }} &bull;
                    Period: {{ ucwords(str_replace('_', ' ', $dateFilterMode)) }} ({{ $dateFrom ? \Carbon\Carbon::parse($dateFrom)->format('M d, Y') : 'Start' }} to {{ $dateTo ? \Carbon\Carbon::parse($dateTo)->format('M d, Y') : 'Today' }}) &bull;
                    View: {{ ucfirst($activeTab) }}
                </p>
            </div>
            <div class="text-right text-xs text-gray-500">
                <p>Printed: {{ now()->format('M d, Y h:i A') }}</p>
                <p>{{ auth()->user()->name ?? 'System' }}</p>
            </div>
        </div>
    </div>

    <!-- Date Presets & Filter Controls -->
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-4 space-y-4 print-hide">
        <!-- Date Presets Row -->
        <div class="flex flex-wrap items-center justify-between gap-2 border-b border-gray-200 dark:border-gray-700 pb-3">
            <div class="flex flex-wrap items-center gap-1.5 text-xs">
                <span class="font-semibold text-gray-600 dark:text-gray-400 mr-1">Period:</span>
                @foreach([
                    'today' => 'Today',
                    'this_week' => 'This Week',
                    'this_month' => 'This Month',
                    'last_month' => 'Last Month',
                    'last_30_days' => 'Last 30 Days',
                    'this_quarter' => 'This Quarter',
                    'this_year' => 'This Year',
                    'all_time' => 'All Time',
                ] as $presetKey => $presetLabel)
                    <button type="button" wire:click="setDatePreset('{{ $presetKey }}')"
                        class="px-3 py-1.5 rounded-lg font-medium transition {{ $dateFilterMode === $presetKey ? 'bg-indigo-600 text-white shadow-sm' : 'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600' }}">
                        {{ $presetLabel }}
                    </button>
                @endforeach
            </div>

            <!-- Custom Date Inputs -->
            <div class="flex items-center gap-2 text-xs">
                <span class="text-gray-500">From:</span>
                <input type="date" wire:model.live="dateFrom" class="px-2 py-1 rounded-md border border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-xs dark:text-white" />
                <span class="text-gray-500">To:</span>
                <input type="date" wire:model.live="dateTo" class="px-2 py-1 rounded-md border border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-xs dark:text-white" />
            </div>
        </div>

        <!-- Filter Dropdowns Row -->
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
            <div>
                <label class="block text-xs font-semibold text-gray-600 dark:text-gray-400 mb-1">Supplier</label>
                <select wire:model.live="selectedSupplierId" class="w-full text-xs rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white p-2">
                    <option value="all">All Suppliers ({{ $suppliersList->count() }} registered)</option>
                    @foreach($suppliersList as $s)
                        <option value="{{ $s->id }}">{{ $s->name }} ({{ $s->code ?? 'SUP' }})</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-xs font-semibold text-gray-600 dark:text-gray-400 mb-1">Payment Status</label>
                <select wire:model.live="selectedPaymentStatus" class="w-full text-xs rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white p-2">
                    <option value="all">All Payment Statuses</option>
                    <option value="paid">Paid Only</option>
                    <option value="pending">Pending / Unpaid (Outstanding Debt)</option>
                </select>
            </div>

            <div>
                <label class="block text-xs font-semibold text-gray-600 dark:text-gray-400 mb-1">Quick Search</label>
                <div class="relative">
                    <input type="text" wire:model.live.debounce.300ms="search" placeholder="Search supplier, item, or invoice #..."
                        class="w-full pl-8 pr-3 py-2 text-xs rounded-lg border border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-indigo-500" />
                    <svg class="w-3.5 h-3.5 text-gray-400 absolute left-2.5 top-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                </div>
            </div>
        </div>
    </div>

    @if($kpi['total_orders'] === 0)
        <div class="p-4 rounded-xl bg-amber-50 dark:bg-amber-950/40 border border-amber-200 dark:border-amber-800 text-amber-900 dark:text-amber-200 flex flex-col sm:flex-row sm:items-center justify-between gap-3 text-xs print-hide">
            <div class="flex items-center gap-2">
                <svg class="w-5 h-5 text-amber-600 dark:text-amber-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <span>
                    No purchase orders recorded for <strong>{{ ucwords(str_replace('_', ' ', $dateFilterMode)) }}</strong> ({{ $dateFrom ? \Carbon\Carbon::parse($dateFrom)->format('M d, Y') : 'Start' }} &ndash; {{ $dateTo ? \Carbon\Carbon::parse($dateTo)->format('M d, Y') : 'Today' }}).
                </span>
            </div>
            <div class="flex items-center gap-2">
                <button type="button" wire:click="setDatePreset('last_30_days')" class="px-2.5 py-1 rounded bg-amber-200 dark:bg-amber-800 font-semibold hover:bg-amber-300 transition">
                    View Last 30 Days
                </button>
                <button type="button" wire:click="setDatePreset('all_time')" class="px-2.5 py-1 rounded bg-white dark:bg-zinc-800 border border-amber-300 dark:border-amber-700 font-semibold hover:bg-amber-100 transition">
                    View All Time
                </button>
            </div>
        </div>
    @endif

    <!-- Executive KPI Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <!-- Card 1: Total Spend -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-4 border border-gray-200 dark:border-gray-700">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Total Procurement Spend</p>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white mt-1">
                        ₦{{ number_format($kpi['total_spend'], 2) }}
                    </p>
                </div>
                <div class="p-2.5 rounded-lg bg-indigo-50 dark:bg-indigo-900/30 text-indigo-600 dark:text-indigo-400">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" />
                    </svg>
                </div>
            </div>
            <p class="text-xs text-gray-500 dark:text-gray-400 mt-2">
                {{ $kpi['total_orders'] }} purchases &middot; Avg ₦{{ number_format($kpi['avg_order_value'], 0) }} / order
            </p>
        </div>

        <!-- Card 2: Paid Amount -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-4 border border-gray-200 dark:border-gray-700">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Paid to Suppliers</p>
                    <p class="text-2xl font-bold text-emerald-600 dark:text-emerald-400 mt-1">
                        ₦{{ number_format($kpi['total_paid'], 2) }}
                    </p>
                </div>
                <div class="p-2.5 rounded-lg bg-emerald-50 dark:bg-emerald-900/30 text-emerald-600 dark:text-emerald-400">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
            </div>
            <p class="text-xs text-emerald-600 dark:text-emerald-400 mt-2 font-medium">
                {{ $kpi['paid_percentage'] }}% of procurement settled
            </p>
        </div>

        <!-- Card 3: Pending Debt (AP) -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-4 border border-gray-200 dark:border-gray-700">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Outstanding Supplier Debt</p>
                    <p class="text-2xl font-bold text-rose-600 dark:text-rose-400 mt-1">
                        ₦{{ number_format($kpi['total_pending'], 2) }}
                    </p>
                </div>
                <div class="p-2.5 rounded-lg bg-rose-50 dark:bg-rose-900/30 text-rose-600 dark:text-rose-400">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
            </div>
            <p class="text-xs text-rose-600 dark:text-rose-400 mt-2 font-medium">
                {{ $kpi['pending_percentage'] }}% unpaid pending liability
            </p>
        </div>

        <!-- Card 4: Active Suppliers -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-4 border border-gray-200 dark:border-gray-700">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Suppliers Active</p>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white mt-1">
                        {{ $kpi['active_suppliers'] }}
                    </p>
                </div>
                <div class="p-2.5 rounded-lg bg-amber-50 dark:bg-amber-900/30 text-amber-600 dark:text-amber-400">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                    </svg>
                </div>
            </div>
            <p class="text-xs text-gray-500 dark:text-gray-400 mt-2">
                Out of 58 registered vendors
            </p>
        </div>
    </div>

    <!-- 3 Tabs Navigation -->
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
        <div class="border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800/80 px-4 pt-3 print-hide">
            <nav class="flex space-x-2" aria-label="Tabs">
                <!-- Suppliers Summary Tab -->
                <button wire:click="setTab('suppliers')"
                    class="px-4 py-2.5 text-sm font-medium rounded-t-lg transition flex items-center gap-2 border-b-2 {{ $activeTab === 'suppliers' ? 'border-indigo-600 text-indigo-600 bg-white dark:bg-gray-800 dark:text-indigo-400 font-bold' : 'border-transparent text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200' }}">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                    </svg>
                    <span>Suppliers Summary & Debt Balances</span>
                </button>

                <!-- Raw Materials Tab -->
                <button wire:click="setTab('items')"
                    class="px-4 py-2.5 text-sm font-medium rounded-t-lg transition flex items-center gap-2 border-b-2 {{ $activeTab === 'items' ? 'border-indigo-600 text-indigo-600 bg-white dark:bg-gray-800 dark:text-indigo-400 font-bold' : 'border-transparent text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200' }}">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                    </svg>
                    <span>Raw Material Supplies & Unit Costs</span>
                </button>

                <!-- Purchases Log Tab -->
                <button wire:click="setTab('purchases')"
                    class="px-4 py-2.5 text-sm font-medium rounded-t-lg transition flex items-center gap-2 border-b-2 {{ $activeTab === 'purchases' ? 'border-indigo-600 text-indigo-600 bg-white dark:bg-gray-800 dark:text-indigo-400 font-bold' : 'border-transparent text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200' }}">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                    </svg>
                    <span>Purchases & Invoices Log</span>
                </button>
            </nav>
        </div>

        <!-- Tab 1: Suppliers Summary -->
        @if($activeTab === 'suppliers')
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm">
                    <thead class="bg-gray-50 dark:bg-gray-900/50 text-xs uppercase font-semibold text-gray-500 dark:text-gray-400">
                        <tr>
                            <th class="px-4 py-3 text-left">Supplier / Vendor</th>
                            <th class="px-4 py-3 text-left">Contact</th>
                            <th class="px-4 py-3 text-center">Orders</th>
                            <th class="px-4 py-3 text-right">Total Supplied</th>
                            <th class="px-4 py-3 text-right">Amount Paid</th>
                            <th class="px-4 py-3 text-right">Balance Due</th>
                            <th class="px-4 py-3 text-center">Payment %</th>
                            <th class="px-4 py-3 text-center print-hide">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700 bg-white dark:bg-gray-800">
                        @forelse($suppliersSummary as $sup)
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition">
                                <td class="px-4 py-3 font-semibold text-gray-900 dark:text-white">
                                    {{ $sup['supplier_name'] }}
                                    @if($sup['code'] && $sup['code'] !== 'N/A')
                                        <div class="text-xs text-gray-400 font-normal">{{ $sup['code'] }}</div>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-xs text-gray-600 dark:text-gray-400">
                                    {{ $sup['phone'] }}
                                </td>
                                <td class="px-4 py-3 text-center font-mono">
                                    {{ $sup['orders_count'] }}
                                </td>
                                <td class="px-4 py-3 text-right font-mono font-semibold text-gray-900 dark:text-white">
                                    ₦{{ number_format($sup['total_supplied'], 2) }}
                                </td>
                                <td class="px-4 py-3 text-right font-mono text-emerald-600 dark:text-emerald-400 font-semibold">
                                    ₦{{ number_format($sup['amount_paid'], 2) }}
                                </td>
                                <td class="px-4 py-3 text-right font-mono font-bold {{ $sup['balance_due'] > 0 ? 'text-rose-600 dark:text-rose-400' : 'text-gray-500' }}">
                                    ₦{{ number_format($sup['balance_due'], 2) }}
                                </td>
                                <td class="px-4 py-3 text-center">
                                    <div class="flex items-center justify-center gap-1.5">
                                        <div class="w-16 bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                                            <div class="bg-emerald-500 h-2 rounded-full" style="width: {{ min(100, $sup['paid_percentage']) }}%"></div>
                                        </div>
                                        <span class="text-xs font-mono {{ $sup['paid_percentage'] >= 100 ? 'text-emerald-600 font-bold' : 'text-gray-500' }}">
                                            {{ $sup['paid_percentage'] }}%
                                        </span>
                                    </div>
                                </td>
                                <td class="px-4 py-3 text-center print-hide">
                                    <button type="button"
                                        wire:click="openSupplierStatement('{{ $sup['supplier_id'] }}', '{{ addslashes($sup['supplier_name']) }}')"
                                        class="px-2.5 py-1 text-xs font-semibold rounded-md bg-indigo-50 hover:bg-indigo-100 text-indigo-700 dark:bg-indigo-900/30 dark:text-indigo-300 dark:hover:bg-indigo-900/50 transition">
                                        Statement
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="px-4 py-8 text-center text-gray-500">No supplier records found matching the filter criteria.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        @endif

        <!-- Tab 2: Raw Material Supplies Breakdown -->
        @if($activeTab === 'items')
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm">
                    <thead class="bg-gray-50 dark:bg-gray-900/50 text-xs uppercase font-semibold text-gray-500 dark:text-gray-400">
                        <tr>
                            <th class="px-4 py-3 text-left">Material / Ingredient</th>
                            <th class="px-4 py-3 text-right">Total Quantity</th>
                            <th class="px-4 py-3 text-right">Avg Unit Cost</th>
                            <th class="px-4 py-3 text-center">Min &rarr; Max Cost</th>
                            <th class="px-4 py-3 text-right">Total Spend</th>
                            <th class="px-4 py-3 text-center">Share of Spend</th>
                            <th class="px-4 py-3 text-center">Purchases</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700 bg-white dark:bg-gray-800">
                        @forelse($itemsSummary as $item)
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition">
                                <td class="px-4 py-3 font-semibold text-gray-900 dark:text-white">
                                    {{ $item['item_name'] }}
                                    @if($item['sku'] && $item['sku'] !== 'N/A')
                                        <div class="text-xs text-gray-400 font-normal">SKU: {{ $item['sku'] }}</div>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-right font-mono text-gray-800 dark:text-gray-200">
                                    {{ number_format($item['total_qty'], 1) }} <span class="text-xs text-gray-500">{{ $item['uom'] }}</span>
                                </td>
                                <td class="px-4 py-3 text-right font-mono font-medium text-gray-900 dark:text-white">
                                    ₦{{ number_format($item['avg_unit_cost'], 2) }}
                                </td>
                                <td class="px-4 py-3 text-center font-mono text-xs text-gray-500">
                                    ₦{{ number_format($item['min_unit_cost'], 0) }} &rarr; ₦{{ number_format($item['max_unit_cost'], 0) }}
                                    @if($item['max_unit_cost'] > $item['min_unit_cost'])
                                        <span class="ml-1 text-amber-500 font-bold" title="Price variance detected">&Delta;</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-right font-mono font-bold text-gray-900 dark:text-white">
                                    ₦{{ number_format($item['total_spend'], 2) }}
                                </td>
                                <td class="px-4 py-3 text-center">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold bg-indigo-50 text-indigo-700 dark:bg-indigo-900/40 dark:text-indigo-300">
                                        {{ $item['spend_share'] }}%
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-center font-mono text-xs">
                                    {{ $item['purchases_count'] }} orders
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-4 py-8 text-center text-gray-500">No raw material purchases found matching the filter criteria.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        @endif

        <!-- Tab 3: Detailed Purchases Log -->
        @if($activeTab === 'purchases')
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm">
                    <thead class="bg-gray-50 dark:bg-gray-900/50 text-xs uppercase font-semibold text-gray-500 dark:text-gray-400">
                        <tr>
                            <th class="px-4 py-3 text-left">Purchase Date</th>
                            <th class="px-4 py-3 text-left">Purchase #</th>
                            <th class="px-4 py-3 text-left">Supplier</th>
                            <th class="px-4 py-3 text-center">Items</th>
                            <th class="px-4 py-3 text-right">FOB (NGN)</th>
                            <th class="px-4 py-3 text-right">Other Costs</th>
                            <th class="px-4 py-3 text-right">Landing Cost</th>
                            <th class="px-4 py-3 text-center">Payment Status</th>
                            <th class="px-4 py-3 text-center print-hide">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700 bg-white dark:bg-gray-800">
                        @forelse($purchases as $p)
                            @php
                                $landing = (float) ($p->landing_cost ?: ($p->total_cost ?: $p->calculateTotalCost()));
                            @endphp
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition">
                                <td class="px-4 py-3 text-xs text-gray-600 dark:text-gray-400 font-medium">
                                    {{ $p->purchase_date ? $p->purchase_date->format('M d, Y') : 'N/A' }}
                                </td>
                                <td class="px-4 py-3 font-mono font-semibold text-indigo-600 dark:text-indigo-400 text-xs">
                                    {{ $p->purchase_number }}
                                </td>
                                <td class="px-4 py-3 font-semibold text-gray-900 dark:text-white">
                                    {{ $p->supplier?->name ?? $p->supplier_name }}
                                </td>
                                <td class="px-4 py-3 text-center font-mono text-xs">
                                    <span class="px-2 py-0.5 rounded-full bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300">
                                        {{ $p->purchase_items_count }} items
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-right font-mono text-xs text-gray-600 dark:text-gray-400">
                                    ₦{{ number_format($p->total_fob_ngn ?? 0, 2) }}
                                </td>
                                <td class="px-4 py-3 text-right font-mono text-xs text-gray-500">
                                    ₦{{ number_format($p->other_costs ?? 0, 2) }}
                                </td>
                                <td class="px-4 py-3 text-right font-mono font-bold text-gray-900 dark:text-white">
                                    ₦{{ number_format($landing, 2) }}
                                </td>
                                <td class="px-4 py-3 text-center">
                                    @if($p->payment_status === 'paid')
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-300">
                                             Paid
                                        </span>
                                    @else
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold bg-rose-100 text-rose-800 dark:bg-rose-900/40 dark:text-rose-300">
                                            Pending
                                        </span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-center print-hide">
                                    <button type="button" wire:click="openPurchaseDetail({{ $p->id }})"
                                        class="px-2.5 py-1 text-xs font-semibold rounded bg-gray-100 hover:bg-gray-200 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300 transition">
                                        Inspect
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="px-4 py-8 text-center text-gray-500">No purchases found matching the filter criteria.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="p-4 border-t border-gray-200 dark:border-gray-700 print-hide">
                {{ $purchases->links() }}
            </div>
        @endif
    </div>

    <!-- Modal 1: Supplier Statement Modal -->
    @if($showSupplierModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4 print-hide">
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-2xl border border-gray-200 dark:border-gray-700 max-w-4xl w-full max-h-[85vh] flex flex-col overflow-hidden animate-fade-in">
                <!-- Modal Header -->
                <div class="p-5 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between bg-gray-50 dark:bg-gray-900/40">
                    <div>
                        <span class="text-xs uppercase font-bold text-indigo-600 dark:text-indigo-400">Supplier Statement</span>
                        <h2 class="text-xl font-bold text-gray-900 dark:text-white mt-0.5">{{ $selectedSupplierName }}</h2>
                    </div>
                    <button type="button" wire:click="closeSupplierModal" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 text-2xl font-bold">
                        &times;
                    </button>
                </div>

                <!-- Modal Body: Statement Summary & Invoices -->
                <div class="p-5 overflow-y-auto space-y-4">
                    @php
                        $stmtTotal = collect($supplierStatement)->sum('amount');
                        $stmtPaid = collect($supplierStatement)->where('payment_status', 'paid')->sum('amount');
                        $stmtPending = collect($supplierStatement)->where('payment_status', 'pending')->sum('amount');
                    @endphp

                    <!-- Statement Mini KPIs -->
                    <div class="grid grid-cols-3 gap-3 p-3 bg-gray-50 dark:bg-gray-700/40 rounded-lg text-center">
                        <div>
                            <p class="text-xs text-gray-500">Total Invoiced</p>
                            <p class="text-base font-bold text-gray-900 dark:text-white">₦{{ number_format($stmtTotal, 2) }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-emerald-600">Total Settled</p>
                            <p class="text-base font-bold text-emerald-600">₦{{ number_format($stmtPaid, 2) }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-rose-600">Net Balance Due</p>
                            <p class="text-base font-bold text-rose-600">₦{{ number_format($stmtPending, 2) }}</p>
                        </div>
                    </div>

                    <!-- Invoices Table -->
                    <div class="overflow-x-auto border border-gray-200 dark:border-gray-700 rounded-lg">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-xs">
                            <thead class="bg-gray-50 dark:bg-gray-700 font-semibold text-gray-600 dark:text-gray-300">
                                <tr>
                                    <th class="px-3 py-2 text-left">Date</th>
                                    <th class="px-3 py-2 text-left">Purchase #</th>
                                    <th class="px-3 py-2 text-center">Items</th>
                                    <th class="px-3 py-2 text-right">Amount (NGN)</th>
                                    <th class="px-3 py-2 text-center">Status</th>
                                    <th class="px-3 py-2 text-left">Notes</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                                @forelse($supplierStatement as $stmt)
                                    <tr>
                                        <td class="px-3 py-2 text-gray-600 dark:text-gray-400">{{ $stmt['purchase_date'] }}</td>
                                        <td class="px-3 py-2 font-mono font-semibold text-indigo-600">{{ $stmt['purchase_number'] }}</td>
                                        <td class="px-3 py-2 text-center">{{ $stmt['items_count'] }}</td>
                                        <td class="px-3 py-2 text-right font-mono font-bold">₦{{ number_format($stmt['amount'], 2) }}</td>
                                        <td class="px-3 py-2 text-center">
                                            <span class="px-1.5 py-0.5 rounded text-[10px] font-bold uppercase {{ $stmt['payment_status'] === 'paid' ? 'bg-emerald-100 text-emerald-800' : 'bg-rose-100 text-rose-800' }}">
                                                {{ $stmt['payment_status'] }}
                                            </span>
                                        </td>
                                        <td class="px-3 py-2 text-gray-500 italic">{{ $stmt['notes'] ?? '-' }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="px-3 py-6 text-center text-gray-500">No invoices found for this supplier.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="p-4 border-t border-gray-200 dark:border-gray-700 flex justify-end">
                    <button type="button" wire:click="closeSupplierModal"
                        class="px-4 py-2 text-xs font-semibold rounded-lg bg-gray-200 hover:bg-gray-300 dark:bg-gray-700 text-gray-800 dark:text-gray-200 transition">
                        Close Statement
                    </button>
                </div>
            </div>
        </div>
    @endif

    <!-- Modal 2: Purchase Line Items Inspection -->
    @if($showPurchaseModal && $selectedPurchase)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4 print-hide">
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-2xl border border-gray-200 dark:border-gray-700 max-w-4xl w-full max-h-[85vh] flex flex-col overflow-hidden animate-fade-in">
                <!-- Modal Header -->
                <div class="p-5 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between bg-gray-50 dark:bg-gray-900/40">
                    <div>
                        <span class="text-xs uppercase font-bold text-indigo-600 dark:text-indigo-400">Purchase Order Details</span>
                        <h2 class="text-xl font-bold text-gray-900 dark:text-white mt-0.5">
                            {{ $selectedPurchase->purchase_number }} &middot; {{ $selectedPurchase->supplier?->name ?? $selectedPurchase->supplier_name }}
                        </h2>
                        <p class="text-xs text-gray-500">
                            Date: {{ $selectedPurchase->purchase_date ? $selectedPurchase->purchase_date->format('M d, Y') : 'N/A' }} |
                            Status: <span class="capitalize font-semibold text-emerald-600">{{ $selectedPurchase->status }}</span> |
                            Payment: <span class="capitalize font-semibold {{ $selectedPurchase->payment_status === 'paid' ? 'text-emerald-600' : 'text-rose-600' }}">{{ $selectedPurchase->payment_status }}</span>
                        </p>
                    </div>
                    <button type="button" wire:click="closePurchaseModal" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 text-2xl font-bold">
                        &times;
                    </button>
                </div>

                <!-- Modal Body: Line Items -->
                <div class="p-5 overflow-y-auto space-y-4">
                    <div class="overflow-x-auto border border-gray-200 dark:border-gray-700 rounded-lg">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-xs">
                            <thead class="bg-gray-50 dark:bg-gray-700 font-semibold text-gray-600 dark:text-gray-300">
                                <tr>
                                    <th class="px-3 py-2 text-left">Item Name</th>
                                    <th class="px-3 py-2 text-right">Quantity</th>
                                    <th class="px-3 py-2 text-right">Unit Price</th>
                                    <th class="px-3 py-2 text-right">Other Costs</th>
                                    <th class="px-3 py-2 text-right">Total Cost</th>
                                    <th class="px-3 py-2 text-center">Batch #</th>
                                    <th class="px-3 py-2 text-center">Expiry</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                                @forelse($purchaseModalItems as $pItem)
                                    <tr>
                                        <td class="px-3 py-2 font-semibold text-gray-900 dark:text-white">
                                            {{ $pItem['item_name'] }}
                                            @if($pItem['sku'] && $pItem['sku'] !== 'N/A')
                                                <div class="text-[10px] text-gray-400">SKU: {{ $pItem['sku'] }}</div>
                                            @endif
                                        </td>
                                        <td class="px-3 py-2 text-right font-mono">
                                            {{ number_format($pItem['quantity'], 1) }} {{ $pItem['uom'] }}
                                        </td>
                                        <td class="px-3 py-2 text-right font-mono">
                                            ₦{{ number_format($pItem['unit_price'], 2) }}
                                        </td>
                                        <td class="px-3 py-2 text-right font-mono text-gray-500">
                                            ₦{{ number_format($pItem['other_costs'], 2) }}
                                        </td>
                                        <td class="px-3 py-2 text-right font-mono font-bold text-gray-900 dark:text-white">
                                            ₦{{ number_format($pItem['total_cost'], 2) }}
                                        </td>
                                        <td class="px-3 py-2 text-center font-mono text-gray-500">{{ $pItem['batch_number'] }}</td>
                                        <td class="px-3 py-2 text-center text-gray-500">{{ $pItem['expiry_date'] }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="px-3 py-6 text-center text-gray-500">No line items found for this purchase.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                            <tfoot class="bg-gray-50 dark:bg-gray-900/50 font-bold border-t border-gray-200 dark:border-gray-700">
                                <tr>
                                    <td class="px-3 py-2 text-right" colspan="4">Total Landing Cost:</td>
                                    <td class="px-3 py-2 text-right font-mono font-bold text-indigo-600 text-sm">
                                        ₦{{ number_format(collect($purchaseModalItems)->sum('total_cost'), 2) }}
                                    </td>
                                    <td colspan="2"></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>

                <div class="p-4 border-t border-gray-200 dark:border-gray-700 flex justify-end">
                    <button type="button" wire:click="closePurchaseModal"
                        class="px-4 py-2 text-xs font-semibold rounded-lg bg-gray-200 hover:bg-gray-300 dark:bg-gray-700 text-gray-800 dark:text-gray-200 transition">
                        Close
                    </button>
                </div>
            </div>
        </div>
    @endif

    <style>
    @media print {
        header, nav, aside, [role="navigation"],
        flux-sidebar, .flux-header,
        button, input, select, form,
        .print-hide, .no-print, .breadcrumb-container {
            display: none !important;
        }

        body, main, div.min-h-screen {
            background: #ffffff !important;
            color: #000000 !important;
            padding: 0 !important;
            margin: 0 !important;
            min-height: auto !important;
        }

        .bg-white, .dark\:bg-gray-800, .bg-gray-50, .dark\:bg-gray-900 {
            box-shadow: none !important;
            border: 1px solid #d1d5db !important;
            background: #ffffff !important;
            color: #000000 !important;
            page-break-inside: avoid;
            margin-bottom: 0.75rem;
        }

        table {
            width: 100% !important;
            border-collapse: collapse !important;
        }

        th, td {
            border: 1px solid #d1d5db !important;
            padding: 5px 8px !important;
            color: #000000 !important;
            background: #ffffff !important;
        }

        th {
            background: #f3f4f6 !important;
            font-weight: bold !important;
        }

        .text-white, .dark\:text-white, .text-gray-900, .dark\:text-gray-100 {
            color: #111827 !important;
        }

        .text-gray-500, .text-gray-600, .dark\:text-gray-400 {
            color: #374151 !important;
        }
    }
    </style>
</div>
