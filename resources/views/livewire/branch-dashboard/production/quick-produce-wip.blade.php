<div class="p-3 space-y-4">

    <x-breadcrumb
        title="Produce WIP"
        :items="[
            ['label' => 'Dashboard', 'url' => branch_route('branch-dashboard.index')],
            ['label' => 'Production'],
            ['label' => 'Produce WIP']
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

            <!-- Product Selection -->
            <div class="bg-white dark:bg-zinc-800 rounded-lg shadow border p-4">
                <h3 class="text-lg font-semibold text-zinc-800 dark:text-zinc-100 mb-4">Select WIP Product to Produce</h3>

                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Work in Progress (WIP)</label>
                        <select wire:change="selectProduct($event.target.value)" class="w-full rounded border p-2 dark:bg-zinc-700 dark:border-zinc-600">
                            <option value="">-- Select WIP Product --</option>
                            @foreach($this->getProducts() as $product)
                                <option value="{{ $product->id }}">{{ $product->name }} ({{ $product->sku }})</option>
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
                                    <label class="block text-xs font-medium text-green-700 dark:text-green-400">Approved Quantity</label>
                                    <input type="number" wire:model.live="approvedQuantity" step="0.01" min="0"
                                           class="w-full rounded border p-2 bg-green-50 dark:bg-green-900/10 dark:border-green-800"/>
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-red-700 dark:text-red-400">Rejected Quantity</label>
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
                    </div>
                @endif
            </div>

            <!-- Ingredients Preview -->
            @if($selectedRecipe)
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
            @endif
        </div>

        <!-- Produce Button -->
        @if($selectedRecipe)
            <div class="flex justify-center">
                <button wire:click="produce" @if($hasInsufficientStock) disabled @endif
                        class="px-8 py-3 {{ $hasInsufficientStock ? 'bg-zinc-400 cursor-not-allowed' : 'bg-green-600 hover:bg-green-700' }} text-white rounded-lg font-semibold flex items-center gap-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                    </svg>
                    PRODUCE WIP
                </button>
            </div>
        @endif
    @endif

    <!-- Dispatch Modal -->
    @if($showDispatchModal)
        <div class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
            <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-xl w-full max-w-md">
                <div class="p-6">
                    <h3 class="text-lg font-semibold text-zinc-800 dark:text-zinc-100 mb-4">WIP Production Complete!</h3>
                    <p class="text-zinc-600 dark:text-zinc-300 mb-4">
                        Produced {{ number_format($yieldOutput, 2) }} units of {{ $selectedRecipe?->product_name }}
                    </p>

                    <div class="mb-4 p-3 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg">
                        <p class="text-blue-700 dark:text-blue-300 text-sm">
                            <strong>WIP Item:</strong> This item is Work in Progress and cannot be dispatched to sales. It will be available for use in other recipes.
                        </p>
                    </div>

                    @if(!$selectedRecipe?->product_id)
                        <div class="mb-4 p-3 bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-lg">
                            <p class="text-yellow-700 dark:text-yellow-300 text-sm">
                                <strong>Assignment Required:</strong> This recipe is not linked to a product. Please use Product Assignments to link it first.
                            </p>
                        </div>
                    @endif

                    <div class="flex justify-end gap-2">
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
