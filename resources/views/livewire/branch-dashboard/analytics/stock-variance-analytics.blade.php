<div class="p-3 space-y-3">
    <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-sm border border-zinc-200 dark:border-zinc-700 p-4">
        <h2 class="text-lg font-semibold text-zinc-800 dark:text-zinc-100 mb-6">Stock Variance Analytics</h2>

        <div class="grid grid-cols-1 md:grid-cols-5 gap-4 mb-6">
            <div class="bg-blue-50 dark:bg-blue-900/20 rounded-lg shadow-sm p-4">
                <p class="text-sm text-gray-600 dark:text-gray-400">Total Stock Takes</p>
                <p class="text-2xl font-bold text-blue-600 dark:text-blue-500">{{ $summary['total_stock_takes'] }}</p>
            </div>
            <div class="bg-purple-50 dark:bg-purple-900/20 rounded-lg shadow-sm p-4">
                <p class="text-sm text-gray-600 dark:text-gray-400">Items Counted</p>
                <p class="text-2xl font-bold text-purple-600 dark:text-purple-500">{{ $summary['total_items_counted'] }}</p>
            </div>
            <div class="bg-green-50 dark:bg-green-900/20 rounded-lg shadow-sm p-4">
                <p class="text-sm text-gray-600 dark:text-gray-400">Positive Variances</p>
                <p class="text-2xl font-bold text-green-600 dark:text-green-500">{{ $summary['positive_variances'] }}</p>
            </div>
            <div class="bg-red-50 dark:bg-red-900/20 rounded-lg shadow-sm p-4">
                <p class="text-sm text-gray-600 dark:text-gray-400">Negative Variances</p>
                <p class="text-2xl font-bold text-red-600 dark:text-red-500">{{ $summary['negative_variances'] }}</p>
            </div>
            <div class="bg-yellow-50 dark:bg-yellow-900/20 rounded-lg shadow-sm p-4">
                <p class="text-sm text-gray-600 dark:text-gray-400">Variance Value</p>
                <p class="text-2xl font-bold text-yellow-600 dark:text-yellow-500">
                    {{ \App\Helpers\LocalizationHelper::formatCurrency($summary['total_variance_value'] ?? 0) }}
                </p>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
            <div>
                <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">From</label>
                <input type="date" wire:model.live="dateFrom" class="w-full px-4 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-800 dark:text-zinc-200 focus:ring-2 focus:ring-blue-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">To</label>
                <input type="date" wire:model.live="dateTo" class="w-full px-4 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-800 dark:text-zinc-200 focus:ring-2 focus:ring-blue-500">
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
            <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-sm border border-zinc-200 dark:border-zinc-700 p-4">
                <h3 class="text-base font-semibold text-zinc-800 dark:text-zinc-100 mb-4">Variance Trend</h3>
                <div id="varianceTrendChart" class="h-80"></div>
            </div>
            <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-sm border border-zinc-200 dark:border-zinc-700 p-4">
                <h3 class="text-base font-semibold text-zinc-800 dark:text-zinc-100 mb-4">Top Variance Items</h3>
                <div class="space-y-2">
                    @foreach($topVarianceItems as $item)
                        <div class="flex justify-between items-center p-3 bg-gray-50 dark:bg-zinc-700/50 rounded">
                            <div>
                                <p class="font-medium text-zinc-900 dark:text-zinc-100">{{ $item['item']->name }}</p>
                                <p class="text-xs text-gray-500 dark:text-gray-400">{{ $item['count'] }} stock takes</p>
                            </div>
                            <p class="font-bold {{ $item['total_variance'] >= 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                                {{ $item['total_variance'] >= 0 ? '+' : '' }}{{ number_format($item['total_variance'], 2) }}
                            </p>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-sm border border-zinc-200 dark:border-zinc-700 p-4">
            <h3 class="text-base font-semibold text-zinc-800 dark:text-zinc-100 mb-4">Stock Take History</h3>
            <div class="overflow-x-auto">
                <table class="w-full text-sm text-left">
                    <thead class="text-xs uppercase bg-zinc-100 dark:bg-zinc-700 text-zinc-700 dark:text-zinc-300">
                        <tr>
                            <th class="px-4 py-3">Date</th>
                            <th class="px-4 py-3">Reference</th>
                            <th class="px-4 py-3">Recorded By</th>
                            <th class="px-4 py-3">Items Counted</th>
                            <th class="px-4 py-3">Status</th>
                            <th class="px-4 py-3">Notes</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                        @forelse($stockTakes as $take)
                            <tr class="bg-white dark:bg-zinc-800 hover:bg-zinc-50 dark:hover:bg-zinc-700/50">
                                <td class="px-4 py-3 text-zinc-900 dark:text-zinc-100">{{ \Carbon\Carbon::parse($take->stock_take_date)->format('M d, Y') }}</td>
                                <td class="px-4 py-3 text-zinc-900 dark:text-zinc-100">{{ $take->stock_take_number }}</td>
                                <td class="px-4 py-3 text-zinc-900 dark:text-zinc-100">{{ $take->conductor->name ?? 'N/A' }}</td>
                                <td class="px-4 py-3 text-zinc-900 dark:text-zinc-100">{{ $take->stockTakeDetails->count() }}</td>
                                <td class="px-4 py-3">
                                    @php
                                        $badgeColors = match($take->status) {
                                            'completed' => 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200',
                                            'in_progress' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200',
                                            'pending' => 'bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-200',
                                            default => 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200'
                                        };
                                    @endphp
                                    <span class="px-2 py-1 text-xs font-semibold rounded {{ $badgeColors }}">
                                        {{ ucfirst(str_replace('_', ' ', $take->status)) }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-zinc-900 dark:text-zinc-100">{{ $take->notes ?? '-' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-4 py-8 text-center text-gray-500 dark:text-gray-400">No stock takes found</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="mt-4">{{ $stockTakes->links() }}</div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
    <script>
        document.addEventListener('livewire:navigated', () => {
            new ApexCharts(document.querySelector("#varianceTrendChart"), {
                series: @js($varianceTrend['series']),
                chart: { type: 'bar', height: 320, stacked: false },
                plotOptions: { bar: { horizontal: false, columnWidth: '55%' } },
                xaxis: { categories: @js($varianceTrend['categories']) },
                colors: ['#10B981', '#EF4444'],
                legend: { position: 'top' }
            }).render();
        });
    </script>
</div>
