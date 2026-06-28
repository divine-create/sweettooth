<div class="p-3 space-y-3">

    <x-breadcrumb
        title="Store Transfers"
        :items="[
            ['label' => 'Dashboard', 'url' => branch_route('branch-dashboard.index')],
            ['label' => 'Production'],
            ['label' => 'Store Transfers']
        ]"
        :compact="false"
        :with-icons="true"/>

    @if(!$store)
        <div class="bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-lg p-6 text-center">
            <h3 class="text-lg font-semibold text-yellow-800 dark:text-yellow-200">No Production Store Found</h3>
            <p class="text-yellow-700 dark:text-yellow-300 mt-1">
                This department doesn't have a Production Store yet.
            </p>
        </div>
    @else
        <!-- Tabs -->
        <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-sm border border-zinc-200 dark:border-zinc-700 p-4">
            <div class="flex gap-2">
                <button wire:click="setTab('incoming')"
                        class="px-4 py-2 rounded-lg font-medium transition {{ $tab === 'incoming' ? 'bg-blue-600 text-white' : 'bg-zinc-100 dark:bg-zinc-700 text-zinc-600 dark:text-zinc-300 hover:bg-zinc-200 dark:hover:bg-zinc-600' }}">
                    Incoming
                    @if($counts['incoming'] > 0)
                        <span class="ml-1 px-2 py-0.5 text-xs rounded-full bg-amber-500 text-white">{{ $counts['incoming'] }}</span>
                    @endif
                </button>
                <button wire:click="setTab('sent')"
                        class="px-4 py-2 rounded-lg font-medium transition {{ $tab === 'sent' ? 'bg-blue-600 text-white' : 'bg-zinc-100 dark:bg-zinc-700 text-zinc-600 dark:text-zinc-300 hover:bg-zinc-200 dark:hover:bg-zinc-600' }}">
                    Sent
                    @if($counts['sent'] > 0)
                        <span class="ml-1 px-2 py-0.5 text-xs rounded-full bg-amber-500 text-white">{{ $counts['sent'] }}</span>
                    @endif
                </button>
                <button wire:click="setTab('all')"
                        class="px-4 py-2 rounded-lg font-medium transition {{ $tab === 'all' ? 'bg-blue-600 text-white' : 'bg-zinc-100 dark:bg-zinc-700 text-zinc-600 dark:text-zinc-300 hover:bg-zinc-200 dark:hover:bg-zinc-600' }}">
                    All
                </button>
            </div>
        </div>

        <!-- Table -->
        @if($rows->isEmpty())
            <div class="bg-zinc-50 dark:bg-zinc-800/50 rounded-lg p-8 text-center">
                <h3 class="text-lg font-semibold text-zinc-600 dark:text-zinc-400">No transfers</h3>
                <p class="text-zinc-500 dark:text-zinc-500 mt-1">
                    @if($tab === 'incoming')
                        No raw materials have been transferred to this store.
                    @elseif($tab === 'sent')
                        This store hasn't transferred any raw materials out.
                    @else
                        No transfers involve this store yet.
                    @endif
                </p>
            </div>
        @else
            <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-sm border border-zinc-200 dark:border-zinc-700 overflow-hidden">
                <table class="w-full">
                    <thead class="bg-zinc-50 dark:bg-zinc-700 border-b border-zinc-200 dark:border-zinc-600">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-zinc-500 dark:text-zinc-400 uppercase">Item</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-zinc-500 dark:text-zinc-400 uppercase">From → To</th>
                            <th class="px-4 py-3 text-right text-xs font-semibold text-zinc-500 dark:text-zinc-400 uppercase">Sent</th>
                            <th class="px-4 py-3 text-right text-xs font-semibold text-zinc-500 dark:text-zinc-400 uppercase">Received</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-zinc-500 dark:text-zinc-400 uppercase">Status</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-zinc-500 dark:text-zinc-400 uppercase">When</th>
                            <th class="px-4 py-3 text-right text-xs font-semibold text-zinc-500 dark:text-zinc-400 uppercase">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                        @foreach($rows as $t)
                            @php
                                $isIncoming = $store && $t->to_store_id === $store->id;
                                $isOutgoing = $store && $t->from_store_id === $store->id;
                                $variance = $t->variance;
                            @endphp
                            <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-700/50">
                                <td class="px-4 py-3">
                                    <div class="font-medium text-zinc-900 dark:text-zinc-100">{{ $t->item?->name ?? 'Unknown item' }}</div>
                                    @if($t->notes)
                                        <div class="text-xs text-zinc-500 dark:text-zinc-400">{{ $t->notes }}</div>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-sm text-zinc-600 dark:text-zinc-300">
                                    {{ $t->fromDepartment?->name ?? '—' }}
                                    <span class="text-zinc-400">→</span>
                                    {{ $t->toDepartment?->name ?? '—' }}
                                </td>
                                <td class="px-4 py-3 text-right font-medium text-zinc-900 dark:text-zinc-100">
                                    {{ rtrim(rtrim(number_format($t->quantity,2),'0'),'.') }} {{ $t->uom }}
                                </td>
                                <td class="px-4 py-3 text-right text-zinc-600 dark:text-zinc-300">
                                    @if($t->received_quantity !== null)
                                        {{ rtrim(rtrim(number_format($t->received_quantity,2),'0'),'.') }} {{ $t->uom }}
                                        @if($variance !== null && abs($variance) > 0.001)
                                            <div class="text-xs text-red-600">({{ $variance > 0 ? '+' : '' }}{{ rtrim(rtrim(number_format($variance,2),'0'),'.') }})</div>
                                        @endif
                                    @else
                                        <span class="text-zinc-400">—</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3">
                                    <span class="px-2 py-1 text-xs rounded-full
                                        @if($t->status === 'pending_receipt') bg-amber-100 text-amber-800 dark:bg-amber-900 dark:text-amber-200
                                        @elseif($t->status === 'received') bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200
                                        @else bg-zinc-100 text-zinc-700 dark:bg-zinc-700 dark:text-zinc-300 @endif">
                                        {{ $t->status_label }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-sm text-zinc-500 dark:text-zinc-400">
                                    {{ $t->sent_at ? \Carbon\Carbon::parse($t->sent_at)->format('M d, Y H:i') : $t->created_at?->format('M d, Y H:i') }}
                                </td>
                                <td class="px-4 py-3 text-right">
                                    <div class="flex items-center justify-end gap-2">
                                        @if($t->status === 'pending_receipt' && $isIncoming)
                                            <button wire:click="openConfirmModal({{ $t->id }})"
                                                    class="px-3 py-1.5 text-xs font-medium rounded-lg bg-teal-600 hover:bg-teal-700 text-white">
                                                Confirm receipt
                                            </button>
                                            <button wire:click="rejectTransfer({{ $t->id }})"
                                                    wire:confirm="Reject this transfer and return the stock to the sender?"
                                                    class="px-3 py-1.5 text-xs font-medium rounded-lg border border-red-300 text-red-700 dark:text-red-300 hover:bg-red-50 dark:hover:bg-red-900/30">
                                                Reject
                                            </button>
                                        @elseif($t->status === 'pending_receipt' && $isOutgoing)
                                            <button wire:click="cancelTransfer({{ $t->id }})"
                                                    wire:confirm="Cancel this transfer and return the stock to your store?"
                                                    class="px-3 py-1.5 text-xs font-medium rounded-lg border border-zinc-300 dark:border-zinc-600 text-zinc-700 dark:text-zinc-200 hover:bg-zinc-50 dark:hover:bg-zinc-700">
                                                Cancel
                                            </button>
                                        @else
                                            <span class="text-xs text-zinc-400">—</span>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div>{{ $rows->links() }}</div>
        @endif
    @endif

    @if($showConfirmModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div class="absolute inset-0 bg-black/50" wire:click="closeConfirmModal"></div>
            <div class="relative bg-white dark:bg-zinc-800 rounded-xl shadow-xl border border-zinc-200 dark:border-zinc-700 w-full max-w-md p-5">
                <div class="flex items-start justify-between mb-4">
                    <div>
                        <h3 class="text-lg font-semibold text-zinc-900 dark:text-zinc-100">Confirm Receipt</h3>
                        <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ $confirmItemName }}</p>
                    </div>
                    <button wire:click="closeConfirmModal" class="text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-200">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>

                <div class="space-y-4">
                    <div class="text-sm text-zinc-600 dark:text-zinc-300">
                        Sent quantity:
                        <span class="font-semibold text-zinc-900 dark:text-white">{{ rtrim(rtrim(number_format($confirmSent,2),'0'),'.') }} {{ $confirmUom }}</span>
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-zinc-500 dark:text-zinc-400 mb-1">Quantity received ({{ $confirmUom }})</label>
                        <input type="number" step="0.01" min="0" max="{{ $confirmSent }}" wire:model="confirmReceivedQuantity"
                               class="w-full rounded-lg border-zinc-300 dark:border-zinc-600 dark:bg-zinc-900 text-sm" />
                        @error('confirmReceivedQuantity') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
                        <p class="text-xs text-zinc-500 dark:text-zinc-400 mt-1">
                            If you received less than was sent, the shortfall is recorded as a transit loss.
                        </p>
                    </div>
                </div>

                <div class="flex justify-end gap-2 mt-6">
                    <button wire:click="closeConfirmModal"
                            class="px-4 py-2 rounded-lg border border-zinc-300 dark:border-zinc-600 text-sm text-zinc-700 dark:text-zinc-200 hover:bg-zinc-50 dark:hover:bg-zinc-700">
                        Cancel
                    </button>
                    <button wire:click="confirmReceipt"
                            wire:loading.attr="disabled" wire:target="confirmReceipt"
                            class="px-4 py-2 rounded-lg bg-teal-600 hover:bg-teal-700 text-white text-sm font-medium disabled:opacity-50 disabled:cursor-not-allowed">
                        <span wire:loading.remove wire:target="confirmReceipt">Confirm</span>
                        <span wire:loading wire:target="confirmReceipt">Saving…</span>
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>
