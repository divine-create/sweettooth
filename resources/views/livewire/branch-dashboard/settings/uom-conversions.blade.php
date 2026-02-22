<div>
    <h2 class="text-2xl font-bold mb-6 text-zinc-900 dark:text-zinc-100">Unit Conversion Management</h2>

    @if (session()->has('message'))
        <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 5000)"
            class="mb-4 p-4 bg-green-100 dark:bg-green-900 text-green-700 dark:text-green-100 rounded-lg transition-opacity duration-300">
            <div class="flex justify-between items-center">
                <span>{{ session('message') }}</span>
                <button @click="show = false" class="text-green-700 dark:text-green-100">&times;</button>
            </div>
        </div>
    @endif

    @if (session()->has('error'))
        <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 7000)"
            class="mb-4 p-4 bg-red-100 dark:bg-red-900 text-red-700 dark:text-red-100 rounded-lg transition-opacity duration-300">
            <div class="flex justify-between items-center">
                <span>{{ session('error') }}</span>
                <button @click="show = false" class="text-red-700 dark:text-red-100">&times;</button>
            </div>
        </div>
    @endif

    <div class="setting-group p-4 bg-zinc-50 dark:bg-zinc-700 rounded-lg transition-colors duration-300 mb-6">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-3">
            <div class="md:col-span-2">
                <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Search</label>
                <input type="text" wire:model.live.debounce.300ms="search" placeholder="Search by unit, notes, item, product, branch"
                    class="w-full px-3 py-2 border border-zinc-300 dark:border-zinc-600 rounded-md bg-white dark:bg-zinc-800 text-zinc-900 dark:text-zinc-100" />
            </div>

            <div>
                <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Scope</label>
                <select wire:model.live="scopeFilter"
                    class="w-full px-3 py-2 border border-zinc-300 dark:border-zinc-600 rounded-md bg-white dark:bg-zinc-800 text-zinc-900 dark:text-zinc-100">
                    <option value="all">All</option>
                    <option value="global">Global</option>
                    <option value="branch">Branch</option>
                    <option value="item">Item</option>
                    <option value="product">Product</option>
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Status</label>
                <select wire:model.live="statusFilter"
                    class="w-full px-3 py-2 border border-zinc-300 dark:border-zinc-600 rounded-md bg-white dark:bg-zinc-800 text-zinc-900 dark:text-zinc-100">
                    <option value="active">Active</option>
                    <option value="inactive">Inactive</option>
                    <option value="all">All</option>
                </select>
            </div>
        </div>

        <div class="mt-4 flex justify-end">
            <button type="button" wire:click="openCreateForm"
                class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded transition-colors duration-200">
                Add Conversion
            </button>
        </div>
    </div>

    @if($showForm)
        <div class="setting-group p-4 bg-zinc-50 dark:bg-zinc-700 rounded-lg transition-colors duration-300 mb-6">
            <h3 class="text-lg font-semibold mb-4 text-zinc-900 dark:text-zinc-100">
                {{ $isEditing ? 'Edit UOM Conversion' : 'Create UOM Conversion' }}
            </h3>

            <form wire:submit.prevent="saveConversion">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Scope *</label>
                        <select wire:model.live="scopeType" @if($isEditing) disabled @endif
                            class="w-full px-3 py-2 border border-zinc-300 dark:border-zinc-600 rounded-md bg-white dark:bg-zinc-800 text-zinc-900 dark:text-zinc-100 disabled:opacity-60">
                            <option value="global">Global</option>
                            <option value="branch">Branch</option>
                            <option value="item">Item</option>
                            <option value="product">Product</option>
                        </select>
                        @error('scopeType') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Branch (optional)</label>
                        <select wire:model="branchId" @if($scopeType === 'global' || $isEditing) disabled @endif
                            class="w-full px-3 py-2 border border-zinc-300 dark:border-zinc-600 rounded-md bg-white dark:bg-zinc-800 text-zinc-900 dark:text-zinc-100 disabled:opacity-60">
                            <option value="">Not scoped to branch</option>
                            @foreach($branches as $branch)
                                <option value="{{ $branch->id }}">{{ $branch->name }} ({{ $branch->code }})</option>
                            @endforeach
                        </select>
                        @error('branchId') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                    </div>

                    @if($scopeType === 'item')
                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Item Search</label>
                            <input type="text" wire:model.live.debounce.300ms="itemSearch" placeholder="Search item name or SKU"
                                class="w-full px-3 py-2 mb-2 border border-zinc-300 dark:border-zinc-600 rounded-md bg-white dark:bg-zinc-800 text-zinc-900 dark:text-zinc-100" />
                            <select wire:model="itemId" @if($isEditing) disabled @endif
                                class="w-full px-3 py-2 border border-zinc-300 dark:border-zinc-600 rounded-md bg-white dark:bg-zinc-800 text-zinc-900 dark:text-zinc-100 disabled:opacity-60">
                                <option value="">Select item</option>
                                @foreach($itemOptions as $item)
                                    <option value="{{ $item->id }}">
                                        {{ $item->name }} ({{ $item->sku }}){{ $item->branch ? ' - ' . $item->branch->name : '' }}
                                    </option>
                                @endforeach
                            </select>
                            @error('itemId') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                        </div>
                    @endif

                    @if($scopeType === 'product')
                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Product Search</label>
                            <input type="text" wire:model.live.debounce.300ms="productSearch" placeholder="Search product name or SKU"
                                class="w-full px-3 py-2 mb-2 border border-zinc-300 dark:border-zinc-600 rounded-md bg-white dark:bg-zinc-800 text-zinc-900 dark:text-zinc-100" />
                            <select wire:model="productId" @if($isEditing) disabled @endif
                                class="w-full px-3 py-2 border border-zinc-300 dark:border-zinc-600 rounded-md bg-white dark:bg-zinc-800 text-zinc-900 dark:text-zinc-100 disabled:opacity-60">
                                <option value="">Select product</option>
                                @foreach($productOptions as $product)
                                    <option value="{{ $product->id }}">
                                        {{ $product->name }} ({{ $product->sku }})
                                    </option>
                                @endforeach
                            </select>
                            @error('productId') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                        </div>
                    @endif

                    <div>
                        <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">From Unit *</label>
                        <select wire:model="fromUomId" @if($isEditing) disabled @endif
                            class="w-full px-3 py-2 border border-zinc-300 dark:border-zinc-600 rounded-md bg-white dark:bg-zinc-800 text-zinc-900 dark:text-zinc-100 disabled:opacity-60">
                            <option value="">Select from unit</option>
                            @foreach($units as $unit)
                                <option value="{{ $unit->id }}">{{ $unit->name }} ({{ $unit->symbol }})</option>
                            @endforeach
                        </select>
                        @error('fromUomId') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">To Unit *</label>
                        <select wire:model="toUomId" @if($isEditing) disabled @endif
                            class="w-full px-3 py-2 border border-zinc-300 dark:border-zinc-600 rounded-md bg-white dark:bg-zinc-800 text-zinc-900 dark:text-zinc-100 disabled:opacity-60">
                            <option value="">Select to unit</option>
                            @foreach($units as $unit)
                                <option value="{{ $unit->id }}">{{ $unit->name }} ({{ $unit->symbol }})</option>
                            @endforeach
                        </select>
                        @error('toUomId') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Factor *</label>
                        <input type="number" step="0.000000000001" min="0.000000000001" wire:model="factor" placeholder="e.g. 0.001"
                            class="w-full px-3 py-2 border border-zinc-300 dark:border-zinc-600 rounded-md bg-white dark:bg-zinc-800 text-zinc-900 dark:text-zinc-100" />
                        @error('factor') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                    </div>

                    <div class="flex items-center gap-6 pt-7">
                        <label class="inline-flex items-center text-sm text-zinc-700 dark:text-zinc-300">
                            <input type="checkbox" wire:model="isActive" class="rounded border-zinc-300 dark:border-zinc-600 text-blue-600">
                            <span class="ml-2">Active</span>
                        </label>

                        <label class="inline-flex items-center text-sm text-zinc-700 dark:text-zinc-300">
                            <input type="checkbox" wire:model="isSystem" class="rounded border-zinc-300 dark:border-zinc-600 text-blue-600">
                            <span class="ml-2">System Conversion</span>
                        </label>
                    </div>

                    <div class="md:col-span-2">
                        <label class="inline-flex items-center text-sm text-zinc-700 dark:text-zinc-300">
                            <input type="checkbox" wire:model="syncInverse" class="rounded border-zinc-300 dark:border-zinc-600 text-blue-600">
                            <span class="ml-2">Auto-create/update inverse conversion</span>
                        </label>
                    </div>

                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Notes</label>
                        <textarea wire:model="notes" rows="2" placeholder="Optional context notes"
                            class="w-full px-3 py-2 border border-zinc-300 dark:border-zinc-600 rounded-md bg-white dark:bg-zinc-800 text-zinc-900 dark:text-zinc-100"></textarea>
                        @error('notes') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                    </div>
                </div>

                <div class="mt-4 flex justify-end gap-3">
                    <button type="button" wire:click="cancelForm"
                        class="bg-zinc-500 hover:bg-zinc-700 text-white font-bold py-2 px-4 rounded transition-colors duration-200">
                        Cancel
                    </button>
                    <button type="submit"
                        class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded transition-colors duration-200">
                        {{ $isEditing ? 'Update Conversion' : 'Save Conversion' }}
                    </button>
                </div>
            </form>
        </div>
    @endif

    <div class="setting-group p-4 bg-zinc-50 dark:bg-zinc-700 rounded-lg transition-colors duration-300">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-zinc-200 dark:divide-zinc-600">
                <thead class="bg-zinc-100 dark:bg-zinc-800">
                    <tr>
                        <th class="px-4 py-2 text-left text-xs font-semibold text-zinc-600 dark:text-zinc-300">From</th>
                        <th class="px-4 py-2 text-left text-xs font-semibold text-zinc-600 dark:text-zinc-300">To</th>
                        <th class="px-4 py-2 text-left text-xs font-semibold text-zinc-600 dark:text-zinc-300">Factor</th>
                        <th class="px-4 py-2 text-left text-xs font-semibold text-zinc-600 dark:text-zinc-300">Scope</th>
                        <th class="px-4 py-2 text-left text-xs font-semibold text-zinc-600 dark:text-zinc-300">Context</th>
                        <th class="px-4 py-2 text-left text-xs font-semibold text-zinc-600 dark:text-zinc-300">Status</th>
                        <th class="px-4 py-2 text-left text-xs font-semibold text-zinc-600 dark:text-zinc-300">Updated</th>
                        <th class="px-4 py-2 text-left text-xs font-semibold text-zinc-600 dark:text-zinc-300">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-200 dark:divide-zinc-600">
                    @forelse($conversions as $conversion)
                        <tr wire:key="uom-conv-{{ $conversion->id }}">
                            <td class="px-4 py-2 text-sm text-zinc-800 dark:text-zinc-100">
                                {{ $conversion->fromUnit?->symbol ?? 'N/A' }}
                            </td>
                            <td class="px-4 py-2 text-sm text-zinc-800 dark:text-zinc-100">
                                {{ $conversion->toUnit?->symbol ?? 'N/A' }}
                            </td>
                            <td class="px-4 py-2 text-sm text-zinc-800 dark:text-zinc-100">
                                {{ $this->formatFactor($conversion->factor) }}
                            </td>
                            <td class="px-4 py-2 text-sm">
                                @if($conversion->product_id)
                                    <span class="px-2 py-1 rounded bg-purple-100 text-purple-700 dark:bg-purple-900 dark:text-purple-100">Product</span>
                                @elseif($conversion->item_id)
                                    <span class="px-2 py-1 rounded bg-blue-100 text-blue-700 dark:bg-blue-900 dark:text-blue-100">Item</span>
                                @elseif($conversion->branch_id)
                                    <span class="px-2 py-1 rounded bg-amber-100 text-amber-700 dark:bg-amber-900 dark:text-amber-100">Branch</span>
                                @else
                                    <span class="px-2 py-1 rounded bg-emerald-100 text-emerald-700 dark:bg-emerald-900 dark:text-emerald-100">Global</span>
                                @endif
                            </td>
                            <td class="px-4 py-2 text-sm text-zinc-700 dark:text-zinc-300">
                                @if($conversion->item)
                                    {{ $conversion->item->name }} ({{ $conversion->item->sku }})
                                @elseif($conversion->product)
                                    {{ $conversion->product->name }} ({{ $conversion->product->sku }})
                                @elseif($conversion->branch)
                                    {{ $conversion->branch->name }}
                                @else
                                    All items/products
                                @endif
                            </td>
                            <td class="px-4 py-2 text-sm">
                                @if($conversion->is_active)
                                    <span class="px-2 py-1 rounded bg-green-100 text-green-700 dark:bg-green-900 dark:text-green-100">Active</span>
                                @else
                                    <span class="px-2 py-1 rounded bg-red-100 text-red-700 dark:bg-red-900 dark:text-red-100">Inactive</span>
                                @endif
                            </td>
                            <td class="px-4 py-2 text-sm text-zinc-700 dark:text-zinc-300">
                                {{ optional($conversion->updated_at)->format('Y-m-d H:i') }}
                            </td>
                            <td class="px-4 py-2 text-sm text-zinc-700 dark:text-zinc-300">
                                <div class="flex flex-wrap gap-2">
                                    <button type="button" wire:click="editConversion({{ $conversion->id }})"
                                        class="px-2 py-1 bg-blue-500 text-white rounded hover:bg-blue-700 transition-colors">
                                        Edit
                                    </button>
                                    <button type="button" wire:click="toggleStatus({{ $conversion->id }})"
                                        class="px-2 py-1 bg-amber-500 text-white rounded hover:bg-amber-700 transition-colors">
                                        {{ $conversion->is_active ? 'Deactivate' : 'Activate' }}
                                    </button>
                                    <button type="button"
                                        onclick="if(!confirm('Delete this conversion and its inverse pair?')) return false;"
                                        wire:click="deleteConversion({{ $conversion->id }})"
                                        class="px-2 py-1 bg-red-500 text-white rounded hover:bg-red-700 transition-colors">
                                        Delete
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-4 py-6 text-center text-zinc-500 dark:text-zinc-400">
                                No conversions found for current filters.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-4">
            {{ $conversions->links() }}
        </div>
    </div>
</div>
