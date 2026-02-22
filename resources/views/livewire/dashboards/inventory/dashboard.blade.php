<div class="min-h-screen bg-zinc-50 dark:bg-zinc-900">
    <!-- Header -->
    <div class="bg-white dark:bg-zinc-800 border-b border-zinc-200 dark:border-zinc-700 sticky top-0 z-40">
        <div class="px-6 py-4">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-zinc-900 dark:text-zinc-100">Inventory Dashboard</h1>
                    <p class="text-sm text-zinc-600 dark:text-zinc-400 mt-1">Real-time stock and inventory overview</p>
                </div>
                <div class="flex items-center space-x-3">
                    <button wire:click="refresh" 
                        class="p-2 hover:bg-zinc-100 dark:hover:bg-zinc-700 rounded-lg transition-colors"
                        title="Refresh">
                        <svg class="w-5 h-5 text-zinc-600 dark:text-zinc-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                        </svg>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div class="p-6 space-y-6">
        <!-- Critical Alerts -->
        @if(!empty($criticalAlerts))
            <div class="space-y-3">
                @foreach($criticalAlerts as $alert)
                    <x-dashboard.alert-box :type="$alert['type']" :title="$alert['title']">
                        {{ $alert['message'] }}
                    </x-dashboard.alert-box>
                @endforeach
            </div>
        @endif

        <!-- KPI Cards -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
            <!-- Total Stock Value -->
            <x-dashboard.metric-card
                title="Total Stock Value"
                :value="$totalStockValue"
                color="blue"
                format="currency"
                icon='<svg class="w-5 h-5 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>'
            />

            <!-- Total Items -->
            <x-dashboard.metric-card
                title="Items in Stock"
                :value="$totalItems"
                color="green"
                format="number"
                icon='<svg class="w-5 h-5 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10l8-4" /></svg>'
            />

            <!-- Low Stock Items -->
            <x-dashboard.metric-card
                title="Low Stock Alerts"
                :value="$lowStockCount"
                :color="$healthStatus === 'critical' ? 'red' : 'yellow'"
                format="number"
                :trend="$lowStockCount > 0 ? 'down' : 'neutral'"
                icon='<svg class="w-5 h-5 text-yellow-600 dark:text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4v2m0 4v2M8 5h8a2 2 0 012 2v12a2 2 0 01-2 2H8a2 2 0 01-2-2V7a2 2 0 012-2z" /></svg>'
            />

            <!-- Today Movements -->
            <x-dashboard.metric-card
                title="Today Movements"
                :value="$todayMovementsCount"
                color="purple"
                format="number"
                icon='<svg class="w-5 h-5 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.658 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1" /></svg>'
            />
        </div>

        <!-- Health Status -->
        <div class="bg-white dark:bg-zinc-800 rounded-lg border border-zinc-200 dark:border-zinc-700 p-6">
            <h2 class="text-lg font-semibold text-zinc-900 dark:text-zinc-100 mb-4">Inventory Health</h2>
            <div class="flex items-center space-x-4">
                @php
                    $healthColor = match($healthStatus) {
                        'healthy' => 'text-green-600 dark:text-green-400 bg-green-100 dark:bg-green-900/30',
                        'warning' => 'text-yellow-600 dark:text-yellow-400 bg-yellow-100 dark:bg-yellow-900/30',
                        'critical' => 'text-red-600 dark:text-red-400 bg-red-100 dark:bg-red-900/30',
                        default => 'text-zinc-600 dark:text-zinc-400 bg-zinc-100 dark:bg-zinc-900/30',
                    };
                    $healthText = match($healthStatus) {
                        'healthy' => 'Healthy',
                        'warning' => 'Warning',
                        'critical' => 'Critical',
                        default => 'Unknown',
                    };
                    $healthIcon = match($healthStatus) {
                        'healthy' => '<svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>',
                        'warning' => '<svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4v2m0 4v2M8 5h8a2 2 0 012 2v12a2 2 0 01-2 2H8a2 2 0 01-2-2V7a2 2 0 012-2z" /></svg>',
                        'critical' => '<svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l-2-2m0 0l-2-2m2 2l2-2m-2 2l-2 2m9-11a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>',
                        default => '<svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>',
                    };
                @endphp
                <div class="p-3 rounded-lg {{ $healthColor }}">
                    {!! $healthIcon !!}
                </div>
                <div>
                    <p class="text-sm text-zinc-600 dark:text-zinc-400">Current Status</p>
                    <p class="text-xl font-semibold text-zinc-900 dark:text-zinc-100">{{ $healthText }}</p>
                </div>
            </div>
        </div>

        <!-- Content Grid -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Recent Movements (2/3 width) -->
            <div class="lg:col-span-2">
                <div class="bg-white dark:bg-zinc-800 rounded-lg border border-zinc-200 dark:border-zinc-700 p-6">
                    <div class="flex items-center justify-between mb-4">
                        <h2 class="text-lg font-semibold text-zinc-900 dark:text-zinc-100">Recent Movements</h2>
                        <span class="text-sm text-blue-600 dark:text-blue-400 cursor-not-allowed opacity-50">
                            View All →
                        </span>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="border-b border-zinc-200 dark:border-zinc-700">
                                    <th class="text-left py-3 px-4 font-semibold text-zinc-900 dark:text-zinc-100">Item</th>
                                    <th class="text-left py-3 px-4 font-semibold text-zinc-900 dark:text-zinc-100">Type</th>
                                    <th class="text-right py-3 px-4 font-semibold text-zinc-900 dark:text-zinc-100">Qty</th>
                                    <th class="text-left py-3 px-4 font-semibold text-zinc-900 dark:text-zinc-100">Time</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($recentMovements as $movement)
                                    <tr class="border-b border-zinc-100 dark:border-zinc-700 hover:bg-zinc-50 dark:hover:bg-zinc-700/50">
                                        <td class="py-3 px-4 text-zinc-900 dark:text-zinc-100">
                                            {{ $movement->stock?->item?->name ?? 'Unknown Item' }}
                                        </td>
                                        <td class="py-3 px-4">
                                            <span class="px-2 py-1 text-xs font-medium rounded-full {{ $movement->type === 'in' ? 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300' : 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-300' }}">
                                                {{ ucfirst($movement->type) }}
                                            </span>
                                        </td>
                                        <td class="py-3 px-4 text-right font-semibold text-zinc-900 dark:text-zinc-100">{{ $movement->quantity }}</td>
                                        <td class="py-3 px-4 text-zinc-600 dark:text-zinc-400 text-xs">
                                            {{ $movement->created_at?->diffForHumans() ?? '—' }}
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="py-6 px-4 text-center text-zinc-600 dark:text-zinc-400">
                                            No movements recorded
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Low Stock Items (1/3 width) -->
            <div>
                <div class="bg-white dark:bg-zinc-800 rounded-lg border border-zinc-200 dark:border-zinc-700 p-6">
                    <div class="flex items-center justify-between mb-4">
                        <h2 class="text-lg font-semibold text-zinc-900 dark:text-zinc-100">Low Stock</h2>
                        <span class="text-sm font-semibold text-red-600 dark:text-red-400">{{ $lowStockCount }}</span>
                    </div>
                    <div class="space-y-3">
                        @forelse($lowStockItems->take(10) as $item)
                            <div class="border-b border-zinc-100 dark:border-zinc-700 pb-3 last:border-0">
                                @php
                                    $qty = (float) ($item->quantity ?? 0);
                                    $reorderPoint = (float) ($item->reorder_point ?? 0);
                                    $percent = $reorderPoint > 0 ? ($qty / $reorderPoint) * 100 : 0;
                                @endphp
                                <p class="text-sm font-medium text-zinc-900 dark:text-zinc-100">{{ $item->name }}</p>
                                <div class="mt-1 flex items-center justify-between text-xs text-zinc-600 dark:text-zinc-400">
                                    <span>
                                        {{ number_format($qty, 2) }}
                                        /
                                        {{ $reorderPoint > 0 ? number_format($reorderPoint, 2) : '—' }}
                                    </span>
                                    <span class="text-red-600 dark:text-red-400 font-semibold">{{ round($percent, 0) }}%</span>
                                </div>
                                <div class="mt-1 bg-zinc-200 dark:bg-zinc-700 rounded-full h-1.5 overflow-hidden">
                                    <div class="bg-red-600 h-full" style="width: {{ min(100, $percent) }}%"></div>
                                </div>
                            </div>
                        @empty
                            <p class="text-sm text-zinc-600 dark:text-zinc-400 text-center py-4">All items well stocked</p>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
