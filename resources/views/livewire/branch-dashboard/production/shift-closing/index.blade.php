<div class="p-6 bg-gray-50 dark:bg-gray-900 min-h-screen space-y-6">
    <!-- Header -->
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <div class="flex items-center gap-2">
                <span class="px-2.5 py-1 text-xs font-semibold rounded-md bg-purple-100 text-purple-800 dark:bg-purple-900/40 dark:text-purple-300">
                    Production Shift Closing
                </span>
                <span class="text-xs text-gray-500 dark:text-gray-400">
                    {{ $branchName }}
                </span>
            </div>
            <h1 class="text-2xl md:text-3xl font-bold text-gray-900 dark:text-white mt-1">
                {{ $departmentName }} — Shift Closing & Stock Reconciliation
            </h1>
            <p class="text-sm text-gray-600 dark:text-gray-400">
                Reconcile physical closing stocks across Finished Goods, WIP, and Raw Materials before closing shift.
            </p>
        </div>

        <div class="flex items-center gap-3">
            @if($currentShiftId)
                <div class="px-4 py-2 bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 text-sm">
                    <span class="text-gray-500 dark:text-gray-400">Shift:</span>
                    <span class="font-bold text-gray-900 dark:text-white uppercase ml-1">{{ $shiftType }}</span>
                    <span class="mx-2 text-gray-300 dark:text-gray-600">|</span>
                    <span class="text-gray-500 dark:text-gray-400">Date:</span>
                    <span class="font-semibold text-gray-900 dark:text-white ml-1">{{ \Carbon\Carbon::parse($shiftDate)->format('M d, Y') }}</span>
                    <span class="mx-2 text-gray-300 dark:text-gray-600">|</span>
                    @if($isClosed)
                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-bold bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300">
                            Closed
                        </span>
                    @else
                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-bold bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-300 animate-pulse">
                            Active
                        </span>
                    @endif
                </div>
            @endif
        </div>
    </div>

    <!-- Alert / Status Banner -->
    @if(!$currentShiftId)
        <div class="bg-amber-50 dark:bg-amber-900/20 border-l-4 border-amber-500 p-4 rounded-r-lg">
            <div class="flex items-center">
                <svg class="h-6 w-6 text-amber-500 mr-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                </svg>
                <div>
                    <h3 class="text-sm font-semibold text-amber-800 dark:text-amber-200">No active shift found</h3>
                    <p class="text-sm text-amber-700 dark:text-amber-300 mt-0.5">Please clock into your production shift before finalizing closing counts.</p>
                </div>
            </div>
        </div>
    @elseif($isClosed)
        <div class="bg-emerald-50 dark:bg-emerald-900/20 border-l-4 border-emerald-500 p-4 rounded-r-lg">
            <div class="flex items-center">
                <svg class="h-6 w-6 text-emerald-500 mr-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <div>
                    <h3 class="text-sm font-semibold text-emerald-800 dark:text-emerald-200">Production Shift Closed</h3>
                    <p class="text-sm text-emerald-700 dark:text-emerald-300 mt-0.5">
                        This shift was finalized on {{ $closedAt }}. Verified closing counts have been synchronized to the store and will carry over as the opening stock for the next shift.
                    </p>
                </div>
            </div>
        </div>
    @endif

    <!-- Metric Overview Cards -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-4 border border-gray-200 dark:border-gray-700">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Total Batches</p>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white mt-1">
                        {{ collect($productionSummary)->sum('batches_produced') }}
                    </p>
                </div>
                <div class="p-2.5 rounded-lg bg-blue-50 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                    </svg>
                </div>
            </div>
            <p class="text-xs text-gray-500 dark:text-gray-400 mt-2">Planned: {{ collect($productionSummary)->sum('batches_planned') }} batches</p>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-4 border border-gray-200 dark:border-gray-700">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Material Efficiency</p>
                    <p class="text-2xl font-bold mt-1 {{ $totalMaterialEfficiency <= 105 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                        {{ number_format($totalMaterialEfficiency, 1) }}%
                    </p>
                </div>
                <div class="p-2.5 rounded-lg bg-green-50 dark:bg-green-900/30 text-green-600 dark:text-green-400">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                    </svg>
                </div>
            </div>
            <p class="text-xs text-gray-500 dark:text-gray-400 mt-2">Target: 100% material usage</p>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-4 border border-gray-200 dark:border-gray-700">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Units Approved</p>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white mt-1">
                        {{ number_format(collect($productionSummary)->sum('total_approved'), 0) }}
                    </p>
                </div>
                <div class="p-2.5 rounded-lg bg-emerald-50 dark:bg-emerald-900/30 text-emerald-600 dark:text-emerald-400">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                    </svg>
                </div>
            </div>
            <p class="text-xs text-gray-500 dark:text-gray-400 mt-2">Rejections: {{ number_format(collect($productionSummary)->sum('total_rejected'), 0) }}</p>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-4 border border-gray-200 dark:border-gray-700">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Variance Alerts</p>
                    <p class="text-2xl font-bold {{ count($materialVariances) > 0 ? 'text-amber-600 dark:text-amber-400' : 'text-gray-900 dark:text-white' }} mt-1">
                        {{ count($materialVariances) }}
                    </p>
                </div>
                <div class="p-2.5 rounded-lg bg-amber-50 dark:bg-amber-900/30 text-amber-600 dark:text-amber-400">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                    </svg>
                </div>
            </div>
            <p class="text-xs text-gray-500 dark:text-gray-400 mt-2">Tracked raw items: {{ count($rawMaterialUsage) }}</p>
        </div>
    </div>

    <!-- 3-Category Tabs (Option B) -->
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
        <div class="border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800/80 px-4 pt-3">
            <div class="flex flex-wrap items-center justify-between gap-4">
                <nav class="flex space-x-2" aria-label="Tabs">
                    <!-- Finished Goods Tab -->
                    <button wire:click="setTab('finished')"
                        class="px-4 py-2.5 text-sm font-medium rounded-t-lg transition flex items-center gap-2 border-b-2 {{ $activeTab === 'finished' ? 'border-purple-600 text-purple-600 bg-white dark:bg-gray-800 dark:text-purple-400 font-semibold' : 'border-transparent text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200' }}">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4" />
                        </svg>
                        <span>Finished Goods</span>
                        <span class="ml-1.5 px-2 py-0.5 text-xs rounded-full {{ $activeTab === 'finished' ? 'bg-purple-100 text-purple-700 dark:bg-purple-900/50 dark:text-purple-300 font-bold' : 'bg-gray-200 text-gray-700 dark:bg-gray-700 dark:text-gray-300' }}">
                            {{ $finishedCount }}
                        </span>
                    </button>

                    <!-- WIP Tab -->
                    <button wire:click="setTab('wip')"
                        class="px-4 py-2.5 text-sm font-medium rounded-t-lg transition flex items-center gap-2 border-b-2 {{ $activeTab === 'wip' ? 'border-amber-600 text-amber-600 bg-white dark:bg-gray-800 dark:text-amber-400 font-semibold' : 'border-transparent text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200' }}">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z" />
                        </svg>
                        <span>Work-in-Progress (WIP)</span>
                        <span class="ml-1.5 px-2 py-0.5 text-xs rounded-full {{ $activeTab === 'wip' ? 'bg-amber-100 text-amber-700 dark:bg-amber-900/50 dark:text-amber-300 font-bold' : 'bg-gray-200 text-gray-700 dark:bg-gray-700 dark:text-gray-300' }}">
                            {{ $wipCount }}
                        </span>
                    </button>

                    <!-- Raw Materials Tab -->
                    <button wire:click="setTab('raw')"
                        class="px-4 py-2.5 text-sm font-medium rounded-t-lg transition flex items-center gap-2 border-b-2 {{ $activeTab === 'raw' ? 'border-emerald-600 text-emerald-600 bg-white dark:bg-gray-800 dark:text-emerald-400 font-semibold' : 'border-transparent text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200' }}">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                        </svg>
                        <span>Raw Materials & Ingredients</span>
                        <span class="ml-1.5 px-2 py-0.5 text-xs rounded-full {{ $activeTab === 'raw' ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/50 dark:text-emerald-300 font-bold' : 'bg-gray-200 text-gray-700 dark:bg-gray-700 dark:text-gray-300' }}">
                            {{ $rawCount }}
                        </span>
                    </button>
                </nav>

                <!-- Search and Batch Match Actions -->
                <div class="flex items-center gap-3 pb-2">
                    <div class="relative">
                        <input type="text" wire:model.live.debounce.250ms="search" placeholder="Search item or SKU..."
                            class="w-60 pl-8 pr-3 py-1.5 text-xs rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-purple-500" />
                        <svg class="w-3.5 h-3.5 text-gray-400 absolute left-2.5 top-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                    </div>

                    @if(!$isClosed)
                        <button type="button" wire:click="matchAllTheoretical"
                            class="px-3 py-1.5 text-xs font-semibold rounded-lg bg-gray-100 hover:bg-gray-200 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-200 transition border border-gray-300 dark:border-gray-600">
                            Match All Theoretical
                        </button>
                    @endif
                </div>
            </div>
        </div>

        <!-- Stock Table -->
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm">
                <thead class="bg-gray-50 dark:bg-gray-900/50 text-xs uppercase font-semibold text-gray-500 dark:text-gray-400">
                    <tr>
                        <th class="px-4 py-3 text-left">Item Name & SKU</th>
                        <th class="px-4 py-3 text-right">Opening</th>
                        <th class="px-4 py-3 text-right">Added / Produced</th>
                        <th class="px-4 py-3 text-right">Used / Sent</th>
                        <th class="px-4 py-3 text-right bg-blue-50/50 dark:bg-blue-900/10 text-blue-900 dark:text-blue-300">Theoretical</th>
                        <th class="px-4 py-3 text-left w-36">Physical Closing</th>
                        <th class="px-4 py-3 text-center">Variance</th>
                        <th class="px-4 py-3 text-left">Notes</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700 bg-white dark:bg-gray-800">
                    @forelse($filteredItems as $itemId => $item)
                        @php
                            $actual = (float) ($closingCounts[$itemId] ?? 0);
                            $theo = (float) $item['theoretical'];
                            $diff = round($actual - $theo, 2);
                        @endphp
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition">
                            <!-- Item & SKU -->
                            <td class="px-4 py-3 font-medium text-gray-900 dark:text-white">
                                <div class="font-semibold">{{ $item['name'] }}</div>
                                <div class="text-xs text-gray-500 dark:text-gray-400">SKU: {{ $item['sku'] }}</div>
                            </td>

                            <!-- Opening -->
                            <td class="px-4 py-3 text-right text-gray-700 dark:text-gray-300 font-mono">
                                {{ number_format($item['opening'], 2) }} <span class="text-xs text-gray-400">{{ $item['uom'] }}</span>
                            </td>

                            <!-- Added -->
                            <td class="px-4 py-3 text-right text-emerald-600 dark:text-emerald-400 font-mono">
                                +{{ number_format($item['added'], 2) }}
                            </td>

                            <!-- Used -->
                            <td class="px-4 py-3 text-right text-rose-600 dark:text-rose-400 font-mono">
                                -{{ number_format($item['used'], 2) }}
                            </td>

                            <!-- Theoretical -->
                            <td class="px-4 py-3 text-right font-mono font-bold bg-blue-50/30 dark:bg-blue-900/10 text-blue-800 dark:text-blue-200">
                                {{ number_format($theo, 2) }} <span class="text-xs text-blue-400 font-normal">{{ $item['uom'] }}</span>
                            </td>

                            <!-- Physical Count Input -->
                            <td class="px-4 py-2">
                                <input type="number" step="0.01" min="0"
                                    wire:model.live.debounce.300ms="closingCounts.{{ $itemId }}"
                                    {{ $isClosed ? 'disabled' : '' }}
                                    class="w-full px-2.5 py-1.5 text-sm font-semibold rounded-lg border {{ $diff != 0 ? 'border-amber-400 bg-amber-50/50 dark:bg-amber-900/20' : 'border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700' }} text-gray-900 dark:text-white focus:ring-2 focus:ring-purple-500 disabled:opacity-60 disabled:cursor-not-allowed" />
                            </td>

                            <!-- Variance Indicator -->
                            <td class="px-4 py-3 text-center">
                                @if($diff == 0)
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-300">
                                        <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                        </svg>
                                        Balanced
                                    </span>
                                @elseif($diff > 0)
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-blue-100 text-blue-800 dark:bg-blue-900/40 dark:text-blue-300 font-mono">
                                        +{{ number_format($diff, 2) }}
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-rose-100 text-rose-800 dark:bg-rose-900/40 dark:text-rose-300 font-mono">
                                        {{ number_format($diff, 2) }}
                                    </span>
                                @endif
                            </td>

                            <!-- Notes -->
                            <td class="px-4 py-2">
                                <input type="text"
                                    wire:model.defer="itemNotes.{{ $itemId }}"
                                    placeholder="Optional note..."
                                    {{ $isClosed ? 'disabled' : '' }}
                                    class="w-full px-2.5 py-1 text-xs rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white disabled:opacity-60" />
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-4 py-8 text-center text-gray-500 dark:text-gray-400">
                                No items found matching the selected category and filter.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Production Batches & Raw Material Usage Collapsibles -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Production Batch Summary -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-5">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-base font-bold text-gray-900 dark:text-white flex items-center gap-2">
                    <svg class="w-5 h-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                    </svg>
                    Production Batches Summary
                </h3>
                <span class="text-xs text-gray-500">{{ count($productionSummary) }} recipes produced</span>
            </div>

            <div class="overflow-x-auto max-h-72">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-xs">
                    <thead class="bg-gray-50 dark:bg-gray-700/50 uppercase font-semibold text-gray-500">
                        <tr>
                            <th class="px-3 py-2 text-left">Recipe</th>
                            <th class="px-3 py-2 text-center">Batches</th>
                            <th class="px-3 py-2 text-right">Produced</th>
                            <th class="px-3 py-2 text-right">Approved</th>
                            <th class="px-3 py-2 text-right">Yield %</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                        @forelse($productionSummary as $produce)
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/40">
                                <td class="px-3 py-2.5 font-medium text-gray-900 dark:text-white">{{ $produce['recipe_name'] }}</td>
                                <td class="px-3 py-2.5 text-center">{{ $produce['batches_produced'] }} / {{ $produce['batches_planned'] }}</td>
                                <td class="px-3 py-2.5 text-right font-mono">{{ number_format($produce['total_produced'], 0) }}</td>
                                <td class="px-3 py-2.5 text-right font-mono font-semibold text-emerald-600 dark:text-emerald-400">{{ number_format($produce['total_approved'], 0) }}</td>
                                <td class="px-3 py-2.5 text-right font-mono {{ $produce['yield_percentage'] >= 90 ? 'text-green-600' : 'text-amber-600' }}">
                                    {{ number_format($produce['yield_percentage'], 1) }}%
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-3 py-6 text-center text-gray-500">No batch records entered during this shift.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Material Variance Alerts -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-5">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-base font-bold text-gray-900 dark:text-white flex items-center gap-2">
                    <svg class="w-5 h-5 text-amber-500" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                    </svg>
                    Material Efficiency & Cost Variances
                </h3>
                <span class="text-xs {{ count($materialVariances) > 0 ? 'text-red-500 font-semibold' : 'text-green-500' }}">
                    {{ count($materialVariances) }} alerts
                </span>
            </div>

            <div class="overflow-y-auto max-h-72 space-y-2">
                @forelse($materialVariances as $v)
                    <div class="p-3 rounded-lg border {{ $v['severity'] === 'high' ? 'border-red-300 bg-red-50/60 dark:bg-red-900/20 dark:border-red-800' : 'border-amber-300 bg-amber-50/60 dark:bg-amber-900/20 dark:border-amber-800' }} flex items-center justify-between">
                        <div>
                            <p class="font-semibold text-gray-900 dark:text-white text-xs">{{ $v['item_name'] }} ({{ $v['item_code'] }})</p>
                            <p class="text-xs text-gray-600 dark:text-gray-400 mt-0.5">
                                <span class="{{ $v['type'] === 'overuse' ? 'text-rose-600 dark:text-rose-400 font-medium' : 'text-blue-600 dark:text-blue-400 font-medium' }}">
                                    {{ $v['type'] === 'overuse' ? 'Overused' : 'Underused' }} by {{ number_format(abs($v['variance_quantity']), 2) }}
                                </span>
                                ({{ number_format($v['variance_percentage'], 1) }}% variance)
                            </p>
                        </div>
                        <div class="text-right">
                            <p class="text-xs font-bold {{ $v['cost_variance'] > 0 ? 'text-rose-600' : 'text-green-600' }}">
                                {{ $v['cost_variance'] > 0 ? '+' : '' }}{{ \App\Helpers\LocalizationHelper::formatCurrency(abs($v['cost_variance'])) }}
                            </p>
                            <span class="text-[10px] uppercase font-bold px-1.5 py-0.5 rounded {{ $v['severity'] === 'high' ? 'bg-red-200 text-red-900 dark:bg-red-900 dark:text-red-200' : 'bg-amber-200 text-amber-900 dark:bg-amber-900 dark:text-amber-200' }}">
                                {{ $v['severity'] }}
                            </span>
                        </div>
                    </div>
                @empty
                    <div class="py-8 text-center text-gray-500 dark:text-gray-400 text-xs">
                        No significant material variances detected for this shift. Perfect adherence to recipes!
                    </div>
                @endforelse
            </div>
        </div>
    </div>

    <!-- Shift Closing Notes & Actions -->
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6 space-y-4">
        <div>
            <label class="block text-sm font-semibold text-gray-900 dark:text-white mb-1.5">
                Shift Handover & Closing Remarks
            </label>
            <textarea wire:model="notes" rows="3"
                placeholder="Mention any kitchen equipment issues, pending dough fermentations, cake decoration tasks, or handover instructions for the next shift..."
                {{ $isClosed ? 'disabled' : '' }}
                class="w-full px-3 py-2 text-sm rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-purple-500 disabled:opacity-60"></textarea>
        </div>

        <div class="flex flex-col sm:flex-row items-center justify-between gap-4 pt-2">
            <div class="text-xs text-gray-500 dark:text-gray-400">
                <p class="font-medium text-gray-700 dark:text-gray-300">Note on Strict Shift Handover:</p>
                <p>When you close this shift, all verified closing stocks are written to the master store and will become the next shift's expected opening stock.</p>
            </div>

            <div class="flex items-center gap-3">
                <button type="button" wire:click="loadClosingStockItems"
                    class="px-4 py-2.5 text-sm font-medium rounded-lg bg-gray-100 hover:bg-gray-200 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-200 transition">
                    Refresh
                </button>

                @if(!$isClosed && $currentShiftId)
                    <button type="button"
                        wire:click="saveShiftClosing"
                        wire:confirm="Are you sure you want to finalize closing stock and close this shift? All production records will be locked and closing counts will be transferred to the next shift."
                        class="px-6 py-2.5 text-sm font-bold rounded-lg bg-purple-600 hover:bg-purple-700 text-white shadow transition flex items-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                        </svg>
                        <span>Finalize & Close Shift</span>
                    </button>
                @elseif($isClosed)
                    <span class="px-6 py-2.5 text-sm font-bold rounded-lg bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-300">
                        Shift Finalized
                    </span>
                @endif
            </div>
        </div>
    </div>
</div>
