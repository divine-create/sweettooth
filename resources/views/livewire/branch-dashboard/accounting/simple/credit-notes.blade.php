<div class="space-y-6">
    <div class="rounded-2xl border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-zinc-500">Accounting</p>
                <h1 class="mt-2 text-3xl font-semibold text-zinc-900 dark:text-white">Credit Notes</h1>
                <p class="mt-2 text-sm text-zinc-600 dark:text-zinc-400">Issue credit notes to customers against completed sales.</p>
            </div>
            <button wire:click="openCreateModal"
                class="inline-flex items-center gap-2 rounded-full bg-zinc-900 px-5 py-2.5 text-xs font-semibold uppercase tracking-wide text-white hover:bg-zinc-800 dark:bg-zinc-100 dark:text-zinc-900 dark:hover:bg-zinc-200">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
                New Credit Note
            </button>
        </div>
    </div>

    {{-- Search --}}
    <div class="flex gap-3">
        <input type="text" wire:model.live.debounce.300ms="search" placeholder="Search by number or reason…"
            class="w-full max-w-xs rounded-lg border border-zinc-200 bg-white px-3 py-2 text-sm shadow-sm dark:border-zinc-700 dark:bg-zinc-950 dark:text-white" />
    </div>

    {{-- Table --}}
    <div class="rounded-2xl border border-zinc-200 bg-white shadow-sm dark:border-zinc-800 dark:bg-zinc-900 overflow-x-auto">
        <table class="min-w-full divide-y divide-zinc-100 dark:divide-zinc-800 text-sm">
            <thead class="bg-zinc-50 dark:bg-zinc-800/50">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-zinc-500">Number</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-zinc-500">Date</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-zinc-500">Reason</th>
                    <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-zinc-500">Total</th>
                    <th class="px-4 py-3 text-center text-xs font-semibold uppercase tracking-wide text-zinc-500">GL Status</th>
                    <th class="px-4 py-3 text-center text-xs font-semibold uppercase tracking-wide text-zinc-500">Status</th>
                    <th class="px-4 py-3 text-center text-xs font-semibold uppercase tracking-wide text-zinc-500">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-50 dark:divide-zinc-800">
                @forelse ($notes as $note)
                <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-800/40">
                    <td class="px-4 py-3 font-mono text-xs text-zinc-700 dark:text-zinc-300">{{ $note->credit_note_number }}</td>
                    <td class="px-4 py-3 text-zinc-600 dark:text-zinc-400">{{ $note->credit_note_date->format('d M Y') }}</td>
                    <td class="px-4 py-3 text-zinc-800 dark:text-zinc-200 max-w-xs truncate">{{ $note->reason }}</td>
                    <td class="px-4 py-3 text-right font-semibold text-zinc-900 dark:text-white">
                        {{ \App\Helpers\LocalizationHelper::formatCurrency($note->total) }}
                    </td>
                    <td class="px-4 py-3 text-center">
                        <span class="inline-flex rounded-full px-2 py-0.5 text-xs font-semibold
                            {{ $note->gl_posting_status === 'posted' ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300' :
                               ($note->gl_posting_status === 'failed' ? 'bg-red-100 text-red-700 dark:bg-red-900/40 dark:text-red-300' :
                               'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/40 dark:text-yellow-300') }}">
                            {{ ucfirst($note->gl_posting_status ?? 'pending') }}
                        </span>
                    </td>
                    <td class="px-4 py-3 text-center">
                        <span class="inline-flex rounded-full px-2 py-0.5 text-xs font-semibold
                            {{ $note->status === 'issued' ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300' : 'bg-zinc-100 text-zinc-600 dark:bg-zinc-700 dark:text-zinc-300' }}">
                            {{ ucfirst($note->status) }}
                        </span>
                    </td>
                    <td class="px-4 py-3 text-center">
                        <div class="flex items-center justify-center gap-2">
                            <button wire:click="viewDetail({{ $note->id }})"
                                class="text-xs text-zinc-500 underline hover:text-zinc-800 dark:hover:text-zinc-200">View</button>
                            @if ($note->status === 'draft')
                            <button wire:click="issue({{ $note->id }})"
                                class="text-xs font-semibold text-emerald-600 hover:text-emerald-800">Issue</button>
                            @endif
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="px-4 py-10 text-center text-sm text-zinc-400">No credit notes found.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
        <div class="border-t border-zinc-100 px-4 py-3 dark:border-zinc-800">
            {{ $notes->links() }}
        </div>
    </div>

    {{-- Create Modal --}}
    @if ($showCreateModal)
    <div class="fixed inset-0 z-50 flex items-start justify-center overflow-y-auto bg-black/50 p-4 pt-16">
        <div class="w-full max-w-2xl rounded-2xl border border-zinc-200 bg-white p-6 shadow-xl dark:border-zinc-700 dark:bg-zinc-900">
            <div class="mb-5 flex items-center justify-between">
                <h2 class="text-lg font-semibold text-zinc-900 dark:text-white">New Credit Note</h2>
                <button wire:click="$set('showCreateModal', false)" class="text-zinc-400 hover:text-zinc-600">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            <div class="space-y-4">
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="text-xs font-semibold uppercase tracking-wide text-zinc-500">Date</label>
                        <input type="date" wire:model.defer="credit_note_date"
                            class="mt-1 w-full rounded-lg border border-zinc-200 bg-white px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-950 dark:text-white" />
                        @error('credit_note_date') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="text-xs font-semibold uppercase tracking-wide text-zinc-500">Linked Sale (optional)</label>
                        <select wire:model.defer="sale_id"
                            class="mt-1 w-full rounded-lg border border-zinc-200 bg-white px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-950 dark:text-white">
                            <option value="">— No linked sale —</option>
                            @foreach ($sales as $sale)
                            <option value="{{ $sale->id }}">{{ $sale->sale_number }} ({{ \Carbon\Carbon::parse($sale->sale_time)->format('d M Y') }})</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div>
                    <label class="text-xs font-semibold uppercase tracking-wide text-zinc-500">Reason *</label>
                    <textarea wire:model.defer="reason" rows="2"
                        class="mt-1 w-full rounded-lg border border-zinc-200 bg-white px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-950 dark:text-white"></textarea>
                    @error('reason') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                {{-- Line Items --}}
                <div>
                    <div class="mb-2 flex items-center justify-between">
                        <label class="text-xs font-semibold uppercase tracking-wide text-zinc-500">Line Items</label>
                        <button type="button" wire:click="addLine"
                            class="text-xs text-zinc-500 underline hover:text-zinc-800">+ Add line</button>
                    </div>
                    <div class="space-y-2">
                        @foreach ($lineItems as $i => $item)
                        <div class="grid grid-cols-12 gap-2 items-start">
                            <div class="col-span-5">
                                <input type="text" wire:model.defer="lineItems.{{ $i }}.description" placeholder="Description"
                                    class="w-full rounded-lg border border-zinc-200 bg-white px-3 py-1.5 text-sm dark:border-zinc-700 dark:bg-zinc-950 dark:text-white" />
                                @error("lineItems.{$i}.description") <p class="text-xs text-red-600">{{ $message }}</p> @enderror
                            </div>
                            <div class="col-span-2">
                                <input type="number" wire:model.defer="lineItems.{{ $i }}.quantity" placeholder="Qty" min="0.01" step="0.01"
                                    class="w-full rounded-lg border border-zinc-200 bg-white px-3 py-1.5 text-sm dark:border-zinc-700 dark:bg-zinc-950 dark:text-white" />
                            </div>
                            <div class="col-span-2">
                                <input type="number" wire:model.defer="lineItems.{{ $i }}.unit_price" placeholder="Price" min="0" step="0.01"
                                    class="w-full rounded-lg border border-zinc-200 bg-white px-3 py-1.5 text-sm dark:border-zinc-700 dark:bg-zinc-950 dark:text-white" />
                            </div>
                            <div class="col-span-2">
                                <input type="number" wire:model.defer="lineItems.{{ $i }}.tax_rate" placeholder="Tax %" min="0" max="100"
                                    class="w-full rounded-lg border border-zinc-200 bg-white px-3 py-1.5 text-sm dark:border-zinc-700 dark:bg-zinc-950 dark:text-white" />
                            </div>
                            <div class="col-span-1 flex justify-center">
                                @if (count($lineItems) > 1)
                                <button type="button" wire:click="removeLine({{ $i }})" class="text-red-400 hover:text-red-600">
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                </button>
                                @endif
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>

            <div class="mt-6 flex items-center justify-between">
                <button wire:click="$set('showCreateModal', false)"
                    class="rounded-full border border-zinc-200 px-5 py-2 text-xs font-semibold text-zinc-600 hover:bg-zinc-50 dark:border-zinc-700 dark:text-zinc-300">
                    Cancel
                </button>
                <button wire:click="save"
                    class="rounded-full bg-zinc-900 px-6 py-2 text-xs font-semibold uppercase tracking-wide text-white hover:bg-zinc-800 dark:bg-zinc-100 dark:text-zinc-900">
                    Save Draft
                </button>
            </div>
        </div>
    </div>
    @endif

    {{-- Detail Modal --}}
    @if ($showDetailModal && $viewingNote)
    <div class="fixed inset-0 z-50 flex items-start justify-center overflow-y-auto bg-black/50 p-4 pt-16">
        <div class="w-full max-w-lg rounded-2xl border border-zinc-200 bg-white p-6 shadow-xl dark:border-zinc-700 dark:bg-zinc-900">
            <div class="mb-4 flex items-center justify-between">
                <h2 class="text-lg font-semibold text-zinc-900 dark:text-white">{{ $viewingNote->credit_note_number }}</h2>
                <button wire:click="$set('showDetailModal', false)" class="text-zinc-400 hover:text-zinc-600">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
            <dl class="space-y-2 text-sm">
                <div class="flex justify-between">
                    <dt class="text-zinc-500">Date</dt>
                    <dd class="font-medium">{{ $viewingNote->credit_note_date->format('d M Y') }}</dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-zinc-500">Status</dt>
                    <dd class="font-medium">{{ ucfirst($viewingNote->status) }}</dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-zinc-500">Reason</dt>
                    <dd class="font-medium">{{ $viewingNote->reason }}</dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-zinc-500">Subtotal</dt>
                    <dd>{{ \App\Helpers\LocalizationHelper::formatCurrency($viewingNote->subtotal) }}</dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-zinc-500">Tax</dt>
                    <dd>{{ \App\Helpers\LocalizationHelper::formatCurrency($viewingNote->tax_amount) }}</dd>
                </div>
                <div class="flex justify-between font-bold">
                    <dt>Total</dt>
                    <dd>{{ \App\Helpers\LocalizationHelper::formatCurrency($viewingNote->total) }}</dd>
                </div>
            </dl>
            @if ($viewingNote->items->isNotEmpty())
            <table class="mt-4 w-full text-xs">
                <thead><tr class="border-b border-zinc-200 dark:border-zinc-700">
                    <th class="pb-1 text-left text-zinc-500">Description</th>
                    <th class="pb-1 text-right text-zinc-500">Qty</th>
                    <th class="pb-1 text-right text-zinc-500">Price</th>
                    <th class="pb-1 text-right text-zinc-500">Total</th>
                </tr></thead>
                <tbody>
                    @foreach ($viewingNote->items as $item)
                    <tr class="border-b border-zinc-50 dark:border-zinc-800">
                        <td class="py-1">{{ $item->description }}</td>
                        <td class="py-1 text-right">{{ $item->quantity }}</td>
                        <td class="py-1 text-right">{{ \App\Helpers\LocalizationHelper::formatCurrency($item->unit_price) }}</td>
                        <td class="py-1 text-right">{{ \App\Helpers\LocalizationHelper::formatCurrency($item->line_total) }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
            @endif
        </div>
    </div>
    @endif
</div>
