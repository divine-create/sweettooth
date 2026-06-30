<div class="p-3 space-y-3"
     x-data="{
        transfer: { open: false, itemId: null, name: '', uom: '', available: 0, qty: null, deptId: '', notes: '', sending: false },
        openTransfer(d) {
            this.transfer = { open: true, itemId: d.itemId, name: d.name, uom: d.uom, available: d.available, qty: d.available, deptId: '', notes: '', sending: false };
        },
        closeTransfer() { this.transfer.open = false; },
        submitTransfer() {
            if (this.transfer.sending) return;
            const t = this.transfer;
            if (!t.deptId || !(parseFloat(t.qty) > 0)) return;
            this.transfer.sending = true;
            $wire.transferStock(t.itemId, t.qty, t.deptId, t.notes)
                .then(() => { this.transfer.open = false; })
                .finally(() => { this.transfer.sending = false; });
        }
     }">

    <x-breadcrumb
        title="Production Store"
        :items="[
            ['label' => 'Dashboard', 'url' => branch_route('branch-dashboard.index')],
            ['label' => 'Production'],
            ['label' => 'Store Stock']
        ]"
        :compact="false"
        :with-icons="true"/>

    <div class="flex justify-end mb-2">
        <a href="{{ branch_route('branch-dashboard.production.material-request', ['deptSlug' => $dept_slug, 'b_id' => $b_id]) }}"
           class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg font-medium flex items-center gap-2">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
            </svg>
            Make Material Request
        </a>
    </div>

    @if(!$store)
        <div class="bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-lg p-6 text-center">
            <svg class="w-12 h-12 mx-auto text-yellow-500 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
            </svg>
            <h3 class="text-lg font-semibold text-yellow-800 dark:text-yellow-200">No Production Store Found</h3>
            <p class="text-yellow-700 dark:text-yellow-300 mt-1">
                This department doesn't have a Production Store yet. Make an item request to start stocking.
            </p>
        </div>
    @else
        <!-- Summary Cards -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-3">
            <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-sm border border-zinc-200 dark:border-zinc-700 p-4">
                <p class="text-sm text-zinc-500 dark:text-zinc-400">Total Items</p>
                <p class="text-2xl font-bold text-zinc-900 dark:text-zinc-100">{{ $stocks->count() }}</p>
            </div>
            <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-sm border border-zinc-200 dark:border-zinc-700 p-4">
                <p class="text-sm text-zinc-500 dark:text-zinc-400">Items in Stock</p>
                <p class="text-2xl font-bold text-green-600 dark:text-green-400">{{ $stocks->where('quantity_available', '>', 0)->count() }}</p>
            </div>
            <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-sm border border-zinc-200 dark:border-zinc-700 p-4">
                <p class="text-sm text-zinc-500 dark:text-zinc-400">Total Quantity</p>
                <p class="text-2xl font-bold text-zinc-900 dark:text-zinc-100">
                    {{ number_format($stocks->sum('quantity_available'), 0) }}
                </p>
            </div>
            <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-sm border border-zinc-200 dark:border-zinc-700 p-4">
                <p class="text-sm text-zinc-500 dark:text-zinc-400">Total Value</p>
                <p class="text-2xl font-bold text-zinc-900 dark:text-zinc-100">
                    {{ \App\Helpers\LocalizationHelper::formatCurrency($totalValue ?? 0) }}
                </p>
            </div>
        </div>

        <!-- Search and Info -->
        <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-sm border border-zinc-200 dark:border-zinc-700 p-4">
            <div class="flex flex-col md:flex-row gap-4 justify-between">
                <div class="flex-1">
                    <input type="text"
                           wire:model.live.debounce.400ms="search"
                           placeholder="Search items by name or SKU..."
                           class="w-full px-4 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-800 dark:text-zinc-200"/>
                </div>
                <div class="flex items-center gap-2 text-sm text-zinc-500 dark:text-zinc-400">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <span>Items are auto-stocked when inventory dispatches to this department</span>
                </div>
            </div>
        </div>

        <!-- Filter Tabs -->
        <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-sm border border-zinc-200 dark:border-zinc-700 p-4">
            <div class="flex items-center justify-between">
                <div class="flex gap-2">
                    <button wire:click="$set('stockFilter', 'all')"
                            class="px-4 py-2 rounded-lg font-medium transition {{ $stockFilter === 'all' ? 'bg-blue-600 text-white' : 'bg-zinc-100 dark:bg-zinc-700 text-zinc-600 dark:text-zinc-300 hover:bg-zinc-200 dark:hover:bg-zinc-600' }}">
                        All ({{ $stockCounts['all'] }})
                    </button>
                    <button wire:click="$set('stockFilter', 'wip')"
                            class="px-4 py-2 rounded-lg font-medium transition {{ $stockFilter === 'wip' ? 'bg-blue-600 text-white' : 'bg-zinc-100 dark:bg-zinc-700 text-zinc-600 dark:text-zinc-300 hover:bg-zinc-200 dark:hover:bg-zinc-600' }}">
                        WIP Items ({{ $stockCounts['wip'] }})
                    </button>
                    <button wire:click="$set('stockFilter', 'raw')"
                            class="px-4 py-2 rounded-lg font-medium transition {{ $stockFilter === 'raw' ? 'bg-blue-600 text-white' : 'bg-zinc-100 dark:bg-zinc-700 text-zinc-600 dark:text-zinc-300 hover:bg-zinc-200 dark:hover:bg-zinc-600' }}">
                        Raw Materials ({{ $stockCounts['raw'] }})
                    </button>
                    <button wire:click="$set('stockFilter', 'finished')"
                            class="px-4 py-2 rounded-lg font-medium transition {{ $stockFilter === 'finished' ? 'bg-blue-600 text-white' : 'bg-zinc-100 dark:bg-zinc-700 text-zinc-600 dark:text-zinc-300 hover:bg-zinc-200 dark:hover:bg-zinc-600' }}">
                        Finished Goods ({{ $stockCounts['finished'] }})
                    </button>
                </div>
            </div>
        </div>

        <!-- Stock Table -->
        @if($stocks->isEmpty())
            <div class="bg-zinc-50 dark:bg-zinc-800/50 rounded-lg p-8 text-center">
                <svg class="w-16 h-16 mx-auto text-zinc-400 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10m0-10l-8 4"/>
                </svg>
                <h3 class="text-lg font-semibold text-zinc-600 dark:text-zinc-400">No Items in Store</h3>
                <p class="text-zinc-500 dark:text-zinc-500 mt-1">
                    @if($search)
                        No items match your search "{{ $search }}"
                    @else
                        Make an item request to start stocking your production store
                    @endif
                </p>
            </div>
        @else
            <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-sm border border-zinc-200 dark:border-zinc-700 overflow-x-auto">
                <table class="w-full" style="min-width: 900px;">
                    <thead class="bg-zinc-50 dark:bg-zinc-700 border-b border-zinc-200 dark:border-zinc-600">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-zinc-500 dark:text-zinc-400 uppercase">Item</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-zinc-500 dark:text-zinc-400 uppercase">Category</th>
                            <th class="px-4 py-3 text-right text-xs font-semibold text-zinc-500 dark:text-zinc-400 uppercase">Quantity</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-zinc-500 dark:text-zinc-400 uppercase">UOM</th>
                            <th class="px-4 py-3 text-right text-xs font-semibold text-zinc-500 dark:text-zinc-400 uppercase">Avg Cost</th>
                            <th class="px-4 py-3 text-right text-xs font-semibold text-zinc-500 dark:text-zinc-400 uppercase">Value</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-zinc-500 dark:text-zinc-400 uppercase">Last Updated</th>
                            <th class="px-4 py-3 text-right text-xs font-semibold text-zinc-500 dark:text-zinc-400 uppercase">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                        @foreach($stocks as $stock)
                            @php
                                $isWip = in_array($stock->item_id, $wipProductIds);
                                $isFinished = !$isWip && !ctype_digit((string) $stock->item_id);
                            @endphp
                            <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-700/50">
                                <td class="px-4 py-3">
                                    <div class="flex items-center gap-2">
                                        <div class="font-medium text-zinc-900 dark:text-zinc-100">
                                            {{ $stock->displayItem?->name ?? 'Unknown Item' }}
                                        </div>
                                        @if($isWip)
                                            <span class="px-2 py-0.5 text-xs rounded-full bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200">
                                                WIP
                                            </span>
                                        @endif
                                    </div>
                                    <div class="text-xs text-zinc-500 dark:text-zinc-400">
                                        SKU: {{ $stock->displayItem?->sku ?? 'N/A' }}
                                    </div>
                                </td>
                                <td class="px-4 py-3">
                                    <span class="px-2 py-1 text-xs rounded-full whitespace-nowrap
                                        @if($stock->displayItem?->category?->name === 'Raw Material')
                                            bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200
                                        @elseif($stock->displayItem?->category?->name === 'Packaging')
                                            bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-200
                                        @else
                                            bg-zinc-100 text-zinc-800 dark:bg-zinc-700 dark:text-zinc-200
                                        @endif">
                                        {{ $stock->displayItem?->category?->name ?? 'Uncategorized' }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-right">
                                    <span class="font-medium text-zinc-900 dark:text-zinc-100
                                        @if(($stock->quantity_available ?? 0) <= 0) text-red-600
                                        @elseif(($stock->quantity_available ?? 0) < ($stock->item?->reorder_level ?? 0)) text-yellow-600
                                        @else text-green-600
                                        @endif">
                                        {{ number_format($stock->quantity_available ?? 0, 2) }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-zinc-600 dark:text-zinc-400">
                                    {{ $stock->displayItem?->unitOfMeasure?->symbol ?? 'N/A' }}
                                </td>
                                <td class="px-4 py-3 text-right text-zinc-600 dark:text-zinc-400">
                                    {{ \App\Helpers\LocalizationHelper::formatCurrency($stock->average_cost ?? 0) }}
                                </td>
                                <td class="px-4 py-3 text-right font-medium text-zinc-900 dark:text-zinc-100">
                                    {{ \App\Helpers\LocalizationHelper::formatCurrency(($stock->quantity_available ?? 0) * ($stock->average_cost ?? 0)) }}
                                </td>
                                <td class="px-4 py-3 text-sm text-zinc-500 dark:text-zinc-400">
                                    @if($stock->last_stock_take_date)
                                        {{ \Carbon\Carbon::parse($stock->last_stock_take_date)->format('M d, Y') }}
                                    @else
                                        <span class="text-zinc-400">Never</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-right">
                                    <div class="flex items-center justify-end gap-2 whitespace-nowrap">
                                        @if($isFinished && ($stock->quantity_available ?? 0) > 0)
                                            <button wire:click="openSendModal('{{ $stock->item_id }}')"
                                                    class="px-3 py-1.5 text-xs font-medium rounded-lg bg-indigo-600 hover:bg-indigo-700 text-white inline-flex items-center gap-1 whitespace-nowrap">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14M12 5l7 7-7 7"/>
                                                </svg>
                                                Send to Sales
                                            </button>
                                        @endif
                                        @if($stockFilter === 'raw' && !$isWip && !$isFinished && ctype_digit((string) $stock->item_id) && ($stock->quantity_available ?? 0) > 0)
                                            @php
                                                $rawName = $stock->displayItem->name ?? ($stock->item->name ?? 'Item');
                                                $rawUom = $stock->item->unitOfMeasure->symbol ?? '';
                                            @endphp
                                            <button type="button"
                                                    x-on:click="openTransfer({
                                                        itemId: '{{ $stock->item_id }}',
                                                        name: @js($rawName),
                                                        uom: @js($rawUom),
                                                        available: {{ (float) ($stock->quantity_available ?? 0) }}
                                                    })"
                                                    class="px-3 py-1.5 text-xs font-medium rounded-lg bg-teal-600 hover:bg-teal-700 text-white inline-flex items-center gap-1 whitespace-nowrap">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m4 6H4m0 0l4 4m-4-4l4-4"/>
                                                </svg>
                                                Transfer
                                            </button>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if($stocks->hasPages())
                <div class="mt-3">
                    {{ $stocks->onEachSide(1)->links() }}
                </div>
            @endif
        @endif
    @endif

    @if($showSendModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div class="absolute inset-0 bg-black/50" wire:click="closeSendModal"></div>
            <div class="relative bg-white dark:bg-zinc-800 rounded-xl shadow-xl border border-zinc-200 dark:border-zinc-700 w-full max-w-md p-5">
                <div class="flex items-start justify-between mb-4">
                    <div>
                        <h3 class="text-lg font-semibold text-zinc-900 dark:text-zinc-100">Send to Sales</h3>
                        <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ $sendProductName }}</p>
                    </div>
                    <button wire:click="closeSendModal" class="text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-200">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>

                <div class="space-y-4">
                    <div class="text-sm text-zinc-600 dark:text-zinc-300">
                        Available in store:
                        <span class="font-semibold text-zinc-900 dark:text-white">{{ rtrim(rtrim(number_format($sendAvailableSales,2),'0'),'.') }} {{ $sendSalesUom }}</span>
                        @if($sendSalesUom !== $sendUom)
                            <span class="text-xs text-zinc-400">(= {{ rtrim(rtrim(number_format($sendAvailable,2),'0'),'.') }} {{ $sendUom }})</span>
                        @endif
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-zinc-500 dark:text-zinc-400 mb-1">Sales department</label>
                        @if(count($sendDepartments))
                            <select wire:model="sendSalesDepartmentId"
                                    class="w-full rounded-lg border-zinc-300 dark:border-zinc-600 dark:bg-zinc-900 text-sm">
                                <option value="">Select a sales department…</option>
                                @foreach($sendDepartments as $id => $name)
                                    <option value="{{ $id }}">{{ $name }}</option>
                                @endforeach
                            </select>
                        @else
                            <p class="text-sm text-amber-600 dark:text-amber-400">
                                This product is not assigned to any sales department. Assign it via Product Assignments first.
                            </p>
                        @endif
                        @error('sendSalesDepartmentId') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-zinc-500 dark:text-zinc-400 mb-1">Quantity to send ({{ $sendSalesUom }})</label>
                        <input type="number" step="0.01" min="0.01" max="{{ $sendAvailableSales }}" wire:model.live="sendQuantity"
                               class="w-full rounded-lg border-zinc-300 dark:border-zinc-600 dark:bg-zinc-900 text-sm" />
                        @error('sendQuantity') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
                        @php $sendPreview = $this->sendBasePreview(); @endphp
                        @if($sendPreview['converts'])
                            <p class="text-xs text-indigo-600 dark:text-indigo-300 mt-1">
                                = {{ rtrim(rtrim(number_format($sendPreview['base'], 2), '0'), '.') }} {{ $sendPreview['base_uom'] }} drawn from the production store
                            </p>
                        @endif
                    </div>
                </div>

                <div class="flex justify-end gap-2 mt-6">
                    <button wire:click="closeSendModal"
                            class="px-4 py-2 rounded-lg border border-zinc-300 dark:border-zinc-600 text-sm text-zinc-700 dark:text-zinc-200 hover:bg-zinc-50 dark:hover:bg-zinc-700">
                        Cancel
                    </button>
                    <button wire:click="sendToSales" @disabled(!count($sendDepartments))
                            wire:loading.attr="disabled" wire:target="sendToSales"
                            class="px-4 py-2 rounded-lg bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium disabled:opacity-50 disabled:cursor-not-allowed">
                        <span wire:loading.remove wire:target="sendToSales">Send to Sales</span>
                        <span wire:loading wire:target="sendToSales">Sending…</span>
                    </button>
                </div>
            </div>
        </div>
    @endif

    <div x-show="transfer.open" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4" style="display:none;">
        <div class="absolute inset-0 bg-black/50" x-on:click="closeTransfer()"></div>
        <div class="relative bg-white dark:bg-zinc-800 rounded-xl shadow-xl border border-zinc-200 dark:border-zinc-700 w-full max-w-md p-5">
            <div class="flex items-start justify-between mb-4">
                <div>
                    <h3 class="text-lg font-semibold text-zinc-900 dark:text-zinc-100">Transfer to Another Store</h3>
                    <p class="text-sm text-zinc-500 dark:text-zinc-400" x-text="transfer.name"></p>
                </div>
                <button type="button" x-on:click="closeTransfer()" class="text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-200">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            <div class="space-y-4">
                <div class="text-sm text-zinc-600 dark:text-zinc-300">
                    Available in store:
                    <span class="font-semibold text-zinc-900 dark:text-white">
                        <span x-text="(Math.round(transfer.available * 100) / 100)"></span>
                        <span x-text="transfer.uom"></span>
                    </span>
                </div>

                <div>
                    <label class="block text-xs font-medium text-zinc-500 dark:text-zinc-400 mb-1">Destination department</label>
                    @if(count($transferDepartments))
                        <select x-model="transfer.deptId"
                                class="w-full rounded-lg border-zinc-300 dark:border-zinc-600 dark:bg-zinc-900 text-sm">
                            <option value="">Select a department…</option>
                            @foreach($transferDepartments as $id => $name)
                                <option value="{{ $id }}">{{ $name }}</option>
                            @endforeach
                        </select>
                    @else
                        <p class="text-sm text-amber-600 dark:text-amber-400">
                            No other production departments are available to transfer to.
                        </p>
                    @endif
                </div>

                <div>
                    <label class="block text-xs font-medium text-zinc-500 dark:text-zinc-400 mb-1">
                        Quantity to transfer (<span x-text="transfer.uom"></span>)
                    </label>
                    <input type="number" step="0.01" min="0.01" x-bind:max="transfer.available" x-model="transfer.qty"
                           class="w-full rounded-lg border-zinc-300 dark:border-zinc-600 dark:bg-zinc-900 text-sm" />
                    <p class="text-xs text-red-600" x-show="transfer.qty !== null && transfer.qty !== '' && parseFloat(transfer.qty) > transfer.available">
                        Quantity exceeds what is available.
                    </p>
                </div>

                <div>
                    <label class="block text-xs font-medium text-zinc-500 dark:text-zinc-400 mb-1">Notes (optional)</label>
                    <textarea x-model="transfer.notes" rows="2" maxlength="500"
                              class="w-full rounded-lg border-zinc-300 dark:border-zinc-600 dark:bg-zinc-900 text-sm"
                              placeholder="Reason / reference…"></textarea>
                </div>

                <p class="text-xs text-zinc-500 dark:text-zinc-400">
                    Stock leaves this store immediately and is credited to the destination once that department confirms receipt.
                </p>
            </div>

            <div class="flex justify-end gap-2 mt-6">
                <button type="button" x-on:click="closeTransfer()"
                        class="px-4 py-2 rounded-lg border border-zinc-300 dark:border-zinc-600 text-sm text-zinc-700 dark:text-zinc-200 hover:bg-zinc-50 dark:hover:bg-zinc-700">
                    Cancel
                </button>
                <button type="button" x-on:click="submitTransfer()"
                        x-bind:disabled="transfer.sending || !transfer.deptId || !(parseFloat(transfer.qty) > 0) || parseFloat(transfer.qty) > transfer.available {{ count($transferDepartments) ? '' : '|| true' }}"
                        class="px-4 py-2 rounded-lg bg-teal-600 hover:bg-teal-700 text-white text-sm font-medium disabled:opacity-50 disabled:cursor-not-allowed">
                    <span x-show="!transfer.sending">Send Transfer</span>
                    <span x-show="transfer.sending">Sending…</span>
                </button>
            </div>
        </div>
    </div>
</div>