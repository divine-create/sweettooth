<div class="space-y-6">
    <!-- Header -->
    <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-md p-6">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold text-zinc-900 dark:text-white">Branch Comparison</h1>
                <p class="text-zinc-600 dark:text-zinc-400">Side-by-side revenue, COGS, and net income across all branches</p>
            </div>
            <div class="flex items-center gap-3">
                <select wire:model.live="periodId" class="px-4 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white">
                    <option value="">All Periods</option>
                    @foreach ($periods as $period)
                        <option value="{{ $period->id }}">{{ $period->name ?? "{$period->month}/{$period->year}" }}</option>
                    @endforeach
                </select>
            </div>
        </div>
    </div>

    <!-- Summary Cards -->
    <div class="grid grid-cols-2 md:grid-cols-5 gap-4">
        <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-md p-4 border-l-4 border-green-500">
            <p class="text-zinc-500 text-xs font-semibold mb-1 uppercase">Total Revenue</p>
            <p class="text-xl font-bold text-green-600 dark:text-green-400">{{ number_format($totals['revenue'], 2) }}</p>
        </div>
        <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-md p-4 border-l-4 border-orange-500">
            <p class="text-zinc-500 text-xs font-semibold mb-1 uppercase">Total COGS</p>
            <p class="text-xl font-bold text-orange-600 dark:text-orange-400">{{ number_format($totals['cogs'], 2) }}</p>
        </div>
        <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-md p-4 border-l-4 border-blue-500">
            <p class="text-zinc-500 text-xs font-semibold mb-1 uppercase">Gross Profit</p>
            <p class="text-xl font-bold text-blue-600 dark:text-blue-400">{{ number_format($totals['gross_profit'], 2) }}</p>
        </div>
        <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-md p-4 border-l-4 border-purple-500">
            <p class="text-zinc-500 text-xs font-semibold mb-1 uppercase">Total OpEx</p>
            <p class="text-xl font-bold text-purple-600 dark:text-purple-400">{{ number_format($totals['opex'], 2) }}</p>
        </div>
        <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-md p-4 border-l-4 {{ $totals['net_income'] >= 0 ? 'border-emerald-500' : 'border-red-500' }}">
            <p class="text-zinc-500 text-xs font-semibold mb-1 uppercase">Net Income</p>
            <p class="text-xl font-bold {{ $totals['net_income'] >= 0 ? 'text-emerald-600 dark:text-emerald-400' : 'text-red-600 dark:text-red-400' }}">
                {{ number_format($totals['net_income'], 2) }}
            </p>
        </div>
    </div>

    <!-- Comparison Table -->
    <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-md overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-zinc-50 dark:bg-zinc-700/50 border-b border-zinc-200 dark:border-zinc-700">
                    <tr>
                        <th class="px-6 py-3 text-left font-semibold text-zinc-900 dark:text-white text-sm">Branch</th>
                        <th class="px-6 py-3 text-right font-semibold text-zinc-900 dark:text-white text-sm">Revenue</th>
                        <th class="px-6 py-3 text-right font-semibold text-zinc-900 dark:text-white text-sm">COGS</th>
                        <th class="px-6 py-3 text-right font-semibold text-zinc-900 dark:text-white text-sm">Gross Profit</th>
                        <th class="px-6 py-3 text-right font-semibold text-zinc-900 dark:text-white text-sm">OpEx</th>
                        <th class="px-6 py-3 text-right font-semibold text-zinc-900 dark:text-white text-sm">Net Income</th>
                        <th class="px-6 py-3 text-right font-semibold text-zinc-900 dark:text-white text-sm">Net Margin</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                    @forelse($rows as $row)
                        @php $netClass = $row['net_income'] >= 0 ? 'text-emerald-600 dark:text-emerald-400' : 'text-red-600 dark:text-red-400'; @endphp
                        <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-700/30 transition">
                            <td class="px-6 py-4 text-sm font-medium text-zinc-900 dark:text-white">{{ $row['branch'] }}</td>
                            <td class="px-6 py-4 text-sm text-right text-zinc-900 dark:text-white">{{ number_format($row['revenue'], 2) }}</td>
                            <td class="px-6 py-4 text-sm text-right text-zinc-600 dark:text-zinc-400">{{ number_format($row['cogs'], 2) }}</td>
                            <td class="px-6 py-4 text-sm text-right font-medium text-blue-600 dark:text-blue-400">{{ number_format($row['gross_profit'], 2) }}</td>
                            <td class="px-6 py-4 text-sm text-right text-zinc-600 dark:text-zinc-400">{{ number_format($row['opex'], 2) }}</td>
                            <td class="px-6 py-4 text-sm text-right font-bold {{ $netClass }}">{{ number_format($row['net_income'], 2) }}</td>
                            <td class="px-6 py-4 text-sm text-right {{ $netClass }}">
                                @if($row['net_margin'] !== null)
                                    {{ number_format($row['net_margin'], 1) }}%
                                @else
                                    —
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-12 text-center text-zinc-500 dark:text-zinc-400">
                                No branch data available for this period.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
                @if(count($rows) > 0)
                <tfoot class="bg-zinc-50 dark:bg-zinc-700/50 border-t-2 border-zinc-300 dark:border-zinc-600">
                    <tr>
                        <td class="px-6 py-3 font-bold text-zinc-900 dark:text-white text-sm">All Branches</td>
                        <td class="px-6 py-3 text-right font-bold text-zinc-900 dark:text-white text-sm">{{ number_format($totals['revenue'], 2) }}</td>
                        <td class="px-6 py-3 text-right font-bold text-zinc-900 dark:text-white text-sm">{{ number_format($totals['cogs'], 2) }}</td>
                        <td class="px-6 py-3 text-right font-bold text-blue-600 dark:text-blue-400 text-sm">{{ number_format($totals['gross_profit'], 2) }}</td>
                        <td class="px-6 py-3 text-right font-bold text-zinc-900 dark:text-white text-sm">{{ number_format($totals['opex'], 2) }}</td>
                        <td class="px-6 py-3 text-right font-bold text-sm {{ $totals['net_income'] >= 0 ? 'text-emerald-600 dark:text-emerald-400' : 'text-red-600 dark:text-red-400' }}">
                            {{ number_format($totals['net_income'], 2) }}
                        </td>
                        <td class="px-6 py-3 text-right font-bold text-sm {{ $totals['net_income'] >= 0 ? 'text-emerald-600 dark:text-emerald-400' : 'text-red-600 dark:text-red-400' }}">
                            @if($totals['revenue'] > 0)
                                {{ number_format(($totals['net_income'] / $totals['revenue']) * 100, 1) }}%
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
