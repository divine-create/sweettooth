<div class="p-3 space-y-4">

    <x-breadcrumb title="Sales-Point Transfers" :items="[
        ['label' => 'Dashboard', 'url' => branch_route('branch-dashboard.index')],
        ['label' => 'Sales'],
        ['label' => $department->name],
        ['label' => 'Transfers'],
    ]" :compact="false" :with-icons="true" />

    <div class="rounded-lg border border-blue-200 bg-blue-50 dark:bg-blue-900/20 dark:border-blue-800 p-3 text-sm text-blue-900 dark:text-blue-100">
        Move product stock from <strong>{{ $department->name }}</strong> to another sales point. The
        product's available quantity drops here and rises at the destination. Both points must have an
        open shift.
    </div>

    <!-- Create transfer -->
    <div class="bg-white dark:bg-zinc-900 rounded-lg shadow-sm border border-zinc-200 dark:border-zinc-700 p-4">
        <h2 class="text-lg font-semibold text-zinc-900 dark:text-zinc-100 mb-4">Send stock to another sales point</h2>

        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div>
                <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Product *</label>
                <select wire:model.live="product_id"
                    class="w-full px-3 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-800 text-zinc-900 dark:text-zinc-100">
                    <option value="">Select product</option>
                    @foreach ($availableProducts as $p)
                        <option value="{{ $p->product_id }}">{{ $p->name }} (avail: {{ (float) $p->available }})</option>
                    @endforeach
                </select>
                @error('product_id') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">To sales point *</label>
                <select wire:model="to_department_id"
                    class="w-full px-3 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-800 text-zinc-900 dark:text-zinc-100">
                    <option value="">Select destination</option>
                    @foreach ($toDepartments as $d)
                        <option value="{{ $d->id }}">{{ $d->name }}</option>
                    @endforeach
                </select>
                @error('to_department_id') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Quantity *</label>
                <input type="number" step="0.01" min="0.01" wire:model="quantity" placeholder="0"
                    class="w-full px-3 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-800 text-zinc-900 dark:text-zinc-100">
                @error('quantity') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Notes</label>
                <input type="text" wire:model="notes" placeholder="Optional"
                    class="w-full px-3 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-800 text-zinc-900 dark:text-zinc-100">
            </div>
        </div>

        <div class="mt-4 flex justify-end">
            <button wire:click="createTransfer" wire:loading.attr="disabled" wire:target="createTransfer"
                class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg font-medium transition-colors disabled:opacity-60">
                <span wire:loading.remove wire:target="createTransfer">Transfer</span>
                <span wire:loading wire:target="createTransfer">Transferring…</span>
            </button>
        </div>
    </div>

    <!-- Tabs -->
    <div class="flex gap-2">
        @foreach (['sent' => 'Sent', 'incoming' => 'Received', 'all' => 'All'] as $key => $label)
            <button wire:click="setTab('{{ $key }}')"
                @class([
                    'px-3 py-1.5 rounded-lg text-sm font-medium transition-colors',
                    'bg-blue-600 text-white' => $tab === $key,
                    'bg-zinc-100 dark:bg-zinc-800 text-zinc-700 dark:text-zinc-300 hover:bg-zinc-200' => $tab !== $key,
                ])>
                {{ $label }}
            </button>
        @endforeach
    </div>

    <!-- List -->
    <div class="bg-white dark:bg-zinc-900 rounded-lg shadow-sm border border-zinc-200 dark:border-zinc-700 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-zinc-50 dark:bg-zinc-800 text-zinc-600 dark:text-zinc-300">
                    <tr>
                        <th class="text-left px-4 py-2">#</th>
                        <th class="text-left px-4 py-2">Product</th>
                        <th class="text-left px-4 py-2">From</th>
                        <th class="text-left px-4 py-2">To</th>
                        <th class="text-right px-4 py-2">Qty</th>
                        <th class="text-left px-4 py-2">Status</th>
                        <th class="text-left px-4 py-2">When</th>
                        <th class="text-right px-4 py-2">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
                    @forelse ($transfers as $t)
                        <tr class="text-zinc-800 dark:text-zinc-200">
                            <td class="px-4 py-2">{{ $t->id }}</td>
                            <td class="px-4 py-2">{{ $t->product?->name ?? '—' }}</td>
                            <td class="px-4 py-2">{{ $t->fromDepartment?->name ?? '—' }}</td>
                            <td class="px-4 py-2">{{ $t->toDepartment?->name ?? '—' }}</td>
                            <td class="px-4 py-2 text-right">{{ (float) $t->quantity }}</td>
                            <td class="px-4 py-2">
                                <span @class([
                                    'px-2 py-0.5 rounded-full text-xs font-medium',
                                    'bg-green-100 text-green-800' => $t->status === 'completed',
                                    'bg-zinc-200 text-zinc-700' => $t->status === 'reversed',
                                    'bg-amber-100 text-amber-800' => $t->status === 'pending',
                                    'bg-red-100 text-red-800' => $t->status === 'rejected',
                                ])>{{ ucfirst($t->status) }}</span>
                            </td>
                            <td class="px-4 py-2 text-zinc-500">{{ $t->completed_at?->diffForHumans() ?? $t->created_at?->diffForHumans() }}</td>
                            <td class="px-4 py-2 text-right">
                                @if ($t->status === 'completed' && (int) $t->from_department_id === (int) $department->id)
                                    <button wire:click="reverseTransfer({{ $t->id }})"
                                        wire:confirm="Reverse this transfer? Stock returns to this sales point."
                                        class="text-red-600 hover:text-red-800 text-xs font-medium">Reverse</button>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-4 py-8 text-center text-zinc-500">No transfers yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="p-3">{{ $transfers->links() }}</div>
    </div>
</div>
