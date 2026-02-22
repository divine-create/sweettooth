<div class="p-3 space-y-3">

    <select 
    wire:model.live="branch" 
    id="branch-select"
    class="w-full px-4 py-2 text-sm font-medium text-gray-900 bg-white border border-gray-300 rounded-lg shadow-sm transition-all duration-200 
           focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500
           dark:bg-gray-800 dark:text-gray-100 dark:border-gray-600 
           dark:focus:ring-blue-400 dark:focus:border-blue-400">
    @foreach ($branches as $branch)
        <option value="{{ $branch->id }}" class="text-gray-900 dark:text-gray-100">
            {{ ucfirst($branch->name) }}
        </option>
    @endforeach
</select>

    <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-sm border border-zinc-200 dark:border-zinc-700 p-4">
        <div class="flex items-center justify-between mb-6">
            <h2 class="text-lg font-semibold text-zinc-800 dark:text-zinc-100">Alerts Dashboard</h2>
            <div class="flex gap-2">
                <button wire:click="exportCSV" class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium bg-green-600 hover:bg-green-700 text-white rounded-lg transition-all duration-200 hover:shadow-lg active:scale-95">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2m0 0v-8m0 8H3m15 0h3"/>
                    </svg>
                    CSV
                </button>

                <button wire:click="exportCSV" class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg transition-all duration-200 hover:shadow-lg active:scale-95">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                    Excel
                </button>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
            <div class="bg-blue-50 dark:bg-blue-900/20 rounded-lg shadow-sm p-4">
                <p class="text-sm text-gray-600 dark:text-gray-400">Total Alerts</p>
                <p class="text-3xl font-bold text-blue-600 dark:text-blue-500">{{ $summary['total'] }}</p>
            </div>
            <div class="bg-red-50 dark:bg-red-900/20 rounded-lg shadow-sm p-4">
                <p class="text-sm text-gray-600 dark:text-gray-400">Critical</p>
                <p class="text-3xl font-bold text-red-600 dark:text-red-500">{{ $summary['critical'] }}</p>
            </div>
            <div class="bg-yellow-50 dark:bg-yellow-900/20 rounded-lg shadow-sm p-4">
                <p class="text-sm text-gray-600 dark:text-gray-400">Warnings</p>
                <p class="text-3xl font-bold text-yellow-600 dark:text-yellow-500">{{ $summary['warning'] }}</p>
            </div>
            <div class="bg-purple-50 dark:bg-purple-900/20 rounded-lg shadow-sm p-4">
                <p class="text-sm text-gray-600 dark:text-gray-400">Info</p>
                <p class="text-3xl font-bold text-purple-600 dark:text-purple-500">{{ $summary['info'] }}</p>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
            <div class="bg-red-50 dark:bg-red-900/20 border-l-4 border-red-500 rounded-lg shadow-sm p-4">
                <p class="text-sm text-gray-600 dark:text-gray-400">Expired Items</p>
                <p class="text-2xl font-bold text-red-600 dark:text-red-500">{{ $summary['expired'] }}</p>
            </div>
            <div class="bg-yellow-50 dark:bg-yellow-900/20 border-l-4 border-yellow-500 rounded-lg shadow-sm p-4">
                <p class="text-sm text-gray-600 dark:text-gray-400">Low Stock</p>
                <p class="text-2xl font-bold text-yellow-600 dark:text-yellow-500">{{ $summary['low_stock'] }}</p>
            </div>
            <div class="bg-orange-50 dark:bg-orange-900/20 border-l-4 border-orange-500 rounded-lg shadow-sm p-4">
                <p class="text-sm text-gray-600 dark:text-gray-400">Out of Stock</p>
                <p class="text-2xl font-bold text-orange-600 dark:text-orange-500">{{ $summary['out_of_stock'] }}</p>
            </div>
        </div>

        <div class="mb-6">
            <select wire:model.live="alertType" class="w-full px-4 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-800 dark:text-zinc-200 focus:ring-2 focus:ring-blue-500">
                <option value="">All Alerts</option>
                <option value="expired">Expired</option>
                <option value="expiring_soon">Expiring Soon</option>
                <option value="low_stock">Low Stock</option>
                <option value="out_of_stock">Out of Stock</option>
                <option value="health">Health Issues</option>
                <option value="damaged">Damaged Stock</option>
                <option value="pending_requests">Pending Requests</option>
            </select>
        </div>

        <div class="space-y-3">
            @forelse($alerts as $alert)
                <div class="
                    {{ $alert['type'] === 'critical' ? 'bg-red-50 dark:bg-red-900/20 border-l-4 border-red-500' : '' }}
                    {{ $alert['type'] === 'warning' ? 'bg-yellow-50 dark:bg-yellow-900/20 border-l-4 border-yellow-500' : '' }}
                    {{ $alert['type'] === 'info' ? 'bg-blue-50 dark:bg-blue-900/20 border-l-4 border-blue-500' : '' }}
                    rounded-lg shadow-sm p-4
                ">
                    <div class="flex items-start gap-4">
                        <svg class="w-6 h-6 flex-shrink-0 {{ $alert['type'] === 'critical' ? 'text-red-600 dark:text-red-500' : ($alert['type'] === 'warning' ? 'text-yellow-600 dark:text-yellow-500' : 'text-blue-600 dark:text-blue-500') }}"
                             fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            @if($alert['type'] === 'critical')
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            @elseif($alert['type'] === 'warning')
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                            @else
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            @endif
                        </svg>
                        <div class="flex-1">
                            <div class="flex items-start justify-between gap-4">
                                <div>
                                    <h4 class="font-bold {{ $alert['type'] === 'critical' ? 'text-red-800 dark:text-red-300' : ($alert['type'] === 'warning' ? 'text-yellow-800 dark:text-yellow-300' : 'text-blue-800 dark:text-blue-300') }}">
                                        {{ $alert['message'] }}
                                    </h4>
                                    @if(isset($alert['item']))
                                        <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                                            <span class="font-medium">Item:</span> {{ $alert['item'] }} ({{ $alert['sku'] }})
                                        </p>
                                    @endif
                                    @if(isset($alert['current']))
                                        <p class="text-sm text-gray-600 dark:text-gray-400">
                                            <span class="font-medium">Current:</span> {{ number_format($alert['current'], 2) }}
                                            | <span class="font-medium">Reorder Level:</span> {{ number_format($alert['reorder_level'], 2) }}
                                        </p>
                                    @endif
                                    @if(isset($alert['days_left']))
                                        <p class="text-sm text-gray-600 dark:text-gray-400">
                                            <span class="font-medium">Days until expiry:</span> {{ $alert['days_left'] }}
                                        </p>
                                    @endif
                                    @if(isset($alert['damaged_qty']))
                                        <p class="text-sm text-gray-600 dark:text-gray-400">
                                            <span class="font-medium">Damaged Qty:</span> {{ number_format($alert['damaged_qty'], 2) }} ({{ $alert['percentage'] }}%)
                                        </p>
                                    @endif
                                    @if(isset($alert['count']))
                                        <p class="text-sm text-gray-600 dark:text-gray-400">
                                            <span class="font-medium">Count:</span> {{ $alert['count'] }}
                                        </p>
                                    @endif
                                </div>
                                @php
                                    $badgeColors = match($alert['type']) {
                                        'critical' => 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200',
                                        'warning' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200',
                                        'info' => 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200',
                                        default => 'bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-200'
                                    };
                                @endphp
                                <span class="px-2 py-1 text-xs font-semibold rounded {{ $badgeColors }}">
                                    {{ ucfirst(str_replace('_', ' ', $alert['category'])) }}
                                </span>
                            </div>
                            <div class="mt-2 p-2 bg-white dark:bg-zinc-700/50 rounded border border-gray-200 dark:border-zinc-600">
                                <p class="text-sm font-medium text-gray-700 dark:text-gray-300">
                                    <svg class="w-4 h-4 inline mr-1 text-gray-600 dark:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z" />
                                    </svg>
                                    Recommended Action: {{ $alert['action'] }}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            @empty
                <div class="bg-green-50 dark:bg-green-900/20 border-l-4 border-green-500 rounded-lg shadow-sm p-4">
                    <div class="flex items-center gap-3">
                        <svg class="w-8 h-8 text-green-600 dark:text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <div>
                            <h4 class="font-bold text-green-800 dark:text-green-300">No Alerts</h4>
                            <p class="text-sm text-green-700 dark:text-green-400">Everything looks good! No alerts at this time.</p>
                        </div>
                    </div>
                </div>
            @endforelse
        </div>
    </div>
</div>
