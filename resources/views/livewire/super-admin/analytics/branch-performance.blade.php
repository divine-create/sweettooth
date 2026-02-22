<div class="p-3 space-y-3">
    <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-sm border border-zinc-200 dark:border-zinc-700 p-4">
        <h2 class="text-lg font-semibold text-zinc-800 dark:text-zinc-100 mb-6">Branch Performance</h2>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
            <div class="bg-gradient-to-br from-blue-500 to-blue-600 text-white rounded-lg shadow-sm p-4">
                <p class="text-sm opacity-90">Total Stock Value</p>
                <p class="text-3xl font-bold">
                    {{ \App\Helpers\LocalizationHelper::formatCurrency($metrics['total_stock_value'] ?? 0) }}
                </p>
            </div>
            <div class="bg-gradient-to-br from-green-500 to-green-600 text-white rounded-lg shadow-sm p-4">
                <p class="text-sm opacity-90">Fulfillment Rate</p>
                <p class="text-3xl font-bold">{{ number_format($metrics['fulfillment_rate'], 1) }}%</p>
            </div>
            <div class="bg-gradient-to-br from-purple-500 to-purple-600 text-white rounded-lg shadow-sm p-4">
                <p class="text-sm opacity-90">Stock Accuracy</p>
                <p class="text-3xl font-bold">{{ number_format($metrics['stock_accuracy'], 1) }}%</p>
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

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 mb-6">
            <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-sm border border-zinc-200 dark:border-zinc-700 p-4">
                <p class="text-sm text-gray-600 dark:text-gray-400">Inventory Turnover Ratio</p>
                <p class="text-3xl font-bold text-blue-600 dark:text-blue-500">{{ number_format($metrics['inventory_turnover_ratio'], 2) }}</p>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-2">Higher is better</p>
            </div>
            <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-sm border border-zinc-200 dark:border-zinc-700 p-4">
                <p class="text-sm text-gray-600 dark:text-gray-400">Stockout Rate</p>
                <p class="text-3xl font-bold text-red-600 dark:text-red-500">{{ number_format($metrics['stockout_rate'], 1) }}%</p>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-2">Lower is better</p>
            </div>
            <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-sm border border-zinc-200 dark:border-zinc-700 p-4">
                <p class="text-sm text-gray-600 dark:text-gray-400">Fulfillment Rate</p>
                <p class="text-3xl font-bold text-green-600 dark:text-green-500">{{ number_format($metrics['fulfillment_rate'], 1) }}%</p>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-2">Higher is better</p>
            </div>
        </div>

        <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-sm border border-zinc-200 dark:border-zinc-700 p-4">
            <h3 class="text-base font-semibold text-zinc-800 dark:text-zinc-100 mb-4">Monthly Performance Trend</h3>
            <div id="monthlyPerformanceChart" class="h-80"></div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
    <script>
        document.addEventListener('livewire:navigated', () => {
            new ApexCharts(document.querySelector("#monthlyPerformanceChart"), {
                series: @js($monthlyPerformance['series']),
                chart: { type: 'line', height: 320 },
                stroke: { curve: 'smooth', width: 3 },
                xaxis: { categories: @js($monthlyPerformance['categories']) },
                colors: ['#10B981', '#3B82F6'],
                legend: { position: 'top' }
            }).render();
        });
    </script>
</div>
