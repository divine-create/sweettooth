<div x-data="{ showBill: false }" class="p-4 space-y-4">

    <!-- Branch & Department Context Bar -->
    <div class="rounded-xl border border-blue-200 dark:border-blue-800 bg-gradient-to-r from-blue-50 to-indigo-50 dark:from-blue-950/30 dark:to-indigo-950/30 p-3">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-full bg-blue-600 text-white flex items-center justify-center">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-5 h-5"><path fill-rule="evenodd" d="M5.625 1.5H9a3.75 3.75 0 0 1 3.75 3.75v1.875c0 1.036.84 1.875 1.875 1.875H16.5a3.75 3.75 0 0 1 3.75 3.75v7.875c0 1.035-.84 1.875-1.875 1.875H5.625a1.875 1.875 0 0 1-1.875-1.875V3.375c0-1.036.84-1.875 1.875-1.875Zm6.905 9.97a.75.75 0 0 0-1.06 0l-3 3a.75.75 0 1 0 1.06 1.06l1.72-1.72V18a.75.75 0 0 0 1.5 0v-4.19l1.72 1.72a.75.75 0 1 0 1.06-1.06l-3-3Z" clip-rule="evenodd"/><path d="M14.25 5.25a5.23 5.23 0 0 0-1.279-3.434 9.768 9.768 0 0 1 6.963 6.963A5.23 5.23 0 0 0 16.5 7.5h-1.875a.375.375 0 0 1-.375-.375V5.25Z"/></svg>
                </div>
                <div>
                    <div class="font-semibold text-blue-900 dark:text-blue-100">
                        {{ $branchName }}
                        @if($departmentName)
                            <span class="text-blue-700 dark:text-blue-300">/ {{ $departmentName }}</span>
                        @endif
                    </div>
                    <div class="text-xs text-blue-700 dark:text-blue-300">
                        Generate a customer bill before payment • Print only, no sale recorded
                    </div>
                </div>
            </div>
            <div class="text-right">
                <div class="text-xs text-blue-600 dark:text-blue-400 font-medium">Branch ID</div>
                <div class="text-sm font-mono text-blue-900 dark:text-blue-100">{{ $branchId ?? 'N/A' }}</div>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-12 gap-4">
        <!-- Product Picker -->
        <div class="col-span-12 lg:col-span-8 space-y-4">
            <input type="text" wire:model.live="search" placeholder="Search product..."
                class="w-full rounded-md border border-zinc-300 dark:border-zinc-700 bg-white dark:bg-zinc-900 text-zinc-900 dark:text-zinc-100 placeholder-zinc-400 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-emerald-500" />

            <!-- From Inventory Section -->
            @if(count($dispatchedItems) > 0)
            <div class="rounded-xl border border-blue-200 dark:border-blue-800 bg-blue-50 dark:bg-blue-950/30 p-3">
                <div class="flex items-center gap-2 mb-3">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-4 h-4 text-blue-600 dark:text-blue-400">
                        <path d="M3.375 3C2.339 3 1.5 3.84 1.5 4.875v.75c0 1.036.84 1.875 1.875 1.875h17.25c1.035 0 1.875-.84 1.875-1.875v-.75C22.5 3.839 21.66 3 20.625 3H3.375Z"/>
                        <path fill-rule="evenodd" d="m3.087 9 .54 9.176A3 3 0 0 0 6.62 21h10.757a3 3 0 0 0 2.995-2.824L20.913 9H3.087Zm6.163 3.75A.75.75 0 0 1 10 12h4a.75.75 0 0 1 0 1.5h-4a.75.75 0 0 1-.75-.75Z" clip-rule="evenodd"/>
                    </svg>
                    <span class="text-sm font-semibold text-blue-700 dark:text-blue-300">From Inventory</span>
                    <span class="text-xs text-blue-500 dark:text-blue-400">({{ count($dispatchedItems) }} item{{ count($dispatchedItems) !== 1 ? 's' : '' }})</span>
                </div>
                <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-2">
                    @foreach($dispatchedItems as $invItem)
                    <div class="rounded-lg border border-blue-200 dark:border-blue-700 bg-white dark:bg-zinc-900 p-3 flex flex-col gap-2">
                        <div class="text-sm font-semibold text-zinc-900 dark:text-zinc-100">{{ $invItem['name'] }}</div>
                        <div class="text-xs text-zinc-500">{{ $this->formatCurrency($invItem['price']) }} / {{ $invItem['uom'] }}</div>
                        <button type="button" wire:click="addItemToBill({{ $invItem['item_id'] }})"
                            class="mt-auto inline-flex items-center justify-center gap-1 px-3 py-2 rounded-md bg-blue-600 text-white hover:bg-blue-500 text-sm">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-4 h-4"><path fill-rule="evenodd" d="M12 4.5a.75.75 0 0 1 .75.75v6h6a.75.75 0 0 1 0 1.5h-6v6a.75.75 0 0 1-1.5 0v-6h-6a.75.75 0 0 1 0-1.5h6v-6A.75.75 0 0 1 12 4.5Z" clip-rule="evenodd"/></svg>
                            Add
                        </button>
                    </div>
                    @endforeach
                </div>
            </div>
            @endif

            <!-- Products Grid -->
            <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-3">
                @forelse($this->products as $product)
                    <div class="rounded-xl border border-zinc-200 dark:border-zinc-800 p-3 flex flex-col gap-2 bg-white dark:bg-zinc-900">
                        <div class="text-sm font-semibold text-zinc-900 dark:text-zinc-100">{{ $product->name }}</div>
                        <div class="text-xs text-zinc-500">
                            {{ $this->formatCurrency($product->price ?? 0) }}
                            <span class="ml-1 text-[11px] text-zinc-400">/ {{ $product->salesUom?->symbol ?? $product->unitOfMeasure?->symbol ?? 'unit' }}</span>
                        </div>
                        <button type="button" wire:click="addProductToBill('{{ $product->id }}')"
                            class="mt-auto inline-flex items-center justify-center gap-2 px-3 py-2 rounded-md bg-emerald-600 text-white hover:bg-emerald-500 text-sm">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-5 h-5"><path fill-rule="evenodd" d="M12 4.5a.75.75 0 0 1 .75.75v6h6a.75.75 0 0 1 0 1.5h-6v6a.75.75 0 0 1-1.5 0v-6h-6a.75.75 0 0 1 0-1.5h6v-6A.75.75 0 0 1 12 4.5Z" clip-rule="evenodd"/></svg>
                            Add to Bill
                        </button>
                    </div>
                @empty
                    <div class="col-span-full text-center py-10 text-zinc-500 dark:text-zinc-400">
                        <div class="text-sm font-medium">No products found</div>
                        <div class="text-xs mt-1">Try a different search.</div>
                    </div>
                @endforelse
            </div>

            <div class="mt-4">
                {{ $this->products->links() }}
            </div>
        </div>

        <!-- Bill Builder -->
        <div class="col-span-12 lg:col-span-4">
            <div class="sticky top-4 space-y-3">
                <div class="rounded-xl border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900">
                    <div class="p-3 border-b border-zinc-200 dark:border-zinc-800 flex items-center justify-between">
                        <div class="font-semibold text-zinc-900 dark:text-zinc-100">Bill</div>
                        <button type="button" wire:click="clearBill" class="inline-flex items-center gap-1.5 px-2 py-1.5 rounded-md bg-zinc-100 dark:bg-zinc-800 text-zinc-700 dark:text-zinc-200 hover:bg-zinc-200 dark:hover:bg-zinc-700 text-sm">
                            Clear
                        </button>
                    </div>

                    <div class="max-h-[40vh] overflow-auto divide-y divide-zinc-200 dark:divide-zinc-800">
                        @forelse($billItems as $key => $line)
                            <div class="p-3 flex items-center gap-2">
                                <div class="flex-1">
                                    <div class="text-sm font-medium text-zinc-900 dark:text-zinc-100">{{ $line['name'] }}</div>
                                    <div class="text-xs text-zinc-500">
                                        {{ $this->formatCurrency($line['price']) }}
                                        @if(!empty($line['uom']))<span class="text-zinc-400">/ {{ $line['uom'] }}</span>@endif
                                    </div>
                                </div>
                                <div class="flex items-center gap-1">
                                    <button type="button" wire:click="decrementBill('{{ $key }}')" class="inline-flex items-center justify-center w-7 h-8 rounded-md bg-zinc-100 dark:bg-zinc-800 text-zinc-700 dark:text-zinc-200 hover:bg-zinc-200 dark:hover:bg-zinc-700 text-sm">−</button>
                                    <input type="number" min="1"
                                        wire:model.blur="billItems.{{ $key }}.qty"
                                        wire:change="updateBillQty('{{ $key }}', $event.target.value)"
                                        class="w-12 text-center rounded-md border border-zinc-300 dark:border-zinc-700 bg-white dark:bg-zinc-900 text-zinc-900 dark:text-zinc-100 px-1 py-1 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500" />
                                    <button type="button" wire:click="incrementBill('{{ $key }}')" class="inline-flex items-center justify-center w-7 h-8 rounded-md bg-zinc-100 dark:bg-zinc-800 text-zinc-700 dark:text-zinc-200 hover:bg-zinc-200 dark:hover:bg-zinc-700 text-sm">+</button>
                                </div>
                                <div class="w-20 text-right text-sm font-semibold text-zinc-900 dark:text-zinc-100">{{ $this->formatCurrency($line['qty'] * $line['price']) }}</div>
                                <button type="button" wire:click="removeFromBill('{{ $key }}')" class="inline-flex items-center justify-center w-8 h-8 rounded-md bg-rose-600 text-white hover:bg-rose-500">×</button>
                            </div>
                        @empty
                            <div class="p-6 text-center text-sm text-zinc-500">No items selected</div>
                        @endforelse
                    </div>

                    <div class="p-3 space-y-2 border-t border-zinc-200 dark:border-zinc-800">
                        <div class="flex items-center justify-between text-sm text-zinc-700 dark:text-zinc-300">
                            <span>Subtotal</span>
                            <span>{{ $this->formatCurrency($this->billSubtotal) }}</span>
                        </div>
                        <div class="flex items-center justify-between text-sm text-zinc-700 dark:text-zinc-300">
                            <span>Discount</span>
                            <input type="number" step="0.01" wire:model.live="billDiscount" class="w-28 text-right rounded-md border border-zinc-300 dark:border-zinc-700 bg-white dark:bg-zinc-900 text-zinc-900 dark:text-zinc-100 px-2 py-1" />
                        </div>
                        <div class="flex items-center justify-between text-base font-semibold text-zinc-900 dark:text-zinc-100">
                            <span>Total</span>
                            <span>{{ $this->formatCurrency($this->billTotal) }}</span>
                        </div>
                        <button type="button" x-on:click="showBill = true"
                            :disabled="{{ count($billItems) === 0 ? 'true' : 'false' }}"
                            @if(count($billItems) === 0) title="Add items to the bill first" @endif
                            class="w-full inline-flex items-center justify-center gap-2 px-3 py-2 rounded-md bg-blue-600 text-white hover:bg-blue-500 disabled:opacity-50 disabled:cursor-not-allowed text-sm mt-1">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-5 h-5"><path fill-rule="evenodd" d="M7.875 1.5C6.839 1.5 6 2.34 6 3.375v2.99c-.426.053-.851.11-1.274.174-1.454.218-2.476 1.483-2.476 2.917v6.294a3 3 0 0 0 3 3h.27l-.155 1.705A1.875 1.875 0 0 0 7.232 22.5h9.536a1.875 1.875 0 0 0 1.867-2.045l-.155-1.705h.27a3 3 0 0 0 3-3V9.456c0-1.434-1.022-2.7-2.476-2.917A48.716 48.716 0 0 0 18 6.366V3.375c0-1.036-.84-1.875-1.875-1.875h-8.25Zm8.625 4.705v-2.83A.375.375 0 0 0 16.125 3h-8.25a.375.375 0 0 0-.375.375v2.83a49.353 49.353 0 0 1 9 0Zm-.217 8.265c.178.018.317.16.333.337l.526 5.784a.375.375 0 0 1-.374.409H7.232a.375.375 0 0 1-.374-.409l.526-5.784a.373.373 0 0 1 .333-.337 41.741 41.741 0 0 1 8.566 0Z" clip-rule="evenodd"/></svg>
                            Generate Bill
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bill Print Modal -->
    <div x-show="showBill" x-cloak class="fixed inset-0 z-50 flex items-center justify-center">
        <div class="absolute inset-0 bg-black/40" @click="showBill = false"></div>
        <div class="relative w-full max-w-sm mx-auto bg-white shadow-2xl" id="bill-to-print">
            <div class="p-6">
                <div class="text-center mb-4">
                    <div class="text-2xl font-bold">SWEET TOOTH</div>
                    @if(!empty($branchName))
                        <div class="text-xs text-zinc-500">{{ $branchName }}</div>
                    @endif
                    <div class="text-sm font-semibold text-zinc-700 mt-1">BILL</div>
                    <div class="text-xs text-zinc-500 mt-1">{{ now()->format('d M Y, h:i A') }}</div>
                    <div class="text-xs text-zinc-500">Cashier: {{ auth()->user()->name ?? 'N/A' }}</div>
                </div>

                @if(count($billItems) > 0)
                    <div class="border-t-2 border-b-2 border-dashed border-zinc-300 py-3 my-3">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="border-b border-zinc-200">
                                    <th class="text-left pb-1">Item</th>
                                    <th class="text-center pb-1">Qty</th>
                                    <th class="text-right pb-1">Price</th>
                                    <th class="text-right pb-1">Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($billItems as $line)
                                    <tr>
                                        <td class="py-1">{{ $line['name'] ?? 'Item' }}</td>
                                        <td class="text-center">
                                            {{ number_format($line['qty'] ?? 0, 0) }}
                                            @if(!empty($line['uom']))<span class="text-[10px] text-zinc-400">{{ $line['uom'] }}</span>@endif
                                        </td>
                                        <td class="text-right">{{ number_format($line['price'] ?? 0, 2) }}</td>
                                        <td class="text-right font-semibold">{{ number_format(($line['price'] ?? 0) * ($line['qty'] ?? 0), 2) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <div class="space-y-1 text-sm mb-3">
                        <div class="flex justify-between">
                            <span>Subtotal:</span>
                            <span>{{ $this->formatCurrency($this->billSubtotal) }}</span>
                        </div>
                        @if((float) ($billDiscount ?: 0) > 0)
                            <div class="flex justify-between text-orange-600">
                                <span>Discount:</span>
                                <span>-{{ $this->formatCurrency((float) $billDiscount) }}</span>
                            </div>
                        @endif
                        <div class="flex justify-between text-lg font-bold border-t border-zinc-300 pt-1 mt-2">
                            <span>TOTAL:</span>
                            <span>{{ $this->formatCurrency($this->billTotal) }}</span>
                        </div>
                    </div>

                    <div class="text-center text-xs text-zinc-500 border-t border-zinc-200 pt-3">
                        <p class="font-semibold">This is a bill, not a payment receipt.</p>
                        <p class="mt-1">Please proceed to payment.</p>
                    </div>
                @else
                    <div class="text-center py-8 text-zinc-500">
                        <p>Add items to generate a bill</p>
                    </div>
                @endif
            </div>

            <div class="bg-zinc-100 px-4 py-3 flex items-center justify-end gap-2 print:hidden">
                <button type="button" @click="showBill = false" class="inline-flex items-center gap-2 px-3 py-2 rounded-md bg-white text-zinc-700 hover:bg-zinc-200 border border-zinc-300">
                    Close
                </button>
                <button type="button" onclick="window.print()" class="inline-flex items-center gap-2 px-3 py-2 rounded-md bg-blue-600 text-white hover:bg-blue-500">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-5 h-5"><path fill-rule="evenodd" d="M7.875 1.5C6.839 1.5 6 2.34 6 3.375v2.99c-.426.053-.851.11-1.274.174-1.454.218-2.476 1.483-2.476 2.917v6.294a3 3 0 0 0 3 3h.27l-.155 1.705A1.875 1.875 0 0 0 7.232 22.5h9.536a1.875 1.875 0 0 0 1.867-2.045l-.155-1.705h.27a3 3 0 0 0 3-3V9.456c0-1.434-1.022-2.7-2.476-2.917A48.716 48.716 0 0 0 18 6.366V3.375c0-1.036-.84-1.875-1.875-1.875h-8.25ZM16.5 6.205v-2.83A.375.375 0 0 0 16.125 3h-8.25a.375.375 0 0 0-.375.375v2.83a49.353 49.353 0 0 1 9 0Zm-.217 8.265c.178.018.317.16.333.337l.526 5.784a.375.375 0 0 1-.374.409H7.232a.375.375 0 0 1-.374-.409l.526-5.784a.373.373 0 0 1 .333-.337 41.741 41.741 0 0 1 8.566 0Zm.967-3.97a.75.75 0 0 1 .75-.75h.008a.75.75 0 0 1 .75.75v.008a.75.75 0 0 1-.75.75H18a.75.75 0 0 1-.75-.75V10.5ZM15 9.75a.75.75 0 0 0-.75.75v.008c0 .414.336.75.75.75h.008a.75.75 0 0 0 .75-.75V10.5a.75.75 0 0 0-.75-.75H15Z" clip-rule="evenodd"/></svg>
                    Print Bill
                </button>
            </div>
        </div>
    </div>

    <style>
        @media print {
            body * {
                visibility: hidden;
            }
            #bill-to-print, #bill-to-print * {
                visibility: visible;
            }
            #bill-to-print {
                position: absolute;
                left: 0;
                top: 0;
                width: 80mm;
            }
            .print\:hidden {
                display: none !important;
            }
        }
    </style>
</div>
