<div class="p-3 space-y-3">
    <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-sm border border-zinc-200 dark:border-zinc-700 p-4">
        <h2 class="text-lg font-semibold text-zinc-800 dark:text-zinc-100 mb-6">Supplier Performance</h2>

        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
            <div class="bg-blue-50 dark:bg-blue-900/20 rounded-lg shadow-sm p-4">
                <p class="text-sm text-gray-600 dark:text-gray-400">Total Suppliers</p>
                <p class="text-2xl font-bold text-blue-600 dark:text-blue-500">{{ $summary['total_suppliers'] }}</p>
            </div>
            <div class="bg-green-50 dark:bg-green-900/20 rounded-lg shadow-sm p-4">
                <p class="text-sm text-gray-600 dark:text-gray-400">Total Purchases</p>
                <p class="text-2xl font-bold text-green-600 dark:text-green-500">{{ $summary['total_purchases'] }}</p>
            </div>
            <div class="bg-purple-50 dark:bg-purple-900/20 rounded-lg shadow-sm p-4">
                <p class="text-sm text-gray-600 dark:text-gray-400">Total Spent</p>
                <p class="text-2xl font-bold text-purple-600 dark:text-purple-500">
                    {{ \App\Helpers\LocalizationHelper::formatCurrency($summary['total_spent'] ?? 0) }}
                </p>
            </div>
            <div class="bg-yellow-50 dark:bg-yellow-900/20 rounded-lg shadow-sm p-4">
                <p class="text-sm text-gray-600 dark:text-gray-400">Avg Purchase</p>
                <p class="text-2xl font-bold text-yellow-600 dark:text-yellow-500">
                    {{ \App\Helpers\LocalizationHelper::formatCurrency($summary['avg_purchase_value'] ?? 0) }}
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
                <h3 class="text-base font-semibold text-zinc-800 dark:text-zinc-100 mb-4">Top 10 Suppliers by Spending</h3>
                <div id="topSuppliersChart" class="h-80"></div>
            </div>
            <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-sm border border-zinc-200 dark:border-zinc-700 p-4">
                <h3 class="text-base font-semibold text-zinc-800 dark:text-zinc-100 mb-4">Supplier Reliability Scores</h3>
                <div class="space-y-3">
                    @foreach($supplierReliability as $supplier)
                        <div>
                            <div class="flex justify-between items-center mb-1">
                                <span class="font-medium text-sm text-zinc-900 dark:text-zinc-100">{{ $supplier->supplier_name }}</span>
                                <span class="text-sm font-bold {{ $supplier->reliability_score >= 80 ? 'text-green-600 dark:text-green-400' : ($supplier->reliability_score >= 50 ? 'text-yellow-600 dark:text-yellow-400' : 'text-red-600 dark:text-red-400') }}">
                                    {{ number_format($supplier->reliability_score, 1) }}%
                                </span>
                            </div>
                            <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                                <div class="h-2 rounded-full {{ $supplier->reliability_score >= 80 ? 'bg-green-600' : ($supplier->reliability_score >= 50 ? 'bg-yellow-600' : 'bg-red-600') }}"
                                     style="width: {{ $supplier->reliability_score }}%"></div>
                            </div>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                {{ $supplier->purchase_count }} purchases |
                                {{ \App\Helpers\LocalizationHelper::formatCurrency($supplier->total_spent ?? 0) }}
                            </p>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
    <script>
        document.addEventListener('livewire:navigated', () => {
            new ApexCharts(document.querySelector("#topSuppliersChart"), {
                series: @js($topSuppliers['series']),
                chart: { type: 'bar', height: 320 },
                plotOptions: { bar: { horizontal: true, distributed: false } },
                xaxis: { categories: @js($topSuppliers['labels']) },
                colors: ['#3B82F6']
            }).render();
        });
    </script>
</div>
