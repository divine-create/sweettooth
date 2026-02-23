<div class="p-3 space-y-3">
    <x-breadcrumb title="Product Assignments" :items="[
        ['label' => 'Dashboard', 'url' => branch_route('branch-dashboard.index')],
        ['label' => 'Production'],
        ['label' => 'Product Assignments'],
    ]" :compact="false" :with-icons="true" />

    @if ($department === null)
        <div class="rounded-lg border border-amber-200 bg-amber-50 p-4 text-amber-900">
            A department should be chosen to view product assignments.
        </div>
    @else
        <div x-data="{ activeTab: 'sales' }" class="bg-white dark:bg-zinc-800 rounded-lg shadow-sm border border-zinc-200 dark:border-zinc-700 p-4 space-y-4">
            <div class="flex gap-2 border-b border-zinc-200 dark:border-zinc-700 pb-2">
                <button @click="activeTab = 'sales'"
                    :class="activeTab === 'sales' ? 'bg-blue-600 text-white' : 'bg-zinc-100 dark:bg-zinc-700 text-zinc-700 dark:text-zinc-200'"
                    class="px-3 py-1.5 rounded-md text-sm font-medium transition">
                    Sales Department
                </button>
                <button @click="activeTab = 'recipe'"
                    :class="activeTab === 'recipe' ? 'bg-blue-600 text-white' : 'bg-zinc-100 dark:bg-zinc-700 text-zinc-700 dark:text-zinc-200'"
                    class="px-3 py-1.5 rounded-md text-sm font-medium transition">
                    Recipe
                </button>
            </div>

            <div x-show="activeTab === 'sales'" class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Products (multi-select)</label>
                    <x-dynamic-component
                        :component="TallStackUi::prefix('select.styled')"
                        wire:model.live="selectedProductIds"
                        :options="$productOptions"
                        :selectable="['label' => 'label', 'value' => 'value']"
                        multiple
                        searchable
                        placeholder="Select products"
                    />
                </div>
                <div>
                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Sales Department</label>
                    <x-dynamic-component
                        :component="TallStackUi::prefix('select.styled')"
                        wire:model.live="selectedSalesDepartmentId"
                        :options="$salesDepartmentOptions"
                        :selectable="['label' => 'label', 'value' => 'value']"
                        searchable
                        placeholder="Select sales department"
                    />
                </div>
                <div>
                    <button wire:click="applySalesDepartmentAssignment" wire:loading.attr="disabled" wire:target="applySalesDepartmentAssignment"
                        class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg font-medium transition-colors duration-200 flex items-center shadow-sm">
                        <span wire:loading.remove wire:target="applySalesDepartmentAssignment">Assign Sales Department</span>
                        <span wire:loading wire:target="applySalesDepartmentAssignment" class="flex items-center">
                            <svg class="w-4 h-4 mr-2 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            Applying...
                        </span>
                    </button>
                </div>
            </div>

            <div x-show="activeTab === 'recipe'" class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Product</label>
                    <x-dynamic-component
                        :component="TallStackUi::prefix('select.styled')"
                        wire:model.live="selectedProductIdForRecipe"
                        :options="$productOptions"
                        :selectable="['label' => 'label', 'value' => 'value']"
                        searchable
                        placeholder="Select product"
                    />
                </div>
                <div>
                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Recipe</label>
                    <x-dynamic-component
                        :component="TallStackUi::prefix('select.styled')"
                        wire:model.live="selectedRecipeId"
                        :options="$recipeOptions"
                        :selectable="['label' => 'label', 'value' => 'value']"
                        searchable
                        placeholder="Select recipe"
                    />
                </div>
                <div>
                    <button wire:click="applyRecipeAssignment" wire:loading.attr="disabled" wire:target="applyRecipeAssignment"
                        class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg font-medium transition-colors duration-200 flex items-center shadow-sm">
                        <span wire:loading.remove wire:target="applyRecipeAssignment">Assign Recipe</span>
                        <span wire:loading wire:target="applyRecipeAssignment" class="flex items-center">
                            <svg class="w-4 h-4 mr-2 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            Applying...
                        </span>
                    </button>
                </div>
            </div>
        </div>

        <!-- Filters Section -->
        <div x-data="{ open: false }"
            class="bg-white dark:bg-zinc-800 rounded-lg shadow-sm border border-zinc-200 dark:border-zinc-700 transition-all duration-300">
            <div class="flex justify-between items-center px-3 py-2 border-b border-zinc-200 dark:border-zinc-700">
                <h2 class="text-sm font-semibold text-zinc-800 dark:text-zinc-100">Filters</h2>
                <button @click="open = !open"
                    class="flex items-center px-2.5 py-1 rounded text-xs font-medium bg-blue-600 hover:bg-blue-700 text-white transition-all duration-200">
                    <span x-text="open ? 'Close' : 'Show Filters'"></span>
                </button>
            </div>

            <div x-show="open" x-collapse class="p-3 space-y-3" wire:loading.class="opacity-75 pointer-events-none"
                wire:target="updatedSearch, updatedFilterProductType, resetFilters">
                <div>
                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Search</label>
                    <input type="text" wire:model.live.debounce.300ms="search"
                        placeholder="Search by name, SKU, or description..."
                        class="w-full px-4 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-800 dark:text-zinc-200 focus:ring-2 focus:ring-blue-500">
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Product Type</label>
                        <select wire:model.live="filterProductType"
                            class="w-full px-4 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-800 dark:text-zinc-200 focus:ring-2 focus:ring-blue-500">
                            <option value="">All Types</option>
                            @foreach ($productTypes as $type)
                                <option value="{{ $type->id }}">{{ $type->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="flex justify-end pt-2.5 border-t border-zinc-200 dark:border-zinc-700">
                    <button wire:click="resetFilters" wire:target="resetFilters"
                        class="px-4 py-2 bg-zinc-200 hover:bg-zinc-300 dark:bg-zinc-700 dark:hover:bg-zinc-600 text-zinc-800 dark:text-zinc-200 rounded-lg font-medium transition-colors duration-200">
                        Reset
                    </button>
                </div>
            </div>
        </div>

        <!-- Audit Modal -->
        @include('livewire.branch-dashboard.production.partials.audit-modal')
    @endif
</div>
