<div class="bg-white dark:bg-zinc-800 rounded-lg shadow-sm p-6 border border-zinc-200 dark:border-zinc-700">
    <div class="flex justify-between items-center mb-6">
        <h2 class="text-2xl font-semibold text-zinc-900 dark:text-white">Request Products from Kitchen</h2>
        <button wire:click="$toggle('showModal')" class="text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-300">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
            </svg>
        </button>
    </div>

    @if ($showSuccessMessage)
        <div class="mb-4 p-4 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-lg text-green-800 dark:text-green-200">
            <p class="font-semibold">Production request(s) created successfully!</p>
        </div>
    @endif

    @if ($errors->any())
        <div class="mb-4 p-4 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg">
            <ul class="text-red-800 dark:text-red-200 text-sm space-y-1">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form wire:submit="createRequest" class="space-y-6">
        <!-- Production Department Selection -->
        <div>
            <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2">
                Select Kitchen <span class="text-red-500">*</span>
            </label>
            <select
                wire:model.live="productionDepartmentId"
                class="w-full px-4 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white focus:ring-2 focus:ring-blue-500"
            >
                <option value="">Choose a Kitchen...</option>
                @foreach ($productionDepartments as $dept)
                    <option value="{{ $dept['id'] }}">{{ $dept['name'] }}</option>
                @endforeach
            </select>
            @error('productionDepartmentId')
                <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
            @enderror
        </div>

        <!-- Two-Column Layout: Selected & Available -->
        @if ($productionDepartmentId)
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- Selected Products Section -->
                <div class="bg-zinc-50 dark:bg-zinc-700/30 rounded-lg p-4 border border-zinc-200 dark:border-zinc-600">
                    <div class="flex items-center gap-2 mb-4">
                        <h3 class="font-semibold text-zinc-900 dark:text-white">Selected Products</h3>
                        <span class="inline-flex items-center justify-center w-6 h-6 text-xs font-bold text-white bg-blue-600 rounded-full">
                            {{ count($selectedProducts) }}
                        </span>
                    </div>

                    @if (count($selectedProductsDetails) > 0)
                        <div class="space-y-3 max-h-96 overflow-y-auto">
                            @foreach ($selectedProductsDetails as $index => $product)
                                <div class="bg-white dark:bg-zinc-800 rounded-lg p-3 border border-zinc-200 dark:border-zinc-600">
                                    <div class="flex justify-between items-start gap-3 mb-2">
                                        <div class="flex-1">
                                            <p class="font-semibold text-zinc-900 dark:text-white text-sm">{{ $product['name'] }}</p>
                                            @if(isset($product['sku']) && $product['sku'])
                                                <p class="text-xs text-zinc-500 dark:text-zinc-400">SKU: {{ $product['sku'] }}</p>
                                            @endif
                                            @if(isset($product['has_recipe']) && $product['has_recipe'])
                                                <span class="inline-flex items-center text-xs text-green-600 dark:text-green-400">
                                                    <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                                    </svg>
                                                    Recipe Available
                                                </span>
                                            @endif
                                        </div>
                                        <button
                                            type="button"
                                            wire:click="removeProduct({{ $index }})"
                                            class="text-red-500 hover:text-red-700 dark:hover:text-red-400"
                                        >
                                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z"/>
                                            </svg>
                                        </button>
                                    </div>

                                    <div class="flex items-center gap-2 text-sm">
                                        <span class="text-zinc-600 dark:text-zinc-400">Batches:</span>
                                        <input
                                            type="number"
                                            wire:change="updateBatches({{ $index }}, $event.target.value)"
                                            value="{{ $product['batches'] }}"
                                            min="1"
                                            class="w-16 px-2 py-1 border border-zinc-300 dark:border-zinc-600 rounded bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white"
                                        />
                                        @if(isset($product['yield_per_batch']))
                                            <span class="text-zinc-500 dark:text-zinc-400 ml-auto text-xs">
                                                = {{ $product['batches'] * $product['yield_per_batch'] }} {{ $product['uom'] ?? 'units' }}
                                            </span>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="text-center py-8 text-zinc-500 dark:text-zinc-400">
                            <p class="text-sm">No products selected yet</p>
                            <p class="text-xs mt-1">Add products from the available list</p>
                        </div>
                    @endif
                </div>

                <!-- Available Products Section -->
                <div class="bg-zinc-50 dark:bg-zinc-700/30 rounded-lg p-4 border border-zinc-200 dark:border-zinc-600">
                    <h3 class="font-semibold text-zinc-900 dark:text-white mb-4">Available Recipes</h3>

                    @if (count($availableProducts) > 0)
                        <div class="space-y-2 max-h-96 overflow-y-auto">
                            @foreach ($availableProducts as $product)
                                @php
                                    $isSelected = collect($selectedProducts)->contains('product_id', $product['id']);
                                @endphp
                                <div class="bg-white dark:bg-zinc-800 rounded-lg p-3 border border-zinc-200 dark:border-zinc-600 hover:border-blue-300 dark:hover:border-blue-500 transition">
                                    <div class="flex items-start justify-between gap-3">
                                        <div class="flex-1">
                                            <p class="font-medium text-zinc-900 dark:text-white text-sm">{{ $product['name'] }}</p>
                                            @if(isset($product['sku']) && $product['sku'])
                                                <p class="text-xs text-zinc-500 dark:text-zinc-400">SKU: {{ $product['sku'] }}</p>
                                            @endif
                                            <div class="flex items-center gap-2 mt-1">
                                                <span class="text-xs text-zinc-600 dark:text-zinc-400">
                                                    Yield: {{ $product['yield_quantity'] ?? 1 }} {{ $product['uom'] ?? 'units' }}/batch
                                                </span>
                                                @if($product['has_recipe'] ?? false)
                                                    <span class="text-xs px-2 py-0.5 bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300 rounded">
                                                        Recipe Ready
                                                    </span>
                                                @else
                                                    <span class="text-xs px-2 py-0.5 bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-300 rounded">
                                                        No Recipe
                                                    </span>
                                                @endif
                                            </div>
                                        </div>

                                        @if ($isSelected)
                                            <span class="text-xs font-semibold text-green-600 dark:text-green-400 px-2 py-1 bg-green-50 dark:bg-green-900/20 rounded">
                                                Added
                                            </span>
                                        @else
                                            <button
                                                type="button"
                                                wire:click="addProduct('{{ $product['id'] }}', '{{ $product['recipe_id'] ?? '' }}')"
                                                class="px-3 py-1.5 text-sm font-medium text-blue-600 dark:text-blue-400 hover:bg-blue-50 dark:hover:bg-blue-900/20 rounded transition"
                                            >
                                                + Add
                                            </button>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="text-center py-8 text-zinc-500 dark:text-zinc-400">
                            <p class="text-sm">No recipes available for this production department</p>
                            <p class="text-xs mt-1">Please ensure recipes are set up for this department</p>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Priority & Notes -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 border-t border-zinc-200 dark:border-zinc-700 pt-6">
                <div>
                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2">
                        Priority
                    </label>
                    <select
                        wire:model="priority"
                        class="w-full px-4 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white"
                    >
                        <option value="normal">Normal Priority</option>
                        <option value="urgent">Urgent Priority</option>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2">
                        Special Instructions
                    </label>
                    <input
                        type="text"
                        wire:model="notes"
                        placeholder="Add notes..."
                        class="w-full px-4 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white placeholder-zinc-400"
                    />
                </div>
            </div>
        @endif

        <!-- Action Buttons -->
        <div class="flex justify-end gap-3 pt-4 border-t border-zinc-200 dark:border-zinc-700">
            <button
                type="button"
                wire:click="resetForm"
                class="px-6 py-2 border border-zinc-300 dark:border-zinc-600 text-zinc-700 dark:text-zinc-300 rounded-lg hover:bg-zinc-50 dark:hover:bg-zinc-700 transition font-medium"
            >
                Clear
            </button>
            <button
                type="submit"
                @disabled(count($selectedProducts) === 0)
                class="px-6 py-2 bg-blue-600 hover:bg-blue-700 disabled:bg-zinc-400 text-white rounded-lg font-medium transition cursor-pointer"
            >
                Request from Kitchen
            </button>
        </div>
    </form>
</div>
