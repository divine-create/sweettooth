<div class="p-3 space-y-3">

    <x-breadcrumb
        title="Add Recipe"
        :items="[
            ['label' => 'Dashboard', 'url' => branch_route('branch-dashboard.index')],
            ['label' => 'Production'],
            ['label' => 'Recipes', 'url' => branch_route('branch-dashboard.production.recipes.index', ['deptSlug'=>$dept_slug])],
            ['label' => 'Add']
        ]"
        :compact="false"
        :with-icons="true"/>

    <!-- Form Container -->
    <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-sm border border-zinc-200 dark:border-zinc-700 p-6">
        <form wire:submit.prevent="save" class="space-y-6">

            <!-- Basic Recipe Info -->
            <div class="space-y-4">
                <h3 class="text-lg font-semibold text-zinc-900 dark:text-zinc-100">Basic Information</h3>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2">Product Name *</label>
                        <select wire:model.live="product_id"
                                class="w-full px-4 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-800 dark:text-zinc-200 focus:ring-2 focus:ring-blue-500">
                            <option value="">Select Product</option>
                            @foreach($products as $product)
                                <option value="{{ $product->id }}">{{ $product->name }}</option>
                            @endforeach
                        </select>
                        @error('productName') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2">SKU *</label>
                        <input type="text" wire:model="sku" readonly
                               class="w-full px-4 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-zinc-100 dark:bg-zinc-900 text-zinc-800 dark:text-zinc-200 cursor-not-allowed">
                        <p class="text-xs text-zinc-500 dark:text-zinc-400 mt-1">Auto-generated from product</p>
                        @error('sku') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
                    </div>
                </div>

                @if($hasExistingRecipe)
                    <div class="rounded-lg border border-amber-200 bg-amber-50 p-4 text-amber-900">
                        This product already has a recipe. Please edit the existing recipe instead.
                        @if($existingRecipeId)
                            <a class="ml-2 underline font-medium"
                               href="{{ branch_route('branch-dashboard.production.recipes.edit', ['deptSlug' => $dept_slug, 'id' => $existingRecipeId]) }}">
                                Go to Edit Recipe
                            </a>
                        @endif
                    </div>
                @endif

                @if(!$hasExistingRecipe)
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2">Department</label>
                        <input type="text" value="{{ $department->name }}" readonly
                                class="w-full px-4 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-zinc-100 dark:bg-zinc-800 text-zinc-800 dark:text-zinc-200 cursor-not-allowed">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2">Product Type *</label>
                        @if($product_type)
                            <!-- Show selected product type as read-only -->
                            <div class="w-full px-4 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-zinc-100 dark:bg-zinc-900 text-zinc-800 dark:text-zinc-200">
                                @php
                                    $selectedType = $productTypes->firstWhere('id', $product_type);
                                @endphp
                                {{ $selectedType?->name ?? 'Unknown Type' }}
                            </div>
                            <input type="hidden" wire:model="product_type" />
                        @else
                            <!-- Show placeholder when no product selected -->
                            <div class="w-full px-4 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-zinc-100 dark:bg-zinc-900 text-zinc-500 dark:text-zinc-400">
                                Select a product first
                            </div>
                        @endif
                        <p class="text-xs text-zinc-500 dark:text-zinc-400 mt-1">Auto-filled from selected product</p>
                        @error('product_type') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2">Status *</label>
                        <select wire:model="status"
                                class="w-full px-4 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-800 dark:text-zinc-200 focus:ring-2 focus:ring-blue-500">
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                            <option value="testing">Testing</option>
                        </select>
                        @error('status') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
                    </div>
                </div>
                @endif

                <!-- Recipe Yield Configuration - Simplified -->
                @if(!$hasExistingRecipe)
                <div class="bg-gradient-to-br from-blue-50 to-blue-25 dark:from-blue-900/30 dark:to-blue-900/10 p-6 rounded-xl border border-blue-200 dark:border-blue-800/50 shadow-sm">
                    <div class="flex items-center gap-3 mb-6">
                        <div class="p-2 bg-blue-100 dark:bg-blue-800/40 rounded-lg">
                            <svg class="w-5 h-5 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                            </svg>
                        </div>
                        <h4 class="text-lg font-semibold text-zinc-900 dark:text-zinc-100">Recipe Output</h4>
                    </div>
                    
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <!-- Quantity per Batch -->
                        <div class="md:col-span-2">
                            <label class="block text-sm font-semibold text-zinc-900 dark:text-zinc-100 mb-3">
                                Batch Quantity <span class="text-red-500">*</span>
                            </label>
                            <div class="flex gap-3 items-end">
                                <div class="flex-1">
                                    <input type="number" step="0.01" wire:model="yield_quantity"
                                           class="w-full px-4 py-3 border-2 border-blue-200 dark:border-blue-700 rounded-lg bg-white dark:bg-zinc-800 text-zinc-900 dark:text-zinc-100 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all text-lg font-medium"
                                           placeholder="e.g., 10">
                                    <p class="text-xs text-zinc-600 dark:text-zinc-400 mt-2">How many units per batch?</p>
                                </div>
                                <!-- Hidden input to maintain the UOM value -->
                                <input type="hidden" wire:model="uom" />
                            </div>
                            @error('yield_quantity') <span class="text-red-500 text-xs mt-2 block">{{ $message }}</span> @enderror
                            @error('uom') <span class="text-red-500 text-xs mt-2 block">{{ $message }}</span> @enderror
                        </div>

                        <!-- Unit of Measure Display (auto-selected from product) -->
                        <div class="flex flex-col justify-center">
                            <div class="px-4 py-3 bg-blue-100 dark:bg-blue-900/60 border-2 border-blue-300 dark:border-blue-700 rounded-lg text-center">
                                <p class="text-xs text-blue-600 dark:text-blue-300 uppercase tracking-wide font-medium">Current Unit</p>
                                @php
                                    $selectedUOM = $unitsOfMeasure->firstWhere('code', $uom);
                                    $icon = match($selectedUOM?->category ?? 'weight') {
                                        'weight' => '📊',
                                        'volume' => '🧪',
                                        'count' => '🔢',
                                        'length' => '📏',
                                        default => '📦'
                                    };
                                @endphp
                                <p class="text-2xl font-bold text-blue-700 dark:text-blue-200 mt-2">{{ $icon }} {{ $selectedUOM?->symbol ?? 'unit' }}</p>
                            </div>
                            <p class="text-xs text-zinc-500 dark:text-zinc-400 mt-2 text-center">Auto-selected from product</p>
                        </div>
                    </div>
                </div>
                @endif

                @if(!$hasExistingRecipe)
                <!-- Prep Time -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2">Preparation Time *</label>
                        <div class="flex gap-2">
                            <input type="number" wire:model="preparation_time" placeholder="0"
                                   class="flex-1 px-4 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-zinc-100 dark:bg-zinc-900 text-zinc-800 dark:text-zinc-200 cursor-not-allowed"
                                   readonly>
                            <div class="flex items-center px-3 bg-zinc-100 dark:bg-zinc-900 rounded-lg text-zinc-600 dark:text-zinc-400 font-medium">
                                minutes
                            </div>
                        </div>
                        <p class="text-xs text-zinc-500 dark:text-zinc-400 mt-1">Auto-filled from product's primary recipe</p>
                        @error('preparation_time') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
                    </div>
                </div>

            <!-- Ingredients Section -->
            <div class="space-y-4">
                <div class="flex items-center justify-between">
                    <h3 class="text-lg font-semibold text-zinc-900 dark:text-zinc-100">Ingredients *</h3>
                    <div class="flex gap-2">
                        <button type="button" wire:click="addRawIngredient"
                                class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg font-medium transition-colors flex items-center">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                            </svg>
                            Add Raw Material
                        </button>
                        <button type="button" wire:click="addWipIngredient"
                                class="px-4 py-2 bg-purple-600 hover:bg-purple-700 text-white rounded-lg font-medium transition-colors flex items-center">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                            </svg>
                            Add WIP Item
                        </button>
                    </div>
                </div>

                @if(count($ingredients) > 0)
                <div id="ingredients-container" class="space-y-4 bg-zinc-50 dark:bg-zinc-900/50 p-4 rounded-lg">
                    @foreach($ingredients as $index => $ingredient)
                    <div id="ingredient-{{ $index }}" class="bg-white dark:bg-zinc-800 p-4 rounded-lg border border-zinc-200 dark:border-zinc-700">
                        <div class="flex justify-between items-center mb-3">
                            <div class="flex items-center gap-2">
                                <span class="font-semibold text-zinc-900 dark:text-zinc-100">Ingredient #{{ $index + 1 }}</span>
                                @if(isset($ingredient['is_wip']) && $ingredient['is_wip'])
                                    <span class="px-2 py-0.5 text-xs rounded-full bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-200">WIP</span>
                                @else
                                    <span class="px-2 py-0.5 text-xs rounded-full bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200">Raw</span>
                                @endif
                            </div>
                            <button type="button" wire:click="removeIngredient({{ $loop->index }})"
                                    class="text-red-600 hover:text-red-800 dark:text-red-400">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div class="md:col-span-2">
                                 <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2">Item *</label>
                                 <select wire:model.live="ingredients.{{ $index }}.item_id"
                                         class="w-full px-4 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-800 dark:text-zinc-200 focus:ring-2 focus:ring-blue-500">
                                     <option value="">Select Item</option>
                                     @if(isset($ingredient['is_wip']) && $ingredient['is_wip'])
                                         @foreach($wipItems ?? [] as $item)
                                             <option value="{{ $item->id }}">{{ $item->name }} (WIP)</option>
                                         @endforeach
                                     @else
                                         @foreach($items as $item)
                                             <option value="{{ $item->id }}">{{ $item->name }}</option>
                                         @endforeach
                                     @endif
                                 </select>
                                 @error("ingredients.{$index}.item_id") <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
                             </div>

                             <div>
                                 <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2">Quantity *</label>
                                 <input type="number" step="0.01" wire:model="ingredients.{{ $index }}.quantity" placeholder="Enter quantity"
                                        class="w-full px-4 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-800 dark:text-zinc-200 focus:ring-2 focus:ring-blue-500">
                                 @error("ingredients.{$index}.quantity") <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
                             </div>

                             <div>
                                 <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2">UOM *</label>
                                 <input type="text" wire:model="ingredients.{{ $index }}.uom" readonly
                                        class="w-full px-4 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-zinc-100 dark:bg-zinc-900 text-zinc-800 dark:text-zinc-200 cursor-not-allowed"
                                        placeholder="Auto-filled from item">
                                 <p class="text-xs text-zinc-500 dark:text-zinc-400 mt-1">Auto-filled from selected item</p>
                                 @error("ingredients.{$index}.uom") <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
                             </div>


                             <div>
                                 <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2">Waste % (optional)</label>
                                 <input type="number" step="0.01" wire:model="ingredients.{{ $index }}.waste_percentage"
                                        placeholder="e.g., 5 for 5%"
                                        class="w-full px-4 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-800 dark:text-zinc-200 focus:ring-2 focus:ring-blue-500">
                                 @error("ingredients.{$index}.waste_percentage") <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
                             </div>

                            <div class="md:col-span-2">
                                <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2">Preparation Notes</label>
                                <input type="text" wire:model="ingredients.{{ $index }}.preparation_notes"
                                       placeholder="e.g., Dice into 1cm cubes"
                                       class="w-full px-4 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-800 dark:text-zinc-200 focus:ring-2 focus:ring-blue-500">
                            </div>

                            <div class="md:col-span-2">
                                <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2">General Notes</label>
                                <input type="text" wire:model="ingredients.{{ $index }}.notes"
                                       placeholder="Any additional notes"
                                       class="w-full px-4 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-800 dark:text-zinc-200 focus:ring-2 focus:ring-blue-500">
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>
                @else
                <div class="text-center py-8 bg-zinc-50 dark:bg-zinc-900/50 rounded-lg border-2 border-dashed border-zinc-300 dark:border-zinc-600">
                    <p class="text-zinc-500 dark:text-zinc-400">No ingredients added yet. Click "Add Ingredient" to get started.</p>
                </div>
                @endif
                @error('ingredients') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
            </div>

            <!-- Instructions Section -->
            <div class="space-y-4">
                <div class="flex items-center justify-between">
                    <h3 class="text-lg font-semibold text-zinc-900 dark:text-zinc-100">Preparation Instructions</h3>
                    <button type="button" wire:click="addInstruction"
                            class="px-4 py-2 bg-purple-600 hover:bg-purple-700 text-white rounded-lg font-medium transition-colors flex items-center">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                        Add Step
                    </button>
                </div>

                @if(count($instructions) > 0)
                <div id="instructions-container" class="space-y-3 bg-zinc-50 dark:bg-zinc-900/50 p-4 rounded-lg">
                    @foreach($instructions as $index => $instruction)
                    <div id="instruction-{{ $index }}" class="flex items-start gap-3">
                        <span class="flex-shrink-0 w-8 h-8 flex items-center justify-center bg-purple-100 dark:bg-purple-900 text-purple-800 dark:text-purple-200 rounded-full font-bold text-sm">
                            {{ $index + 1 }}
                        </span>
                        <input type="text" wire:model="instructions.{{ $index }}"
                               placeholder="Describe this step..."
                               class="flex-1 px-4 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-800 dark:text-zinc-200 focus:ring-2 focus:ring-purple-500">
                        <button type="button" wire:click="removeInstruction({{ $index }})"
                                class="flex-shrink-0 p-2 text-red-600 hover:text-red-800 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-lg transition-colors">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                            </svg>
                        </button>
                    </div>
                    @endforeach
                </div>
                @endif
            </div>

            @endif

            <!-- Form Actions -->
            @if(!$hasExistingRecipe)
            <div class="flex justify-end gap-3 pt-4 border-t border-zinc-200 dark:border-zinc-700">
                <a href="{{ branch_route('branch-dashboard.production.recipes.index', ['deptSlug'=>$dept_slug]) }}"
                   class="px-6 py-2 border border-zinc-300 dark:border-zinc-600 text-zinc-700 dark:text-zinc-300 rounded-lg hover:bg-zinc-50 dark:hover:bg-zinc-700 transition-colors">
                    Cancel
                </a>
                <button type="submit"
                        wire:loading.attr="disabled" wire:target="save"
                        class="px-6 py-2 bg-blue-600 hover:bg-blue-700 disabled:bg-blue-400 text-white rounded-lg font-medium transition-colors flex items-center">
                    <span wire:loading.remove wire:target="save" class="flex items-center">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                        </svg>
                        Create Recipe
                    </span>
                    <span wire:loading wire:target="save" class="flex items-center gap-2">
                        <svg class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        Creating...
                    </span>
                </button>
            </div>
            @endif
        </form>
    </div>

    <!-- Audit Request Modal -->
    @include('livewire.branch-dashboard.production.partials.audit-modal')

    <!-- Auto-scroll script for new ingredients and instructions -->
    <script>
        document.addEventListener('livewire:init', () => {
            Livewire.on('added-ingredient', () => {
                // Scroll to the first ingredient element after it's added
                const firstIngredient = document.querySelector('#ingredient-0');
                if (firstIngredient) {
                    firstIngredient.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
                }
            });

            Livewire.on('added-instruction', () => {
                // Scroll to the first instruction element after it's added
                const firstInstruction = document.querySelector('#instruction-0');
                if (firstInstruction) {
                    firstInstruction.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
                }
            });
        });
    </script>
</div>
