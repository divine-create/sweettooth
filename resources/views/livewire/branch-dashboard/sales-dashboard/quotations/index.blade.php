<div class="p-3 space-y-3">
    <x-breadcrumb title="Quotations" :items="[
        ['label' => 'Dashboard', 'url' => branch_route('branch-dashboard.index')],
        ['label' => 'Sales Dashboard'],
        ['label' => 'Quotations'],
    ]" :compact="false" :with-icons="true" />

    <!-- Header -->
    <div class="bg-gradient-to-r from-indigo-600 to-indigo-700 rounded-lg p-4 text-white shadow-lg">
        <div class="flex justify-between items-center">
            <div>
                <h2 class="text-xl font-bold">Quotations / Draft Orders</h2>
                <p class="text-sm opacity-90 mt-1">
                    Saved price quotes — convert one to a sale at the POS when the customer confirms.
                </p>
            </div>
            @php
                $deptSlug = $salesDeptSlug ?? request()->query('salesDeptSlug');
            @endphp
            <a href="{{ branch_route('branch-dashboard.sales-dashboard.pos.index', [
                    'salesDeptSlug' => $deptSlug,
                    'page' => $deptSlug ? 'POS_' . $deptSlug : null,
                ]) }}" wire:navigate
                class="px-4 py-2 bg-white text-indigo-600 rounded-lg font-medium hover:bg-indigo-50 transition-colors">
                Go to POS
            </a>
        </div>
    </div>

    <!-- Filters -->
    <div class="bg-white dark:bg-zinc-800 rounded-lg p-3 shadow-sm border border-zinc-200 dark:border-zinc-700 flex flex-col sm:flex-row gap-3 sm:items-center">
        <div class="flex-1">
            <input type="text" wire:model.live.debounce.400ms="search" placeholder="Search number, customer, phone…"
                class="w-full rounded-lg border-zinc-300 dark:border-zinc-600 dark:bg-zinc-900 text-sm" />
        </div>
        <div>
            <select wire:model.live="statusFilter"
                class="rounded-lg border-zinc-300 dark:border-zinc-600 dark:bg-zinc-900 text-sm">
                <option value="open">Open</option>
                <option value="all">All</option>
                <option value="draft">Draft</option>
                <option value="sent">Sent</option>
                <option value="accepted">Accepted</option>
                <option value="converted">Converted</option>
                <option value="cancelled">Cancelled</option>
            </select>
        </div>
    </div>

    <!-- Table -->
    <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-sm border border-zinc-200 dark:border-zinc-700 overflow-x-auto">
        <table class="min-w-full text-sm">
            <thead class="bg-zinc-50 dark:bg-zinc-900/50 text-zinc-600 dark:text-zinc-300">
                <tr>
                    <th class="text-left px-4 py-3 font-medium">Quotation #</th>
                    <th class="text-left px-4 py-3 font-medium">Customer</th>
                    <th class="text-right px-4 py-3 font-medium">Items</th>
                    <th class="text-right px-4 py-3 font-medium">Total</th>
                    <th class="text-left px-4 py-3 font-medium">Valid until</th>
                    <th class="text-left px-4 py-3 font-medium">Status</th>
                    <th class="text-right px-4 py-3 font-medium">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-100 dark:divide-zinc-700">
                @forelse($quotations as $q)
                    @php
                        $display = $q->isExpired() ? 'expired' : $q->status;
                        $badge = [
                            'draft' => 'bg-zinc-100 text-zinc-700',
                            'sent' => 'bg-blue-100 text-blue-700',
                            'accepted' => 'bg-amber-100 text-amber-700',
                            'converted' => 'bg-green-100 text-green-700',
                            'cancelled' => 'bg-red-100 text-red-700',
                            'expired' => 'bg-orange-100 text-orange-700',
                        ][$display] ?? 'bg-zinc-100 text-zinc-700';
                    @endphp
                    <tr class="text-zinc-800 dark:text-zinc-100">
                        <td class="px-4 py-3 font-mono">{{ $q->quotation_number }}</td>
                        <td class="px-4 py-3">
                            <div>{{ $q->customer_name ?: '—' }}</div>
                            @if($q->customer_phone)
                                <div class="text-xs text-zinc-500">{{ $q->customer_phone }}</div>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-right">{{ $q->items->count() }}</td>
                        <td class="px-4 py-3 text-right">₦{{ number_format((float) $q->total, 2) }}</td>
                        <td class="px-4 py-3">{{ $q->valid_until ? $q->valid_until->format('d M Y') : '—' }}</td>
                        <td class="px-4 py-3">
                            <span class="px-2 py-1 rounded-full text-xs font-medium {{ $badge }}">{{ ucfirst($display) }}</span>
                        </td>
                        <td class="px-4 py-3">
                            <div class="flex justify-end gap-2">
                                <button wire:click="view({{ $q->id }})"
                                    class="px-2 py-1 text-xs rounded bg-zinc-100 dark:bg-zinc-700 hover:bg-zinc-200">View</button>
                                @if($q->isOpen())
                                    <button wire:click="convertToSale({{ $q->id }})"
                                        class="px-2 py-1 text-xs rounded bg-indigo-600 text-white hover:bg-indigo-700">Convert to sale</button>
                                    <button wire:click="cancelQuotation({{ $q->id }})"
                                        wire:confirm="Cancel this quotation?"
                                        class="px-2 py-1 text-xs rounded bg-red-50 text-red-600 hover:bg-red-100">Cancel</button>
                                @elseif($q->status === 'converted' && $q->converted_sale_id)
                                    <span class="px-2 py-1 text-xs text-green-600">Sale #{{ $q->converted_sale_id }}</span>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-4 py-10 text-center text-zinc-500">No quotations found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div>{{ $quotations->links() }}</div>

    <!-- View / Print modal -->
    @if($this->viewing)
        @php $v = $this->viewing; @endphp
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4 print:static print:bg-transparent print:p-0">
            <div id="quote-print" class="bg-white dark:bg-zinc-800 rounded-lg shadow-xl w-full max-w-lg max-h-[90vh] overflow-y-auto print:max-w-full print:shadow-none">
                <div class="p-5 space-y-4">
                    <div class="flex justify-between items-start border-b border-zinc-200 dark:border-zinc-700 pb-3">
                        <div>
                            <h3 class="text-lg font-bold text-zinc-900 dark:text-zinc-100">Quotation</h3>
                            <p class="font-mono text-sm text-zinc-600 dark:text-zinc-300">{{ $v->quotation_number }}</p>
                        </div>
                        <button wire:click="closeView" class="text-zinc-400 hover:text-zinc-600 print:hidden text-2xl leading-none">&times;</button>
                    </div>

                    <div class="grid grid-cols-2 gap-2 text-sm">
                        <div><span class="text-zinc-500">Customer:</span> {{ $v->customer_name ?: '—' }}</div>
                        <div><span class="text-zinc-500">Phone:</span> {{ $v->customer_phone ?: '—' }}</div>
                        <div><span class="text-zinc-500">Date:</span> {{ $v->created_at->format('d M Y') }}</div>
                        <div><span class="text-zinc-500">Valid until:</span> {{ $v->valid_until ? $v->valid_until->format('d M Y') : '—' }}</div>
                    </div>

                    <table class="w-full text-sm border-t border-zinc-200 dark:border-zinc-700">
                        <thead>
                            <tr class="text-zinc-500 text-left">
                                <th class="py-2">Item</th>
                                <th class="py-2 text-right">Qty</th>
                                <th class="py-2 text-right">Price</th>
                                <th class="py-2 text-right">Subtotal</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-zinc-100 dark:divide-zinc-700">
                            @foreach($v->items as $line)
                                <tr>
                                    <td class="py-2">{{ $line->name }}</td>
                                    <td class="py-2 text-right">{{ rtrim(rtrim(number_format((float) $line->quantity, 3), '0'), '.') }}</td>
                                    <td class="py-2 text-right">₦{{ number_format((float) $line->unit_price, 2) }}</td>
                                    <td class="py-2 text-right">₦{{ number_format((float) $line->subtotal, 2) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>

                    <div class="space-y-1 text-sm border-t border-zinc-200 dark:border-zinc-700 pt-3">
                        <div class="flex justify-between"><span class="text-zinc-500">Subtotal</span><span>₦{{ number_format((float) $v->subtotal, 2) }}</span></div>
                        <div class="flex justify-between"><span class="text-zinc-500">Discount</span><span>₦{{ number_format((float) $v->discount, 2) }}</span></div>
                        <div class="flex justify-between font-bold text-base"><span>Total</span><span>₦{{ number_format((float) $v->total, 2) }}</span></div>
                    </div>

                    @if($v->notes)
                        <div class="text-sm text-zinc-600 dark:text-zinc-300"><span class="text-zinc-500">Notes:</span> {{ $v->notes }}</div>
                    @endif

                    <div class="flex justify-end gap-2 pt-2 print:hidden">
                        <button onclick="window.print()" class="px-3 py-2 text-sm rounded bg-zinc-100 dark:bg-zinc-700 hover:bg-zinc-200">Print</button>
                        @if($v->isOpen())
                            <button wire:click="convertToSale({{ $v->id }})"
                                class="px-3 py-2 text-sm rounded bg-indigo-600 text-white hover:bg-indigo-700">Convert to sale</button>
                        @endif
                        <button wire:click="closeView" class="px-3 py-2 text-sm rounded bg-zinc-200 dark:bg-zinc-600 hover:bg-zinc-300">Close</button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
