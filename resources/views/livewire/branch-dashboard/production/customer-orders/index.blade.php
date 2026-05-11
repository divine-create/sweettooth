<div>
    <div class="p-6 space-y-6">

        {{-- Header --}}
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                <h1 class="text-xl font-bold text-zinc-900 dark:text-zinc-100">Customer Orders</h1>
                <p class="text-sm text-zinc-500 dark:text-zinc-400">Orders placed by customers via the public order page.</p>
            </div>

            {{-- Copy Order Link --}}
            <div x-data="{ copied: false }">
                <button
                    @click="
                        navigator.clipboard.writeText('{{ $orderLink }}');
                        copied = true;
                        setTimeout(() => copied = false, 2000);
                    "
                    class="flex items-center gap-2 px-4 py-2 bg-white dark:bg-zinc-800 border border-zinc-300 dark:border-zinc-600 rounded-lg text-sm font-medium text-zinc-700 dark:text-zinc-200 hover:bg-zinc-50 dark:hover:bg-zinc-700 transition"
                >
                    <svg x-show="!copied" class="w-4 h-4 text-zinc-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                    </svg>
                    <svg x-show="copied" class="w-4 h-4 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                    </svg>
                    <span x-text="copied ? 'Link Copied!' : 'Copy Order Link'"></span>
                </button>
            </div>
        </div>

        {{-- Status Summary Cards --}}
        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-3">
            @foreach($statuses as $statusEnum)
            @php $count = $statusCounts[$statusEnum->value] ?? 0; @endphp
            <button wire:click="$set('statusFilter', '{{ $statusFilter === $statusEnum->value ? 'all' : $statusEnum->value }}')"
                    class="text-left p-3 rounded-xl border transition
                           {{ $statusFilter === $statusEnum->value
                               ? 'border-current ring-2 ring-current ring-opacity-30'
                               : 'bg-white dark:bg-zinc-800 border-zinc-200 dark:border-zinc-700 hover:border-zinc-300' }}
                           {{ $statusEnum->badgeClass() }}">
                <p class="text-xs font-medium">{{ $statusEnum->label() }}</p>
                <p class="text-2xl font-bold mt-1">{{ $count }}</p>
            </button>
            @endforeach
        </div>

        {{-- Filters --}}
        <div class="flex flex-col sm:flex-row gap-3">
            <div class="relative flex-1 max-w-sm">
                <svg class="w-4 h-4 text-zinc-400 absolute left-3 top-1/2 -translate-y-1/2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                </svg>
                <input wire:model.live.debounce.300ms="search"
                       type="text"
                       placeholder="Search by name, email, phone, or order #…"
                       class="pl-9 pr-4 py-2 w-full border border-zinc-300 dark:border-zinc-600 rounded-lg text-sm bg-white dark:bg-zinc-800 text-zinc-900 dark:text-zinc-100 focus:outline-none focus:ring-2 focus:ring-emerald-500">
            </div>
            <select wire:model.live="statusFilter"
                    class="border border-zinc-300 dark:border-zinc-600 rounded-lg px-3 py-2 text-sm bg-white dark:bg-zinc-800 text-zinc-900 dark:text-zinc-100 focus:outline-none focus:ring-2 focus:ring-emerald-500">
                <option value="all">All Statuses</option>
                @foreach($statuses as $s)
                    <option value="{{ $s->value }}">{{ $s->label() }}</option>
                @endforeach
            </select>
        </div>

        {{-- Orders Table --}}
        <div class="bg-white dark:bg-zinc-800 rounded-xl border border-zinc-200 dark:border-zinc-700 overflow-hidden">
            <table class="w-full text-sm">
                <thead class="border-b border-zinc-200 dark:border-zinc-700 bg-zinc-50 dark:bg-zinc-900">
                    <tr>
                        <th class="text-left px-4 py-3 text-xs font-semibold text-zinc-500 uppercase tracking-wide">Order #</th>
                        <th class="text-left px-4 py-3 text-xs font-semibold text-zinc-500 uppercase tracking-wide">Customer</th>
                        <th class="text-left px-4 py-3 text-xs font-semibold text-zinc-500 uppercase tracking-wide hidden md:table-cell">Items</th>
                        <th class="text-left px-4 py-3 text-xs font-semibold text-zinc-500 uppercase tracking-wide hidden sm:table-cell">Total</th>
                        <th class="text-left px-4 py-3 text-xs font-semibold text-zinc-500 uppercase tracking-wide">Status</th>
                        <th class="text-left px-4 py-3 text-xs font-semibold text-zinc-500 uppercase tracking-wide hidden lg:table-cell">Date</th>
                        <th class="text-right px-4 py-3 text-xs font-semibold text-zinc-500 uppercase tracking-wide">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-100 dark:divide-zinc-700">
                    @forelse($orders as $order)
                    <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-700/50 transition">
                        <td class="px-4 py-3 font-mono text-xs font-medium text-zinc-900 dark:text-zinc-100">
                            {{ $order->order_number }}
                        </td>
                        <td class="px-4 py-3">
                            <p class="font-medium text-zinc-900 dark:text-zinc-100">{{ $order->customer_name }}</p>
                            <p class="text-xs text-zinc-500">{{ $order->customer_phone }}</p>
                        </td>
                        <td class="px-4 py-3 hidden md:table-cell text-zinc-600 dark:text-zinc-400">
                            {{ $order->items->count() }} item(s)
                        </td>
                        <td class="px-4 py-3 hidden sm:table-cell font-semibold text-zinc-900 dark:text-zinc-100">
                            {{ number_format((float)$order->total_amount, 2) }}
                        </td>
                        <td class="px-4 py-3">
                            <span class="px-2 py-1 rounded-full text-xs font-medium {{ $order->status->badgeClass() }}">
                                {{ $order->status->label() }}
                            </span>
                        </td>
                        <td class="px-4 py-3 hidden lg:table-cell text-xs text-zinc-500">
                            {{ $order->created_at->format('M d, Y H:i') }}
                        </td>
                        <td class="px-4 py-3 text-right">
                            <div class="flex items-center justify-end gap-2">
                                <button wire:click="openDetail({{ $order->id }})"
                                        class="px-3 py-1.5 text-xs font-medium bg-zinc-100 dark:bg-zinc-700 text-zinc-700 dark:text-zinc-200 rounded-lg hover:bg-zinc-200 dark:hover:bg-zinc-600 transition">
                                    View
                                </button>
                                @if($order->status->canApprove())
                                <button wire:click="approveOrder({{ $order->id }})"
                                        wire:confirm="Approve order {{ $order->order_number }} and create production requests?"
                                        class="px-3 py-1.5 text-xs font-medium bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg transition">
                                    Approve
                                </button>
                                @endif
                                @if($order->status->canReject())
                                <button wire:click="openRejectModal({{ $order->id }})"
                                        class="px-3 py-1.5 text-xs font-medium bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-300 rounded-lg hover:bg-red-200 dark:hover:bg-red-900/50 transition">
                                    Reject
                                </button>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="px-4 py-12 text-center text-zinc-500">
                            <svg class="w-10 h-10 text-zinc-300 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                            No orders found.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>

            @if($orders->hasPages())
            <div class="px-4 py-3 border-t border-zinc-200 dark:border-zinc-700">
                {{ $orders->links() }}
            </div>
            @endif
        </div>
    </div>

    {{-- Detail Modal --}}
    @if($showDetailModal && $selectedOrder)
    <div class="fixed inset-0 z-50 overflow-y-auto" x-data>
        <div class="fixed inset-0 bg-black/50" wire:click="closeDetail"></div>
        <div class="relative min-h-screen flex items-center justify-center p-4">
            <div class="relative bg-white dark:bg-zinc-800 rounded-2xl shadow-xl w-full max-w-2xl">

                {{-- Modal Header --}}
                <div class="flex items-center justify-between px-6 py-4 border-b border-zinc-200 dark:border-zinc-700">
                    <div>
                        <h3 class="text-lg font-bold text-zinc-900 dark:text-zinc-100">{{ $selectedOrder->order_number }}</h3>
                        <p class="text-sm text-zinc-500">Placed {{ $selectedOrder->created_at->diffForHumans() }}</p>
                    </div>
                    <span class="px-3 py-1 rounded-full text-sm font-medium {{ $selectedOrder->status->badgeClass() }}">
                        {{ $selectedOrder->status->label() }}
                    </span>
                </div>

                {{-- Modal Body --}}
                <div class="px-6 py-4 space-y-5">

                    {{-- Customer Info --}}
                    <div class="grid grid-cols-3 gap-4">
                        <div>
                            <p class="text-xs text-zinc-500 font-medium">Customer</p>
                            <p class="text-sm text-zinc-900 dark:text-zinc-100 mt-1 font-semibold">{{ $selectedOrder->customer_name }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-zinc-500 font-medium">Email</p>
                            <p class="text-sm text-zinc-900 dark:text-zinc-100 mt-1">{{ $selectedOrder->customer_email }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-zinc-500 font-medium">Phone</p>
                            <p class="text-sm text-zinc-900 dark:text-zinc-100 mt-1">{{ $selectedOrder->customer_phone }}</p>
                        </div>
                    </div>

                    @if($selectedOrder->notes)
                    <div>
                        <p class="text-xs text-zinc-500 font-medium">Customer Notes</p>
                        <p class="text-sm text-zinc-700 dark:text-zinc-300 mt-1 bg-zinc-50 dark:bg-zinc-900 rounded-lg px-3 py-2">{{ $selectedOrder->notes }}</p>
                    </div>
                    @endif

                    {{-- Items --}}
                    <div>
                        <p class="text-xs text-zinc-500 font-medium mb-2">Order Items</p>
                        <div class="border border-zinc-200 dark:border-zinc-700 rounded-xl overflow-hidden">
                            <table class="w-full text-sm">
                                <thead class="bg-zinc-50 dark:bg-zinc-900">
                                    <tr>
                                        <th class="text-left px-4 py-2 text-xs font-semibold text-zinc-500">Product</th>
                                        <th class="text-right px-4 py-2 text-xs font-semibold text-zinc-500">Qty</th>
                                        <th class="text-right px-4 py-2 text-xs font-semibold text-zinc-500">Unit Price</th>
                                        <th class="text-right px-4 py-2 text-xs font-semibold text-zinc-500">Subtotal</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-zinc-100 dark:divide-zinc-700">
                                    @foreach($selectedOrder->items as $item)
                                    <tr>
                                        <td class="px-4 py-2 text-zinc-900 dark:text-zinc-100">{{ $item->product?->name ?? '—' }}</td>
                                        <td class="px-4 py-2 text-right text-zinc-700 dark:text-zinc-300">{{ number_format((float)$item->quantity, 0) }}</td>
                                        <td class="px-4 py-2 text-right text-zinc-700 dark:text-zinc-300">{{ number_format((float)$item->unit_price, 2) }}</td>
                                        <td class="px-4 py-2 text-right font-semibold text-zinc-900 dark:text-zinc-100">{{ number_format((float)$item->subtotal, 2) }}</td>
                                    </tr>
                                    @endforeach
                                </tbody>
                                <tfoot class="border-t border-zinc-200 dark:border-zinc-700 bg-zinc-50 dark:bg-zinc-900">
                                    <tr>
                                        <td colspan="3" class="px-4 py-2 text-right text-sm font-semibold text-zinc-700 dark:text-zinc-300">Total</td>
                                        <td class="px-4 py-2 text-right font-bold text-zinc-900 dark:text-zinc-100">{{ number_format((float)$selectedOrder->total_amount, 2) }}</td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>

                    {{-- Dispatch Progress --}}
                    @if(in_array($selectedOrder->status->value, ['approved', 'in_production', 'completed']))
                    <div>
                        <p class="text-xs text-zinc-500 font-medium mb-2">Dispatch Progress</p>
                        <div class="space-y-2">
                            @foreach($selectedOrder->items as $item)
                            @php
                                $dispatched = $selectedOrder->dispatches
                                    ->where('product_id', $item->product_id)
                                    ->sum('quantity');
                                $needed = (float) $item->quantity;
                                $pct = $needed > 0 ? min(100, round(($dispatched / $needed) * 100)) : 0;
                                $fulfilled = $dispatched >= $needed;
                            @endphp
                            <div class="bg-zinc-50 dark:bg-zinc-900 rounded-lg px-3 py-2">
                                <div class="flex justify-between items-center mb-1">
                                    <span class="text-sm font-medium text-zinc-800 dark:text-zinc-200">{{ $item->product?->name }}</span>
                                    <span class="text-xs font-semibold {{ $fulfilled ? 'text-emerald-600' : 'text-zinc-500' }}">
                                        {{ number_format($dispatched, 0) }} / {{ number_format($needed, 0) }}
                                        @if($fulfilled) ✓ @endif
                                    </span>
                                </div>
                                <div class="w-full bg-zinc-200 dark:bg-zinc-700 rounded-full h-1.5">
                                    <div class="h-1.5 rounded-full {{ $fulfilled ? 'bg-emerald-500' : 'bg-blue-500' }}"
                                         style="width: {{ $pct }}%"></div>
                                </div>
                            </div>
                            @endforeach
                        </div>
                    </div>
                    @endif

                    @if($selectedOrder->rejection_reason)
                    <div class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-xl px-4 py-3">
                        <p class="text-xs font-semibold text-red-700 dark:text-red-300">Rejection Reason</p>
                        <p class="text-sm text-red-700 dark:text-red-300 mt-1">{{ $selectedOrder->rejection_reason }}</p>
                    </div>
                    @endif
                </div>

                {{-- Modal Footer --}}
                <div class="px-6 py-4 border-t border-zinc-200 dark:border-zinc-700 flex flex-wrap justify-end gap-3 bg-zinc-50 dark:bg-zinc-900 rounded-b-2xl">
                    @if($selectedOrder->status->canApprove())
                    <button wire:click="approveOrder({{ $selectedOrder->id }})"
                            wire:confirm="Approve this order and create production requests?"
                            class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-semibold rounded-lg transition">
                        Approve &amp; Create Production Request
                    </button>
                    @endif
                    @if($selectedOrder->status->canReject())
                    <button wire:click="openRejectModal({{ $selectedOrder->id }})"
                            class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white text-sm font-semibold rounded-lg transition">
                        Reject
                    </button>
                    @endif
                    <button wire:click="closeDetail"
                            class="px-4 py-2 bg-zinc-200 dark:bg-zinc-700 text-zinc-800 dark:text-zinc-200 text-sm font-medium rounded-lg hover:bg-zinc-300 dark:hover:bg-zinc-600 transition">
                        Close
                    </button>
                </div>
            </div>
        </div>
    </div>
    @endif

    {{-- Reject Modal --}}
    @if($showRejectModal)
    <div class="fixed inset-0 z-50 overflow-y-auto">
        <div class="fixed inset-0 bg-black/50" wire:click="closeRejectModal"></div>
        <div class="relative min-h-screen flex items-center justify-center p-4">
            <div class="relative bg-white dark:bg-zinc-800 rounded-2xl shadow-xl w-full max-w-md">
                <div class="px-6 py-4 border-b border-zinc-200 dark:border-zinc-700">
                    <h3 class="text-lg font-bold text-zinc-900 dark:text-zinc-100">Reject Order</h3>
                    <p class="text-sm text-zinc-500">Provide a reason so the customer can be informed.</p>
                </div>
                <div class="px-6 py-4">
                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2">Reason *</label>
                    <textarea wire:model="rejectionReason" rows="3"
                              placeholder="e.g. Product unavailable for the requested quantity…"
                              class="w-full border border-zinc-300 dark:border-zinc-600 rounded-lg px-3 py-2 text-sm bg-white dark:bg-zinc-900 text-zinc-900 dark:text-zinc-100 focus:outline-none focus:ring-2 focus:ring-red-500 resize-none"></textarea>
                    @error('rejectionReason') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                </div>
                <div class="px-6 py-4 border-t border-zinc-200 dark:border-zinc-700 flex justify-end gap-3 bg-zinc-50 dark:bg-zinc-900 rounded-b-2xl">
                    <button wire:click="closeRejectModal"
                            class="px-4 py-2 bg-zinc-200 dark:bg-zinc-700 text-zinc-800 dark:text-zinc-200 text-sm font-medium rounded-lg hover:bg-zinc-300 transition">
                        Cancel
                    </button>
                    <button wire:click="confirmReject"
                            class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white text-sm font-semibold rounded-lg transition">
                        Confirm Rejection
                    </button>
                </div>
            </div>
        </div>
    </div>
    @endif
</div>
