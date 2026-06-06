<div class="space-y-6">
    <!-- Header -->
    <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-md p-6">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold text-zinc-900 dark:text-white">Budget vs Actual</h1>
                <p class="text-zinc-600 dark:text-zinc-400">Compare approved budgets against actual GL spend per period</p>
            </div>
            <div class="flex items-center gap-3 flex-wrap">
                <select wire:model.live="periodId" class="px-4 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white">
                    <option value="">All Periods</option>
                    @foreach ($periods as $period)
                        <option value="{{ $period->id }}">{{ $period->name ?? "{$period->month}/{$period->year}" }}</option>
                    @endforeach
                </select>
                <select wire:model.live="category" class="px-4 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white">
                    <option value="">All Categories</option>
                    <option value="revenue">Revenue</option>
                    <option value="expense">Expense</option>
                    <option value="cogs">COGS</option>
                    <option value="capex">CapEx</option>
                </select>
                <button wire:click="exportToCsv" class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition">
                    Export CSV
                </button>
            </div>
        </div>
    </div>

    <!-- Summary Cards -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-md p-6 border-l-4 border-blue-500">
            <p class="text-zinc-600 dark:text-zinc-400 text-sm font-semibold mb-2">TOTAL BUDGET</p>
            <p class="text-2xl font-bold text-blue-600 dark:text-blue-400">{{ number_format($totalBudget, 2) }}</p>
        </div>
        <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-md p-6 border-l-4 border-green-500">
            <p class="text-zinc-600 dark:text-zinc-400 text-sm font-semibold mb-2">TOTAL ACTUAL</p>
            <p class="text-2xl font-bold text-green-600 dark:text-green-400">{{ number_format($totalActual, 2) }}</p>
        </div>
        <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-md p-6 border-l-4 {{ $totalVariance >= 0 ? 'border-emerald-500' : 'border-red-500' }}">
            <p class="text-zinc-600 dark:text-zinc-400 text-sm font-semibold mb-2">TOTAL VARIANCE</p>
            <p class="text-2xl font-bold {{ $totalVariance >= 0 ? 'text-emerald-600 dark:text-emerald-400' : 'text-red-600 dark:text-red-400' }}">
                {{ ($totalVariance >= 0 ? '+' : '') . number_format($totalVariance, 2) }}
            </p>
        </div>
    </div>

    <!-- Report Table -->
    <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-md overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-zinc-50 dark:bg-zinc-700/50 border-b border-zinc-200 dark:border-zinc-700">
                    <tr>
                        <th class="px-6 py-3 text-left font-semibold text-zinc-900 dark:text-white text-sm">Account</th>
                        <th class="px-6 py-3 text-left font-semibold text-zinc-900 dark:text-white text-sm">Type</th>
                        <th class="px-6 py-3 text-left font-semibold text-zinc-900 dark:text-white text-sm">Category</th>
                        <th class="px-6 py-3 text-right font-semibold text-zinc-900 dark:text-white text-sm">Budget</th>
                        <th class="px-6 py-3 text-right font-semibold text-zinc-900 dark:text-white text-sm">Actual</th>
                        <th class="px-6 py-3 text-right font-semibold text-zinc-900 dark:text-white text-sm">Variance</th>
                        <th class="px-6 py-3 text-right font-semibold text-zinc-900 dark:text-white text-sm">Var %</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                    @forelse($rows as $row)
                        @php
                            $isOver = $row['variance'] < 0;
                            $varClass = $row['variance'] >= 0 ? 'text-emerald-600 dark:text-emerald-400' : 'text-red-600 dark:text-red-400';
                        @endphp
                        <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-700/30 transition">
                            <td class="px-6 py-4 text-sm text-zinc-900 dark:text-white">
                                <span class="font-mono text-xs text-zinc-500 mr-2">{{ $row['account_number'] }}</span>
                                {{ $row['account_name'] }}
                            </td>
                            <td class="px-6 py-4 text-sm text-zinc-600 dark:text-zinc-400">{{ ucfirst($row['account_type']) }}</td>
                            <td class="px-6 py-4 text-sm text-zinc-600 dark:text-zinc-400">{{ ucfirst($row['category']) }}</td>
                            <td class="px-6 py-4 text-sm text-right font-medium text-zinc-900 dark:text-white">{{ number_format($row['budget'], 2) }}</td>
                            <td class="px-6 py-4 text-sm text-right font-medium text-zinc-900 dark:text-white">{{ number_format($row['actual'], 2) }}</td>
                            <td class="px-6 py-4 text-sm text-right font-semibold {{ $varClass }}">
                                {{ ($row['variance'] >= 0 ? '+' : '') . number_format($row['variance'], 2) }}
                            </td>
                            <td class="px-6 py-4 text-sm text-right {{ $varClass }}">
                                @if($row['variance_pct'] !== null)
                                    {{ ($row['variance_pct'] >= 0 ? '+' : '') . number_format($row['variance_pct'], 1) }}%
                                @else
                                    —
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-12 text-center text-zinc-500 dark:text-zinc-400">
                                No approved budgets found for this period.
                                <a href="{{ route('branch-dashboard.accounting.budgets') }}" class="text-blue-600 hover:underline ml-1">Go to Budgets</a>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
                @if(count($rows) > 0)
                <tfoot class="bg-zinc-50 dark:bg-zinc-700/50 border-t-2 border-zinc-300 dark:border-zinc-600">
                    <tr>
                        <td colspan="3" class="px-6 py-3 font-bold text-zinc-900 dark:text-white text-sm">Total</td>
                        <td class="px-6 py-3 text-right font-bold text-zinc-900 dark:text-white text-sm">{{ number_format($totalBudget, 2) }}</td>
                        <td class="px-6 py-3 text-right font-bold text-zinc-900 dark:text-white text-sm">{{ number_format($totalActual, 2) }}</td>
                        <td class="px-6 py-3 text-right font-bold text-sm {{ $totalVariance >= 0 ? 'text-emerald-600 dark:text-emerald-400' : 'text-red-600 dark:text-red-400' }}">
                            {{ ($totalVariance >= 0 ? '+' : '') . number_format($totalVariance, 2) }}
                        </td>
                        <td class="px-6 py-3 text-right font-bold text-sm {{ $totalVariance >= 0 ? 'text-emerald-600 dark:text-emerald-400' : 'text-red-600 dark:text-red-400' }}">
                            @if($totalBudget > 0)
                                {{ ($totalVariance >= 0 ? '+' : '') . number_format(($totalVariance / $totalBudget) * 100, 1) }}%
                            @else
                                —
                            @endif
                        </td>
                    </tr>
                </tfoot>
                @endif
            </table>
        </div>
    </div>
</div>
