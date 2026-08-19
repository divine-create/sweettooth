<div class="p-3 space-y-3">
    <x-breadcrumb title="Stock Opening" :items="[
        ['label' => 'Dashboard', 'url' => branch_route('branch-dashboard.index')],
        ['label' => 'Sales Dashboard'],
        ['label' => 'Stock Opening'],
    ]" :compact="false" :with-icons="true" />
    <!-- Header with Status -->
    <div class="bg-gradient-to-r from-blue-600 to-blue-700 rounded-lg p-4 text-white shadow-lg">
        <div class="flex justify-between items-center">
            <div>
                <h2 class="text-xl font-bold">Daily Stock Opening</h2>
                <p class="text-sm opacity-90 mt-1">
                    {{ \Carbon\Carbon::parse($stockDate)->format('l, F d, Y') }} - {{ ucfirst($shiftType) }} Shift
                </p>
            </div>
          
            <div class="text-right flex items-center gap-3">
                @if ($isVerified)
                    <div class="flex items-center bg-green-500 px-4 py-2 rounded-lg shadow-sm">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <span class="font-semibold">Verified</span>
                    </div>

                    @if($currentShiftId)
                    <div class="flex gap-2">
                        <button wire:click="toggleEdit" 
                            class="flex items-center {{ $isEditing ? 'bg-zinc-700' : 'bg-white/20 hover:bg-white/30' }} px-4 py-2 rounded-lg transition-colors border border-white/30 shadow-sm">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                @if($isEditing)
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                @else
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                @endif
                            </svg>
                            <span class="font-semibold text-sm">{{ $isEditing ? 'Cancel Edit' : 'Edit Opening' }}</span>
                        </button>

                        @if(!$isEditing)
                            <a href="{{ branch_route('branch-dashboard.sales-dashboard.pos.index', ['salesDeptSlug' => $salesDeptSlug]) }}"
                                class="flex items-center bg-emerald-500 hover:bg-emerald-600 px-4 py-2 rounded-lg transition-colors shadow-sm" wire:navigate>
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z" />
                                </svg>
                                <span class="font-semibold text-sm">Go to POS</span>
                            </a>
                        @endif
                    </div>
                    @endif
                @else
                    <div class="flex items-center bg-yellow-500 px-4 py-2 rounded-lg">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                        </svg>
                        <span class="font-semibold">Not Verified</span>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Unresolved Variance Banner -->
    @if(count($unresolvedVariances) > 0)
        <div class="bg-amber-50 dark:bg-amber-900/20 border border-amber-300 dark:border-amber-700 rounded-lg p-4">
            <div class="flex items-start gap-3">
                <svg class="w-5 h-5 text-amber-500 mt-0.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                </svg>
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-semibold text-amber-800 dark:text-amber-300">
                        {{ count($unresolvedVariances) }} unresolved stock variance{{ count($unresolvedVariances) > 1 ? 's' : '' }} from the last 7 days
                    </p>
                    <div class="mt-2 flex flex-wrap gap-2">
                        @foreach($unresolvedVariances as $variance)
                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-medium
                                {{ $variance['reason'] === 'shortage' ? 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400' : 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400' }}">
                                {{ $variance['product']['name'] ?? 'Unknown' }}
                                &mdash; {{ $variance['reason'] }} {{ number_format($variance['quantity'], 2) }}
                                <span class="text-opacity-70">({{ \Carbon\Carbon::parse($variance['variance_date'])->format('M d') }})</span>
                            </span>
                        @endforeach
                    </div>
                    @php $userRoleLevel = function_exists('get_user_role_level') ? get_user_role_level(auth()->user()) : 1; @endphp
                    @if($userRoleLevel >= 2)
                        <div class="mt-2">
                            <a href="{{ branch_route('branch-dashboard.sales-dashboard.variance-resolution.index', ['salesDeptSlug' => $salesDeptSlug]) }}"
                                class="text-xs font-medium text-amber-700 dark:text-amber-400 underline hover:text-amber-900 dark:hover:text-amber-200" wire:navigate>
                                Resolve variances &rarr;
                            </a>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    @endif

    <!-- Shift Selector -->
    @if(count($availableShifts) > 0)
        <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-sm border border-zinc-200 dark:border-zinc-700 p-4">
            <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2">
                Select Shift to View/Edit
            </label>
            <select wire:model.live="selectedShiftForViewing"
                class="w-full px-4 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-800 dark:text-zinc-200 focus:ring-2 focus:ring-blue-500">
                <option value="">-- Current Shift --</option>
                @foreach($availableShifts as $shift)
                    <option value="{{ $shift->id }}">
                        {{ $shift->shift_date->format('M d, Y') }} - {{ ucfirst($shift->shift_type) }} Shift 
                        @if($shift->relationLoaded('employee') && $shift->employee) ({{ $shift->employee->name }}) @endif
                        @if($shift->id == $currentShiftId) (Current) @endif
                    </option>
                @endforeach
            </select>
        </div>
    @endif
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
                    <p class="text-sm text-yellow-700 dark:text-yellow-300 mt-1">Please clock in and select your shift
                        to begin stock opening.</p>
                </div>
            </div>
        </div>
    @endif
    <!-- Product Filters -->
    <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-sm border border-zinc-200 dark:border-zinc-700 p-4 space-y-3">
        <div class="flex items-center justify-between">
            <h2 class="text-sm font-semibold text-zinc-800 dark:text-zinc-100">Products</h2>
            <div class="flex items-center gap-2">
                <span class="text-xs px-2 py-1 rounded bg-zinc-100 text-zinc-700 dark:bg-zinc-700 dark:text-zinc-200">
                    {{ count($stockOpenings) }} products
                </span>
                <span wire:loading.flex wire:target="search, filterProductType, loadStockOpeningData"
                      class="items-center gap-1.5 text-xs text-blue-700 dark:text-blue-300">
                    <svg class="animate-spin h-3.5 w-3.5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    Loading...
                </span>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
            <div>
                <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Product Type</label>
                <select wire:model.live="filterProductType"
                    class="w-full px-4 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-800 dark:text-zinc-200 focus:ring-2 focus:ring-blue-500">
                    <option value="">All Types</option>
                    @foreach ($productTypes as $type)
                        <option value="{{ $type['id'] }}">{{ $type['name'] }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Search Products</label>
                <input type="text" wire:model.live.debounce.400ms="search"
                    placeholder="Type product name or SKU to filter..."
                    class="w-full px-4 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-800 dark:text-zinc-200 focus:ring-2 focus:ring-blue-500">
            </div>
        </div>

        <p class="text-xs text-zinc-600 dark:text-zinc-400">
            All products are listed below. Use filters to narrow the view; verification will apply to the currently listed products.
        </p>
    </div>

    @if(!empty($unclosedProducts))
        <div id="unclosed-products" class="bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 rounded-lg p-4">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="text-sm font-semibold text-amber-900 dark:text-amber-100">Unclosed Products</h3>
                    <p class="text-xs text-amber-700 dark:text-amber-300 mt-1">
                        Selected products with previous closing but no closing entry for {{ \Carbon\Carbon::parse($stockDate)->subDay()->format('M d, Y') }}.
                    </p>
                </div>
            </div>
            <div class="mt-3 overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead>
                        <tr class="text-left text-amber-900 dark:text-amber-100">
                            <th class="py-2 pr-4">Product</th>
                            <th class="py-2 pr-4">SKU</th>
                            <th class="py-2 pr-4">Last Closing</th>
                            <th class="py-2 pr-4">Last Stock Date</th>
                            <th class="py-2 pr-4">Shift</th>
                        </tr>
                    </thead>
                    <tbody class="text-amber-900 dark:text-amber-100">
                        @foreach($unclosedProducts as $item)
                            <tr class="border-t border-amber-200 dark:border-amber-800">
                                <td class="py-2 pr-4">{{ $item['product_name'] }}</td>
                                <td class="py-2 pr-4 text-xs text-amber-700 dark:text-amber-300">{{ $item['product_sku'] }}</td>
                                <td class="py-2 pr-4 font-semibold">
                                    {{ number_format($item['last_closing'], 2) }} {{ $item['product_uom'] }}
                                </td>
                                <td class="py-2 pr-4">{{ $item['last_stock_date'] ?? '-' }}</td>
                                <td class="py-2 pr-4">{{ ucfirst($item['last_shift_type'] ?? '-') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    <div x-data="{ openDetails: true }"
        class="bg-white dark:bg-zinc-800 rounded-lg shadow-sm border border-zinc-200 dark:border-zinc-700 relative">
        <div wire:loading wire:target="selectedProductId, loadStockOpeningData"
             class="absolute inset-0 bg-white/30 dark:bg-zinc-800/30 z-10 flex items-center justify-center pointer-events-none">
            <div class="flex items-center bg-white/90 dark:bg-zinc-700/90 px-4 py-2 rounded-lg shadow-lg">
                <svg class="animate-spin h-5 w-5 text-blue-600 mr-2" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <span class="text-sm font-medium text-zinc-700 dark:text-zinc-200">Loading stock data...</span>
            </div>
        </div>
        <div class="flex items-center justify-between px-4 py-3 border-b border-zinc-200 dark:border-zinc-700">
            <h3 class="text-sm font-semibold text-zinc-800 dark:text-zinc-100">Product Details</h3>
            <button type="button" @click="openDetails = !openDetails"
                class="inline-flex items-center px-3 py-1.5 rounded text-xs font-medium bg-blue-600 hover:bg-blue-700 text-white">
                <span x-text="openDetails ? 'Collapse' : 'Expand'"></span>
            </button>
        </div>

        <div x-show="openDetails" x-collapse class="p-3 space-y-3">
        @if (count($rows) > 0)
            <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
                @foreach ($rows as $row)
                    @php
                        $variance = $row->variance;
                        $varianceClass = $variance == 0 ? 'text-green-600 dark:text-green-400' : ($variance > 0 ? 'text-blue-600 dark:text-blue-400' : 'text-red-600 dark:text-red-400');
                        $varianceIcon = $variance == 0 ? '=' : ($variance > 0 ? '↑' : '↓');
                    @endphp
                    <div class="rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 p-4 space-y-3 shadow-sm">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <div class="text-sm font-semibold text-zinc-900 dark:text-zinc-100">{{ $row->product_name }}</div>
                                <div class="text-xs text-zinc-500 dark:text-zinc-400">{{ $row->product_sku ?? '-' }}</div>
                            </div>
                            <div class="text-xs px-2 py-1 rounded bg-zinc-100 dark:bg-zinc-800 text-zinc-700 dark:text-zinc-200">
                                {{ $row->product_uom ?? 'unit' }}
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-2 text-xs">
                            <div class="rounded-lg bg-zinc-50 dark:bg-zinc-800/60 p-2">
                                <div class="text-zinc-500 dark:text-zinc-400">Previous Closing</div>
                                <div class="text-sm font-medium text-zinc-900 dark:text-zinc-100">
                                    {{ number_format($row->yesterday_closing, 2) }}
                                </div>
                                @if(!empty($row->is_carried_forward))
                                    <div class="text-[10px] text-amber-700 dark:text-amber-300">
                                        carried from {{ $row->previous_closing_source ?? '-' }}
                                    </div>
                                @endif
                            </div>
                            <div class="rounded-lg bg-zinc-50 dark:bg-zinc-800/60 p-2">
                                <div class="text-zinc-500 dark:text-zinc-400">Production Sent</div>
                                <div class="text-sm font-medium text-blue-600 dark:text-blue-400">
                                    {{ number_format($row->today_additions, 2) }}
                                </div>
                                @if(($row->dispatch_count ?? 0) > 0)
                                    <div class="text-[10px] text-blue-700 dark:text-blue-300">
                                        {{ number_format($row->dispatch_count) }} dispatch{{ $row->dispatch_count == 1 ? '' : 'es' }}
                                    </div>
                                @endif
                            </div>
                            <div class="rounded-lg bg-zinc-50 dark:bg-zinc-800/60 p-2">
                                <div class="text-zinc-500 dark:text-zinc-400">Expected Opening</div>
                                <div class="text-sm font-semibold text-green-600 dark:text-green-400">
                                    {{ number_format($row->expected_opening, 2) }}
                                </div>
                            </div>
                            <div class="rounded-lg bg-zinc-50 dark:bg-zinc-800/60 p-2">
                                <div class="text-zinc-500 dark:text-zinc-400">Variance</div>
                                <div class="text-sm font-semibold {{ $varianceClass }}">
                                    {{ $varianceIcon }} {{ number_format(abs($variance), 2) }}
                                </div>
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-xs font-medium text-zinc-600 dark:text-zinc-400 mb-1">Actual Opening</label>
                                <input type="number" step="0.01" min="0"
                                    wire:model.blur="stockOpenings.{{ $row->index }}.actual_opening"
                                    wire:change="updateActualOpening({{ $row->product_id }}, $event.target.value)"
                                    @if($row->is_saved && !$isEditing) readonly @endif
                                    class="w-full px-2 py-1.5 border border-zinc-300 dark:border-zinc-600 rounded
                                        @if($row->is_saved && !$isEditing) bg-zinc-100 dark:bg-zinc-800 cursor-not-allowed @else bg-white dark:bg-zinc-900 @endif
                                        text-zinc-900 dark:text-zinc-100 focus:ring-2 focus:ring-blue-500 text-sm">
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-zinc-600 dark:text-zinc-400 mb-1">Production Date</label>
                                <input type="date"
                                    wire:model.blur="stockOpenings.{{ $row->index }}.production_date"
                                    wire:change="updateProductionDate({{ $row->product_id }}, $event.target.value)"
                                    @if($row->is_saved && !$isEditing) readonly @endif
                                    class="w-full px-2 py-1.5 border border-zinc-300 dark:border-zinc-600 rounded
                                        @if($row->is_saved && !$isEditing) bg-zinc-100 dark:bg-zinc-800 cursor-not-allowed @else bg-white dark:bg-zinc-900 @endif
                                        text-zinc-900 dark:text-zinc-100 focus:ring-2 focus:ring-blue-500 text-sm">
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <div class="text-xs text-zinc-500 dark:text-zinc-400">Shelf Life</div>
                                <div class="text-sm font-medium text-zinc-800 dark:text-zinc-200">
                                    {{ $row->shelf_life_days }} days
                                </div>
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-zinc-600 dark:text-zinc-400 mb-1">Notes</label>
                                <input type="text"
                                    wire:model.blur="stockOpenings.{{ $row->index }}.notes"
                                    wire:change="updateNotes({{ $row->product_id }}, $event.target.value)"
                                    @if($row->is_saved && !$isEditing) readonly @endif
                                    placeholder="Add notes..."
                                    class="w-full px-2 py-1.5 border border-zinc-300 dark:border-zinc-600 rounded
                                        @if($row->is_saved && !$isEditing) bg-zinc-100 dark:bg-zinc-800 cursor-not-allowed @else bg-white dark:bg-zinc-900 @endif
                                        text-zinc-900 dark:text-zinc-100 focus:ring-2 focus:ring-blue-500 text-sm">
                            </div>
                        </div>

                        @if($row->variance != 0)
                            <div class="text-[11px]">
                                <span class="px-2 py-1 rounded-full font-medium {{ $row->variance > 0 ? 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200' : 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200' }}">
                                    {{ $row->variance_source }}
                                </span>
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>
            @if ($productsPaginator)
                <div class="mt-4 space-y-2">
                    <div class="text-xs text-zinc-600 dark:text-zinc-400">
                        Page {{ $productsPaginator->currentPage() }} of {{ $productsPaginator->lastPage() }}
                    </div>
                    <nav role="navigation" aria-label="Pagination Navigation" class="flex items-center justify-between">
                        <div class="flex-1 flex items-center justify-between">
                            <div>
                                <span class="relative z-0 inline-flex rounded-md shadow-sm">
                                    @if ($productsPaginator->onFirstPage())
                                        <span class="relative inline-flex items-center px-3 py-1.5 border border-zinc-300 dark:border-zinc-700 text-sm font-medium text-zinc-400 dark:text-zinc-500 bg-white dark:bg-zinc-900 cursor-default">
                                            Previous
                                        </span>
                                    @else
                                        <button type="button"
                                            wire:click="gotoPage({{ $productsPaginator->currentPage() - 1 }}, 'stock_opening_page')"
                                            class="relative inline-flex items-center px-3 py-1.5 border border-zinc-300 dark:border-zinc-700 text-sm font-medium text-zinc-700 dark:text-zinc-200 bg-white dark:bg-zinc-900 hover:bg-zinc-50 dark:hover:bg-zinc-800">
                                            Previous
                                        </button>
                                    @endif
                                </span>
                            </div>
                            <div class="hidden md:flex">
                                <span class="relative z-0 inline-flex rounded-md shadow-sm">
                                    @php
                                        $currentPage = $productsPaginator->currentPage();
                                        $lastPage = $productsPaginator->lastPage();
                                        $startPage = max(1, $currentPage - 1);
                                        $endPage = min($lastPage, $currentPage + 1);
                                        if ($currentPage <= 2) {
                                            $startPage = 1;
                                            $endPage = min($lastPage, 3);
                                        } elseif ($currentPage >= $lastPage - 1) {
                                            $endPage = $lastPage;
                                            $startPage = max(1, $lastPage - 2);
                                        }
                                    @endphp
                                    @for ($page = $startPage; $page <= $endPage; $page++)
                                        @if ($page == $currentPage)
                                            <span class="relative inline-flex items-center px-3 py-1.5 border border-zinc-300 dark:border-zinc-700 text-sm font-medium text-emerald-700 dark:text-emerald-300 bg-emerald-50 dark:bg-emerald-900/30">
                                                {{ $page }}
                                            </span>
                                        @else
                                            <button type="button"
                                                wire:click="gotoPage({{ $page }}, 'stock_opening_page')"
                                                class="relative inline-flex items-center px-3 py-1.5 border border-zinc-300 dark:border-zinc-700 text-sm font-medium text-zinc-700 dark:text-zinc-200 bg-white dark:bg-zinc-900 hover:bg-zinc-50 dark:hover:bg-zinc-800">
                                                {{ $page }}
                                            </button>
                                        @endif
                                    @endfor
                                </span>
                            </div>
                            <div>
                                <span class="relative z-0 inline-flex rounded-md shadow-sm">
                                    @if ($productsPaginator->hasMorePages())
                                        <button type="button"
                                            wire:click="gotoPage({{ $productsPaginator->currentPage() + 1 }}, 'stock_opening_page')"
                                            class="relative inline-flex items-center px-3 py-1.5 border border-zinc-300 dark:border-zinc-700 text-sm font-medium text-zinc-700 dark:text-zinc-200 bg-white dark:bg-zinc-900 hover:bg-zinc-50 dark:hover:bg-zinc-800">
                                            Next
                                        </button>
                                    @else
                                        <span class="relative inline-flex items-center px-3 py-1.5 border border-zinc-300 dark:border-zinc-700 text-sm font-medium text-zinc-400 dark:text-zinc-500 bg-white dark:bg-zinc-900 cursor-default">
                                            Next
                                        </span>
                                    @endif
                                </span>
                            </div>
                        </div>
                    </nav>
                </div>
            @endif
        @else
            <div class="p-6 text-center">
                <h3 class="text-sm font-semibold text-zinc-800 dark:text-zinc-100">No Product Selected</h3>
                <p class="text-sm text-zinc-600 dark:text-zinc-400 mt-1">
                    No products found for the current filters.
                </p>
            </div>
        @endif

        <!-- Action Buttons -->
        @if (count($stockOpenings) > 0 && (!$isVerified || $isEditing))
            @if($isEditing)
                <div class="mb-4 bg-amber-50 dark:bg-amber-900/20 border-l-4 border-amber-500 p-4 rounded mx-3">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <svg class="h-5 w-5 text-amber-500" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                            </svg>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm text-amber-700 dark:text-amber-200 font-medium">
                                Editing Verified Stock: Updating opening stock will automatically recalculate your current closing balance and variance.
                            </p>
                        </div>
                    </div>
                </div>
            @endif
            <div class="flex flex-wrap items-center justify-between gap-3 mt-4 pt-4 border-t border-zinc-200 dark:border-zinc-700 px-3 pb-3">
                <div class="flex gap-2">
                    <button wire:click="matchAllExpected" 
                        class="px-4 py-2 bg-blue-100 hover:bg-blue-200 text-blue-800 dark:bg-blue-900/30 dark:hover:bg-blue-900/50 dark:text-blue-200 rounded-lg text-sm font-medium transition-colors flex items-center">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                        </svg>
                        Match All Expected
                    </button>
                    <button wire:click="saveAsDraft" wire:loading.attr="disabled"
                        class="px-4 py-2 bg-zinc-100 hover:bg-zinc-200 text-zinc-800 dark:bg-zinc-700 dark:hover:bg-zinc-600 dark:text-zinc-200 rounded-lg text-sm font-medium transition-colors flex items-center">
                        <svg wire:loading.remove wire:target="saveAsDraft" class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4" />
                        </svg>
                        <svg wire:loading wire:target="saveAsDraft" class="animate-spin h-4 w-4 mr-2" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        Save Draft
                    </button>
                </div>

                <div class="flex gap-2">
                    <button wire:click="loadStockOpeningData" wire:loading.attr="disabled" wire:target="loadStockOpeningData"
                        class="px-4 py-2 bg-zinc-600 hover:bg-zinc-700 text-white rounded-lg text-sm font-medium transition-colors flex items-center">
                        <svg wire:loading.remove wire:target="loadStockOpeningData" class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                        </svg>
                        <span wire:loading wire:target="loadStockOpeningData" class="animate-spin h-4 w-4 mr-2 border-2 border-white border-t-transparent rounded-full"></span>
                        Refresh
                    </button>
                    <button wire:click="saveStockOpenings" wire:loading.attr="disabled"
                        class="px-6 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg text-sm font-bold transition-colors flex items-center shadow-md">
                        <svg wire:loading.remove wire:target="saveStockOpenings" class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <span wire:loading wire:target="saveStockOpenings" class="animate-spin h-5 w-5 mr-2 border-2 border-white border-t-transparent rounded-full"></span>
                        Verify & Finalize
                    </button>
                </div>
            </div>
        @endif
        </div>
    </div>
    <!-- Information Panel -->
    <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-4">
        <div class="flex items-start">
            <svg class="w-5 h-5 text-blue-600 dark:text-blue-400 mt-0.5 mr-3 flex-shrink-0" fill="none"
                stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <div class="text-sm text-blue-800 dark:text-blue-200">
                <h4 class="font-semibold mb-1">Stock Opening Instructions:</h4>
                <ul class="list-disc list-inside space-y-1">
                    <li><strong>Previous Closing:</strong> Stock from previous shift/day's closing count</li>
                    <li><strong>Production Sent:</strong> Products received from production dispatches (received-only)</li>
                    <li><strong>Expected Opening:</strong> Previous closing + Production sent</li>
                    <li><strong>Actual Opening:</strong> Physically count and enter the actual quantity you have at START of shift</li>
                    <li><strong>Variance:</strong> Difference between expected and actual opening (investigate if significant)</li>
                    <li><strong>Variance From:</strong> Shows which shift/day caused the variance (Previous closing or Production)</li>
                    <li><strong>Production Date:</strong> When the product was made (affects expiry calculation)</li>
                    <li>Note: Closing stock is tracked separately at end of day</li>
                    <li><strong>Shelf Life Status:</strong> Fresh (green), Warning (yellow), Critical (orange), Expired (red)</li>
                    <li>Use the shift selector to view or edit past shifts</li>
                    <li>Once verified and saved, stock opening cannot be modified for that shift</li>
                </ul>
            </div>
        </div>
    </div>
</div>
