<div class="space-y-6">
    <div class="rounded-2xl border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-zinc-500">Accounting</p>
                <h1 class="mt-2 text-3xl font-semibold text-zinc-900 dark:text-white">AP Aging Report</h1>
                <p class="mt-2 text-sm text-zinc-600 dark:text-zinc-400">Accounts payable outstanding balances by age bucket.</p>
            </div>
            <div class="flex items-end gap-3">
                <div>
                    <label class="text-xs font-semibold uppercase tracking-wide text-zinc-500">As Of Date</label>
                    <input type="date" wire:model.live="asOfDate"
                        class="mt-1 block w-44 rounded-lg border border-zinc-200 bg-white px-3 py-2 text-sm text-zinc-900 shadow-sm dark:border-zinc-700 dark:bg-zinc-950 dark:text-white" />
                </div>
            </div>
        </div>
    </div>

    {{-- Summary strip --}}
    <div class="grid grid-cols-2 gap-4 lg:grid-cols-5">
        @foreach ($buckets as $bucket)
        <div class="rounded-xl border border-zinc-200 bg-white p-4 shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
            <p class="text-xs font-semibold uppercase tracking-wide text-zinc-500">{{ $bucket['label'] }}</p>
            <p class="mt-2 text-xl font-bold text-zinc-900 dark:text-white">
                {{ \App\Helpers\LocalizationHelper::formatCurrency($bucket['total']) }}
            </p>
            <p class="mt-1 text-xs text-zinc-500">{{ $bucket['items']->count() }} invoice(s)</p>
        </div>
        @endforeach
    </div>

    {{-- Buckets --}}
    @foreach ($buckets as $key => $bucket)
        @if ($bucket['items']->isNotEmpty())
        <div class="rounded-2xl border border-zinc-200 bg-white shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
            <div class="flex items-center justify-between border-b border-zinc-100 px-6 py-4 dark:border-zinc-800">
                <h2 class="text-sm font-semibold text-zinc-800 dark:text-zinc-200">{{ $bucket['label'] }}</h2>
                <span class="text-sm font-bold text-zinc-900 dark:text-white">
                    {{ \App\Helpers\LocalizationHelper::formatCurrency($bucket['total']) }}
                </span>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-zinc-100 dark:divide-zinc-800 text-sm">
                    <thead class="bg-zinc-50 dark:bg-zinc-800/50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-zinc-500">Purchase #</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-zinc-500">Supplier</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-zinc-500">Purchase Date</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-zinc-500">Due Date</th>
                            <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-zinc-500">Days Overdue</th>
                            <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-zinc-500">Balance</th>
                            <th class="px-4 py-3 text-center text-xs font-semibold uppercase tracking-wide text-zinc-500">Payment Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-50 dark:divide-zinc-800">
                        @foreach ($bucket['items'] as $purchase)
                        <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-800/40">
                            <td class="px-4 py-3 font-mono text-xs text-zinc-700 dark:text-zinc-300">{{ $purchase->purchase_number }}</td>
                            <td class="px-4 py-3 text-zinc-800 dark:text-zinc-200">{{ $purchase->supplier?->name ?? $purchase->supplier_name ?? '—' }}</td>
                            <td class="px-4 py-3 text-zinc-600 dark:text-zinc-400">{{ \Carbon\Carbon::parse($purchase->purchase_date)->format('d M Y') }}</td>
                            <td class="px-4 py-3 text-zinc-600 dark:text-zinc-400">{{ \Carbon\Carbon::parse($purchase->due_date)->format('d M Y') }}</td>
                            <td class="px-4 py-3 text-right">
                                @if ($purchase->days_overdue > 0)
                                    <span class="inline-flex rounded-full px-2 py-0.5 text-xs font-semibold
                                        {{ $purchase->days_overdue > 90 ? 'bg-red-100 text-red-700 dark:bg-red-900/40 dark:text-red-300' :
                                           ($purchase->days_overdue > 60 ? 'bg-orange-100 text-orange-700 dark:bg-orange-900/40 dark:text-orange-300' :
                                           'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/40 dark:text-yellow-300') }}">
                                        {{ $purchase->days_overdue }}d
                                    </span>
                                @else
                                    <span class="text-zinc-400">—</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-right font-semibold text-zinc-900 dark:text-white">
                                {{ \App\Helpers\LocalizationHelper::formatCurrency($purchase->outstanding_balance) }}
                            </td>
                            <td class="px-4 py-3 text-center">
                                <span class="inline-flex rounded-full px-2 py-0.5 text-xs font-semibold
                                    {{ $purchase->payment_status === 'partial' ? 'bg-blue-100 text-blue-700 dark:bg-blue-900/40 dark:text-blue-300' : 'bg-rose-100 text-rose-700 dark:bg-rose-900/40 dark:text-rose-300' }}">
                                    {{ ucfirst($purchase->payment_status) }}
                                </span>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        @endif
    @endforeach

    @if (collect($buckets)->sum(fn($b) => $b['items']->count()) === 0)
    <div class="rounded-2xl border border-zinc-200 bg-white p-12 text-center shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
        <p class="text-sm text-zinc-500">No outstanding payables as of {{ $asOf->format('d M Y') }}.</p>
    </div>
    @endif

    {{-- Grand Total --}}
    <div class="flex justify-end">
        <div class="rounded-xl border border-zinc-300 bg-zinc-900 px-8 py-4 text-white dark:border-zinc-700 dark:bg-zinc-800">
            <p class="text-xs font-semibold uppercase tracking-widest text-zinc-400">Total Outstanding AP</p>
            <p class="mt-1 text-2xl font-bold">{{ \App\Helpers\LocalizationHelper::formatCurrency($grandTotal) }}</p>
        </div>
    </div>
</div>
