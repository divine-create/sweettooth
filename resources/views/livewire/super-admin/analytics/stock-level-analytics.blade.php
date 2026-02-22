<div class="p-3 space-y-3">
    {{-- Header --}}
    <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-sm border border-zinc-200 dark:border-zinc-700 p-4">
        <div class="flex items-center justify-between mb-4">
            <h2 class="text-lg font-semibold text-zinc-800 dark:text-zinc-100">Stock Level Analytics</h2>
            <div class="flex gap-2">
                <button wire:click="exportCSV" class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium bg-green-600 hover:bg-green-700 text-white rounded-lg transition-all duration-200 hover:shadow-lg active:scale-95">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2m0 0v-8m0 8H3m15 0h3"/>
                    </svg>
                    CSV
                </button>

                <button wire:click="exportCSV" class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg transition-all duration-200 hover:shadow-lg active:scale-95">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                    Excel
                </button>
            </div>
        </div>

        {{-- Summary Cards --}}
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
            <div class="bg-blue-50 dark:bg-blue-900/20 rounded-lg shadow-sm p-4">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-600 dark:text-gray-400">Total Items</p>
                        <p class="text-2xl font-bold text-blue-600 dark:text-blue-500">{{ number_format($summary['total_items']) }}</p>
                    </div>
                    <svg class="w-12 h-12 text-blue-500 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                    </svg>
                </div>
            </div>

            <div class="bg-green-50 dark:bg-green-900/20 rounded-lg shadow-sm p-4">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-600 dark:text-gray-400">Available Stock</p>
                        <p class="text-2xl font-bold text-green-600 dark:text-green-500">{{ number_format($summary['total_available'], 2) }}</p>
                    </div>
                    <svg class="w-12 h-12 text-green-500 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
            </div>

            <div class="bg-yellow-50 dark:bg-yellow-900/20 rounded-lg shadow-sm p-4">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-600 dark:text-gray-400">Low Stock Items</p>
                        <p class="text-2xl font-bold text-yellow-600 dark:text-yellow-500">{{ number_format($summary['low_stock_count']) }}</p>
                    </div>
                    <svg class="w-12 h-12 text-yellow-500 dark:text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                    </svg>
                </div>
            </div>

            <div class="bg-red-50 dark:bg-red-900/20 rounded-lg shadow-sm p-4">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-600 dark:text-gray-400">Critical/Expired</p>
                        <p class="text-2xl font-bold text-red-600 dark:text-red-500">{{ number_format($summary['critical_items'] + $summary['expired_items']) }}</p>
                    </div>
                    <svg class="w-12 h-12 text-red-500 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
            </div>
        </div>

        {{-- Filters --}}
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
                <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Select Item</label>
                <x-select.styled
                    wire:model.live="selectedItem"
                    :options="$availableItems"
                    select="label:name|value:id"
                    searchable
                    placeholder="Search items..."
                />
            </div>

            <div>
                <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Category</label>
                <select wire:model.live="selectedCategory"
                    class="w-full px-4 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-800 dark:text-zinc-200 focus:ring-2 focus:ring-blue-500">
                    <option value="">All Categories</option>
                    @foreach($categories as $category)
                        <option value="{{ $category }}">{{ str_replace('_', ' ', ucfirst($category)) }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">From</label>
                <input type="date" wire:model.live="dateFrom"
                    class="w-full px-4 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-800 dark:text-zinc-200 focus:ring-2 focus:ring-blue-500">
            </div>

            <div>
                <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">To</label>
                <input type="date" wire:model.live="dateTo"
                    class="w-full px-4 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-800 dark:text-zinc-200 focus:ring-2 focus:ring-blue-500">
            </div>
        </div>
    </div>

    {{-- Charts Row 1 --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
        {{-- Stock Level Trend --}}
        <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-sm border border-zinc-200 dark:border-zinc-700 p-4">
            <h3 class="text-base font-semibold text-zinc-800 dark:text-zinc-100 mb-4">Stock Level Trend</h3>
            <div id="stockLevelTrendChart" class="h-80" wire:ignore>
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

        {{-- Health Status Distribution --}}
        <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-sm border border-zinc-200 dark:border-zinc-700 p-4">
            <h3 class="text-base font-semibold text-zinc-800 dark:text-zinc-100 mb-4">Health Status Distribution</h3>
            <div id="healthDistributionChart" class="h-80" wire:ignore>
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

    {{-- Charts Row 2 --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
        {{-- Low Stock Items --}}
        <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-sm border border-zinc-200 dark:border-zinc-700 p-4">
            <h3 class="text-base font-semibold text-zinc-800 dark:text-zinc-100 mb-4">Top 10 Low Stock Items</h3>
            <div class="space-y-3">
                @forelse($lowStockItems as $item)
                    <div class="flex items-center justify-between p-3 bg-gray-50 dark:bg-zinc-700/50 rounded">
                        <div>
                            <p class="font-medium text-zinc-900 dark:text-zinc-100">{{ $item->item->name }}</p>
                            <p class="text-sm text-gray-500 dark:text-gray-400">{{ $item->item->sku }}</p>
                        </div>
                        <div class="text-right">
                            <p class="font-bold text-red-600 dark:text-red-400">{{ number_format($item->quantity_available, 2) }}</p>
                            <p class="text-xs text-gray-500 dark:text-gray-400">{{ $item->item->uom }}</p>
                        </div>
                    </div>
                @empty
                    <div class="text-center py-4 text-gray-500 dark:text-gray-400">
                        No low stock items
                    </div>
                @endforelse
            </div>
        </div>

        {{-- Stock Turnover Analysis --}}
        <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-sm border border-zinc-200 dark:border-zinc-700 p-4">
            <h3 class="text-base font-semibold text-zinc-800 dark:text-zinc-100 mb-4">Stock Turnover Analysis</h3>
            <div id="turnoverChart" class="h-80" wire:ignore>
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

    {{-- Category Distribution --}}
    <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-sm border border-zinc-200 dark:border-zinc-700 p-4">
        <h3 class="text-base font-semibold text-zinc-800 dark:text-zinc-100 mb-4">Category Distribution</h3>
        <div id="categoryDistributionChart" class="h-80" wire:ignore>
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

    {{-- Stock Items Table --}}
    <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-sm border border-zinc-200 dark:border-zinc-700 p-4">
        <h3 class="text-base font-semibold text-zinc-800 dark:text-zinc-100 mb-4">Stock Items Details</h3>
        <div class="overflow-x-auto" wire:loading.class="opacity-50" wire:target="updatedSearchTerm, updatedSelectedCategory, updatedHealthFilter, updatedDateFrom, updatedDateTo">
            <table class="w-full text-sm text-left">
                <thead class="text-xs uppercase bg-zinc-100 dark:bg-zinc-700 text-zinc-700 dark:text-zinc-300">
                    <tr>
                        <th class="px-4 py-3">Item</th>
                        <th class="px-4 py-3">Category</th>
                        <th class="px-4 py-3">Available</th>
                        <th class="px-4 py-3">Reserved</th>
                        <th class="px-4 py-3">Damaged</th>
                        <th class="px-4 py-3">Reorder Level</th>
                        <th class="px-4 py-3">Health Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                    @forelse($stocks as $stock)
                        <tr class="bg-white dark:bg-zinc-800 hover:bg-zinc-50 dark:hover:bg-zinc-700/50">
                            <td class="px-4 py-3">
                                <div class="font-medium text-zinc-900 dark:text-zinc-100">{{ $stock->item->name }}</div>
                                <div class="text-xs text-gray-500 dark:text-gray-400">{{ $stock->item->sku }}</div>
                            </td>
                            <td class="px-4 py-3">
                                <span class="px-2 py-1 text-xs font-semibold rounded bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200">
                                    {{ str_replace('_', ' ', ucfirst($stock->item->category)) }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-zinc-900 dark:text-zinc-100">
                                {{ number_format($stock->quantity_available, 2) }} {{ $stock->item->uom }}
                            </td>
                            <td class="px-4 py-3 text-zinc-900 dark:text-zinc-100">
                                {{ number_format($stock->quantity_reserved, 2) }} {{ $stock->item->uom }}
                            </td>
                            <td class="px-4 py-3 text-zinc-900 dark:text-zinc-100">
                                {{ number_format($stock->quantity_damaged, 2) }} {{ $stock->item->uom }}
                            </td>
                            <td class="px-4 py-3 text-zinc-900 dark:text-zinc-100">
                                @if($stock->item->reorder_level)
                                    {{ number_format($stock->item->reorder_level, 2) }}
                                @else
                                    <span class="text-gray-400 dark:text-gray-500">Not set</span>
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                @php
                                    $badgeColors = match($stock->health_status) {
                                        'good' => 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200',
                                        'warning' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200',
                                        'critical' => 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200',
                                        'expired' => 'bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-200',
                                        default => 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200'
                                    };
                                @endphp
                                <span class="px-2 py-1 text-xs font-semibold rounded {{ $badgeColors }}">
                                    {{ ucfirst($stock->health_status) }}
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-8 text-center text-gray-500 dark:text-gray-400">
                                No stock items found
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-4">
            {{ $stocks->links() }}
        </div>
    </div>


    @push('scripts')
<script src="https://code.highcharts.com/highcharts.js"></script>
<script src="https://code.highcharts.com/modules/exporting.js"></script>
<script src="https://code.highcharts.com/modules/export-data.js"></script>
<script src="https://code.highcharts.com/modules/accessibility.js"></script>

<script>
    let trendChart, healthChart, turnoverChart, categoryChart;
    let chartData = {
        trendData: @js($trendData),
        healthDistribution: @js($healthDistribution),
        turnoverAnalysis: @js($turnoverAnalysis),
        categoryDistribution: @js($categoryDistribution)
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

    // Listen for chart update events from Livewire
    document.addEventListener('livewire:init', () => {
        Livewire.on('chartsUpdated', (event) => {
            const data = event[0];
            chartData.trendData = data.trendData;
            chartData.healthDistribution = data.healthDistribution;
            chartData.turnoverAnalysis = data.turnoverAnalysis;
            chartData.categoryDistribution = data.categoryDistribution;
            updateCharts();
        });
    });

    function initCharts() {
        if (typeof Highcharts === 'undefined') {
            console.error('Highcharts is not loaded');
            return;
        }

        // Destroy existing charts if they exist
        if (trendChart) trendChart.destroy();
        if (healthChart) healthChart.destroy();
        if (turnoverChart) turnoverChart.destroy();
        if (categoryChart) categoryChart.destroy();

        // Clear loading spinners
        const trendContainer = document.getElementById('stockLevelTrendChart');
        const healthContainer = document.getElementById('healthDistributionChart');
        const turnoverContainer = document.getElementById('turnoverChart');
        const categoryContainer = document.getElementById('categoryDistributionChart');
        if (trendContainer) trendContainer.innerHTML = '';
        if (healthContainer) healthContainer.innerHTML = '';
        if (turnoverContainer) turnoverContainer.innerHTML = '';
        if (categoryContainer) categoryContainer.innerHTML = '';

        const themeColors = getThemeColors();

        // Stock Level Trend Chart - Area/Spline Chart with Gradient
        trendChart = Highcharts.chart('stockLevelTrendChart', {
            chart: {
                type: 'areaspline',
                height: 320,
                backgroundColor: 'transparent'
            },
            title: {
                text: null
            },
            credits: {
                enabled: false
            },
            xAxis: {
                categories: chartData.trendData.categories,
                title: {
                    text: 'Date',
                    style: {
                        color: themeColors.textColor
                    }
                },
                labels: {
                    style: {
                        color: themeColors.textColor
                    }
                },
                gridLineColor: themeColors.gridColor,
                gridLineWidth: 1,
                gridLineDashStyle: 'Dot'
            },
            yAxis: {
                title: {
                    text: 'Stock Level',
                    style: {
                        color: themeColors.textColor
                    }
                },
                labels: {
                    style: {
                        color: themeColors.textColor
                    }
                },
                gridLineColor: themeColors.gridColor,
                min: 0
            },
            tooltip: {
                shared: true,
                crosshairs: true,
                valueDecimals: 2,
                backgroundColor: themeColors.backgroundColor,
                borderWidth: 1,
                borderRadius: 8,
                shadow: true,
                style: {
                    color: themeColors.textColor
                }
            },
            plotOptions: {
                areaspline: {
                    fillOpacity: 0.3,
                    marker: {
                        enabled: true,
                        radius: 5,
                        lineWidth: 2,
                        lineColor: '#ffffff'
                    },
                    lineWidth: 3
                }
            },
            series: chartData.trendData.series.map((serie, index) => ({
                name: serie.name,
                data: serie.data,
                color: index === 0 ? '#3B82F6' : '#10B981',
                fillColor: {
                    linearGradient: { x1: 0, y1: 0, x2: 0, y2: 1 },
                    stops: [
                        [0, index === 0 ? 'rgba(59, 130, 246, 0.5)' : 'rgba(16, 185, 129, 0.5)'],
                        [1, index === 0 ? 'rgba(59, 130, 246, 0.05)' : 'rgba(16, 185, 129, 0.05)']
                    ]
                }
            })),
            legend: {
                align: 'center',
                verticalAlign: 'top',
                floating: false,
                backgroundColor: themeColors.backgroundColor,
                borderWidth: 1,
                borderColor: themeColors.gridColor,
                borderRadius: 5,
                itemStyle: {
                    color: themeColors.textColor
                },
                itemHoverStyle: {
                    color: themeColors.textColor
                }
            },
            exporting: {
                enabled: true,
                buttons: {
                    contextButton: {
                        menuItems: ['viewFullscreen', 'separator', 'downloadPNG', 'downloadJPEG', 'downloadPDF', 'downloadSVG', 'separator', 'downloadCSV', 'downloadXLS']
                    }
                }
            }
        });

        // Health Status Distribution Chart - Pie Chart
        const healthData = chartData.healthDistribution.labels.map((label, index) => ({
            name: label,
            y: chartData.healthDistribution.series[index]
        }));

        healthChart = Highcharts.chart('healthDistributionChart', {
            chart: {
                type: 'pie',
                height: 320,
                backgroundColor: 'transparent'
            },
            title: {
                text: null
            },
            credits: {
                enabled: false
            },
            tooltip: {
                pointFormat: '<b>{point.y}</b> items ({point.percentage:.1f}%)',
                backgroundColor: themeColors.backgroundColor,
                borderWidth: 1,
                style: {
                    color: themeColors.textColor
                }
            },
            plotOptions: {
                pie: {
                    allowPointSelect: true,
                    cursor: 'pointer',
                    dataLabels: {
                        enabled: true,
                        format: '<b>{point.name}</b>: {point.percentage:.1f} %',
                        style: {
                            fontSize: '11px',
                            color: themeColors.textColor
                        }
                    },
                    showInLegend: true
                }
            },
            series: [{
                name: 'Health Status',
                colorByPoint: true,
                data: healthData
            }],
            colors: ['#10B981', '#F59E0B', '#EF4444', '#6B7280'],
            legend: {
                align: 'center',
                verticalAlign: 'bottom',
                layout: 'horizontal',
                itemStyle: {
                    color: themeColors.textColor
                },
                itemHoverStyle: {
                    color: themeColors.textColor
                }
            },
            exporting: {
                enabled: true,
                buttons: {
                    contextButton: {
                        menuItems: ['viewFullscreen', 'separator', 'downloadPNG', 'downloadJPEG', 'downloadPDF', 'downloadSVG', 'separator', 'downloadCSV', 'downloadXLS']
                    }
                }
            }
        });

        // Turnover Analysis Chart - Horizontal Bar Chart
        turnoverChart = Highcharts.chart('turnoverChart', {
            chart: {
                type: 'bar',
                height: 320,
                backgroundColor: 'transparent'
            },
            title: {
                text: null
            },
            credits: {
                enabled: false
            },
            xAxis: {
                categories: chartData.turnoverAnalysis.labels,
                title: {
                    text: null
                },
                labels: {
                    style: {
                        color: themeColors.textColor
                    }
                },
                gridLineColor: themeColors.gridColor
            },
            yAxis: {
                min: 0,
                title: {
                    text: 'Total Quantity Moved',
                    align: 'high',
                    style: {
                        color: themeColors.textColor
                    }
                },
                labels: {
                    style: {
                        color: themeColors.textColor
                    }
                },
                gridLineColor: themeColors.gridColor
            },
            tooltip: {
                valueSuffix: ' units',
                backgroundColor: themeColors.backgroundColor,
                borderWidth: 1,
                style: {
                    color: themeColors.textColor
                }
            },
            plotOptions: {
                bar: {
                    dataLabels: {
                        enabled: true,
                        style: {
                            color: themeColors.textColor
                        }
                    },
                    colorByPoint: true
                }
            },
            series: [{
                name: 'Total Quantity Moved',
                data: chartData.turnoverAnalysis.series[0].data,
                showInLegend: false
            }],
            colors: ['#3B82F6', '#10B981', '#F59E0B', '#EF4444', '#8B5CF6', '#EC4899', '#6366F1', '#14B8A6', '#F97316', '#84CC16'],
            exporting: {
                enabled: true,
                buttons: {
                    contextButton: {
                        menuItems: ['viewFullscreen', 'separator', 'downloadPNG', 'downloadJPEG', 'downloadPDF', 'downloadSVG', 'separator', 'downloadCSV', 'downloadXLS']
                    }
                }
            }
        });

        // Category Distribution Chart - Pie Chart
        const categoryData = chartData.categoryDistribution.labels.map((label, index) => ({
            name: label,
            y: chartData.categoryDistribution.series[index]
        }));

        categoryChart = Highcharts.chart('categoryDistributionChart', {
            chart: {
                type: 'pie',
                height: 320,
                backgroundColor: 'transparent'
            },
            title: {
                text: null
            },
            credits: {
                enabled: false
            },
            tooltip: {
                pointFormat: '<b>{point.y}</b> items ({point.percentage:.1f}%)',
                backgroundColor: themeColors.backgroundColor,
                borderWidth: 1,
                style: {
                    color: themeColors.textColor
                }
            },
            plotOptions: {
                pie: {
                    allowPointSelect: true,
                    cursor: 'pointer',
                    dataLabels: {
                        enabled: true,
                        format: '<b>{point.name}</b>: {point.percentage:.1f} %',
                        style: {
                            fontSize: '11px',
                            color: themeColors.textColor
                        }
                    },
                    showInLegend: true
                }
            },
            series: [{
                name: 'Categories',
                colorByPoint: true,
                data: categoryData
            }],
            colors: ['#3B82F6', '#10B981', '#F59E0B', '#EF4444', '#8B5CF6', '#EC4899'],
            legend: {
                align: 'center',
                verticalAlign: 'bottom',
                layout: 'horizontal',
                itemStyle: {
                    color: themeColors.textColor
                },
                itemHoverStyle: {
                    color: themeColors.textColor
                }
            },
            exporting: {
                enabled: true,
                buttons: {
                    contextButton: {
                        menuItems: ['viewFullscreen', 'separator', 'downloadPNG', 'downloadJPEG', 'downloadPDF', 'downloadSVG', 'separator', 'downloadCSV', 'downloadXLS']
                    }
                }
            }
        });
    }

    function updateCharts() {
        // Update Trend Chart
        if (trendChart && chartData.trendData) {
            chartData.trendData.series.forEach((serie, index) => {
                if (trendChart.series[index]) {
                    trendChart.series[index].setData(serie.data, false);
                }
            });
            trendChart.xAxis[0].setCategories(chartData.trendData.categories, false);
            trendChart.redraw();
        }

        // Update Health Chart
        if (healthChart && chartData.healthDistribution) {
            const healthData = chartData.healthDistribution.labels.map((label, index) => ({
                name: label,
                y: chartData.healthDistribution.series[index]
            }));
            healthChart.series[0].setData(healthData, true);
        }

        // Update Turnover Chart
        if (turnoverChart && chartData.turnoverAnalysis) {
            turnoverChart.series[0].setData(chartData.turnoverAnalysis.series[0].data, false);
            turnoverChart.xAxis[0].setCategories(chartData.turnoverAnalysis.labels, false);
            turnoverChart.redraw();
        }
    }

    // Detect theme and return appropriate colors
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
