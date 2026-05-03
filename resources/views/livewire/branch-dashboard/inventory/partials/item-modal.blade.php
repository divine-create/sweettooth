<!-- Create/Edit Item Modal (Slide-in) -->
<div x-data="{ show: @entangle('showModal').live }" x-show="show" x-cloak class="fixed inset-0 z-50 overflow-hidden"
    @keydown.escape.window="show = false; $wire.closeModal()">
    <!-- Backdrop -->
    <div x-show="show" x-transition:enter="transition-opacity ease-linear duration-150"
        x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
        x-transition:leave="transition-opacity ease-linear duration-150" x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0" class="fixed inset-0 bg-black bg-opacity-50"
        @click="show = false; $wire.closeModal()">
    </div>

    <!-- Slide-in Panel -->
    <div x-show="show" x-transition:enter="transform transition ease-in-out duration-200"
        x-transition:enter-start="translate-x-full" x-transition:enter-end="translate-x-0"
        x-transition:leave="transform transition ease-in-out duration-200"
        x-transition:leave-start="translate-x-0" x-transition:leave-end="translate-x-full"
        class="fixed inset-y-0 right-0 w-full md:w-1/2 lg:w-1/2 bg-white dark:bg-zinc-900 shadow-xl flex flex-col">

        <!-- Header -->
        <div class="px-6 py-4 border-b border-zinc-200 dark:border-zinc-700 flex items-center justify-between">
            <h2 class="text-xl font-bold text-zinc-900 dark:text-zinc-100">
                {{ $isEditing ? 'Edit Item' : 'Add New Item' }}</h2>
            <button @click="show = false; $wire.closeModal()"
                class="p-2 hover:bg-zinc-100 dark:hover:bg-zinc-800 rounded-lg transition-colors duration-150">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>

        <!-- Scrollable Form Content -->
        <div
            class="flex-1 overflow-y-auto px-6 py-4 scrollbar-thin scrollbar-thumb-zinc-300 dark:scrollbar-thumb-zinc-700 scrollbar-track-transparent">
            <form wire:submit.prevent="save" class="space-y-6">
                <!-- Item Name -->
                <div>
                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2">Item Name *</label>
                    <input type="text" wire:model.live="name"
                        class="w-full px-4 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-800 text-zinc-900 dark:text-zinc-100 focus:ring-2 focus:ring-blue-500"
                        placeholder="Enter item name" required>
                    @error('name')
                        <span class="text-red-500 text-sm">{{ $message }}</span>
                    @enderror
                </div>

                <!-- Category -->
                <div>
                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2">Category *</label>
                    @php
                        $categories = \App\Models\ItemCategory::active()
                            ->forBranch(current_branch_id())
                            ->orderBy('name')
                            ->get()
                            ->map(fn($cat) => ['label' => $cat->name, 'value' => $cat->id])
                            ->toArray();
                    @endphp
                    <x-select.styled
                        wire:model.live="category_id"
                        :options="$categories"
                        select="label:label|value:value"
                        placeholder="Select Category"
                        required
                    />
                    @error('category_id')
                        <span class="text-red-500 text-sm">{{ $message }}</span>
                    @enderror
                </div>

                <!-- SKU (Auto-generated but editable) -->
                <div>
                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2">SKU *</label>
                    <input type="text" wire:model="sku" disabled
                        class="w-full px-4 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-800 text-zinc-900 dark:text-zinc-100 focus:ring-2 focus:ring-blue-500 font-mono"
                        placeholder="Auto-generated from category and name" required>
                    <p class="text-xs text-zinc-500 dark:text-zinc-400 mt-1">Auto-generated, but you can modify it</p>
                    @error('sku')
                        <span class="text-red-500 text-sm">{{ $message }}</span>
                    @enderror
                </div>

                <!-- Unit of Measure -->
                <div>
                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2">Unit of Measure *</label>
                    <select wire:model="uom_id"
                        class="w-full px-4 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-800 text-zinc-900 dark:text-zinc-100 focus:ring-2 focus:ring-blue-500"
                        required>
                        <option value="">Select UOM</option>
                        @php
                            $unitsOfMeasure = \App\Models\UnitOfMeasure::orderBy('name')->get();
                        @endphp
                        @foreach($unitsOfMeasure as $uom)
                            <option value="{{ $uom->id }}">{{ $uom->name }} ({{ $uom->symbol }})</option>
                        @endforeach
                    </select>
                    @error('uom_id')
                        <span class="text-red-500 text-sm">{{ $message }}</span>
                    @enderror
                </div>

                <!-- Reorder Level -->
                <div>
                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2">Reorder Level</label>
                    <input type="number" step="1.0" wire:model="reorder_level"
                        class="w-full px-4 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-800 text-zinc-900 dark:text-zinc-100 focus:ring-2 focus:ring-blue-500"
                        placeholder="Enter reorder level">
                    @error('reorder_level')
                        <span class="text-red-500 text-sm">{{ $message }}</span>
                    @enderror
                </div>

                <!-- Max Stock Level -->
                <div>
                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2">Max Stock Level</label>
                    <input type="number" step="1.0" wire:model="max_stock_level"
                        class="w-full px-4 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-800 text-zinc-900 dark:text-zinc-100 focus:ring-2 focus:ring-blue-500"
                        placeholder="Enter max stock level">
                    @error('max_stock_level')
                        <span class="text-red-500 text-sm">{{ $message }}</span>
                    @enderror
                </div>

                <!-- Unit Price -->
                <div>
                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2">Unit Price</label>
                    <input type="number" step="0.01" min="0" wire:model.live="unit_price"
                        class="w-full px-4 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-800 text-zinc-900 dark:text-zinc-100 focus:ring-2 focus:ring-blue-500"
                        placeholder="Enter unit price">
                    @error('unit_price')
                        <span class="text-red-500 text-sm">{{ $message }}</span>
                    @enderror
                </div>

                <!-- Status -->
                <div>
                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2">Status *</label>
                    <x-select.styled
                        wire:model="status"
                        :options="[
                            ['label' => 'Active', 'value' => 'active'],
                            ['label' => 'Inactive', 'value' => 'inactive']
                        ]"
                        select="label:label|value:value"
                        placeholder="Select Status"
                        required
                    />
                    @error('status')
                        <span class="text-red-500 text-sm">{{ $message }}</span>
                    @enderror
                </div>
            </form>
        </div>

        <!-- Footer -->
        <div
            class="px-6 py-4 border-t border-zinc-200 dark:border-zinc-700 flex items-center justify-end space-x-3">
            <button @click="show = false; $wire.closeModal()"
                class="px-4 py-2 bg-zinc-200 hover:bg-zinc-300 dark:bg-zinc-700 dark:hover:bg-zinc-600 text-zinc-800 dark:text-zinc-200 rounded-lg font-medium transition-colors duration-150">
                Cancel
            </button>
            <button x-data="{ saving: false }"
                @click="saving = true; $wire.save().finally(() => { saving = false })"
                :disabled="saving"
                class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg font-medium transition-colors duration-150 disabled:opacity-50 disabled:cursor-not-allowed flex items-center space-x-2">
                <span x-show="!saving">{{ $isEditing ? 'Update Item' : 'Create Item' }}</span>
                <span x-show="saving" class="flex items-center space-x-2">
                    <svg class="w-4 h-4 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z" />
                    </svg>
                    <span>Saving...</span>
                </span>
            </button>
        </div>
    </div>
</div>
