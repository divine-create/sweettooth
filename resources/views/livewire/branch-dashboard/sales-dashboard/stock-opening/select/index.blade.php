<div class="p-3 space-y-3">
    <x-breadcrumb title="Stock Opening - Select Products" :items="[
        ['label' => 'Dashboard', 'url' => branch_route('branch-dashboard.index')],
        ['label' => 'Sales Dashboard'],
        ['label' => 'Stock Opening', 'url' => branch_route('branch-dashboard.sales-dashboard.stock-opening.select.index', ['salesDeptSlug' => $salesDeptSlug ?? ''])],
        ['label' => 'Select Products'],
    ]" :compact="false" :with-icons="true" />

    <!-- Header -->
    <div class="bg-gradient-to-r from-blue-600 to-blue-700 rounded-lg p-4 text-white shadow-lg">
        <div class="flex justify-between items-center">
            <div>
                <h2 class="text-xl font-bold">Select Products for Stock Opening</h2>
                <p class="text-sm opacity-90 mt-1">
                    Choose the products you want to perform stock opening for today
                </p>
            </div>
            <div class="text-right">
                <div class="bg-white/20 px-4 py-2 rounded-lg">
                    <span class="font-semibold">{{ count($selectedProductIds) }}</span> products selected
                </div>
            </div>
        </div>
    </div>

    <!-- Actions Bar -->
    <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-sm border border-zinc-200 dark:border-zinc-700 p-4">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div class="flex flex-wrap items-center gap-2">
                <button wire:click="proceedToEntry" 
                    class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg font-medium transition-colors flex items-center gap-2"
                    {{ count($selectedProductIds) == 0 ? 'disabled' : '' }}>
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                    </svg>
                    Proceed to Entry ({{ count($selectedProductIds) }})
                </button>
                
                @if(count($selectedProductIds) > 0)
                    <button wire:click="clearSelection" 
                        class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-lg font-medium transition-colors">
                        Clear Selection
                    </button>
                @endif
            </div>

            <div class="flex items-center gap-4">
                <!-- Search -->
                <input type="text" wire:model.live.debounce.300ms="search"
                    placeholder="Search products..."
                    class="px-4 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-800 dark:text-zinc-200 focus:ring-2 focus:ring-blue-500 w-64">

                <!-- Product Type Filter -->
                <select wire:model.live="filterProductType"
                    class="px-4 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-800 dark:text-zinc-200 focus:ring-2 focus:ring-blue-500">
                    <option value="">All Types</option>
                    @foreach(\App\Models\ProductType::active()->orderBy('name')->get() as $type)
                        <option value="{{ $type->id }}">{{ $type->name }}</option>
                    @endforeach
                </select>
            </div>
        </div>
    </div>

    <!-- Bulk Actions -->
    <div class="bg-blue-50 dark:bg-blue-900/20 rounded-lg p-3 flex items-center justify-between">
        <div class="flex items-center gap-2">
            <button wire:click="selectAllCurrentPage" 
                class="px-3 py-1.5 text-sm bg-blue-600 hover:bg-blue-700 text-white rounded transition-colors">
                Select All This Page
            </button>
            <button wire:click="deselectAllCurrentPage" 
                class="px-3 py-1.5 text-sm bg-zinc-600 hover:bg-zinc-700 text-white rounded transition-colors">
                Deselect All This Page
            </button>
        </div>
        <div class="text-sm text-blue-800 dark:text-blue-200">
            Showing {{ $products->count() }} of {{ $products->total() }} products
        </div>
    </div>

    <!-- Product Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
        @foreach($products as $product)
            <div class="bg-white dark:bg-zinc-800 rounded-lg border-2 {{ in_array($product->id, $selectedProductIds) ? 'border-blue-500' : 'border-zinc-200 dark:border-zinc-700' }} 
                        hover:border-blue-400 transition-all cursor-pointer p-4"
                wire:click="toggleProductSelection('{{ $product->id }}')">
                <div class="flex items-start justify-between">
                    <div class="flex-1">
                        <div class="flex items-center gap-2 mb-1">
                            @if(in_array($product->id, $selectedProductIds))
                                <svg class="w-5 h-5 text-blue-600" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                                </svg>
                            @else
                                <div class="w-5 h-5 border-2 border-zinc-300 rounded"></div>
                            @endif
                            <h3 class="font-semibold text-zinc-900 dark:text-zinc-100">{{ $product->name }}</h3>
                        </div>
                        <p class="text-sm text-zinc-600 dark:text-zinc-400">SKU: {{ $product->sku }}</p>
                        <p class="text-xs text-zinc-500 dark:text-zinc-500 mt-1">
                            UOM: {{ $product->unitOfMeasure?->symbol ?? 'N/A' }}
                        </p>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <!-- Pagination -->
    <div class="bg-white dark:bg-zinc-800 rounded-lg border border-zinc-200 dark:border-zinc-700 p-4">
        {{ $products->links() }}
    </div>
</div>