<div class="p-3 space-y-3">

    <x-breadcrumb
        title="Recipe Details"
        :items="[
            ['label' => 'Dashboard', 'url' => branch_route('branch-dashboard.index')],
            ['label' => 'Production'],
            ['label' => 'Recipes', 'url' => branch_route('branch-dashboard.production.recipes.index', ['deptSlug' => $dept_slug])],
            ['label' => 'Details']
        ]"
        :compact="false"
        :with-icons="true"/>

    <!-- Recipe Header -->
    <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-sm border border-zinc-200 dark:border-zinc-700 p-6">
        <div class="flex justify-between items-start">
            <div>
                <h1 class="text-3xl font-bold text-zinc-900 dark:text-zinc-100">{{ $recipe->product_name }}</h1>
                <p class="text-sm text-zinc-600 dark:text-zinc-400 mt-1">SKU: <span class="font-mono font-semibold">{{ $recipe->sku }}</span></p>
            </div>
            <div class="text-right">
                <span class="px-3 py-1 text-sm font-semibold rounded-full
                    @if($recipe->status === 'active') bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200
                    @elseif($recipe->status === 'inactive') bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200
                    @else bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-200
                    @endif">
                    {{ ucfirst($recipe->status) }}
                </span>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mt-6">
            <div class="bg-blue-50 dark:bg-blue-900/20 p-4 rounded-lg">
                <p class="text-sm text-blue-600 dark:text-blue-400 font-medium">Product Type</p>
                <p class="text-lg font-bold text-blue-900 dark:text-blue-100">{{ $recipe->productType->name ?? 'N/A' }}</p>
            </div>
            <div class="bg-green-50 dark:bg-green-900/20 p-4 rounded-lg">
                <p class="text-sm text-green-600 dark:text-green-400 font-medium">Base Yield</p>
                <p class="text-lg font-bold text-green-900 dark:text-green-100">{{ number_format($recipe->yield_quantity, 2) }} {{ $recipe->unitOfMeasure?->symbol ?? 'N/A' }}</p>
            </div>
            <div class="bg-purple-50 dark:bg-purple-900/20 p-4 rounded-lg">
                <p class="text-sm text-purple-600 dark:text-purple-400 font-medium">Cost per Unit</p>
                <p class="text-lg font-bold text-purple-900 dark:text-purple-100">
                    {{ \App\Helpers\LocalizationHelper::formatCurrency($this->costPerUnit ?? 0) }}
                </p>
            </div>
            <div class="bg-orange-50 dark:bg-orange-900/20 p-4 rounded-lg">
                <p class="text-sm text-orange-600 dark:text-orange-400 font-medium">Prep Time</p>
                <p class="text-lg font-bold text-orange-900 dark:text-orange-100">{{ $recipe->preparation_time ?? 'N/A' }} {{ $recipe->preparation_time ? 'min' : '' }}</p>
            </div>
        </div>
    </div>

    <!-- Production Calculator -->
    <div class="bg-gradient-to-r from-blue-50 to-indigo-50 dark:from-blue-900/20 dark:to-indigo-900/20 rounded-lg shadow-sm border-2 border-blue-200 dark:border-blue-700 p-6">
        <h2 class="text-xl font-bold text-zinc-900 dark:text-zinc-100 mb-4 flex items-center">
            <svg class="w-6 h-6 mr-2 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
            </svg>
            Production Calculator
        </h2>

        <div class="space-y-4">
            <!-- Input section -->
            <div class="grid grid-cols-1 gap-4">
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2">Batch Size</label>
                    <div class="flex gap-2">
                        <input type="number" wire:model="batchSize" min="1" step="1"
                            class="flex-1 px-4 py-3 text-lg font-bold border-2 border-blue-300 dark:border-blue-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-800 dark:text-zinc-200 focus:ring-2 focus:ring-blue-500"
                            placeholder="Enter batch size">
                        <button wire:click="calculateBatch"
                            class="px-6 py-3 bg-blue-600 hover:bg-blue-700 text-white rounded-lg font-medium transition-colors whitespace-nowrap">
                            Calculate
                        </button>
                    </div>
                    <p class="text-xs text-zinc-500 dark:text-zinc-400 mt-1">How many batches to produce</p>
                </div>
            </div>

            <!-- Results section -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="bg-white dark:bg-zinc-800 p-4 rounded-lg border border-zinc-200 dark:border-zinc-700">
                    <p class="text-sm text-zinc-600 dark:text-zinc-400 font-medium">Total Yield</p>
                    <p class="text-2xl font-bold text-zinc-900 dark:text-zinc-100">{{ number_format($this->totalYield, 2) }} {{ $recipe->unitOfMeasure?->symbol ?? 'N/A' }}</p>
                </div>

                <div class="bg-white dark:bg-zinc-800 p-4 rounded-lg border border-zinc-200 dark:border-zinc-700">
                    <p class="text-sm text-zinc-600 dark:text-zinc-400 font-medium">Total Cost</p>
                    <p class="text-2xl font-bold text-green-600 dark:text-green-400">
                        {{ \App\Helpers\LocalizationHelper::formatCurrency($this->totalCost ?? 0) }}
                    </p>
                </div>

                <div class="bg-white dark:bg-zinc-800 p-4 rounded-lg border border-zinc-200 dark:border-zinc-700">
                    <p class="text-sm text-zinc-600 dark:text-zinc-400 font-medium">Cost per {{ $recipe->unitOfMeasure?->symbol ?? 'N/A' }}</p>
                    <p class="text-2xl font-bold text-blue-600 dark:text-blue-400">
                        {{ \App\Helpers\LocalizationHelper::formatCurrency(($this->totalYield ?? 0) > 0 ? ($this->totalCost / $this->totalYield) : 0) }}
                    </p>
                </div>
            </div>
        </div>
    </div>

    <!-- Ingredients List -->
    <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-sm border border-zinc-200 dark:border-zinc-700">
        <div class="px-6 py-4 border-b border-zinc-200 dark:border-zinc-700">
            <h2 class="text-xl font-bold text-zinc-900 dark:text-zinc-100 flex items-center">
                <svg class="w-6 h-6 mr-2 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01" />
                </svg>
                Ingredients (for {{ $batchSize }} {{ $batchSize == 1 ? 'batch' : 'batches' }})
            </h2>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-zinc-50 dark:bg-zinc-900/50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">#</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">Ingredient</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">Base Qty</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">Waste %</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">Actual Qty</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">Unit</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">Cost/Unit</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">Total Cost</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">Notes</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                    @foreach($this->calculatedIngredients as $index => $ingredient)
                    <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-700/50 transition-colors">
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-zinc-500 dark:text-zinc-400">{{ $index + 1 }}</td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="text-sm font-semibold text-zinc-900 dark:text-zinc-100">{{ $ingredient['item_name'] }}</span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="text-sm text-zinc-700 dark:text-zinc-300">{{ number_format($ingredient['base_quantity'], 2) }}</span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            @if($ingredient['waste_percentage'] > 0)
                                <span class="px-2 py-1 text-xs font-medium rounded-full bg-orange-100 text-orange-800 dark:bg-orange-900 dark:text-orange-200">
                                    {{ number_format($ingredient['waste_percentage'], 2) }}%
                                </span>
                            @else
                                <span class="text-xs text-zinc-400">-</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="text-sm font-bold text-blue-600 dark:text-blue-400">{{ number_format($ingredient['quantity'], 2) }}</span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="text-sm text-zinc-600 dark:text-zinc-400">{{ $ingredient['uom_symbol'] }}</span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="text-sm text-zinc-700 dark:text-zinc-300">
                                {{ \App\Helpers\LocalizationHelper::formatCurrency($ingredient['cost_per_unit'] ?? 0) }}
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="text-sm font-bold text-green-600 dark:text-green-400">
                                {{ \App\Helpers\LocalizationHelper::formatCurrency($ingredient['total_cost'] ?? 0) }}
                            </span>
                        </td>
                        <td class="px-6 py-4">
                            @if($ingredient['preparation_notes'] || $ingredient['notes'])
                                <div class="text-xs text-zinc-600 dark:text-zinc-400 max-w-xs">
                                    @if($ingredient['preparation_notes'])
                                        <p class="font-medium">{{ $ingredient['preparation_notes'] }}</p>
                                    @endif
                                    @if($ingredient['notes'])
                                        <p class="text-zinc-500">{{ $ingredient['notes'] }}</p>
                                    @endif
                                </div>
                            @else
                                <span class="text-xs text-zinc-400">-</span>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
                <tfoot class="bg-zinc-100 dark:bg-zinc-900">
                    <tr>
                        <td colspan="7" class="px-6 py-4 text-right text-sm font-bold text-zinc-900 dark:text-zinc-100">
                            TOTAL COST:
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="text-lg font-bold text-green-600 dark:text-green-400">
                                {{ \App\Helpers\LocalizationHelper::formatCurrency($this->totalCost ?? 0) }}
                            </span>
                        </td>
                        <td></td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>

    <!-- Instructions -->
    @if(count($this->instructions) > 0)
    <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-sm border border-zinc-200 dark:border-zinc-700 p-6">
        <h2 class="text-xl font-bold text-zinc-900 dark:text-zinc-100 mb-4 flex items-center">
            <svg class="w-6 h-6 mr-2 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
            </svg>
            Preparation Instructions
        </h2>

        <ol class="space-y-4">
            @foreach($this->instructions as $index => $instruction)
            <li class="flex">
                <span class="flex-shrink-0 w-8 h-8 flex items-center justify-center bg-purple-100 dark:bg-purple-900 text-purple-800 dark:text-purple-200 rounded-full font-bold text-sm mr-4">
                    {{ $index + 1 }}
                </span>
                <p class="text-zinc-700 dark:text-zinc-300 pt-1">{{ $instruction }}</p>
            </li>
            @endforeach
        </ol>
    </div>
    @endif

    <!-- Additional Info -->
    <div class="bg-zinc-50 dark:bg-zinc-900/50 rounded-lg p-4 text-sm text-zinc-600 dark:text-zinc-400">
        <p><span class="font-semibold">Department:</span> {{ $recipe->department->name ?? 'N/A' }}</p>
        <p><span class="font-semibold">Created by:</span> {{ $recipe->createdBy->name ?? 'N/A' }}</p>
        <p><span class="font-semibold">Created at:</span> {{ $recipe->created_at->format('M d, Y h:i A') }}</p>
        <p><span class="font-semibold">Last updated:</span> {{ $recipe->updated_at->format('M d, Y h:i A') }}</p>
    </div>

</div>
