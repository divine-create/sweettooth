<div class="p-3 space-y-3">

    <x-breadcrumb
        title="Finished Goods — Daily Sheet"
        :items="[
            ['label' => 'Dashboard', 'url' => branch_route('branch-dashboard.index')],
            ['label' => 'Production'],
            ['label' => 'Finished Goods']
        ]"
        :compact="false"
        :with-icons="true"/>

    <!-- Controls (hidden when printing) -->
    <div class="flex flex-wrap items-end justify-between gap-3 print:hidden">
        <div class="flex flex-wrap items-end gap-3">
            <div>
                <label class="block text-xs font-medium text-zinc-500 dark:text-zinc-400 mb-1">Date</label>
                <input type="date" wire:model.live="date"
                       class="rounded-lg border-zinc-300 dark:border-zinc-600 dark:bg-zinc-800 text-sm" />
            </div>
            <div>
                <label class="block text-xs font-medium text-zinc-500 dark:text-zinc-400 mb-1">Search product</label>
                <input type="text" wire:model.live.debounce.400ms="search" placeholder="Filter…"
                       class="rounded-lg border-zinc-300 dark:border-zinc-600 dark:bg-zinc-800 text-sm" />
            </div>
        </div>
        <div class="flex items-center gap-2">
            <button onclick="window.print()"
                    class="px-4 py-2 bg-zinc-700 hover:bg-zinc-800 text-white rounded-lg font-medium flex items-center gap-2 text-sm">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                Print
            </button>
            <button wire:click="exportCsv"
                    class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg font-medium flex items-center gap-2 text-sm">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                Export CSV
            </button>
        </div>
    </div>

    @if(!$store)
        <div class="bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-lg p-6 text-center">
            <h3 class="text-lg font-semibold text-yellow-800 dark:text-yellow-200">No Production Store Found</h3>
            <p class="text-yellow-700 dark:text-yellow-300 mt-1">This department has no production store, so there is no finished-goods stock to show.</p>
        </div>
    @else
        <div id="sheet-to-print">
            <!-- Print-only header -->
            <div class="hidden print:block mb-3">
                <h1 class="text-lg font-bold">SWEET TOOTH — Finished Goods Closing</h1>
                <p class="text-sm">{{ $department->name }} &middot; {{ \Carbon\Carbon::parse($date)->format('l, d M Y') }}</p>
            </div>

            <!-- Summary cards -->
            <div class="grid grid-cols-2 md:grid-cols-4 gap-3 mb-3 print:hidden">
                <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-sm border border-zinc-200 dark:border-zinc-700 p-4">
                    <p class="text-xs text-zinc-500 dark:text-zinc-400">Opening</p>
                    <p class="text-xl font-bold text-zinc-900 dark:text-zinc-100">{{ rtrim(rtrim(number_format($totals['opening'],2),'0'),'.') }}</p>
                </div>
                <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-sm border border-zinc-200 dark:border-zinc-700 p-4">
                    <p class="text-xs text-zinc-500 dark:text-zinc-400">Produced</p>
                    <p class="text-xl font-bold text-blue-600 dark:text-blue-400">{{ rtrim(rtrim(number_format($totals['produced'],2),'0'),'.') }}</p>
                </div>
                <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-sm border border-zinc-200 dark:border-zinc-700 p-4">
                    <p class="text-xs text-zinc-500 dark:text-zinc-400">Sent out</p>
                    <p class="text-xl font-bold text-amber-600 dark:text-amber-400">{{ rtrim(rtrim(number_format($totals['sent_out'],2),'0'),'.') }}</p>
                </div>
                <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-sm border border-zinc-200 dark:border-zinc-700 p-4">
                    <p class="text-xs text-zinc-500 dark:text-zinc-400">Closing</p>
                    <p class="text-xl font-bold text-green-600 dark:text-green-400">{{ rtrim(rtrim(number_format($totals['closing'],2),'0'),'.') }}</p>
                </div>
            </div>

            <!-- Sheet table -->
            <div class="bg-white dark:bg-zinc-800 rounded-xl shadow-sm border border-zinc-200 dark:border-zinc-700 overflow-hidden">
                <table class="w-full text-sm">
                    <thead class="bg-zinc-50 dark:bg-zinc-700/40">
                        <tr>
                            <th class="px-4 py-2 text-left text-xs font-semibold text-zinc-500 uppercase">Product</th>
                            <th class="px-3 py-2 text-right text-xs font-semibold text-zinc-500 uppercase">Opening</th>
                            <th class="px-3 py-2 text-right text-xs font-semibold text-zinc-500 uppercase">Produced</th>
                            <th class="px-3 py-2 text-right text-xs font-semibold text-zinc-500 uppercase">Sent out</th>
                            <th class="px-3 py-2 text-right text-xs font-semibold text-zinc-500 uppercase">Adj.</th>
                            <th class="px-3 py-2 text-right text-xs font-semibold text-zinc-500 uppercase">Closing</th>
                            <th class="px-3 py-2 text-left text-xs font-semibold text-zinc-500 uppercase print:hidden">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-100 dark:divide-zinc-700">
                        @forelse($rows as $r)
                            <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-700/30">
                                <td class="px-4 py-2 font-medium text-zinc-800 dark:text-zinc-200">
                                    {{ $r['name'] }} <span class="text-xs text-zinc-400">{{ $r['uom'] }}</span>
                                </td>
                                <td class="px-3 py-2 text-right text-zinc-600 dark:text-zinc-300">{{ rtrim(rtrim(number_format($r['opening'],2),'0'),'.') }}</td>
                                <td class="px-3 py-2 text-right text-blue-600 dark:text-blue-400">{{ rtrim(rtrim(number_format($r['produced'],2),'0'),'.') }}</td>
                                <td class="px-3 py-2 text-right text-amber-600 dark:text-amber-400">{{ rtrim(rtrim(number_format($r['sent_out'],2),'0'),'.') }}</td>
                                <td class="px-3 py-2 text-right text-zinc-500">{{ $r['adjust'] != 0 ? rtrim(rtrim(number_format($r['adjust'],2),'0'),'.') : '—' }}</td>
                                <td class="px-3 py-2 text-right font-semibold {{ $r['closing'] < 0 ? 'text-red-600 dark:text-red-400' : 'text-zinc-900 dark:text-white' }}">
                                    {{ rtrim(rtrim(number_format($r['closing'],2),'0'),'.') }}
                                </td>
                                <td class="px-3 py-2 print:hidden">
                                    <div class="flex items-center gap-2">
                                        @if(isset($variances[$r['product_id']]))
                                            @php($vs = $variances[$r['product_id']])
                                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400"
                                                  title="{{ collect($vs)->map(fn($v) => 'sent '.$v['dispatched'].' / received '.($v['received'] ?? 'pending').' ('.$v['status'].')')->implode('; ') }}">
                                                ⚠ {{ count($vs) }} dispatch issue{{ count($vs) > 1 ? 's' : '' }}
                                            </span>
                                        @endif
                                        @if($r['closing'] > 0)
                                            <button wire:click="openSendModal('{{ $r['product_id'] }}')"
                                                    class="inline-flex items-center gap-1 px-2.5 py-1 bg-indigo-600 hover:bg-indigo-700 text-white rounded-md text-xs font-medium">
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                                                Send to Sales
                                            </button>
                                        @elseif(!isset($variances[$r['product_id']]))
                                            <span class="text-xs text-zinc-400">—</span>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="px-4 py-8 text-center text-zinc-500">No finished-goods activity for {{ \Carbon\Carbon::parse($date)->format('d M Y') }}.</td></tr>
                        @endforelse
                    </tbody>
                    @if(count($rows))
                        <tfoot class="bg-zinc-50 dark:bg-zinc-700/40 font-semibold">
                            <tr>
                                <td class="px-4 py-2 text-zinc-700 dark:text-zinc-200">TOTAL</td>
                                <td class="px-3 py-2 text-right">{{ rtrim(rtrim(number_format($totals['opening'],2),'0'),'.') }}</td>
                                <td class="px-3 py-2 text-right">{{ rtrim(rtrim(number_format($totals['produced'],2),'0'),'.') }}</td>
                                <td class="px-3 py-2 text-right">{{ rtrim(rtrim(number_format($totals['sent_out'],2),'0'),'.') }}</td>
                                <td class="px-3 py-2 text-right">{{ rtrim(rtrim(number_format($totals['adjust'],2),'0'),'.') }}</td>
                                <td class="px-3 py-2 text-right">{{ rtrim(rtrim(number_format($totals['closing'],2),'0'),'.') }}</td>
                                <td class="px-3 py-2 print:hidden"></td>
                            </tr>
                        </tfoot>
                    @endif
                </table>
            </div>

            <p class="text-xs text-zinc-400 mt-2">
                Opening / Produced / Sent out / Closing are derived from the production-store movement ledger.
                Opening carries from the previous day's closing automatically.
            </p>
        </div>
    @endif

    <!-- Send to Sales modal -->
    @if($showSendModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4 print:hidden">
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
                        Held (closing) balance:
                        <span class="font-semibold text-zinc-900 dark:text-white">{{ rtrim(rtrim(number_format($sendAvailable,2),'0'),'.') }} {{ $sendUom }}</span>
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
                        <label class="block text-xs font-medium text-zinc-500 dark:text-zinc-400 mb-1">Quantity to send ({{ $sendUom }})</label>
                        <input type="number" step="0.01" min="0.01" max="{{ $sendAvailable }}" wire:model="sendQuantity"
                               class="w-full rounded-lg border-zinc-300 dark:border-zinc-600 dark:bg-zinc-900 text-sm" />
                        @error('sendQuantity') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
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

    <style>
        @media print {
            body * { visibility: hidden; }
            #sheet-to-print, #sheet-to-print * { visibility: visible; }
            #sheet-to-print { position: absolute; left: 0; top: 0; width: 100%; }
            .print\:hidden { display: none !important; }
            .hidden.print\:block { display: block !important; }
        }
    </style>
</div>
