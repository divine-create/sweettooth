<div class="p-3 space-y-4">

    <x-breadcrumb
        title="WIP Recipe"
        :items="[
            ['label' => 'Dashboard', 'url' => branch_route('branch-dashboard.index')],
            ['label' => 'WIP Products']
        ]"
        :compact="false"
        :with-icons="true"/>

    @if(!$product)
        <div class="bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-lg p-4 text-center">
            <p class="text-yellow-800 dark:text-yellow-200">Product not found.</p>
        </div>
        <a href="{{ route('branch-dashboard.production.wip-products', ['deptSlug' => $deptSlug ?? 'hot-kitchen', 'b_id' => request()->get('b_id')]) }}" class="text-blue-600 hover:underline">
            Back to WIP Products
        </a>
        @return;
    @endif

    <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-sm border border-zinc-200 dark:border-zinc-700">
        <div class="border-b border-zinc-200 dark:border-zinc-700 p-4">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-lg font-semibold text-zinc-900 dark:text-zinc-100">{{ $product->name }}</h2>
                    <p class="text-sm text-zinc-500 dark:text-zinc-400">SKU: {{ $product->sku }}</p>
                </div>
                <div class="flex items-center gap-4">
                    @if($isEditing)
                        <span class="px-3 py-1 rounded-full text-sm font-medium bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">
                            Recipe Exists
                        </span>
                    @else
                        <span class="px-3 py-1 rounded-full text-sm font-medium bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200">
                            No Recipe Yet
                        </span>
                    @endif
                </div>
            </div>
        </div>

        <form wire:submit.prevent="saveRecipe" class="p-4 space-y-4">
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Yield Quantity</label>
                    <input type="number" step="0.01" min="0.01" wire:model="yieldQuantity" class="w-full rounded border border-zinc-300 dark:border-zinc-600 dark:bg-zinc-700 px-3 py-2" required>
                </div>
                <div>
                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Status</label>
                    <select wire:model="recipeStatus" class="w-full rounded border border-zinc-300 dark:border-zinc-600 dark:bg-zinc-700 px-3 py-2">
                        <option value="active">Active</option>
                        <option value="testing">Testing</option>
                        <option value="inactive">Inactive</option>
                    </select>
                </div>
            </div>

            <div class="border-t border-zinc-200 dark:border-zinc-700 pt-4">
                <div class="flex items-center justify-between mb-3">
                    <h3 class="text-md font-semibold text-zinc-900 dark:text-zinc-100">Ingredients</h3>
                    <div class="flex gap-2">
                        <button type="button" wire:click="addRawIngredient" class="px-3 py-1.5 text-sm bg-emerald-600 hover:bg-emerald-700 text-white rounded flex items-center gap-1">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                            </svg>
                            + Raw Material
                        </button>
                        <button type="button" wire:click="addWipIngredient" class="px-3 py-1.5 text-sm bg-blue-600 hover:bg-blue-700 text-white rounded flex items-center gap-1">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                            </svg>
                            + WIP Item
                        </button>
                    </div>
                </div>

                @if(count($ingredients) === 0)
                    <div class="text-center py-8 text-zinc-500 dark:text-zinc-400 bg-zinc-50 dark:bg-zinc-700/50 rounded-lg">
                        <svg class="w-12 h-12 mx-auto text-zinc-400 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v7M5 11a2 2 0 012-2h14a2 2 0 012 2"/>
                        </svg>
                        <p>No ingredients added. Add at least one ingredient.</p>
                    </div>
                @else
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead class="bg-zinc-50 dark:bg-zinc-700/50">
                                <tr>
                                    <th class="px-3 py-2 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase">Item</th>
                                    <th class="px-3 py-2 text-right text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase w-24">Qty</th>
                                    <th class="px-3 py-2 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase w-20">UOM</th>
                                    <th class="px-3 py-2 text-right text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase w-24">Cost/Unit</th>
                                    <th class="px-3 py-2 text-right text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase w-20">Waste %</th>
                                    <th class="px-3 py-2 text-center text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase w-16">Type</th>
                                    <th class="px-3 py-2 w-10"></th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                                @foreach($ingredients as $index => $ingredient)
                                    <tr>
                                        <td class="px-3 py-2">
                                            @if($ingredient['is_wip'])
                                                <select wire:change="onIngredientItemChanged({{ $index }}, $event.target.value)" wire:model="ingredients.{{ $index }}.item_id" class="w-full rounded border border-zinc-300 dark:border-zinc-600 dark:bg-zinc-700 px-2 py-1.5 text-sm">
                                                    <option value="">Select WIP Item...</option>
                                                    @foreach($wipProducts->whereNotIn('id', array_merge($wipProductIds, [$productId])) as $wip)
                                                        <option value="{{ $wip->id }}">{{ $wip->name }} ({{ $wip->sku }})</option>
                                                    @endforeach
                                                </select>
                                            @else
                                                <select wire:change="onIngredientItemChanged({{ $index }}, $event.target.value)" wire:model="ingredients.{{ $index }}.item_id" class="w-full rounded border border-zinc-300 dark:border-zinc-600 dark:bg-zinc-700 px-2 py-1.5 text-sm">
                                                    <option value="">Select Raw Material...</option>
                                                    @foreach($rawItems as $item)
                                                        <option value="{{ $item->id }}">{{ $item->name }}</option>
                                                    @endforeach
                                                </select>
                                            @endif
                                        </td>
                                        <td class="px-3 py-2">
                                            <input type="number" step="0.01" min="0" wire:model="ingredients.{{ $index }}.quantity" placeholder="Qty" class="w-full rounded border border-zinc-300 dark:border-zinc-600 dark:bg-zinc-700 px-2 py-1.5 text-sm text-right">
                                        </td>
                                        <td class="px-3 py-2 text-sm text-zinc-500 dark:text-zinc-400">
                                            {{ $ingredient['uom'] ?: '-' }}
                                        </td>
                                        <td class="px-3 py-2">
                                            <input type="number" step="0.01" min="0" wire:model="ingredients.{{ $index }}.cost_per_unit" placeholder="Cost" class="w-full rounded border border-zinc-300 dark:border-zinc-600 dark:bg-zinc-700 px-2 py-1.5 text-sm text-right">
                                        </td>
                                        <td class="px-3 py-2">
                                            <div class="flex items-center gap-1">
                                                <input type="number" step="1" min="0" max="100" wire:model="ingredients.{{ $index }}.waste_percentage" placeholder="%" class="flex-1 rounded border border-zinc-300 dark:border-zinc-600 dark:bg-zinc-700 px-2 py-1.5 text-sm text-right">
                                                <span class="text-zinc-500 dark:text-zinc-400 text-sm">%</span>
                                            </div>
                                        </td>
                                        <td class="px-3 py-2 text-center">
                                            @if($ingredient['is_wip'])
                                                <span class="px-2 py-1 text-xs rounded-full bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200">WIP</span>
                                            @else
                                                <span class="px-2 py-1 text-xs rounded-full bg-emerald-100 text-emerald-800 dark:bg-emerald-900 dark:text-emerald-200">Raw</span>
                                            @endif
                                        </td>
                                        <td class="px-3 py-2">
                                            <button type="button" wire:click="removeIngredient({{ $index }})" class="p-1 text-red-500 hover:text-red-700" title="Remove">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                                </svg>
                                            </button>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>

            <div class="flex justify-between pt-4 border-t border-zinc-200 dark:border-zinc-700">
                <a href="{{ route('branch-dashboard.production.wip-products', ['deptSlug' => $deptSlug ?? 'hot-kitchen', 'b_id' => request()->get('b_id')]) }}" class="px-4 py-2 border border-zinc-300 dark:border-zinc-600 text-zinc-700 dark:text-zinc-300 rounded hover:bg-zinc-50 dark:hover:bg-zinc-700">
                    Cancel
                </a>
                <button type="submit" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded font-medium">
                    {{ $isEditing ? 'Update Recipe' : 'Save Recipe' }}
                </button>
            </div>
        </form>
    </div>
</div>