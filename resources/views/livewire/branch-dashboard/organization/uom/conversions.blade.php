<div class="space-y-4 p-3">
    <x-breadcrumb
        title="UOM Conversions"
        :items="[
            ['label' => 'Dashboard', 'url' => branch_route('branch-dashboard.index')],
            ['label' => 'Organization'],
            ['label' => 'UOM Management', 'url' => branch_route('branch-dashboard.organization.uom.index')],
            ['label' => 'Conversions']
        ]"
        :compact="false"
        :with-icons="true"
    />

    <div class="rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 p-4 space-y-4">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-3">
            <div class="md:col-span-2">
                <label class="block text-xs font-semibold text-zinc-600 dark:text-zinc-300 mb-1">Search</label>
                <input type="text" wire:model.live.debounce.300ms="search" placeholder="Units, scope context, notes"
                    class="w-full rounded-lg border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-900 px-3 py-2 text-sm text-zinc-800 dark:text-zinc-100" />
            </div>
            <div>
                <label class="block text-xs font-semibold text-zinc-600 dark:text-zinc-300 mb-1">Scope</label>
                <select wire:model.live="scope"
                    class="w-full rounded-lg border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-900 px-3 py-2 text-sm text-zinc-800 dark:text-zinc-100">
                    <option value="all">All</option>
                    <option value="global">Global</option>
                    <option value="branch">Branch</option>
                    <option value="item">Item</option>
                    <option value="product">Product</option>
                </select>
            </div>
            <div>
                <label class="block text-xs font-semibold text-zinc-600 dark:text-zinc-300 mb-1">Status</label>
                <select wire:model.live="status"
                    class="w-full rounded-lg border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-900 px-3 py-2 text-sm text-zinc-800 dark:text-zinc-100">
                    <option value="active">Active</option>
                    <option value="inactive">Inactive</option>
                    <option value="all">All</option>
                </select>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-3 items-end">
            <div>
                <label class="block text-xs font-semibold text-zinc-600 dark:text-zinc-300 mb-1">Rows</label>
                <select wire:model.live="perPage"
                    class="w-full rounded-lg border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-900 px-3 py-2 text-sm text-zinc-800 dark:text-zinc-100">
                    <option value="15">15</option>
                    <option value="30">30</option>
                    <option value="50">50</option>
                </select>
            </div>
            <div>
                <a href="{{ branch_route('branch-dashboard.organization.uom.units') }}" wire:navigate
                    class="inline-flex items-center px-3 py-2 rounded-lg bg-zinc-700 hover:bg-zinc-800 text-white text-sm font-medium transition-colors">
                    View Units
                </a>
            </div>
            <div class="md:text-right">
                <a href="{{ branch_route('branch-dashboard.organization.uom.manage') }}" wire:navigate
                    class="inline-flex items-center px-3 py-2 rounded-lg bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium transition-colors">
                    Create / Edit Rules
                </a>
            </div>
        </div>
    </div>

    <div class="rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 overflow-x-auto">
        <table class="min-w-full divide-y divide-zinc-200 dark:divide-zinc-700">
            <thead class="bg-zinc-50 dark:bg-zinc-900/60">
                <tr>
                    <th class="px-4 py-2 text-left text-xs font-semibold text-zinc-600 dark:text-zinc-300">From</th>
                    <th class="px-4 py-2 text-left text-xs font-semibold text-zinc-600 dark:text-zinc-300">To</th>
                    <th class="px-4 py-2 text-left text-xs font-semibold text-zinc-600 dark:text-zinc-300">Factor</th>
                    <th class="px-4 py-2 text-left text-xs font-semibold text-zinc-600 dark:text-zinc-300">Scope</th>
                    <th class="px-4 py-2 text-left text-xs font-semibold text-zinc-600 dark:text-zinc-300">Context</th>
                    <th class="px-4 py-2 text-left text-xs font-semibold text-zinc-600 dark:text-zinc-300">Status</th>
                    <th class="px-4 py-2 text-left text-xs font-semibold text-zinc-600 dark:text-zinc-300">Updated</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                @forelse($conversions as $conversion)
                    <tr>
                        <td class="px-4 py-2 text-sm text-zinc-800 dark:text-zinc-100">{{ $conversion->fromUnit?->symbol ?? 'N/A' }}</td>
                        <td class="px-4 py-2 text-sm text-zinc-800 dark:text-zinc-100">{{ $conversion->toUnit?->symbol ?? 'N/A' }}</td>
                        <td class="px-4 py-2 text-sm font-mono text-zinc-700 dark:text-zinc-300">{{ rtrim(rtrim(number_format((float) $conversion->factor, 12, '.', ''), '0'), '.') }}</td>
                        <td class="px-4 py-2 text-sm">
                            @if($conversion->product_id)
                                <span class="inline-flex px-2 py-0.5 rounded bg-purple-100 text-purple-700 dark:bg-purple-900/40 dark:text-purple-300">Product</span>
                            @elseif($conversion->item_id)
                                <span class="inline-flex px-2 py-0.5 rounded bg-blue-100 text-blue-700 dark:bg-blue-900/40 dark:text-blue-300">Item</span>
                            @elseif($conversion->branch_id)
                                <span class="inline-flex px-2 py-0.5 rounded bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-300">Branch</span>
                            @else
                                <span class="inline-flex px-2 py-0.5 rounded bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300">Global</span>
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
                                <span class="inline-flex px-2 py-0.5 rounded bg-green-100 text-green-700 dark:bg-green-900/40 dark:text-green-300">Active</span>
                            @else
                                <span class="inline-flex px-2 py-0.5 rounded bg-red-100 text-red-700 dark:bg-red-900/40 dark:text-red-300">Inactive</span>
                            @endif
                        </td>
                        <td class="px-4 py-2 text-sm text-zinc-700 dark:text-zinc-300">
                            {{ optional($conversion->updated_at)->format('Y-m-d H:i') }}
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-4 py-8 text-center text-sm text-zinc-500 dark:text-zinc-400">
                            No conversions matched your filters.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div>
        {{ $conversions->links() }}
    </div>
</div>
