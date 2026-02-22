<div class="p-3 space-y-3">
    <x-breadcrumb title="Stock Monitor" :items="[
        ['label' => 'Dashboard', 'url' => branch_route('branch-dashboard.index')],
        ['label' => 'Sales Dashboard'],
        ['label' => 'Stock Monitor'],
    ]" :compact="false" :with-icons="true" />

    <!-- Header -->
    <div class="bg-gradient-to-r from-blue-600 to-blue-700 rounded-lg p-4 text-white shadow-lg">
        <div class="flex justify-between items-center">
            <div>
                <h2 class="text-xl font-bold">Product Stock Monitor</h2>
                <p class="text-sm opacity-90 mt-1">
                    Real-time view of product stock levels - {{ \Carbon\Carbon::parse($stockDate)->format('l, F d, Y') }}
                </p>
            </div>
            <button wire:click="refreshData"
                class="px-4 py-2 bg-white/20 hover:bg-white/30 rounded-lg font-medium transition-colors flex items-center">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                </svg>
                Refresh
            </button>
        </div>
    </div>

    <!-- Alert if no shift -->
    @if (!$currentShiftId)
        <div class="bg-yellow-50 dark:bg-yellow-900/20 border-l-4 border-yellow-400 p-4 rounded">
            <div class="flex items-center">
                <svg class="w-5 h-5 text-yellow-400 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                </svg>
                <div>
                    <h3 class="text-sm font-medium text-yellow-800 dark:text-yellow-200">No Active Shift</h3>
                    <p class="text-sm text-yellow-700 dark:text-yellow-300 mt-1">Please clock in to view stock data.</p>
                </div>
            </div>
        </div>
    @endif

    <!-- Stats Cards -->
    @if ($currentShiftId)
        <div class="grid grid-cols-1 md:grid-cols-4 gap-3">
            <!-- Total Products -->
            <div class="bg-white dark:bg-zinc-800 rounded-lg p-4 border border-zinc-200 dark:border-zinc-700 shadow-sm">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-zinc-600 dark:text-zinc-400">Total Products</p>
                        <p class="text-2xl font-bold text-zinc-900 dark:text-zinc-100">{{ $totalProducts }}</p>
                    </div>
                    <div class="w-12 h-12 bg-blue-100 dark:bg-blue-900/30 rounded-lg flex items-center justify-center">
                        <svg class="w-6 h-6 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                        </svg>
                    </div>
                </div>
            </div>

            <!-- Low Stock -->
            <div class="bg-white dark:bg-zinc-800 rounded-lg p-4 border border-zinc-200 dark:border-zinc-700 shadow-sm">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-zinc-600 dark:text-zinc-400">Low Stock</p>
                        <p class="text-2xl font-bold text-orange-600 dark:text-orange-400">{{ $lowStockCount }}</p>
                    </div>
                    <div class="w-12 h-12 bg-orange-100 dark:bg-orange-900/30 rounded-lg flex items-center justify-center">
                        <svg class="w-6 h-6 text-orange-600 dark:text-orange-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                        </svg>
                    </div>
                </div>
            </div>

            <!-- Critical Items -->
            <div class="bg-white dark:bg-zinc-800 rounded-lg p-4 border border-zinc-200 dark:border-zinc-700 shadow-sm">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-zinc-600 dark:text-zinc-400">Critical</p>
                        <p class="text-2xl font-bold text-orange-600 dark:text-orange-400">{{ $criticalCount }}</p>
                    </div>
                    <div class="w-12 h-12 bg-orange-100 dark:bg-orange-900/30 rounded-lg flex items-center justify-center">
                        <svg class="w-6 h-6 text-orange-600 dark:text-orange-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                </div>
            </div>

            <!-- Expired Items -->
            <div class="bg-white dark:bg-zinc-800 rounded-lg p-4 border border-zinc-200 dark:border-zinc-700 shadow-sm">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-zinc-600 dark:text-zinc-400">Expired</p>
                        <p class="text-2xl font-bold text-red-600 dark:text-red-400">{{ $expiredCount }}</p>
                    </div>
                    <div class="w-12 h-12 bg-red-100 dark:bg-red-900/30 rounded-lg flex items-center justify-center">
                        <svg class="w-6 h-6 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <!-- Filters Section -->
    <div x-data="{ open: false }"
        class="bg-white dark:bg-zinc-800 rounded-lg shadow-sm border border-zinc-200 dark:border-zinc-700">
        <div class="flex justify-between items-center px-3 py-2 border-b border-zinc-200 dark:border-zinc-700">
            <h2 class="text-sm font-semibold text-zinc-800 dark:text-zinc-100 flex items-center">
                <svg class="w-4 h-4 mr-1.5 text-zinc-600 dark:text-zinc-400" fill="none" stroke="currentColor"
                    viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707L14.293 13H10v5l-4-4v-3.586L3.293 7.293A1 1 0 013 6.586V4z" />
                </svg>
                Filters
            </h2>
            <button @click="open = !open"
                class="flex items-center px-2.5 py-1 rounded text-xs font-medium bg-blue-600 hover:bg-blue-700 text-white transition-all duration-200">
                <svg class="w-3.5 h-3.5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path x-show="!open" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M4 8h16M4 16h16" />
                    <path x-show="open" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M6 18L18 6M6 6l12 12" />
                </svg>
                <span x-text="open ? 'Close' : 'Show Filters'"></span>
            </button>
        </div>

        <div x-show="open" x-collapse class="p-3 space-y-3">
            <div>
                <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Search</label>
                <input type="text" wire:model.live.debounce.300ms="search"
                    placeholder="Search by product name or SKU..."
                    class="w-full px-4 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-800 dark:text-zinc-200 focus:ring-2 focus:ring-blue-500">
            </div>

            <div>
                <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Shelf Life Status</label>
                <select wire:model.live="filterStatus"
                    class="w-full px-4 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-800 dark:text-zinc-200 focus:ring-2 focus:ring-blue-500">
                    <option value="">All Status</option>
                    <option value="fresh">Fresh</option>
                    <option value="warning">Warning</option>
                    <option value="critical">Critical</option>
                    <option value="expired">Expired</option>
                </select>
            </div>
        </div>
    </div>

    <!-- Stock Monitor Table -->
    <x-table :$headers :$rows striped paginate persist collapsible
        :filter="['quantity' => 'quantity', 'search' => 'search']"
        :quantity="[10, 20, 50, 100]">

        @interact('column_product', $row)
            <div>
                <div class="font-medium text-zinc-900 dark:text-zinc-100">{{ $row->product->name }}</div>
                <div class="text-xs text-zinc-500 dark:text-zinc-400">{{ $row->product->sku }}</div>
            </div>
        @endinteract

        @interact('column_current_stock', $row)
            @php
                $currentStock = $row->closing_quantity;
                $stockColor = 'text-green-600 dark:text-green-400';
                if ($currentStock <= 0) {
                    $stockColor = 'text-red-600 dark:text-red-400';
                } elseif ($currentStock < ($row->opening_quantity * 0.3)) {
                    $stockColor = 'text-orange-600 dark:text-orange-400';
                }
            @endphp
            <div class="text-center">
                <span class="text-2xl font-bold {{ $stockColor }}">
                    {{ number_format($currentStock, 2) }}
                </span>
                <span class="text-sm text-zinc-600 dark:text-zinc-400 ml-1">
                    {{ $row->product->uom }}
                </span>
            </div>
        @endinteract

        @interact('column_opening', $row)
            <div class="text-center text-sm text-zinc-600 dark:text-zinc-400">
                {{ number_format($row->opening_quantity, 2) }} {{ $row->product->uom }}
            </div>
        @endinteract

        @interact('column_additions', $row)
            <div class="text-center text-sm text-blue-600 dark:text-blue-400 font-medium">
                +{{ number_format($row->addition_quantity, 2) }} {{ $row->product->uom }}
            </div>
        @endinteract

        @interact('column_sold', $row)
            <div class="text-center text-sm text-purple-600 dark:text-purple-400 font-medium">
                -{{ number_format($row->quantity_sold, 2) }} {{ $row->product->uom }}
            </div>
        @endinteract

        @interact('column_callbacks', $row)
            <div class="text-center text-sm text-red-600 dark:text-red-400 font-medium">
                -{{ number_format($row->callback_quantity, 2) }} {{ $row->product->uom }}
            </div>
        @endinteract

        @interact('column_closing', $row)
            <div class="text-center text-sm font-semibold text-zinc-900 dark:text-zinc-100">
                {{ number_format($row->closing_quantity, 2) }} {{ $row->product->uom }}
            </div>
        @endinteract

        @interact('column_production_date', $row)
            <div class="text-center text-xs text-zinc-600 dark:text-zinc-400">
                {{ $row->production_date ? $row->production_date->format('M d, Y') : 'N/A' }}
            </div>
        @endinteract

        @interact('column_shelf_life', $row)
            @php
                $status = $row->getShelfLifeStatus();
                $badgeColor = $row->getShelfLifeBadgeColor();
                $daysRemaining = $row->getDaysRemaining();
            @endphp
            <div class="text-center">
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $badgeColor }}">
                    {{ ucfirst($status) }}
                    @if($daysRemaining !== null && $daysRemaining >= 0)
                        ({{ $daysRemaining }}d)
                    @endif
                </span>
            </div>
        @endinteract

        @interact('column_action', $row)
            <div class="flex justify-center gap-2">
                @if($row->shouldCallback())
                    <a href="{{ branch_route('sales-dashboard.callbacks.index', ['b_id' => $b_id]) }}"
                        class="px-2 py-1 bg-red-600 hover:bg-red-700 text-white rounded text-xs font-medium transition-colors">
                        Callback
                    </a>
                @endif
            </div>
        @endinteract

    </x-table>

    <!-- Info Panel -->
    <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-4">
        <div class="flex items-start">
            <svg class="w-5 h-5 text-blue-600 dark:text-blue-400 mt-0.5 mr-3 flex-shrink-0" fill="none"
                stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <div class="text-sm text-blue-800 dark:text-blue-200">
                <h4 class="font-semibold mb-1">Stock Monitor Guide:</h4>
                <ul class="list-disc list-inside space-y-1">
                    <li><strong>Current Stock:</strong> Real-time available stock (Closing Quantity)</li>
                    <li><strong>Green:</strong> Good stock levels</li>
                    <li><strong>Orange:</strong> Low stock (less than 30% of opening)</li>
                    <li><strong>Red:</strong> Out of stock</li>
                    <li><strong>Shelf Life Colors:</strong> Fresh (Green), Warning (Yellow), Critical (Orange), Expired (Red)</li>
                    <li><strong>Auto-refresh:</strong> Click refresh to see latest sales updates</li>
                </ul>
            </div>
        </div>
    </div>
</div>
