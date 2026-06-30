<div class="p-3 space-y-4">

    <x-breadcrumb
        title="Produce Finished Goods"
        :items="[
            ['label' => 'Dashboard', 'url' => branch_route('branch-dashboard.index')],
            ['label' => 'Production'],
            ['label' => 'Produce Finished Goods']
        ]"
        :compact="false"
        :with-icons="true"/>

    @if(!$this->productionStore)
        <div class="bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-lg p-4">
            <div class="flex items-center gap-2 text-yellow-800 dark:text-yellow-200">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                </svg>
                <span>No Production Store found for this department.</span>
            </div>
            <p class="text-sm text-yellow-700 dark:text-yellow-300 mt-1">Please request materials from Inventory first.</p>
        </div>
    @else
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">

            <!-- Recipe Selection -->
            <div class="bg-white dark:bg-zinc-800 rounded-lg shadow border p-4">
                <h3 class="text-lg font-semibold text-zinc-800 dark:text-zinc-100 mb-4">Select Finished Good to Produce</h3>

                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Finish Good</label>
                        <select wire:model.live="selectedRecipeId" class="w-full rounded border p-2 dark:bg-zinc-700 dark:border-zinc-600">
                            <option value="">-- Select Finish Good --</option>
                            @foreach($this->getRecipes() as $recipe)
                                <option value="{{ $recipe->id }}">{{ $recipe->product_name }} ({{ $recipe->yield_quantity }} {{ $recipe->uomSymbol }})</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                @if($selectedRecipe)
                    <div class="mt-4 pt-4 border-t">
                        <h4 class="font-semibold text-zinc-800 dark:text-zinc-100">{{ $selectedRecipe->product_name }}</h4>
                        <p class="text-sm text-zinc-500">Type: {{ $selectedRecipe->productType?->name ?? 'Standard' }}</p>

                        <div class="mt-4 grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">
                                    Quantity to Produce (batches)
                                </label>
                                <input type="number" wire:model.live="quantity" min="1" step="0.5"
                                       class="w-full rounded border p-2 dark:bg-zinc-700 dark:border-zinc-600"/>
                                <p class="text-xs text-zinc-500 mt-1">
                                    Total expected: <span class="font-semibold">{{ number_format($yieldOutput, 2) }}</span> {{ $selectedRecipe->uomSymbol }}
                                </p>
                            </div>

                            <div class="space-y-3">
                                <div>
                                    <label class="block text-xs font-medium text-green-700 dark:text-green-400">Approved Quantity ({{ $selectedRecipe->uomSymbol }})</label>
                                    <input type="number" wire:model.live="approvedQuantity" step="0.01" min="0"
                                           class="w-full rounded border p-2 bg-green-50 dark:bg-green-900/10 dark:border-green-800"/>
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-red-700 dark:text-red-400">Rejected Quantity ({{ $selectedRecipe->uomSymbol }})</label>
                                    <input type="number" wire:model.live="rejectedQuantity" step="0.01" min="0"
                                           class="w-full rounded border p-2 bg-red-50 dark:bg-red-900/10 dark:border-red-800"/>
                                </div>

                                @if($rejectedQuantity > 0)
                                    <div>
                                        <label class="block text-xs font-medium text-zinc-700 dark:text-zinc-300 mb-1">Rejection Reason</label>
                                        <select wire:model="rejectionReason" class="w-full rounded border p-2 text-xs dark:bg-zinc-700">
                                            <option value="">-- Select Reason --</option>
                                            @foreach($rejectionReasonOptions as $val => $label)
                                                <option value="{{ $val }}">{{ $label }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                @endif
                            </div>
                        </div>

                        @php $salesReceives = $this->salesReceivesFor((float) $approvedQuantity); @endphp
                        <div class="mt-3 rounded-lg border border-indigo-200 dark:border-indigo-800 bg-indigo-50 dark:bg-indigo-900/20 p-3">
                            <div class="flex items-center justify-between">
                                <span class="text-sm font-medium text-indigo-800 dark:text-indigo-200">Sales will receive</span>
                                <span class="text-base font-bold text-indigo-900 dark:text-indigo-100">
                                    {{ rtrim(rtrim(number_format($salesReceives['qty'], 2), '0'), '.') }} {{ $salesReceives['symbol'] }}
                                    @if($salesReceives['converted'])
                                        <span class="text-xs font-normal text-indigo-600 dark:text-indigo-300">
                                            (from {{ rtrim(rtrim(number_format($approvedQuantity, 2), '0'), '.') }} {{ $selectedRecipe->uomSymbol }} produced)
                                        </span>
                                    @endif
                                </span>
                            </div>
                            @unless($salesReceives['converted'])
                                <p class="text-xs text-indigo-600 dark:text-indigo-300 mt-1">
                                    Sold in {{ $selectedRecipe->uomSymbol }} — set a sales unit on this product to sell it by portion/piece.
                                </p>
                            @endunless
                        </div>
                    </div>
                @endif
            </div>

            <!-- Ingredients Preview -->
            <div class="bg-white dark:bg-zinc-800 rounded-lg shadow border p-4">
                <h3 class="text-lg font-semibold text-zinc-800 dark:text-zinc-100 mb-4">Ingredients Required</h3>

                @if(empty($ingredients))
                    <p class="text-zinc-500 text-center py-4">Select a recipe to see ingredients</p>
                @else
                    <div class="space-y-2">
                        @foreach($ingredients as $ing)
                            @php
                                $stockInfo = $ingredientStock[$ing['item_id']] ?? ['available' => 0, 'status' => 'unknown'];
                                $isInsufficient = $stockInfo['status'] === 'insufficient';
                            @endphp
                            <div class="flex items-center justify-between p-2 rounded {{ $isInsufficient ? 'bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800' : 'bg-zinc-50 dark:bg-zinc-700' }}">
                                <div>
                                    <p class="font-medium text-zinc-800 dark:text-zinc-100">{{ $ing['item_name'] }}</p>
                                    @if($ing['is_wip'])
                                        <span class="text-xs text-blue-600 bg-blue-100 dark:bg-blue-900 px-2 py-0.5 rounded">WIP</span>
                                    @endif
                                </div>
                                <div class="text-right">
                                    <p class="font-semibold text-zinc-800 dark:text-zinc-100">
                                        {{ number_format($ing['quantity'], 2) }} {{ $ing['uom_symbol'] }}
                                    </p>
                                    <p class="text-xs text-zinc-500">
                                        Available: {{ number_format($stockInfo['available'], 2) }}
                                        @if($isInsufficient)
                                            <span class="text-red-600 font-semibold">- INSUFFICIENT</span>
                                        @endif
                                    </p>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    @if($hasInsufficientStock)
                        <div class="mt-4 p-3 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg">
                            <p class="text-red-700 dark:text-red-300 font-medium">
                                Cannot produce - Insufficient stock
                            </p>
                        </div>
                    @endif
                @endif
            </div>
        </div>

        <!-- Produce Button -->
        @if($selectedRecipe)
            <div class="flex justify-center">
                <button wire:click="produce" @if($hasInsufficientStock) disabled @endif
                        class="px-8 py-3 {{ $hasInsufficientStock ? 'bg-zinc-400 cursor-not-allowed' : 'bg-green-600 hover:bg-green-700' }} text-white rounded-lg font-semibold flex items-center gap-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                    </svg>
                    PRODUCE
                </button>
            </div>
        @endif

    @endif

    <!-- Dispatch Modal -->
    @if($showDispatchModal)
        <div class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
            <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-xl w-full max-w-md">
                <div class="p-6">
                    <h3 class="text-lg font-semibold text-zinc-800 dark:text-zinc-100 mb-4">
                        {{ $isRedispatch ? 'Dispatch Pending Batch' : 'Production Complete!' }}
                    </h3>
                    <p class="text-zinc-600 dark:text-zinc-300 mb-4">
                        {{ $isRedispatch ? 'Dispatching' : 'Produced' }} {{ number_format($yieldOutput, 2) }} {{ $selectedRecipe?->uomSymbol }} of {{ $selectedRecipe?->product_name }}
                    </p>

                    @if(!$selectedRecipe?->product_id)
                        <div class="mb-4 p-3 bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-lg">
                            <p class="text-yellow-700 dark:text-yellow-300 text-sm">
                                <strong>Assignment Required:</strong> This recipe is not linked to a product. Please use Product Assignments to link it first.
                            </p>
                        </div>
                    @endif

                    {{-- Step 1: Choose dispatch type --}}
                    @if($dispatchType === 'sales' || $dispatchType === 'order')
                    @else
                    <h4 class="font-medium text-zinc-700 dark:text-zinc-300 mb-3">Where should this go?</h4>
                    @endif

                    @if($dispatchType !== 'sales' && $dispatchType !== 'order')
                        <div class="grid grid-cols-2 gap-3 mb-4">
                            <button wire:click="$set('dispatchType', 'sales')"
                                    @disabled(!$selectedRecipe?->product_id)
                                    class="flex flex-col items-center gap-2 p-4 rounded-lg border-2 border-zinc-200 dark:border-zinc-600 hover:border-blue-400 hover:bg-blue-50 dark:hover:bg-blue-900/20 transition {{ !$selectedRecipe?->product_id ? 'opacity-40 cursor-not-allowed' : 'cursor-pointer' }}">
                                <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                                </svg>
                                <span class="text-sm font-semibold text-zinc-800 dark:text-zinc-100">Sales Department</span>
                                <span class="text-xs text-zinc-500 text-center">Goes into sales stock</span>
                            </button>
                            <button wire:click="$set('dispatchType', 'order')"
                                    @disabled(!$selectedRecipe?->product_id)
                                    class="flex flex-col items-center gap-2 p-4 rounded-lg border-2 border-zinc-200 dark:border-zinc-600 hover:border-emerald-400 hover:bg-emerald-50 dark:hover:bg-emerald-900/20 transition {{ !$selectedRecipe?->product_id ? 'opacity-40 cursor-not-allowed' : 'cursor-pointer' }}">
                                <svg class="w-6 h-6 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
                                </svg>
                                <span class="text-sm font-semibold text-zinc-800 dark:text-zinc-100">Customer Order</span>
                                <span class="text-xs text-zinc-500 text-center">Fulfils a pending order</span>
                            </button>
                        </div>

                        @if(!$isRedispatch)
                            <button wire:click="keepInFinishedGoods"
                                    class="w-full flex items-center justify-center gap-2 p-3 rounded-lg border-2 border-dashed border-zinc-200 dark:border-zinc-600 hover:border-zinc-400 hover:bg-zinc-50 dark:hover:bg-zinc-700/40 transition mb-1">
                                <svg class="w-5 h-5 text-zinc-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"/></svg>
                                <span class="text-sm font-medium text-zinc-700 dark:text-zinc-200">Don't send now — keep in Finished Goods</span>
                            </button>
                            <p class="text-xs text-zinc-400 text-center">It stays as held stock; send it later from the Finished Goods sheet.</p>
                        @endif
                    @endif

                    {{-- Sales flow --}}
                    @if($dispatchType === 'sales')
                        <div class="mb-4">
                            <div class="flex items-center justify-between mb-2">
                                <h4 class="font-medium text-zinc-700 dark:text-zinc-300">Sales Department</h4>
                                <button wire:click="$set('dispatchType', '')" class="text-xs text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-300">← Back</button>
                            </div>
                            <select wire:model="selectedSalesDepartmentId" class="w-full rounded border p-2 dark:bg-zinc-700 dark:border-zinc-600">
                                <option value="">-- Select Department --</option>
                                @foreach($this->getSalesDepartments() as $dept)
                                    <option value="{{ $dept->id }}">{{ $dept->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        @php $maxSales = $this->salesReceivesFor((float) $yieldOutput); @endphp
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Quantity to send ({{ $maxSales['symbol'] }})</label>
                            <input type="number" step="0.01" min="0" max="{{ $maxSales['qty'] }}" wire:model.live="dispatchSalesQuantity"
                                   class="w-full rounded border p-2 dark:bg-zinc-700 dark:border-zinc-600" />
                            <p class="text-xs text-zinc-400 mt-1">
                                Max {{ rtrim(rtrim(number_format($maxSales['qty'],2),'0'),'.') }} {{ $maxSales['symbol'] }}. Any remainder stays in finished goods.
                            </p>
                            @if($maxSales['converted'])
                                <p class="text-xs mt-1 text-indigo-700 dark:text-indigo-300">
                                    = <span class="font-semibold">{{ rtrim(rtrim(number_format((float) ($dispatchQuantity ?: 0), 2), '0'), '.') }} {{ $selectedRecipe->uomSymbol }}</span> drawn from the production store
                                </p>
                            @endif
                        </div>
                        <div class="flex justify-end">
                            <button wire:click="dispatchToSales" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 font-medium">
                                Confirm Dispatch to Sales
                            </button>
                        </div>
                    @endif

                    {{-- Order flow --}}
                    @if($dispatchType === 'order')
                        <div class="mb-4">
                            <div class="flex items-center justify-between mb-2">
                                <h4 class="font-medium text-zinc-700 dark:text-zinc-300">Customer Order</h4>
                                <button wire:click="$set('dispatchType', '')" class="text-xs text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-300">← Back</button>
                            </div>
                            @if(empty($pendingOrders))
                                <div class="p-3 bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-lg text-sm text-yellow-700 dark:text-yellow-300">
                                    No approved customer orders found for this product.
                                </div>
                            @else
                                <select wire:model.live="selectedOrderId" class="w-full rounded border p-2 dark:bg-zinc-700 dark:border-zinc-600">
                                    <option value="">-- Select Order --</option>
                                    @foreach($pendingOrders as $order)
                                        <option value="{{ $order['id'] }}">
                                            {{ $order['order_number'] }} — {{ $order['customer_name'] }}
                                            @if($order['item_qty']) (needs {{ $order['item_qty'] }}) @endif
                                            [{{ $order['status'] }}]
                                        </option>
                                    @endforeach
                                </select>
                            @endif
                        </div>
                        @if(!empty($pendingOrders))
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Quantity to send</label>
                            <input type="number" step="0.01" min="0" max="{{ $yieldOutput }}" wire:model="dispatchQuantity"
                                   class="w-full rounded border p-2 dark:bg-zinc-700 dark:border-zinc-600" />
                            <p class="text-xs text-zinc-400 mt-1">
                                Max {{ rtrim(rtrim(number_format($yieldOutput,2),'0'),'.') }} {{ $selectedRecipe->uomSymbol ?? '' }}. Any remainder stays in finished goods.
                            </p>
                        </div>
                        <div class="flex justify-end">
                            <button wire:click="dispatchToOrder"
                                    @disabled(!$selectedOrderId)
                                    class="px-4 py-2 bg-emerald-600 text-white rounded-lg hover:bg-emerald-700 font-medium disabled:opacity-40 disabled:cursor-not-allowed">
                                Confirm Dispatch to Order
                            </button>
                        </div>
                        @endif
                    @endif
                </div>
            </div>
        </div>
    @endif
</div>
