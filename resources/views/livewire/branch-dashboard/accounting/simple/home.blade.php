<div class="space-y-6">
    <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
        <div>
            <h1 class="text-2xl font-semibold text-zinc-900 dark:text-white">Accounting Dashboard</h1>
            <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-400">
                Current period: <span class="font-medium text-zinc-900 dark:text-white">{{ $period?->name ?? 'No open period' }}</span>
            </p>
        </div>
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('branch-dashboard.accounting.posting-status', ['b_id' => request()->query('b_id')]) }}" class="rounded-full border border-zinc-200 px-4 py-2 text-xs font-semibold uppercase tracking-wide text-zinc-700 hover:border-zinc-300 hover:bg-zinc-50 dark:border-zinc-800 dark:text-zinc-200 dark:hover:bg-zinc-800">
                Review Posting Queue
            </a>
            <a href="{{ route('branch-dashboard.accounting.periods', ['b_id' => request()->query('b_id')]) }}" class="rounded-full border border-zinc-200 px-4 py-2 text-xs font-semibold uppercase tracking-wide text-zinc-700 hover:border-zinc-300 hover:bg-zinc-50 dark:border-zinc-800 dark:text-zinc-200 dark:hover:bg-zinc-800">
                Period Control
            </a>
            <a href="{{ route('branch-dashboard.accounting.journal-entry', ['b_id' => request()->query('b_id')]) }}" class="rounded-full border border-zinc-200 px-4 py-2 text-xs font-semibold uppercase tracking-wide text-zinc-700 hover:border-zinc-300 hover:bg-zinc-50 dark:border-zinc-800 dark:text-zinc-200 dark:hover:bg-zinc-800">
                New Journal
            </a>
        </div>
    </div>

    <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-4">
        <div class="rounded-xl border border-zinc-200 bg-white p-4 shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
            <h2 class="text-xs font-semibold uppercase tracking-wide text-zinc-500">Sales Posting</h2>
            <div class="mt-4 flex items-end justify-between">
                <div class="text-3xl font-semibold text-zinc-900 dark:text-white">{{ $stats['sales']['posted'] }}</div>
                <div class="text-xs text-zinc-500">posted</div>
            </div>
            <div class="mt-3 flex gap-3 text-xs text-zinc-600 dark:text-zinc-400">
                <span>Pending: {{ $stats['sales']['pending'] }}</span>
                <span>Failed: {{ $stats['sales']['failed'] }}</span>
            </div>
        </div>

        <div class="rounded-xl border border-zinc-200 bg-white p-4 shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
            <h2 class="text-xs font-semibold uppercase tracking-wide text-zinc-500">Purchases Posting</h2>
            <div class="mt-4 flex items-end justify-between">
                <div class="text-3xl font-semibold text-zinc-900 dark:text-white">{{ $stats['purchases']['posted'] }}</div>
                <div class="text-xs text-zinc-500">posted</div>
            </div>
            <div class="mt-3 flex gap-3 text-xs text-zinc-600 dark:text-zinc-400">
                <span>Pending: {{ $stats['purchases']['pending'] }}</span>
                <span>Failed: {{ $stats['purchases']['failed'] }}</span>
            </div>
        </div>

        <div class="rounded-xl border border-zinc-200 bg-white p-4 shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
            <h2 class="text-xs font-semibold uppercase tracking-wide text-zinc-500">Payments Posting</h2>
            <div class="mt-4 flex items-end justify-between">
                <div class="text-3xl font-semibold text-zinc-900 dark:text-white">{{ $stats['payments']['posted'] }}</div>
                <div class="text-xs text-zinc-500">posted</div>
            </div>
            <div class="mt-3 flex gap-3 text-xs text-zinc-600 dark:text-zinc-400">
                <span>Pending: {{ $stats['payments']['pending'] }}</span>
                <span>Failed: {{ $stats['payments']['failed'] }}</span>
            </div>
        </div>

        <div class="rounded-xl border border-zinc-200 bg-white p-4 shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
            <h2 class="text-xs font-semibold uppercase tracking-wide text-zinc-500">Adjustments Posting</h2>
            <div class="mt-4 flex items-end justify-between">
                <div class="text-3xl font-semibold text-zinc-900 dark:text-white">{{ $stats['adjustments']['posted'] }}</div>
                <div class="text-xs text-zinc-500">posted</div>
            </div>
            <div class="mt-3 flex gap-3 text-xs text-zinc-600 dark:text-zinc-400">
                <span>Pending: {{ $stats['adjustments']['pending'] }}</span>
                <span>Failed: {{ $stats['adjustments']['failed'] }}</span>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 gap-4 lg:grid-cols-3">
        <div class="rounded-xl border border-zinc-200 bg-white p-5 shadow-sm dark:border-zinc-800 dark:bg-zinc-900 lg:col-span-2">
            <div class="flex items-center justify-between">
                <h2 class="text-sm font-semibold text-zinc-700 dark:text-zinc-200">Posting Health</h2>
                <a href="{{ route('branch-dashboard.accounting.posting-status', ['b_id' => request()->query('b_id')]) }}" class="text-xs font-semibold uppercase tracking-wide text-zinc-500 hover:text-zinc-700 dark:hover:text-zinc-200">
                    View queue
                </a>
            </div>
            <div class="mt-4 grid grid-cols-1 gap-3 md:grid-cols-2">
                <div class="rounded-lg border border-zinc-200 p-3 text-sm text-zinc-600 dark:border-zinc-800 dark:text-zinc-300">
                    <div class="flex items-center justify-between">
                        <span>Pending</span>
                        <span class="font-semibold text-zinc-900 dark:text-white">
                            {{ $stats['sales']['pending'] + $stats['purchases']['pending'] + $stats['payments']['pending'] + $stats['adjustments']['pending'] }}
                        </span>
                    </div>
                    <p class="mt-2 text-xs text-zinc-500">Items waiting to post into the ledger.</p>
                </div>
                <div class="rounded-lg border border-zinc-200 p-3 text-sm text-zinc-600 dark:border-zinc-800 dark:text-zinc-300">
                    <div class="flex items-center justify-between">
                        <span>Failed</span>
                        <span class="font-semibold text-rose-600">
                            {{ $stats['sales']['failed'] + $stats['purchases']['failed'] + $stats['payments']['failed'] + $stats['adjustments']['failed'] }}
                        </span>
                    </div>
                    <p class="mt-2 text-xs text-zinc-500">Retry failures from the transactions list.</p>
                </div>
            </div>
        </div>

        <div class="rounded-xl border border-zinc-200 bg-white p-5 shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
            <h2 class="text-sm font-semibold text-zinc-700 dark:text-zinc-200">Quick Actions</h2>
            <div class="mt-4 space-y-2 text-sm">
                <a href="{{ route('branch-dashboard.accounting.bank-accounts', ['b_id' => request()->query('b_id')]) }}" class="block rounded-lg border border-zinc-200 px-3 py-2 text-zinc-700 hover:border-zinc-300 hover:bg-zinc-50 dark:border-zinc-800 dark:text-zinc-200 dark:hover:bg-zinc-800">
                    Cash & Bank Accounts
                </a>
                <a href="{{ route('branch-dashboard.accounting.accounts', ['b_id' => request()->query('b_id')]) }}" class="block rounded-lg border border-zinc-200 px-3 py-2 text-zinc-700 hover:border-zinc-300 hover:bg-zinc-50 dark:border-zinc-800 dark:text-zinc-200 dark:hover:bg-zinc-800">
                    Chart of Accounts
                </a>
                <a href="{{ route('branch-dashboard.accounting.inventory-valuation', ['b_id' => request()->query('b_id')]) }}" class="block rounded-lg border border-zinc-200 px-3 py-2 text-zinc-700 hover:border-zinc-300 hover:bg-zinc-50 dark:border-zinc-800 dark:text-zinc-200 dark:hover:bg-zinc-800">
                    Inventory Valuation
                </a>
            </div>
        </div>
    </div>

    <div class="rounded-xl border border-zinc-200 bg-white p-5 shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
        <div class="flex items-center justify-between">
            <h2 class="text-sm font-semibold text-zinc-700 dark:text-zinc-200">Cash & Bank Accounts</h2>
            <a href="{{ route('branch-dashboard.accounting.bank-accounts', ['b_id' => request()->query('b_id')]) }}" class="text-xs font-semibold uppercase tracking-wide text-zinc-500 hover:text-zinc-700 dark:hover:text-zinc-200">
                Manage
            </a>
        </div>
        <div class="mt-4 space-y-3 text-sm">
            @forelse ($bankAccounts as $account)
                <div class="flex flex-col gap-2 rounded-lg border border-zinc-200 p-3 md:flex-row md:items-center md:justify-between dark:border-zinc-800">
                    <div>
                        <div class="font-semibold text-zinc-900 dark:text-white">{{ $account->bank_name }}</div>
                        <div class="text-xs text-zinc-500">{{ $account->account_number }}</div>
                    </div>
                    <div class="text-right text-sm font-semibold text-zinc-900 dark:text-white">
                        {{ number_format($account->getCurrentBalance() ?? 0, 2) }}
                    </div>
                </div>
            @empty
                <div class="rounded-lg border border-dashed border-zinc-200 p-4 text-zinc-500 dark:border-zinc-800">
                    No active bank accounts found.
                </div>
            @endforelse
        </div>
    </div>
</div>
