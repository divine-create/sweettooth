<div class="p-3 space-y-3">

    <x-breadcrumb
        title="New Production Request"
        :items="[
            ['label' => 'Dashboard', 'url' => branch_route('branch-dashboard.index')],
            ['label' => 'Production'],
            ['label' => 'Requests', 'url' => branch_route('branch-dashboard.production.request.index', ['deptSlug' => $dept_slug])],
            ['label' => 'Create']
        ]"
        :compact="false"
        :with-icons="true"/>

    <!-- Form Container -->
    <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-sm border border-zinc-200 dark:border-zinc-700 p-6">
        <form wire:submit.prevent="save" class="space-y-6">

            <!-- Shift Information -->
            @if(auth()->user()->is_super_admin)
            <div class="bg-purple-50 dark:bg-purple-900/20 border border-purple-200 dark:border-purple-800 rounded-lg p-4">
                <div class="flex items-center gap-3">
                    <svg class="w-6 h-6 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                    </svg>
                    <div>
                        <h3 class="font-semibold text-purple-900 dark:text-purple-100">Super Admin Mode</h3>
                        <p class="text-sm text-purple-700 dark:text-purple-300">
                            ✓ You can create production requests without a shift assignment
                        </p>
                    </div>
                </div>
            </div>
            @else
            <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-4">
                <div class="flex items-center gap-3">
                    <svg class="w-6 h-6 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <div>
                        <h3 class="font-semibold text-blue-900 dark:text-blue-100">Current Shift</h3>
                        <p class="text-sm text-blue-700 dark:text-blue-300">
                            {{ ucfirst($currentShift) }} Shift - {{ now()->format('l, F j, Y') }}
                        </p>
                    </div>
                </div>
            </div>
            @endif

            <!-- Product Selection -->
            <div class="space-y-4">
                <div class="flex items-center justify-between">
                    <h3 class="text-lg font-semibold text-zinc-900 dark:text-zinc-100">Select Products to Produce</h3>
                    <button type="button" wire:click="addProduct" wire:loading.attr="disabled" wire:target="addProduct"
                            class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg font-medium transition-colors flex items-center gap-2 disabled:opacity-50 disabled:cursor-not-allowed cursor-pointer"
                            :class="{ 'opacity-75 cursor-not-allowed': $wire.loading }">
                        <span wire:loading.remove wire:target="addProduct" class="flex items-center gap-2">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                            </svg>
                            Add Product
                        </span>
                        <span wire:loading wire:target="addProduct" class="flex items-center gap-2">
                            <svg class="w-5 h-5 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            Adding...
                        </span>
                    </button>
                </div>

                @if(count($selectedProducts) > 0)
                <div class="space-y-4">
                    @foreach($selectedProducts as $index => $product)
                    <div class="bg-zinc-50 dark:bg-zinc-900/50 rounded-lg border border-zinc-200 dark:border-zinc-700 p-4">
                        <div class="flex justify-between items-start mb-4">
                            <span class="font-semibold text-zinc-900 dark:text-zinc-100">Product #{{ $index + 1 }}</span>
                            <button type="button" wire:click="removeProduct({{ $index }})"
                                    class="text-red-600 hover:text-red-800 dark:text-red-400">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                </svg>
                            </button>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <!-- Product Selection -->
                            <div>
                                <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2">Product *</label>
                                <select wire:model.live="selectedProducts.{{ $index }}.product_id"
                                        class="w-full px-4 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-800 dark:text-zinc-200 focus:ring-2 focus:ring-blue-500">
                                    <option value="">Select Product</option>
                                    @foreach($products as $p)
                                        <option value="{{ $p->id }}">{{ $p->name }}</option>
                                    @endforeach
                                </select>
                                @error("selectedProducts.{$index}.product_id")
                                    <span class="text-red-500 text-xs mt-1">{{ $message }}</span>
                                @enderror
                            </div>

                            <!-- Quantity -->
                            <div>
                                <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2">Production Quantity (Units) *</label>
                                <input type="number" step="1" min="1" wire:model.live="selectedProducts.{{ $index }}.quantity"
                                       placeholder="Units requested"
                                       class="w-full px-4 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-800 dark:text-zinc-200 focus:ring-2 focus:ring-blue-500">
                                @error("selectedProducts.{$index}.quantity")
                                    <span class="text-red-500 text-xs mt-1">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>

                        <!-- Product Details (if selected) -->
                        @if(isset($product['product_details']) && $product['product_details'])
                        <div class="mt-4 p-4 bg-white dark:bg-zinc-800 rounded-lg border border-zinc-200 dark:border-zinc-700">
                            @php
                                $requestedUnits = (float) $product['quantity'];
                                $yieldPerBatch = (float) $product['product_details']['yield_quantity'];
                                $batchCount = $yieldPerBatch > 0 ? (int) ceil($requestedUnits / $yieldPerBatch) : 0;
                                $plannedUnits = $batchCount * $yieldPerBatch;
                            @endphp
                            <div class="mb-3 pb-3 border-b border-zinc-200 dark:border-zinc-700">
                                <h4 class="font-semibold text-sm text-zinc-900 dark:text-zinc-100">Product: {{ $product['product_details']['name'] }}</h4>
                                <p class="text-xs text-zinc-600 dark:text-zinc-400">
                                    Yield: {{ $product['product_details']['yield_quantity'] }} {{ $product['product_details']['uom'] }} per batch
                                </p>
                                <p class="text-xs text-zinc-600 dark:text-zinc-400">
                                    Requested: {{ number_format($requestedUnits, 2) }} {{ $product['product_details']['uom'] }} | Plan: {{ $batchCount }} batch{{ $batchCount === 1 ? '' : 'es' }} = {{ number_format($plannedUnits, 2) }} {{ $product['product_details']['uom'] }}
                                </p>
                            </div>

                            <!-- Items/Ingredients Table -->
                            <h5 class="font-semibold text-sm text-zinc-900 dark:text-zinc-100 mb-3">Required Items (Ingredients)</h5>

                            <div class="overflow-x-auto">
                                <table class="w-full text-sm">
                                    <thead class="bg-zinc-100 dark:bg-zinc-900 border-b border-zinc-200 dark:border-zinc-700">
                                        <tr>
                                            <th class="px-3 py-2 text-left text-xs font-medium text-zinc-600 dark:text-zinc-400">Item</th>
                                            <th class="px-3 py-2 text-right text-xs font-medium text-zinc-600 dark:text-zinc-400">Per Batch</th>
                                            <th class="px-3 py-2 text-right text-xs font-medium text-zinc-600 dark:text-zinc-400">Waste %</th>
                                            <th class="px-3 py-2 text-right text-xs font-medium text-zinc-600 dark:text-zinc-400">Total ({{ $batchCount }} batch{{ $batchCount === 1 ? '' : 'es' }})</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                                        @foreach($product['product_details']['ingredients'] as $ingredient)
                                        @php
                                            $baseQty = $ingredient['quantity_per_batch'];
                                            $wasteMultiplier = 1 + ($ingredient['waste_percentage'] / 100);
                                            $actualQtyPerBatch = $baseQty *(float) $wasteMultiplier;
                                            $totalQty = $actualQtyPerBatch *(float) $batchCount;
                                        @endphp
                                        <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-800/50">
                                            <td class="px-3 py-2 text-zinc-900 dark:text-zinc-100">
                                                {{ $ingredient['item_name'] }}
                                            </td>
                                            <td class="px-3 py-2 text-right text-zinc-600 dark:text-zinc-400">
                                                {{ number_format($baseQty, 2) }} {{ $ingredient['uom'] }}
                                            </td>
                                            <td class="px-3 py-2 text-right">
                                                @if($ingredient['waste_percentage'] > 0)
                                                    <span class="text-orange-600 dark:text-orange-400 text-xs">
                                                        +{{ $ingredient['waste_percentage'] }}%
                                                    </span>
                                                @else
                                                    <span class="text-zinc-400">-</span>
                                                @endif
                                            </td>
                                            <td class="px-3 py-2 text-right font-semibold text-zinc-900 dark:text-zinc-100">
                                                {{ number_format($totalQty, 2) }} {{ $ingredient['uom'] }}
                                            </td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>

                            <!-- Summary -->
                            <div class="mt-4 pt-3 border-t border-zinc-200 dark:border-zinc-700">
                                <div class="flex justify-between items-center text-sm">
                                    <span class="text-zinc-600 dark:text-zinc-400">Total Production:</span>
                                    <span class="font-semibold text-zinc-900 dark:text-zinc-100">
                                        {{ number_format($plannedUnits, 2) }}
                                        {{ $product['product_details']['uom'] }}
                                    </span>
                                </div>
                            </div>
                        </div>
                        @elseif(isset($product['product_id']) && $product['product_id'] && !isset($product['product_details']))
                        <div class="mt-4 p-4 bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-lg">
                            <p class="text-sm text-yellow-800 dark:text-yellow-200">
                                ⚠️ No recipe found for this product. Please create a recipe first.
                            </p>
                        </div>
                        @endif
                    </div>
                    @endforeach
                </div>
                @else
                <div class="text-center py-8 bg-zinc-50 dark:bg-zinc-900/50 rounded-lg border-2 border-dashed border-zinc-300 dark:border-zinc-600">
                    <p class="text-zinc-500 dark:text-zinc-400">No products added yet. Click "Add Product" to get started.</p>
                </div>
                @endif

                @error('selectedProducts')
                    <span class="text-red-500 text-sm">{{ $message }}</span>
                @enderror
            </div>

            <!-- Notes -->
            <div>
                <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2">Additional Notes (Optional)</label>
                <textarea wire:model="notes" rows="3"
                          placeholder="Any special instructions or notes..."
                          class="w-full px-4 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-800 dark:text-zinc-200 focus:ring-2 focus:ring-blue-500"></textarea>
            </div>

            <!-- Form Actions -->
            <div class="flex justify-end gap-3 pt-4 border-t border-zinc-200 dark:border-zinc-700">
                <a href="{{ branch_route('branch-dashboard.production.request.index', ['deptSlug' => $dept_slug]) }}"
                   class="px-6 py-2 border border-zinc-300 dark:border-zinc-600 text-zinc-700 dark:text-zinc-300 rounded-lg hover:bg-zinc-50 dark:hover:bg-zinc-700 transition-colors cursor-pointer">
                    Cancel
                </a>
                <button type="submit" wire:loading.attr="disabled" wire:target="save"
                        @disabled(count($selectedProducts) === 0)
                        class="px-6 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg font-medium transition-colors flex items-center gap-2 disabled:opacity-50 disabled:cursor-not-allowed cursor-pointer"
                        :class="{ 'opacity-75 cursor-not-allowed': $wire.loading }">
                    <span wire:loading.remove wire:target="save" class="flex items-center gap-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                        Submit Production Request
                    </span>
                    <span wire:loading wire:target="save" class="flex items-center gap-2">
                        <svg class="w-5 h-5 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        Submitting...
                    </span>
                </button>
            </div>
        </form>
    </div>

</div>
