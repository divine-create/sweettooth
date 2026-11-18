<div class="p-6 max-w-7xl mx-auto space-y-6">
    <!-- Header -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 bg-white dark:bg-zinc-800 p-6 rounded-2xl shadow-sm border border-zinc-200 dark:border-zinc-700">
        <div>
            <div class="flex items-center gap-3">
                <h1 class="text-2xl font-bold text-zinc-900 dark:text-zinc-100">Shift Opening Stock Verification</h1>
                <span class="px-3 py-1 rounded-full text-xs font-semibold bg-purple-100 text-purple-800 dark:bg-purple-900/60 dark:text-purple-300">
                    {{ $department->name }}
                </span>
            </div>
            <p class="text-sm text-zinc-500 dark:text-zinc-400 mt-1">
                Verify and record starting physical stock across Finished Goods, WIP, and Raw Materials before production begins.
            </p>
        </div>

        @if($currentShift)
            <div class="flex items-center gap-2 bg-zinc-50 dark:bg-zinc-900/80 px-4 py-2.5 rounded-xl border border-zinc-200 dark:border-zinc-700">
                <flux:icon icon="clock" class="w-5 h-5 text-zinc-400" />
                <div class="text-xs">
                    <span class="font-medium text-zinc-700 dark:text-zinc-300">Active Shift:</span>
                    <span class="font-semibold text-zinc-900 dark:text-zinc-100 uppercase">{{ $currentShift->shift_type }}</span>
                    <span class="text-zinc-400">({{ $currentShift->shift_date?->format('M d, Y') }})</span>
                </div>
            </div>
        @else
            <div class="flex items-center gap-2 bg-amber-50 dark:bg-amber-900/30 px-4 py-2.5 rounded-xl border border-amber-200 dark:border-amber-700 text-amber-800 dark:text-amber-200 text-xs">
                <flux:icon icon="exclamation-triangle" class="w-5 h-5 text-amber-500" />
                <span>No active shift found. Please clock in to start your shift.</span>
            </div>
        @endif
    </div>

    <!-- Status Banner -->
    @if($isVerified)
        <div class="flex items-center gap-3 p-4 rounded-xl bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-200 dark:border-emerald-800 text-emerald-800 dark:text-emerald-200">
            <flux:icon icon="check-circle" class="w-6 h-6 text-emerald-600 dark:text-emerald-400 shrink-0" />
            <div class="text-sm">
                <p class="font-semibold">Shift Opening Stock Verified</p>
                <p class="text-xs opacity-90">Verified on {{ $verifiedAt }}. Production, material requests, and dispatches are fully unlocked.</p>
            </div>
        </div>
    @else
        <div class="flex items-center gap-3 p-4 rounded-xl bg-amber-50 dark:bg-amber-950/40 border border-amber-200 dark:border-amber-800 text-amber-800 dark:text-amber-200">
            <flux:icon icon="lock-closed" class="w-6 h-6 text-amber-600 dark:text-amber-400 shrink-0" />
            <div class="text-sm">
                <p class="font-semibold">Production Operations Locked</p>
                <p class="text-xs opacity-90">Please complete your shift opening stock physical count below to unlock Quick Produce, Material Requests, and Dispatches.</p>
            </div>
        </div>
    @endif

    <!-- Main Card -->
    <div class="bg-white dark:bg-zinc-800 rounded-2xl shadow-sm border border-zinc-200 dark:border-zinc-700 overflow-hidden">
        <!-- Tab Bar & Controls -->
        <div class="p-4 border-b border-zinc-200 dark:border-zinc-700 flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-zinc-50/50 dark:bg-zinc-900/40">
            <!-- 3 Tabs -->
            <div class="flex items-center gap-2">
                <button type="button" wire:click="setTab('finished')"
                    class="px-4 py-2 rounded-xl text-sm font-semibold transition-all flex items-center gap-2 {{ $activeTab === 'finished' ? 'bg-purple-600 text-white shadow-sm' : 'bg-white dark:bg-zinc-800 text-zinc-600 dark:text-zinc-300 hover:bg-zinc-100 dark:hover:bg-zinc-700 border border-zinc-200 dark:border-zinc-700' }}">
                    <span>Finished Goods</span>
                    <span class="px-2 py-0.5 rounded-full text-xs {{ $activeTab === 'finished' ? 'bg-purple-700 text-purple-100' : 'bg-zinc-100 dark:bg-zinc-700 text-zinc-600 dark:text-zinc-400' }}">
                        {{ $finishedCount }}
                    </span>
                </button>

                <button type="button" wire:click="setTab('wip')"
                    class="px-4 py-2 rounded-xl text-sm font-semibold transition-all flex items-center gap-2 {{ $activeTab === 'wip' ? 'bg-purple-600 text-white shadow-sm' : 'bg-white dark:bg-zinc-800 text-zinc-600 dark:text-zinc-300 hover:bg-zinc-100 dark:hover:bg-zinc-700 border border-zinc-200 dark:border-zinc-700' }}">
                    <span>WIP Products</span>
                    <span class="px-2 py-0.5 rounded-full text-xs {{ $activeTab === 'wip' ? 'bg-purple-700 text-purple-100' : 'bg-zinc-100 dark:bg-zinc-700 text-zinc-600 dark:text-zinc-400' }}">
                        {{ $wipCount }}
                    </span>
                </button>

                <button type="button" wire:click="setTab('raw')"
                    class="px-4 py-2 rounded-xl text-sm font-semibold transition-all flex items-center gap-2 {{ $activeTab === 'raw' ? 'bg-purple-600 text-white shadow-sm' : 'bg-white dark:bg-zinc-800 text-zinc-600 dark:text-zinc-300 hover:bg-zinc-100 dark:hover:bg-zinc-700 border border-zinc-200 dark:border-zinc-700' }}">
                    <span>Raw Materials</span>
                    <span class="px-2 py-0.5 rounded-full text-xs {{ $activeTab === 'raw' ? 'bg-purple-700 text-purple-100' : 'bg-zinc-100 dark:bg-zinc-700 text-zinc-600 dark:text-zinc-400' }}">
                        {{ $rawCount }}
                    </span>
                </button>
            </div>

            <!-- Search & Actions -->
            <div class="flex items-center gap-3">
                <div class="relative w-full sm:w-64">
                    <input type="text" wire:model.live.debounce.300ms="search" placeholder="Search items..."
                        class="w-full pl-9 pr-4 py-2 text-sm rounded-xl border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-700 text-zinc-800 dark:text-zinc-200 focus:ring-2 focus:ring-purple-500" />
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <flux:icon icon="magnifying-glass" class="w-4 h-4 text-zinc-400" />
                    </div>
                </div>

                <button type="button" wire:click="matchAllExpected"
                    class="px-3 py-2 text-xs font-semibold rounded-xl bg-zinc-100 hover:bg-zinc-200 dark:bg-zinc-700 dark:hover:bg-zinc-600 text-zinc-700 dark:text-zinc-200 transition-colors whitespace-nowrap">
                    Match All Expected
                </button>
            </div>
        </div>

        <!-- Table -->
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm divide-y divide-zinc-200 dark:divide-zinc-700">
                <thead class="bg-zinc-50 dark:bg-zinc-900/60 text-xs uppercase font-semibold text-zinc-500 dark:text-zinc-400">
                    <tr>
                        <th class="py-3 px-4">Item Name</th>
                        <th class="py-3 px-3">SKU</th>
                        <th class="py-3 px-3">UOM</th>
                        <th class="py-3 px-4 text-right">Expected Opening</th>
                        <th class="py-3 px-4 text-center w-48">Actual Physical Count</th>
                        <th class="py-3 px-3 text-right">Variance</th>
                        <th class="py-3 px-4">Notes (Optional)</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-100 dark:divide-zinc-700/60">
                    @forelse($filteredItems as $itemId => $item)
                        @php
                            $actual = (float) ($openingCounts[$itemId] ?? 0);
                            $expected = (float) $item['expected'];
                            $variance = $actual - $expected;
                        @endphp
                        <tr class="hover:bg-zinc-50/70 dark:hover:bg-zinc-700/30 transition-colors">
                            <td class="py-3 px-4 font-medium text-zinc-900 dark:text-zinc-100">
                                {{ $item['name'] }}
                            </td>
                            <td class="py-3 px-3 text-xs text-zinc-400">
                                {{ $item['sku'] }}
                            </td>
                            <td class="py-3 px-3">
                                <span class="px-2 py-0.5 rounded text-xs bg-zinc-100 dark:bg-zinc-700 text-zinc-600 dark:text-zinc-300 font-mono">
                                    {{ $item['uom'] }}
                                </span>
                            </td>
                            <td class="py-3 px-4 text-right font-mono font-semibold text-zinc-700 dark:text-zinc-300">
                                {{ number_format($expected, 2) }}
                            </td>
                            <td class="py-3 px-4">
                                <div class="flex items-center justify-center">
                                    <input type="number" step="0.01" min="0"
                                        wire:model="openingCounts.{{ $itemId }}"
                                        class="w-28 text-right font-mono font-semibold px-3 py-1.5 rounded-lg border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-700 text-zinc-900 dark:text-zinc-100 focus:ring-2 focus:ring-purple-500 @error('openingCounts.'.$itemId) border-red-500 @enderror" />
                                </div>
                            </td>
                            <td class="py-3 px-3 text-right font-mono font-semibold text-xs">
                                @if(abs($variance) < 0.001)
                                    <span class="text-emerald-600 dark:text-emerald-400">0.00</span>
                                @elseif($variance < 0)
                                    <span class="text-rose-600 dark:text-rose-400">{{ number_format($variance, 2) }}</span>
                                @else
                                    <span class="text-amber-600 dark:text-amber-400">+{{ number_format($variance, 2) }}</span>
                                @endif
                            </td>
                            <td class="py-3 px-4">
                                <input type="text" wire:model="notes.{{ $itemId }}" placeholder="Reason if variance..."
                                    class="w-full text-xs px-3 py-1.5 rounded-lg border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 text-zinc-800 dark:text-zinc-200 focus:ring-2 focus:ring-purple-500" />
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center py-10 text-zinc-400 dark:text-zinc-500">
                                No items found in this category.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Footer Submission Bar -->
        <div class="p-5 border-t border-zinc-200 dark:border-zinc-700 bg-zinc-50/50 dark:bg-zinc-900/60 flex flex-col sm:flex-row items-center justify-between gap-4">
            <div class="text-xs text-zinc-500 dark:text-zinc-400">
                Saving will record the opening stock for this shift and unlock kitchen production operations.
            </div>

            <div class="flex items-center gap-3">
                <button type="button" wire:click="saveOpeningStock" wire:loading.attr="disabled"
                    class="px-6 py-2.5 rounded-xl font-semibold text-white bg-purple-600 hover:bg-purple-700 focus:ring-4 focus:ring-purple-500/20 shadow-md transition-all flex items-center gap-2">
                    <svg class="w-4 h-4 animate-spin" wire:loading wire:target="saveOpeningStock" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    <span wire:loading.remove wire:target="saveOpeningStock">
                        {{ $isVerified ? 'Update Opening Stock' : 'Confirm & Unlock Production' }}
                    </span>
                    <span wire:loading wire:target="saveOpeningStock">Saving...</span>
                </button>
            </div>
        </div>
    </div>
</div>
