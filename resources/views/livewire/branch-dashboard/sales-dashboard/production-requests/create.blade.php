<div class="p-3 space-y-3" wire:poll.20s="refreshAvailableRecipes" wire:poll:keep-alive>
    <x-breadcrumb
        title="Request Production Item"
        :items="[
            ['label' => 'Dashboard', 'url' => branch_route('branch-dashboard.index')],
            ['label' => 'Sales Dashboard'],
            ['label' => 'Production Requests', 'url' => branch_route('branch-dashboard.sales-dashboard.production-requests.index', ['salesDeptSlug' => $salesDeptSlug, 'sales_dept_slug' => $salesDeptSlug, 'b_id' => $branchId])],
            ['label' => 'Create Request']
        ]"
        :compact="false"
        :with-icons="true"/>

    @if($submitError !== '')
        <div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700 dark:border-red-800 dark:bg-red-900/20 dark:text-red-300">
            {{ $submitError }}
        </div>
    @endif

    @if($submitSuccess !== '')
        <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700 dark:border-emerald-800 dark:bg-emerald-900/20 dark:text-emerald-300">
            {{ $submitSuccess }}
        </div>
    @endif

    <div class="space-y-3">
        <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-sm border border-zinc-200 dark:border-zinc-700 p-4 space-y-4">
            <!-- Confectioneries Production Section -->
            <div class="border border-zinc-200 dark:border-zinc-700 rounded-lg p-4">
                <h3 class="text-lg font-semibold text-zinc-900 dark:text-zinc-100 mb-3">Confectioneries Production</h3>
                
                <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                    <div>
                        <label class="block text-sm text-zinc-700 dark:text-zinc-300 mb-1">Production Department</label>
                        <select wire:model.live="selectedProductionDepartmentId"
                                class="w-full px-3 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700">
                            <option value="">Select department</option>
                            @foreach($productionDepartments as $department)
                                <option value="{{ $department['id'] }}">{{ $department['name'] }}</option>
                            @endforeach
                        </select>
                        @error('selectedProductionDepartmentId')
                            <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="block text-sm text-zinc-700 dark:text-zinc-300 mb-1">Product</label>
                        <input type="text"
                               wire:model.live.debounce.250ms="productSearch"
                               placeholder="Search product..."
                               class="w-full px-3 py-2 mb-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700"
                               @disabled(!$selectedProductionDepartmentId)>
                        <select wire:model.live="selectedProductId"
                                wire:key="product-select-{{ (string) ($selectedProductionDepartmentId ?? 'none') }}"
                                class="w-full px-3 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700"
                                @disabled(!$selectedProductionDepartmentId)>
                            <option value="">Select product</option>
                            @foreach($availableProducts as $product)
                                <option value="{{ $product['id'] }}">
                                    {{ $product['product_name'] }}
                                    @if($product['uom'])
                                        • {{ $product['uom'] }}
                                    @endif
                                    @if($product['sku'])
                                        ({{ $product['sku'] }})
                                    @endif
                                </option>
                            @endforeach
                        </select>
                        @error('selectedProductId')
                            <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="block text-sm text-zinc-700 dark:text-zinc-300 mb-1">Quantity</label>
                        <div class="flex gap-2">
                            <input type="number" wire:model.live="quantityRequested" min="0.01" step="0.01"
                                   class="w-full px-3 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700">
                            <button type="button" wire:click.prevent="addItem"
                                    class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg text-sm font-semibold">
                                Add
                            </button>
                        </div>
                        @error('quantityRequested')
                            <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </div>

            @if(count($cartItems) === 0)
                <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-3 text-sm text-yellow-700 dark:bg-yellow-900/20 dark:border-yellow-800 dark:text-yellow-300">
                    <strong>Note:</strong> Add at least one item before submitting the request.
                </div>
            @endif

            <!-- Priority and Notes Section -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                <div>
                    <label class="block text-sm text-zinc-700 dark:text-zinc-300 mb-1">Priority</label>
                    <select wire:model="priority"
                            class="w-full px-3 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700">
                        <option value="normal">Normal</option>
                        <option value="urgent">Urgent</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm text-zinc-700 dark:text-zinc-300 mb-1">Notes</label>
                    <input type="text" wire:model="notes"
                           class="w-full px-3 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700"
                           placeholder="Optional notes">
                </div>
            </div>
        </div>

        <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-sm border border-zinc-200 dark:border-zinc-700 overflow-hidden">
            <div class="px-4 py-3 border-b border-zinc-200 dark:border-zinc-700 flex items-center justify-between">
                <h3 class="text-sm font-semibold text-zinc-900 dark:text-zinc-100">Request Items</h3>
                <div class="text-xs text-zinc-500 dark:text-zinc-400">
                    {{ $totalItems }} item(s) • {{ number_format($totalQuantity, 2) }} total qty
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-zinc-50 dark:bg-zinc-900 border-b border-zinc-200 dark:border-zinc-700">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase">Department</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase">Product</th>
                            <th class="px-4 py-3 text-center text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase">Quantity</th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                        @forelse($cartItems as $index => $item)
                            <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-900/50">
                                <td class="px-4 py-3 text-sm text-zinc-800 dark:text-zinc-200">{{ $item['production_department_name'] }}</td>
                                <td class="px-4 py-3 text-sm text-zinc-800 dark:text-zinc-200">
                                    {{ $item['product_name'] ?? '-' }}
                                    @if($item['uom'])
                                        <div class="text-xs text-zinc-500 dark:text-zinc-400">
                                            UOM: {{ $item['uom'] }}
                                        </div>
                                    @endif
                                    @if($item['sku'])
                                        <span class="text-xs text-zinc-500 dark:text-zinc-400">({{ $item['sku'] }})</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-center text-sm text-zinc-800 dark:text-zinc-200">{{ number_format($item['quantity_requested'], 2) }}</td>
                                <td class="px-4 py-3 text-right">
                                    <button type="button" wire:click="removeItem({{ $index }})"
                                            class="px-3 py-1.5 text-xs font-semibold text-red-700 bg-red-100 hover:bg-red-200 rounded">
                                        Remove
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-4 py-6 text-center text-zinc-500 dark:text-zinc-400">
                                    No items added yet.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="px-4 py-3 border-t border-zinc-200 dark:border-zinc-700 flex justify-end gap-2">
                <a href="{{ branch_route('branch-dashboard.sales-dashboard.production-requests.index', ['salesDeptSlug' => $salesDeptSlug, 'sales_dept_slug' => $salesDeptSlug, 'b_id' => $branchId]) }}"
                   class="px-4 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg text-zinc-700 dark:text-zinc-200">
                    Back
                </a>
                <button type="button"
                        wire:click="submitRequest"
                        class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg font-semibold"
                        wire:loading.attr="disabled"
                        wire:target="submitRequest">
                    Submit Request
                </button>
            </div>
        </div>
    </div>
</div>
