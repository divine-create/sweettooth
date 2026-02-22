<div class="p-3 space-y-3">
    <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-sm border border-zinc-200 dark:border-zinc-700 p-4">
        <h2 class="text-lg font-semibold text-zinc-800 dark:text-zinc-100 mb-6">Request & Dispatch Analytics</h2>

        <div class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-6 gap-4 mb-6">
            <div class="bg-blue-50 dark:bg-blue-900/20 rounded-lg shadow-sm p-4">
                <p class="text-sm text-gray-600 dark:text-gray-400">Total Requests</p>
                <p class="text-2xl font-bold text-blue-600 dark:text-blue-500">{{ number_format($summary['total_requests']) }}</p>
            </div>
            <div class="bg-yellow-50 dark:bg-yellow-900/20 rounded-lg shadow-sm p-4">
                <p class="text-sm text-gray-600 dark:text-gray-400">Pending</p>
                <p class="text-2xl font-bold text-yellow-600 dark:text-yellow-500">{{ number_format($summary['pending']) }}</p>
            </div>
            <div class="bg-purple-50 dark:bg-purple-900/20 rounded-lg shadow-sm p-4">
                <p class="text-sm text-gray-600 dark:text-gray-400">Approved</p>
                <p class="text-2xl font-bold text-purple-600 dark:text-purple-500">{{ number_format($summary['approved']) }}</p>
            </div>
            <div class="bg-green-50 dark:bg-green-900/20 rounded-lg shadow-sm p-4">
                <p class="text-sm text-gray-600 dark:text-gray-400">Completed</p>
                <p class="text-2xl font-bold text-green-600 dark:text-green-500">{{ number_format($summary['completed']) }}</p>
            </div>
            <div class="bg-indigo-50 dark:bg-indigo-900/20 rounded-lg shadow-sm p-4">
                <p class="text-sm text-gray-600 dark:text-gray-400">Total Dispatches</p>
                <p class="text-2xl font-bold text-indigo-600 dark:text-indigo-500">{{ number_format($summary['total_dispatches']) }}</p>
            </div>
            <div class="bg-teal-50 dark:bg-teal-900/20 rounded-lg shadow-sm p-4">
                <p class="text-sm text-gray-600 dark:text-gray-400">Fulfillment Rate</p>
                <p class="text-2xl font-bold text-teal-600 dark:text-teal-500">{{ number_format($summary['fulfillment_rate'], 1) }}%</p>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
            <select wire:model.live="statusFilter" class="w-full px-4 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-800 dark:text-zinc-200 focus:ring-2 focus:ring-blue-500">
                <option value="">All Status</option>
                <option value="pending">Pending</option>
                <option value="approved">Approved</option>
                <option value="completed">Completed</option>
                <option value="cancelled">Cancelled</option>
            </select>
            <div>
                <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">From</label>
                <input type="date" wire:model.live="dateFrom" class="w-full px-4 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-800 dark:text-zinc-200 focus:ring-2 focus:ring-blue-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">To</label>
                <input type="date" wire:model.live="dateTo" class="w-full px-4 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-800 dark:text-zinc-200 focus:ring-2 focus:ring-blue-500">
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
            <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-sm border border-zinc-200 dark:border-zinc-700 p-4">
                <h3 class="text-base font-semibold text-zinc-800 dark:text-zinc-100 mb-4">Request Trend</h3>
                <div id="requestTrendChart" class="h-80" wire:ignore>
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
                <h3 class="text-base font-semibold text-zinc-800 dark:text-zinc-100 mb-4">Department Analysis</h3>
                <div id="departmentChart" class="h-80" wire:ignore>
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
                <h3 class="text-base font-semibold text-zinc-800 dark:text-zinc-100 mb-4">Fulfillment Status</h3>
                <div id="fulfillmentChart" class="h-80" wire:ignore>
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

        <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-sm border border-zinc-200 dark:border-zinc-700 p-4">
            <h3 class="text-base font-semibold text-zinc-800 dark:text-zinc-100 mb-4">Recent Requests</h3>
            <div class="overflow-x-auto">
                <table class="w-full text-sm text-left">
                    <thead class="text-xs uppercase bg-zinc-100 dark:bg-zinc-700 text-zinc-700 dark:text-zinc-300">
                        <tr>
                            <th class="px-4 py-3">Request #</th>
                            <th class="px-4 py-3">Date</th>
                            <th class="px-4 py-3">Department</th>
                            <th class="px-4 py-3">Requested By</th>
                            <th class="px-4 py-3">Status</th>
                            <th class="px-4 py-3">Shift</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                        @forelse($requests as $request)
                            <tr class="bg-white dark:bg-zinc-800 hover:bg-zinc-50 dark:hover:bg-zinc-700/50">
                                <td class="px-4 py-3 text-zinc-900 dark:text-zinc-100">{{ $request->request_number }}</td>
                                <td class="px-4 py-3 text-zinc-900 dark:text-zinc-100">{{ \Carbon\Carbon::parse($request->request_date)->format('M d, Y') }}</td>
                                <td class="px-4 py-3 text-zinc-900 dark:text-zinc-100">{{ $request->department->name ?? 'N/A' }}</td>
                                <td class="px-4 py-3 text-zinc-900 dark:text-zinc-100">{{ $request->requestedBy->name ?? 'N/A' }}</td>
                                <td class="px-4 py-3">
                                    @php
                                        $badgeColors = match($request->status) {
                                            'pending' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200',
                                            'approved' => 'bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-200',
                                            'completed' => 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200',
                                            'cancelled' => 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200',
                                            default => 'bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-200'
                                        };
                                    @endphp
                                    <span class="px-2 py-1 text-xs font-semibold rounded {{ $badgeColors }}">
                                        {{ ucfirst($request->status) }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-zinc-900 dark:text-zinc-100">{{ $request->shift ? ucfirst($request->shift) : 'N/A' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-4 py-8 text-center text-gray-500 dark:text-gray-400">No requests found</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="mt-4">{{ $requests->links() }}</div>
        </div>
    </div>

    @push('scripts')
<script src="https://code.highcharts.com/highcharts.js"></script>
<script src="https://code.highcharts.com/modules/exporting.js"></script>
<script src="https://code.highcharts.com/modules/export-data.js"></script>
<script src="https://code.highcharts.com/modules/accessibility.js"></script>

<script>
    let trendChart, departmentChart, fulfillmentChart;
    let chartData = {
        trendData: @js($trendData),
        departmentAnalysis: @js($departmentAnalysis),
        fulfillmentRate: @js($fulfillmentRate)
    };

    document.addEventListener('DOMContentLoaded', function () {
        initCharts();
    });

    document.addEventListener('livewire:navigated', function () {
        initCharts();
    });

    // Listen for chart update events from Livewire
    document.addEventListener('livewire:init', () => {
        Livewire.on('chartsUpdated', (event) => {
            const data = event[0];
            chartData.trendData = data.trendData;
            chartData.departmentAnalysis = data.departmentAnalysis;
            chartData.fulfillmentRate = data.fulfillmentRate;
            updateCharts();
        });
    });

    function initCharts() {
        // Destroy existing charts if they exist
        if (trendChart) trendChart.destroy();
        if (departmentChart) departmentChart.destroy();
        if (fulfillmentChart) fulfillmentChart.destroy();

        // Clear loading spinners
        const trendContainer = document.getElementById('requestTrendChart');
        const deptContainer = document.getElementById('departmentChart');
        const fulfillContainer = document.getElementById('fulfillmentChart');
        if (trendContainer) trendContainer.innerHTML = '';
        if (deptContainer) deptContainer.innerHTML = '';
        if (fulfillContainer) fulfillContainer.innerHTML = '';

        const themeColors = getThemeColors();

        // Request Trend Chart - Line/Spline Chart
        trendChart = Highcharts.chart('requestTrendChart', {
            chart: {
                type: 'spline',
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
                gridLineColor: themeColors.gridColor
            },
            yAxis: {
                title: {
                    text: 'Number of Requests',
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
                backgroundColor: themeColors.backgroundColor,
                borderWidth: 1,
                borderRadius: 8,
                shadow: true,
                style: {
                    color: themeColors.textColor
                }
            },
            plotOptions: {
                spline: {
                    marker: {
                        enabled: true,
                        radius: 4,
                        lineWidth: 2,
                        lineColor: '#ffffff'
                    },
                    lineWidth: 3
                }
            },
            series: chartData.trendData.series.map((s, index) => ({
                name: s.name,
                data: s.data,
                color: ['#F59E0B', '#8B5CF6', '#10B981'][index]
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

        // Department Analysis Chart - Donut Chart
        const departmentData = chartData.departmentAnalysis.labels.map((label, index) => ({
            name: label,
            y: chartData.departmentAnalysis.series[index]
        }));

        departmentChart = Highcharts.chart('departmentChart', {
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
                pointFormat: '<b>{point.y}</b> requests ({point.percentage:.1f}%)',
                backgroundColor: themeColors.backgroundColor,
                borderWidth: 1,
                borderRadius: 8,
                shadow: true,
                style: {
                    color: themeColors.textColor
                }
            },
            plotOptions: {
                pie: {
                    innerSize: '50%', // Makes it a donut
                    allowPointSelect: true,
                    cursor: 'pointer',
                    dataLabels: {
                        enabled: true,
                        format: '<b>{point.name}</b>: {point.percentage:.1f}%',
                        style: {
                            fontSize: '11px',
                            color: themeColors.textColor
                        }
                    },
                    showInLegend: true
                }
            },
            series: [{
                name: 'Requests',
                colorByPoint: true,
                data: departmentData
            }],
            colors: ['#3B82F6', '#10B981', '#F59E0B', '#EF4444', '#8B5CF6', '#EC4899', '#6366F1', '#14B8A6'],
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

        // Fulfillment Status Chart - Pie Chart
        const fulfillmentData = chartData.fulfillmentRate.labels.map((label, index) => ({
            name: label,
            y: chartData.fulfillmentRate.series[index]
        }));

        fulfillmentChart = Highcharts.chart('fulfillmentChart', {
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
                pointFormat: '<b>{point.y}</b> requests ({point.percentage:.1f}%)',
                backgroundColor: themeColors.backgroundColor,
                borderWidth: 1,
                borderRadius: 8,
                shadow: true,
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
                        format: '<b>{point.name}</b>: {point.percentage:.1f}%',
                        style: {
                            fontSize: '11px',
                            color: themeColors.textColor
                        }
                    },
                    showInLegend: true
                }
            },
            series: [{
                name: 'Status',
                colorByPoint: true,
                data: fulfillmentData
            }],
            colors: ['#F59E0B', '#8B5CF6', '#10B981', '#EF4444', '#6366F1', '#14B8A6'],
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
            chartData.trendData.series.forEach((s, index) => {
                if (trendChart.series[index]) {
                    trendChart.series[index].setData(s.data, false);
                }
            });
            trendChart.xAxis[0].setCategories(chartData.trendData.categories, false);
            trendChart.redraw();
        }

        // Update Department Chart
        if (departmentChart && chartData.departmentAnalysis) {
            const departmentData = chartData.departmentAnalysis.labels.map((label, index) => ({
                name: label,
                y: chartData.departmentAnalysis.series[index]
            }));
            departmentChart.series[0].setData(departmentData, true);
        }

        // Update Fulfillment Chart
        if (fulfillmentChart && chartData.fulfillmentRate) {
            const fulfillmentData = chartData.fulfillmentRate.labels.map((label, index) => ({
                name: label,
                y: chartData.fulfillmentRate.series[index]
            }));
            fulfillmentChart.series[0].setData(fulfillmentData, true);
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

    initCharts();
</script>
    @endpush
</div>
