<div class="p-4 space-y-4">
    {{-- Header --}}
    <div class="flex items-start justify-between gap-3 flex-wrap">
        <div>
            <h1 class="text-lg font-semibold text-zinc-800 dark:text-zinc-100">Go-Live: Opening Stock Entry</h1>
            <p class="text-xs text-zinc-500 dark:text-zinc-400">
                Enter yesterday's closing stock as today's opening balance. This tool is temporary — lock it once every department is loaded.
            </p>
        </div>
        @if($access['lock'])
            <button type="button" wire:click="$set('showLockModal', true)"
                    class="inline-flex items-center gap-2 px-3 py-2 text-sm font-medium rounded-lg bg-red-600 hover:bg-red-700 text-white">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                Lock Go-Live
            </button>
        @endif
    </div>

    {{-- Tabs --}}
    <div class="flex gap-1 border-b border-zinc-200 dark:border-zinc-700">
        @if($access['production'])
            <button type="button" wire:click="$set('tab', 'production')"
                    class="px-4 py-2 text-sm font-medium border-b-2 -mb-px {{ $tab === 'production' ? 'border-teal-600 text-teal-700 dark:text-teal-400' : 'border-transparent text-zinc-500 hover:text-zinc-700' }}">
                Production Stores
            </button>
        @endif
        @if($access['sales'])
            <button type="button" wire:click="$set('tab', 'sales')"
                    class="px-4 py-2 text-sm font-medium border-b-2 -mb-px {{ $tab === 'sales' ? 'border-teal-600 text-teal-700 dark:text-teal-400' : 'border-transparent text-zinc-500 hover:text-zinc-700' }}">
                Sales
            </button>
        @endif
        @if($access['inventory'])
            <button type="button" wire:click="$set('tab', 'inventory')"
                    class="px-4 py-2 text-sm font-medium border-b-2 -mb-px {{ $tab === 'inventory' ? 'border-teal-600 text-teal-700 dark:text-teal-400' : 'border-transparent text-zinc-500 hover:text-zinc-700' }}">
                Main Inventory
            </button>
        @endif
    </div>

    {{-- PRODUCTION TAB --}}
    @if($tab === 'production' && $access['production'])
        <div class="space-y-3">
            @if($productionDepartments->isEmpty())
                <p class="text-sm text-amber-600">No production department available for your account.</p>
            @else
                <div class="flex gap-3 flex-wrap">
                    <select wire:model.live="prodDeptId" class="rounded-lg border-zinc-300 dark:border-zinc-600 dark:bg-zinc-800 text-sm">
                        @foreach($productionDepartments as $id => $name)
                            <option value="{{ $id }}">{{ $name }}</option>
                        @endforeach
                    </select>
                    <input type="text" wire:model.live.debounce.400ms="prodSearch"
                           placeholder="Search raw materials or products (min 2 chars)…"
                           class="flex-1 min-w-[220px] rounded-lg border-zinc-300 dark:border-zinc-600 dark:bg-zinc-800 text-sm">
                </div>

                <div class="overflow-x-auto border border-zinc-200 dark:border-zinc-700 rounded-lg">
                    <table class="w-full text-sm" style="min-width: 760px;">
                        <thead class="bg-zinc-50 dark:bg-zinc-800 text-xs text-zinc-500 uppercase">
                            <tr>
                                <th class="text-left px-3 py-2">Item</th>
                                <th class="text-left px-3 py-2">Type</th>
                                <th class="text-left px-3 py-2">UoM</th>
                                <th class="text-right px-3 py-2">Current</th>
                                <th class="text-left px-3 py-2">Opening Qty</th>
                                <th class="text-left px-3 py-2">Unit Cost</th>
                                <th class="px-3 py-2"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
                            @forelse($prodResults as $r)
                                <tr x-data="{ qty: {{ $r['current'] }}, cost: {{ $r['cost'] }}, saving: false }" wire:key="prod-{{ $r['id'] }}">
                                    <td class="px-3 py-2 text-zinc-800 dark:text-zinc-100">{{ $r['name'] }}</td>
                                    <td class="px-3 py-2"><span class="text-xs px-1.5 py-0.5 rounded {{ $r['type'] === 'Raw' ? 'bg-blue-100 text-blue-700' : 'bg-purple-100 text-purple-700' }}">{{ $r['type'] }}</span></td>
                                    <td class="px-3 py-2 text-zinc-500">{{ $r['uom'] }}</td>
                                    <td class="px-3 py-2 text-right text-zinc-500">{{ rtrim(rtrim(number_format($r['current'], 2), '0'), '.') }}</td>
                                    <td class="px-3 py-2"><input type="number" step="any" min="0" x-model="qty" class="w-28 rounded border-zinc-300 dark:border-zinc-600 dark:bg-zinc-800 text-sm"></td>
                                    <td class="px-3 py-2"><input type="number" step="any" min="0" x-model="cost" class="w-28 rounded border-zinc-300 dark:border-zinc-600 dark:bg-zinc-800 text-sm"></td>
                                    <td class="px-3 py-2 text-right">
                                        <button type="button" x-bind:disabled="saving"
                                                x-on:click="saving = true; $wire.saveProduction('{{ $r['id'] }}', qty, cost).finally(() => saving = false)"
                                                class="px-3 py-1.5 text-xs font-medium rounded bg-teal-600 hover:bg-teal-700 text-white disabled:opacity-50">Save</button>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="7" class="px-3 py-6 text-center text-zinc-400 text-sm">Type at least 2 characters to search.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    @endif

    {{-- SALES TAB --}}
    @if($tab === 'sales' && $access['sales'])
        <div class="space-y-3">
            @if($salesDepartments->isEmpty())
                <p class="text-sm text-amber-600">No sales department available for your account.</p>
            @else
                <div class="flex gap-3 flex-wrap">
                    <select wire:model.live="salesDeptId" class="rounded-lg border-zinc-300 dark:border-zinc-600 dark:bg-zinc-800 text-sm">
                        @foreach($salesDepartments as $id => $name)
                            <option value="{{ $id }}">{{ $name }}</option>
                        @endforeach
                    </select>
                    <input type="text" wire:model.live.debounce.400ms="salesSearch"
                           placeholder="Search sellable products (min 2 chars)…"
                           class="flex-1 min-w-[220px] rounded-lg border-zinc-300 dark:border-zinc-600 dark:bg-zinc-800 text-sm">
                </div>

                <div class="overflow-x-auto border border-zinc-200 dark:border-zinc-700 rounded-lg">
                    <table class="w-full text-sm" style="min-width: 620px;">
                        <thead class="bg-zinc-50 dark:bg-zinc-800 text-xs text-zinc-500 uppercase">
                            <tr>
                                <th class="text-left px-3 py-2">Product</th>
                                <th class="text-left px-3 py-2">UoM</th>
                                <th class="text-right px-3 py-2">Current Opening</th>
                                <th class="text-left px-3 py-2">Opening Qty</th>
                                <th class="px-3 py-2"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
                            @forelse($salesProducts as $r)
                                <tr x-data="{ qty: {{ $r['current'] }}, saving: false }" wire:key="sale-{{ $r['id'] }}">
                                    <td class="px-3 py-2 text-zinc-800 dark:text-zinc-100">{{ $r['name'] }}</td>
                                    <td class="px-3 py-2 text-zinc-500">{{ $r['uom'] }}</td>
                                    <td class="px-3 py-2 text-right text-zinc-500">{{ rtrim(rtrim(number_format($r['current'], 2), '0'), '.') }}</td>
                                    <td class="px-3 py-2"><input type="number" step="any" min="0" x-model="qty" class="w-28 rounded border-zinc-300 dark:border-zinc-600 dark:bg-zinc-800 text-sm"></td>
                                    <td class="px-3 py-2 text-right">
                                        <button type="button" x-bind:disabled="saving"
                                                x-on:click="saving = true; $wire.saveSales('{{ $r['id'] }}', qty).finally(() => saving = false)"
                                                class="px-3 py-1.5 text-xs font-medium rounded bg-teal-600 hover:bg-teal-700 text-white disabled:opacity-50">Save</button>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="px-3 py-6 text-center text-zinc-400 text-sm">Type at least 2 characters to search.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    @endif

    {{-- INVENTORY TAB --}}
    @if($tab === 'inventory' && $access['inventory'])
        <div class="space-y-3">
            <input type="text" wire:model.live.debounce.400ms="invSearch"
                   placeholder="Search main-inventory items (min 2 chars)…"
                   class="w-full max-w-md rounded-lg border-zinc-300 dark:border-zinc-600 dark:bg-zinc-800 text-sm">

            <div class="overflow-x-auto border border-zinc-200 dark:border-zinc-700 rounded-lg">
                <table class="w-full text-sm" style="min-width: 700px;">
                    <thead class="bg-zinc-50 dark:bg-zinc-800 text-xs text-zinc-500 uppercase">
                        <tr>
                            <th class="text-left px-3 py-2">Item</th>
                            <th class="text-left px-3 py-2">UoM</th>
                            <th class="text-right px-3 py-2">Current</th>
                            <th class="text-left px-3 py-2">Opening Qty</th>
                            <th class="text-left px-3 py-2">Avg Cost</th>
                            <th class="px-3 py-2"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
                        @forelse($invResults as $r)
                            <tr x-data="{ qty: {{ $r['current'] }}, cost: {{ $r['cost'] }}, saving: false }" wire:key="inv-{{ $r['id'] }}">
                                <td class="px-3 py-2 text-zinc-800 dark:text-zinc-100">{{ $r['name'] }}</td>
                                <td class="px-3 py-2 text-zinc-500">{{ $r['uom'] }}</td>
                                <td class="px-3 py-2 text-right text-zinc-500">{{ rtrim(rtrim(number_format($r['current'], 2), '0'), '.') }}</td>
                                <td class="px-3 py-2"><input type="number" step="any" min="0" x-model="qty" class="w-28 rounded border-zinc-300 dark:border-zinc-600 dark:bg-zinc-800 text-sm"></td>
                                <td class="px-3 py-2"><input type="number" step="any" min="0" x-model="cost" class="w-28 rounded border-zinc-300 dark:border-zinc-600 dark:bg-zinc-800 text-sm"></td>
                                <td class="px-3 py-2 text-right">
                                    <button type="button" x-bind:disabled="saving"
                                            x-on:click="saving = true; $wire.saveInventory('{{ $r['id'] }}', qty, cost).finally(() => saving = false)"
                                            class="px-3 py-1.5 text-xs font-medium rounded bg-teal-600 hover:bg-teal-700 text-white disabled:opacity-50">Save</button>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="px-3 py-6 text-center text-zinc-400 text-sm">Type at least 2 characters to search.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    {{-- LOCK CONFIRMATION MODAL --}}
    @if($access['lock'] && $showLockModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/40" wire:click.self="$set('showLockModal', false)">
            <div class="bg-white dark:bg-zinc-900 rounded-xl shadow-xl w-full max-w-md p-5 space-y-4">
                <h3 class="text-base font-semibold text-zinc-800 dark:text-zinc-100">Lock Go-Live opening-stock entry?</h3>
                <p class="text-sm text-zinc-600 dark:text-zinc-300">
                    This permanently disables opening-stock entry for everyone. Only do this once <strong>all departments</strong> have been loaded.
                    It cannot be undone from this screen.
                </p>
                <div class="flex justify-end gap-2">
                    <button type="button" wire:click="$set('showLockModal', false)" class="px-3 py-2 text-sm rounded-lg border border-zinc-300 dark:border-zinc-600">Cancel</button>
                    <button type="button" wire:click="lockGoLive" class="px-3 py-2 text-sm rounded-lg bg-red-600 hover:bg-red-700 text-white">Lock now</button>
                </div>
            </div>
        </div>
    @endif
</div>
