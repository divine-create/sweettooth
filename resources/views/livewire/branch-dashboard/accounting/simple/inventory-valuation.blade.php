<div class="space-y-6">
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-semibold text-zinc-900 dark:text-white">Inventory Valuation</h1>
            <p class="text-sm text-zinc-600 dark:text-zinc-400">
                Preview and post inventory valuation for the current period.
            </p>
        </div>
        <button type="button" wire:click="exportToCsv" class="self-start rounded-full border border-emerald-600 bg-emerald-600 px-4 py-2 text-xs font-semibold uppercase tracking-wide text-white hover:bg-emerald-700">Export CSV</button>
    </div>

    <div class="rounded-lg border border-zinc-200 bg-white p-4 dark:border-zinc-800 dark:bg-zinc-900">
        <div class="text-sm">
            <div>Current period: {{ $period?->name ?? 'No open period' }}</div>
            <div class="mt-2">
                <div>Total stock value: {{ number_format($summary['total_value'] ?? 0, 2) }}</div>
                <div>Items: {{ $summary['item_count'] ?? 0 }}</div>
                <div>Stock records: {{ $summary['stock_count'] ?? 0 }}</div>
            </div>
            <div class="mt-2 text-zinc-500">
                This page will provide a valuation preview and a single “Post Valuation” action.
            </div>
        </div>
        <div class="mt-4">
            <a class="text-sm text-blue-600" href="{{ route('branch-dashboard.accounting.inventory-valuation-manage', ['b_id' => request()->query('b_id')]) }}">
                Open Valuation Workspace
            </a>
        </div>
    </div>
</div>
