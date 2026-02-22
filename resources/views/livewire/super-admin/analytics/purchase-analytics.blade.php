<div class="p-3 space-y-3">
    <!-- Header -->
    <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-sm border border-zinc-200 dark:border-zinc-700 p-4">
        <h2 class="text-lg font-semibold text-zinc-800 dark:text-zinc-100">Purchase Analytics</h2>
    </div>

    <!-- Summary Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-blue-50 dark:bg-blue-900/20 rounded-lg shadow-sm p-4">
            <p class="text-sm text-gray-600 dark:text-gray-400">Total Purchases</p>
            <p class="text-2xl font-bold text-blue-600 dark:text-blue-500">{{ number_format($summary['total_purchases']) }}</p>
        </div>
        <div class="bg-green-50 dark:bg-green-900/20 rounded-lg shadow-sm p-4">
            <p class="text-sm text-gray-600 dark:text-gray-400">Total Spent</p>
            <p class="text-2xl font-bold text-green-600 dark:text-green-500">
                {{ \App\Helpers\LocalizationHelper::formatCurrency($summary['total_spent'] ?? 0) }}
            </p>
        </div>
        <div class="bg-yellow-50 dark:bg-yellow-900/20 rounded-lg shadow-sm p-4">
            <p class="text-sm text-gray-600 dark:text-gray-400">Avg Purchase Value</p>
            <p class="text-2xl font-bold text-yellow-600 dark:text-yellow-500">
                {{ \App\Helpers\LocalizationHelper::formatCurrency($summary['avg_purchase_value'] ?? 0) }}
            </p>
        </div>
        <div class="bg-purple-50 dark:bg-purple-900/20 rounded-lg shadow-sm p-4">
            <p class="text-sm text-gray-600 dark:text-gray-400">Total Items</p>
            <p class="text-2xl font-bold text-purple-600 dark:text-purple-500">{{ number_format($summary['total_items'], 2) }}</p>
        </div>
    </div>

    <!-- Filters -->
    <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-sm border border-zinc-200 dark:border-zinc-700 p-4">
    <div class="grid grid-cols-1 md:grid-cols-5 gap-4">
    <div>
                <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Branch</label>
                <x-select.styled
                    wire:model.live="selectedBranch"
                    :options="$branches->map(fn($branch) => ['label' => $branch->name, 'value' => $branch->id])->toArray()"
                    select="label:label|value:value"
                    placeholder="All Branches"
                    searchable
                />
            </div>
            <div>
                <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Search Supplier</label>
                <input type="text" wire:model.live="supplierFilter" placeholder="Search supplier..."
                class="border border-zinc-300 dark:border-zinc-600 rounded-lg px-4 py-2 bg-white dark:bg-zinc-700 text-zinc-800 dark:text-zinc-200 focus:outline-none focus:ring-2 focus:ring-blue-500 w-full">
                </div>
                <div>
                <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Payment Status</label>
                <select wire:model.live="paymentStatus"
                    class="border border-zinc-300 dark:border-zinc-600 rounded-lg px-4 py-2 bg-white dark:bg-zinc-700 text-zinc-800 dark:text-zinc-200 focus:outline-none focus:ring-2 focus:ring-blue-500 w-full">
                <option value="">All Status</option>
                <option value="paid">Paid</option>
                <option value="partial">Partial</option>
                <option value="pending">Pending</option>
            </select>
            <div>
                <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">From</label>
                <input type="date" wire:model.live="dateFrom"
                       class="border border-zinc-300 dark:border-zinc-600 rounded-lg px-4 py-2 bg-white dark:bg-zinc-700 text-zinc-800 dark:text-zinc-200 focus:outline-none focus:ring-2 focus:ring-blue-500 w-full">
            </div>
            <div>
                <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">To</label>
                <input type="date" wire:model.live="dateTo"
                       class="border border-zinc-300 dark:border-zinc-600 rounded-lg px-4 py-2 bg-white dark:bg-zinc-700 text-zinc-800 dark:text-zinc-200 focus:outline-none focus:ring-2 focus:ring-blue-500 w-full">
            </div>
        </div>
    </div>

    <!-- Charts -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
        <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-sm border border-zinc-200 dark:border-zinc-700 p-4">
            <h3 class="text-base font-semibold text-zinc-800 dark:text-zinc-100 mb-4">Purchase Trend</h3>
            <div id="purchaseTrendChart" class="h-80" wire:ignore>
                <div class="flex items-center justify-center h-full">
                    <div class="text-center">
                        <svg class="animate-spin h-10 w-10 mx-auto text-blue-600 dark:text-blue-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">Loading chart...</p>
                    </div>
                </div>
            </div>
        </div>
        <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-sm border border-zinc-200 dark:border-zinc-700 p-4">
            <h3 class="text-base font-semibold text-zinc-800 dark:text-zinc-100 mb-4">Top Suppliers by Spending</h3>
            <div id="supplierChart" class="h-80" wire:ignore>
                <div class="flex items-center justify-center h-full">
                    <div class="text-center">
                        <svg class="animate-spin h-10 w-10 mx-auto text-blue-600 dark:text-blue-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">Loading chart...</p>
                    </div>
                </div>
            </div>
        </div>
        <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-sm border border-zinc-200 dark:border-zinc-700 p-4">
            <h3 class="text-base font-semibold text-zinc-800 dark:text-zinc-100 mb-4">Cost Breakdown</h3>
            <div id="costBreakdownChart" class="h-80" wire:ignore>
                <div class="flex items-center justify-center h-full">
                    <div class="text-center">
                        <svg class="animate-spin h-10 w-10 mx-auto text-blue-600 dark:text-blue-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">Loading chart...</p>
                    </div>
                </div>
            </div>
        </div>
        <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-sm border border-zinc-200 dark:border-zinc-700 p-4">
            <h3 class="text-base font-semibold text-zinc-800 dark:text-zinc-100 mb-4">Top 10 Purchased Items</h3>
            <div id="topItemsChart" class="h-80" wire:ignore>
                <div class="flex items-center justify-center h-full">
                    <div class="text-center">
                        <svg class="animate-spin h-10 w-10 mx-auto text-blue-600 dark:text-blue-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">Loading chart...</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Purchase Records Table -->
    <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-sm border border-zinc-200 dark:border-zinc-700 p-4">
        <h3 class="text-base font-semibold text-zinc-800 dark:text-zinc-100 mb-4">Purchase Records</h3>
        <div class="overflow-x-auto">
            <table class="w-full text-sm text-left">
                <thead class="text-xs uppercase bg-zinc-100 dark:bg-zinc-700 text-zinc-700 dark:text-zinc-300">
                    <tr>
                        <th class="px-4 py-3">Purchase #</th>
                        <th class="px-4 py-3">Date</th>
                        <th class="px-4 py-3">Supplier</th>
                        <th class="px-4 py-3">Total Cost</th>
                        <th class="px-4 py-3">Payment Status</th>
                        <th class="px-4 py-3">Recorded By</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                    @forelse($purchases as $purchase)
                        <tr class="bg-white dark:bg-zinc-800 hover:bg-zinc-50 dark:hover:bg-zinc-700/50">
                            <td class="px-4 py-3 text-zinc-900 dark:text-zinc-100">{{ $purchase->purchase_number }}</td>
                            <td class="px-4 py-3 text-zinc-900 dark:text-zinc-100">{{ \Carbon\Carbon::parse($purchase->purchase_date)->format('M d, Y') }}</td>
                            <td class="px-4 py-3 text-zinc-900 dark:text-zinc-100">{{ $purchase->supplier_name }}</td>
                            <td class="px-4 py-3 text-zinc-900 dark:text-zinc-100">
                                {{ \App\Helpers\LocalizationHelper::formatCurrency($purchase->total_cost ?? 0) }}
                            </td>
                            <td class="px-4 py-3">
                                @php
                                    $badgeColors = match($purchase->payment_status) {
                                        'paid' => 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200',
                                        'partial' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200',
                                        'pending' => 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200',
                                        default => 'bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-200'
                                    };
                                @endphp
                                <span class="px-2 py-1 text-xs font-semibold rounded {{ $badgeColors }}">
                                    {{ ucfirst($purchase->payment_status) }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-zinc-900 dark:text-zinc-100">{{ $purchase->recorder->name ?? 'N/A' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-8 text-center text-gray-500 dark:text-gray-400">No purchases found</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-4">{{ $purchases->links() }}</div>
    </div>


    @php
        $currencyCode = \App\Helpers\Settings::currencyLocalization('primary_currency', 'USD');
    @endphp

    @push('scripts')
<script src="https://code.highcharts.com/highcharts.js"></script>
<script src="https://code.highcharts.com/modules/exporting.js"></script>
<script src="https://code.highcharts.com/modules/export-data.js"></script>
<script src="https://code.highcharts.com/modules/accessibility.js"></script>

<script>
    let trendChart, supplierChart, costChart, topItemsChart;
    const currencyCode = @js($currencyCode);
    let chartData = {
        trendData: @js($trendData),
        supplierAnalysis: @js($supplierAnalysis),
        costBreakdown: @js($costBreakdown),
        topItems: @js($topItems->map(function($item) {
            return [
                'name' => $item->item->name,
                'total_cost' => $item->total_cost,
                'total_quantity' => $item->total_quantity,
                'uom' => $item->item->uom
            ];
        })->toArray())
    };

    document.addEventListener('DOMContentLoaded', function () {
        if (typeof Highcharts !== 'undefined') {
            initCharts();
        }
    });

    document.addEventListener('livewire:navigated', function () {
        if (typeof Highcharts !== 'undefined') {
            initCharts();
        }
    });

    document.addEventListener('livewire:init', () => {
        Livewire.on('chartsUpdated', (event) => {
            const data = event[0];
            chartData.trendData = data.trendData;
            chartData.supplierAnalysis = data.supplierAnalysis;
            chartData.costBreakdown = data.costBreakdown;
            chartData.topItems = data.topItems;
            updateCharts();
        });
    });

    function initCharts() {
        if (typeof Highcharts === 'undefined') {
            console.error('Highcharts is not loaded');
            return;
        }

        if (trendChart) trendChart.destroy();
        if (supplierChart) supplierChart.destroy();
        if (costChart) costChart.destroy();
        if (topItemsChart) topItemsChart.destroy();

        // Clear loading spinners
        const trendContainer = document.getElementById('purchaseTrendChart');
        const supplierContainer = document.getElementById('supplierChart');
        const costContainer = document.getElementById('costBreakdownChart');
        const topItemsContainer = document.getElementById('topItemsChart');
        if (trendContainer) trendContainer.innerHTML = '';
        if (supplierContainer) supplierContainer.innerHTML = '';
        if (costContainer) costContainer.innerHTML = '';
        if (topItemsContainer) topItemsContainer.innerHTML = '';

        const themeColors = getThemeColors();

        // Purchase Trend Chart
        trendChart = Highcharts.chart('purchaseTrendChart', {
            chart: { type: 'spline', height: 320, backgroundColor: 'transparent' },
            title: { text: null },
            credits: { enabled: false },
            xAxis: {
                categories: chartData.trendData.categories,
                labels: { style: { color: themeColors.textColor } },
                gridLineColor: themeColors.gridColor
            },
            yAxis: [{
                title: { text: `Total Cost (${currencyCode})`, style: { color: themeColors.textColor } },
                labels: {
                    style: { color: themeColors.textColor },
                    formatter: function() { return currencyCode + ' ' + Highcharts.numberFormat(this.value, 0, '.', ','); }
                },
                gridLineColor: themeColors.gridColor
            }, {
                title: { text: 'Count', style: { color: themeColors.textColor } },
                labels: { style: { color: themeColors.textColor } },
                opposite: true,
                gridLineColor: themeColors.gridColor
            }],
            tooltip: { shared: true, backgroundColor: themeColors.backgroundColor, style: { color: themeColors.textColor } },
            plotOptions: { spline: { marker: { enabled: true, radius: 4 }, lineWidth: 3 } },
            series: [{
                name: chartData.trendData.series[0].name,
                data: chartData.trendData.series[0].data,
                color: '#10B981',
                yAxis: 0
            }, {
                name: chartData.trendData.series[1].name,
                data: chartData.trendData.series[1].data,
                color: '#3B82F6',
                yAxis: 1
            }],
            legend: { itemStyle: { color: themeColors.textColor } },
            exporting: { enabled: true }
        });

        // Supplier Chart
        supplierChart = Highcharts.chart('supplierChart', {
            chart: { type: 'bar', height: 320, backgroundColor: 'transparent' },
            title: { text: null },
            credits: { enabled: false },
            xAxis: {
                categories: chartData.supplierAnalysis.labels,
                labels: { style: { color: themeColors.textColor, fontSize: '10px' } }
            },
            yAxis: {
                title: { text: `Total (${currencyCode})`, style: { color: themeColors.textColor } },
                labels: {
                    style: { color: themeColors.textColor },
                    formatter: function() { return currencyCode + ' ' + Highcharts.numberFormat(this.value, 0, '.', ','); }
                }
            },
            tooltip: {
                backgroundColor: themeColors.backgroundColor,
                formatter: function() { return '<b>' + this.point.category + '</b><br/>' + currencyCode + ' ' + Highcharts.numberFormat(this.y, 2, '.', ','); }
            },
            plotOptions: { bar: { dataLabels: { enabled: true, formatter: function() { return currencyCode + ' ' + Highcharts.numberFormat(this.y, 0, '.', ','); } } } },
            series: [{ name: 'Total Spent', data: chartData.supplierAnalysis.series[0].data, color: '#3B82F6', showInLegend: false }],
            exporting: { enabled: true }
        });

        // Cost Breakdown Chart
        const costData = chartData.costBreakdown.labels.map((label, index) => ({
            name: label,
            y: chartData.costBreakdown.series[index]
        }));

        costChart = Highcharts.chart('costBreakdownChart', {
            chart: { type: 'pie', height: 320, backgroundColor: 'transparent' },
            title: { text: null },
            credits: { enabled: false },
            tooltip: { pointFormat: `<b>${currencyCode}{point.y:,.2f}</b> ({point.percentage:.1f}%)`, backgroundColor: themeColors.backgroundColor },
            plotOptions: {
                pie: {
                    allowPointSelect: true,
                    dataLabels: { enabled: true, format: '<b>{point.name}</b>: {point.percentage:.1f}%', style: { color: themeColors.textColor } },
                    showInLegend: true
                }
            },
            series: [{ name: 'Cost', colorByPoint: true, data: costData }],
            colors: ['#3B82F6', '#F59E0B', '#10B981'],
            legend: { itemStyle: { color: themeColors.textColor } },
            exporting: { enabled: true }
        });

        // Top Items Chart
        const topItemsData = chartData.topItems.map(item => item.total_cost);
        const topItemsLabels = chartData.topItems.map(item => item.name);

        topItemsChart = Highcharts.chart('topItemsChart', {
            chart: { type: 'bar', height: 320, backgroundColor: 'transparent' },
            title: { text: null },
            credits: { enabled: false },
            xAxis: {
                categories: topItemsLabels,
                labels: { style: { color: themeColors.textColor, fontSize: '10px' } }
            },
            yAxis: {
                title: { text: `Cost (${currencyCode})`, style: { color: themeColors.textColor } },
                labels: {
                    style: { color: themeColors.textColor },
                    formatter: function() { return currencyCode + ' ' + Highcharts.numberFormat(this.value, 0, '.', ','); }
                }
            },
            tooltip: {
                backgroundColor: themeColors.backgroundColor,
                formatter: function() {
                    const item = chartData.topItems[this.point.index];
                    return '<b>' + this.point.category + '</b><br/>Cost: ' + currencyCode + ' ' + Highcharts.numberFormat(this.y, 2, '.', ',') + '<br/>Qty: ' + Highcharts.numberFormat(item.total_quantity, 2) + ' ' + item.uom;
                }
            },
            plotOptions: { bar: { dataLabels: { enabled: true, formatter: function() { return currencyCode + ' ' + Highcharts.numberFormat(this.y, 0, '.', ','); } }, colorByPoint: true } },
            series: [{ name: 'Total Cost', data: topItemsData, showInLegend: false }],
            colors: ['#3B82F6', '#10B981', '#F59E0B', '#EF4444', '#8B5CF6', '#EC4899', '#6366F1', '#14B8A6', '#F97316', '#84CC16'],
            exporting: { enabled: true }
        });
    }

    function updateCharts() {
        if (trendChart && chartData.trendData) {
            trendChart.series[0].setData(chartData.trendData.series[0].data, false);
            trendChart.series[1].setData(chartData.trendData.series[1].data, false);
            trendChart.xAxis[0].setCategories(chartData.trendData.categories, false);
            trendChart.redraw();
        }

        if (supplierChart && chartData.supplierAnalysis) {
            supplierChart.series[0].setData(chartData.supplierAnalysis.series[0].data, false);
            supplierChart.xAxis[0].setCategories(chartData.supplierAnalysis.labels, false);
            supplierChart.redraw();
        }

        if (costChart && chartData.costBreakdown) {
            const costData = chartData.costBreakdown.labels.map((label, index) => ({
                name: label,
                y: chartData.costBreakdown.series[index]
            }));
            costChart.series[0].setData(costData, true);
        }

        if (topItemsChart && chartData.topItems) {
            const topItemsData = chartData.topItems.map(item => item.total_cost);
            const topItemsLabels = chartData.topItems.map(item => item.name);
            topItemsChart.series[0].setData(topItemsData, false);
            topItemsChart.xAxis[0].setCategories(topItemsLabels, false);
            topItemsChart.redraw();
        }
    }

    function getThemeColors() {
        const isDark = document.documentElement.classList.contains('dark');
        return {
            textColor: isDark ? '#e4e4e7' : '#27272a',
            gridColor: isDark ? '#3f3f46' : '#e4e4e7',
            backgroundColor: isDark ? '#27272a' : '#ffffff'
        };
    }

    // Initialize charts when script loads
    if (typeof Highcharts !== 'undefined') {
        initCharts();
    } else {
        // Wait for Highcharts to load
        setTimeout(() => {
            if (typeof Highcharts !== 'undefined') {
                initCharts();
            }
        }, 100);
    }
</script>
    @endpush
</div>
