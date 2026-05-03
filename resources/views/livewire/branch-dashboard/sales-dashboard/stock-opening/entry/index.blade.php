<div class="p-3 space-y-3" x-data @navigate-to-pos.window="window.location.href = $event.detail.url">
    <x-breadcrumb title="Stock Opening - Data Entry" :items="[
        ['label' => 'Dashboard', 'url' => branch_route('branch-dashboard.index')],
        ['label' => 'Sales Dashboard'],
        ['label' => 'Stock Opening'],
        ['label' => 'Data Entry'],
    ]" :compact="false" :with-icons="true" />

    <!-- Header -->
    <div class="bg-gradient-to-r from-green-600 to-green-700 rounded-lg p-4 text-white shadow-lg">
        <div class="flex justify-between items-center">
            <div>
                <h2 class="text-xl font-bold">Stock Opening - Data Entry</h2>
                <p class="text-sm opacity-90 mt-1">
                    Enter actual opening quantities for selected products
                </p>
            </div>
            <div class="text-right">
                <div class="bg-white/20 px-4 py-2 rounded-lg">
                    <span class="font-semibold">{{ $savedCount }}/{{ $totalCount }}</span> saved
                </div>
            </div>
        </div>
    </div>

    <!-- Actions Bar -->
    <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-sm border border-zinc-200 dark:border-zinc-700 p-4">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div class="flex flex-wrap items-center gap-2">
                <button wire:click="saveAll" 
                    class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg font-medium transition-colors flex items-center gap-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                    </svg>
                    Save All
                </button>
                
                <button wire:click="addMoreProducts" 
                    class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg font-medium transition-colors flex items-center gap-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                    </svg>
                    Add More Products
                </button>

                <button wire:click="clearAndStartOver" 
                    class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-lg font-medium transition-colors flex items-center gap-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                    </svg>
                    Start Over
                </button>
            </div>

            <div class="text-sm text-zinc-600 dark:text-zinc-400">
                {{ $totalCount }} products selected
            </div>
        </div>
    </div>

    <!-- Stock Entry Table -->
    <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-sm border border-zinc-200 dark:border-zinc-700 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-zinc-50 dark:bg-zinc-900/50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-zinc-600 dark:text-zinc-400 uppercase">Product</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold text-zinc-600 dark:text-zinc-400 uppercase">Prev Shift Closing</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold text-zinc-600 dark:text-zinc-400 uppercase">Expected</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold text-zinc-600 dark:text-zinc-400 uppercase">Actual Opening</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold text-zinc-600 dark:text-zinc-400 uppercase">Variance</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-zinc-600 dark:text-zinc-400 uppercase">Production Date</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-zinc-600 dark:text-zinc-400 uppercase">Expiry Date</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-zinc-600 dark:text-zinc-400 uppercase">Notes</th>
                        <th class="px-4 py-3 text-center text-xs font-semibold text-zinc-600 dark:text-zinc-400 uppercase">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                    @forelse($stockOpenings as $index => $stock)
                        <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-700/50">
                            <td class="px-4 py-3">
                                <div class="font-medium text-zinc-900 dark:text-zinc-100">{{ $stock['product_name'] }}</div>
                                <div class="text-xs text-zinc-500 dark:text-zinc-400">SKU: {{ $stock['product_sku'] }}</div>
                                <div class="text-xs text-zinc-500 dark:text-zinc-400">UOM: {{ $stock['product_uom'] }}</div>
                            </td>
                            <td class="px-4 py-3 text-right text-zinc-900 dark:text-zinc-100">
                                {{ number_format($stock['yesterday_closing'], 2) }}
                            </td>
                            <td class="px-4 py-3 text-right text-zinc-900 dark:text-zinc-100">
                                {{ number_format($stock['expected_opening'], 2) }}
                            </td>
                            <td class="px-4 py-3">
                                <input type="number" step="0.01" min="0"
                                    wire:model="stockOpenings.{{ $index }}.actual_opening"
                                    class="w-32 px-3 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-zinc-100 focus:ring-2 focus:ring-blue-500 text-right"
                                    placeholder="Enter quantity">
                            </td>
                            <td class="px-4 py-3 text-right">
                                <span class="{{ $stock['variance'] == 0 ? 'text-zinc-600' : ($stock['variance'] > 0 ? 'text-green-600' : 'text-red-600') }} font-medium">
                                    {{ $stock['variance'] > 0 ? '+' : '' }}{{ number_format($stock['variance'], 2) }}
                                </span>
                            </td>
                            <td class="px-4 py-3">
                                <input type="date"
                                    wire:model="stockOpenings.{{ $index }}.production_date"
                                    class="w-36 px-3 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-zinc-100 focus:ring-2 focus:ring-blue-500">
                            </td>
                            <td class="px-4 py-3">
                                <input type="date"
                                    wire:model="stockOpenings.{{ $index }}.expiry_date"
                                    class="w-36 px-3 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-zinc-100 focus:ring-2 focus:ring-blue-500">
                            </td>
                            <td class="px-4 py-3">
                                <input type="text"
                                    wire:model="stockOpenings.{{ $index }}.notes"
                                    class="w-40 px-3 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-zinc-100 focus:ring-2 focus:ring-blue-500"
                                    placeholder="Notes...">
                            </td>
                            <td class="px-4 py-3 text-center">
                                @if($stock['is_saved'])
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400">
                                        Saved
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400">
                                        Pending
                                    </span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="px-4 py-8 text-center text-zinc-500 dark:text-zinc-400">
                                <div class="flex flex-col items-center">
                                    <svg class="w-12 h-12 text-zinc-300 dark:text-zinc-600 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" />
                                    </svg>
                                    <p class="text-lg font-medium">No products selected</p>
                                    <p class="text-sm">Please go back and select products for stock opening</p>
                                    <button wire:click="addMoreProducts" 
                                        class="mt-3 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg font-medium transition-colors">
                                        Select Products
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>