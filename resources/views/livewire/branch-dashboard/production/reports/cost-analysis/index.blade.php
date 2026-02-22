<div class="space-y-6">
    {{-- Header Section --}}
    <div class="flex justify-between items-center">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Cost Analysis Report</h1>
            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                Analyze production costs, material expenses, and cost efficiency metrics
            </p>
        </div>
        <div class="flex gap-2">
            <x-button wire:click="refresh" color="secondary" icon="arrow-path">
                Refresh
            </x-button>
            <x-button wire:click="generateReport" color="primary" icon="document-plus">
                Generate & Save Report
            </x-button>
        </div>
    </div>

    {{-- Filters Section --}}
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
        <div class="grid grid-cols-1 md:grid-cols-5 gap-4">
            {{-- Period Filter --}}
            <div>
                <x-select.native
                    label="Period"
                    wire:model.live="periodFilter"
                    :options="[
                        ['label' => 'Today', 'value' => 'today'],
                        ['label' => 'Yesterday', 'value' => 'yesterday'],
                        ['label' => 'This Week', 'value' => 'week'],
                        ['label' => 'This Month', 'value' => 'month'],
                        ['label' => 'Last Month', 'value' => 'last_month'],
                        ['label' => 'Custom Range', 'value' => 'custom'],
                    ]"
                    select="label:label|value:value"
                />
            </div>

            {{-- Custom Date From --}}
            <div>
                <x-input
                    label="From Date"
                    type="date"
                    wire:model="customDateFrom"
                    :disabled="$periodFilter !== 'custom'"
                />
            </div>

            {{-- Custom Date To --}}
            <div>
                <x-input
                    label="To Date"
                    type="date"
                    wire:model="customDateTo"
                    :disabled="$periodFilter !== 'custom'"
                />
            </div>
            @if(count($availableDepartments ?? []) > 0)
                <div>
                    <x-select.native label="Department" wire:model.live="selectedDepartmentId">
                        <option value="">Select Department</option>
                        @foreach($availableDepartments as $dept)
                            <option value="{{ $dept->id }}">{{ $dept->name }}</option>
                        @endforeach
                    </x-select.native>
                </div>
            @endif

            {{-- Generate Button --}}
            <div class="flex items-end">
                <x-button
                    wire:click="generatePreview"
                    wire:loading.attr="disabled"
                    wire:target="generatePreview"
                    color="primary"
                    class="w-full"
                >
                    <span wire:loading.remove wire:target="generatePreview">
                        <x-icon name="chart-bar" class="w-5 h-5 mr-2" />
                        Generate Preview
                    </span>
                    <span wire:loading wire:target="generatePreview">
                        <x-icon name="arrow-path" class="animate-spin w-5 h-5 mr-2" />
                        Generating...
                    </span>
                </x-button>
            </div>
        </div>
    </div>

    @if($reportData)
        {{-- Summary Metrics --}}
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            {{-- Total Material Cost --}}
            <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-4">
                <div class="flex items-center justify-between">
                    <div>
                        <div class="flex items-center gap-2">
                            <p class="text-sm font-medium text-blue-600 dark:text-blue-400">Material Cost</p>
                            <button wire:click="$set('showMetricModal', 'material_cost')" class="text-xs text-blue-400 hover:text-blue-600 dark:text-blue-500 dark:hover:text-blue-300">
                                <x-icon name="information-circle" class="w-4 h-4" />
                            </button>
                        </div>
                        <p class="mt-2 text-2xl font-bold text-blue-700 dark:text-blue-300">
                            {{ \App\Helpers\LocalizationHelper::formatCurrency($summaryMetrics['total_material_cost'] ?? 0) }}
                        </p>
                    </div>
                    <div class="p-2 bg-blue-100 dark:bg-blue-900 rounded-lg">
                        <x-icon name="cube" class="w-6 h-6 text-blue-600 dark:text-blue-400" />
                    </div>
                </div>
            </div>

            {{-- Total Production Cost --}}
            <div class="bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-lg p-4">
                <div class="flex items-center justify-between">
                    <div>
                        <div class="flex items-center gap-2">
                            <p class="text-sm font-medium text-green-600 dark:text-green-400">Production Cost</p>
                            <button wire:click="$set('showMetricModal', 'production_cost')" class="text-xs text-green-400 hover:text-green-600 dark:text-green-500 dark:hover:text-green-300">
                                <x-icon name="information-circle" class="w-4 h-4" />
                            </button>
                        </div>
                        <p class="mt-2 text-2xl font-bold text-green-700 dark:text-green-300">
                            {{ \App\Helpers\LocalizationHelper::formatCurrency($summaryMetrics['total_production_cost'] ?? 0) }}
                        </p>
                    </div>
                    <div class="p-2 bg-green-100 dark:bg-green-900 rounded-lg">
                        <x-icon name="cog" class="w-6 h-6 text-green-600 dark:text-green-400" />
                    </div>
                </div>
            </div>

            {{-- Total Cost --}}
            <div class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg p-4">
                <div class="flex items-center justify-between">
                    <div>
                        <div class="flex items-center gap-2">
                            <p class="text-sm font-medium text-red-600 dark:text-red-400">Total Cost</p>
                            <button wire:click="$set('showMetricModal', 'total_cost')" class="text-xs text-red-400 hover:text-red-600 dark:text-red-500 dark:hover:text-red-300">
                                <x-icon name="information-circle" class="w-4 h-4" />
                            </button>
                        </div>
                        <p class="mt-2 text-2xl font-bold text-red-700 dark:text-red-300">
                            {{ \App\Helpers\LocalizationHelper::formatCurrency($summaryMetrics['total_cost'] ?? 0) }}
                        </p>
                    </div>
                    <div class="p-2 bg-red-100 dark:bg-red-900 rounded-lg">
                        <x-icon name="currency-dollar" class="w-6 h-6 text-red-600 dark:text-red-400" />
                    </div>
                </div>
            </div>

            {{-- Cost per Unit --}}
            <div class="bg-purple-50 dark:bg-purple-900/20 border border-purple-200 dark:border-purple-800 rounded-lg p-4">
                <div class="flex items-center justify-between">
                    <div>
                        <div class="flex items-center gap-2">
                            <p class="text-sm font-medium text-purple-600 dark:text-purple-400">Cost per Unit</p>
                            <button wire:click="$set('showMetricModal', 'cost_per_unit')" class="text-xs text-purple-400 hover:text-purple-600 dark:text-purple-500 dark:hover:text-purple-300">
                                <x-icon name="information-circle" class="w-4 h-4" />
                            </button>
                        </div>
                        <p class="mt-2 text-2xl font-bold text-purple-700 dark:text-purple-300">
                            {{ \App\Helpers\LocalizationHelper::formatCurrency($summaryMetrics['cost_per_unit'] ?? 0) }}
                        </p>
                    </div>
                    <div class="p-2 bg-purple-100 dark:bg-purple-900 rounded-lg">
                        <x-icon name="calculator" class="w-6 h-6 text-purple-600 dark:text-purple-400" />
                    </div>
                </div>
            </div>
        </div>

        {{-- Charts Section --}}
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            {{-- Cost Breakdown Chart --}}
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Cost Breakdown</h3>
                <div id="cost-breakdown-chart" class="h-64"></div>
            </div>

            {{-- Cost Trends Chart --}}
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Daily Cost Trends</h3>
                <div id="cost-trends-chart" class="h-64"></div>
            </div>
        </div>

        {{-- Ingredient Cost Chart --}}
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Top Ingredient Costs</h3>
            <div id="ingredient-cost-chart" class="h-64"></div>
        </div>

        {{-- Product Efficiency Table --}}
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow">
            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Product Efficiency Analysis</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-900">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Product</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Planned</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Actual</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Variance</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Efficiency %</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                        @forelse($reportData['product_efficiency'] ?? [] as $product)
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white">
                                    {{ $product['product_name'] }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400 text-right">
                                    {{ number_format($product['planned']) }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400 text-right">
                                    {{ number_format($product['actual']) }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-right">
                                    <span class="{{ $product['variance'] >= 0 ? 'text-green-600' : 'text-red-600' }} font-medium">
                                        {{ $product['variance'] >= 0 ? '+' : '' }}{{ number_format($product['variance']) }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-right">
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full
                                        {{ $product['efficiency_percentage'] >= 95 ? 'bg-green-100 text-green-800' : ($product['efficiency_percentage'] >= 80 ? 'bg-yellow-100 text-yellow-800' : 'bg-red-100 text-red-800') }}">
                                        {{ number_format($product['efficiency_percentage'], 1) }}%
                                    </span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-6 py-4 text-center text-sm text-gray-500">
                                    No product data available for this period
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Export Actions --}}
        <div class="flex justify-end gap-4">
            <x-button wire:click="exportCsv" color="secondary" icon="document-arrow-down">
                Export CSV
            </x-button>
        </div>
    @else
        {{-- Empty State --}}
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-12">
            <div class="text-center">
                <x-icon name="chart-bar" class="mx-auto h-16 w-16 text-gray-400" />
                <h3 class="mt-4 text-lg font-medium text-gray-900 dark:text-white">No Report Generated</h3>
                <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                    Select a date range and click "Generate Preview" to view the production efficiency report
                </p>
            </div>
        </div>
@endif

@include('livewire.partials.department-select-modal')

    {{-- Reports History --}}
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow mt-6">
        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
            <h3 class="text-lg font-medium text-gray-900 dark:text-white">Reports History</h3>
            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">View previously generated production efficiency reports</p>
        </div>

        @if($savedReports->count() > 0)
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-900">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Report ID</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Period</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Generated</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                        @foreach($savedReports as $report)
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-900">
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white">
                                    {{ substr($report->id, 0, 8) }}...
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                    {{ \Carbon\Carbon::parse($report->period_from)->format('M d, Y') }} - {{ \Carbon\Carbon::parse($report->period_to)->format('M d, Y') }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full
                                        {{ $report->status === 'draft' ? 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200' : '' }}
                                        {{ $report->status === 'pending_review' ? 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200' : '' }}
                                        {{ $report->status === 'approved' ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' : '' }}
                                        {{ $report->status === 'rejected' ? 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200' : '' }}">
                                        {{ ucfirst(str_replace('_', ' ', $report->status)) }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                    {{ $report->created_at->format('M d, Y H:i') }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                    <div class="flex justify-end gap-2">
                                        <x-button wire:click="viewReport('{{ $report->id }}')" size="sm" color="secondary" icon="eye">
                                            View
                                        </x-button>
                                        <x-button wire:click="downloadReport('{{ $report->id }}')" size="sm" color="secondary" icon="document-arrow-down">
                                            Download
                                        </x-button>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="px-6 py-12 text-center">
                <x-icon name="document-text" class="mx-auto h-12 w-12 text-gray-400" />
                <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-white">No Reports Found</h3>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                    Generate your first production efficiency report to see it here.
                </p>
            </div>
    @endif

    {{-- Charts JavaScript --}}
    @if($reportData && $chartsData && count($chartsData) > 0)
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            console.log('Charts data:', @json($chartsData));

            // Cost Breakdown Chart (Pie Chart)
            if (document.getElementById('cost-breakdown-chart') && @json($chartsData['cost_breakdown_chart'])) {
                const costBreakdownData = @json($chartsData['cost_breakdown_chart']);

                const costBreakdownOptions = {
                    series: costBreakdownData.data,
                    chart: {
                        type: 'pie',
                        height: 250,
                        toolbar: {
                            show: false
                        },
                        background: 'transparent'
                    },
                    labels: costBreakdownData.labels,
                    colors: costBreakdownData.colors || ['#3b82f6', '#10b981'],
                    legend: {
                        position: 'bottom',
                        labels: {
                            colors: document.documentElement.classList.contains('dark') ? '#9ca3af' : '#6b7280'
                        }
                    },
                    tooltip: {
                        theme: document.documentElement.classList.contains('dark') ? 'dark' : 'light',
                        y: {
                            formatter: function(value) {
                                return '$' + value.toFixed(2);
                            }
                        }
                    },
                    theme: {
                        mode: document.documentElement.classList.contains('dark') ? 'dark' : 'light'
                    }
                };

                const costBreakdownChart = new ApexCharts(document.querySelector("#cost-breakdown-chart"), costBreakdownOptions);
                costBreakdownChart.render();
            }

            // Cost Trends Chart (Line Chart)
            if (document.getElementById('cost-trends-chart') && @json($chartsData['cost_trends_chart'])) {
                const costTrendsData = @json($chartsData['cost_trends_chart']);

                const costTrendsOptions = {
                    series: [{
                        name: costTrendsData.datasets[0].label,
                        data: costTrendsData.datasets[0].data,
                        color: costTrendsData.datasets[0].color || '#ef4444'
                    }],
                    chart: {
                        type: 'line',
                        height: 250,
                        toolbar: {
                            show: false
                        },
                        background: 'transparent'
                    },
                    stroke: {
                        curve: 'smooth',
                        width: 3
                    },
                    markers: {
                        size: 4,
                        hover: {
                            size: 6
                        }
                    },
                    xaxis: {
                        categories: costTrendsData.labels,
                        labels: {
                            style: {
                                colors: document.documentElement.classList.contains('dark') ? '#9ca3af' : '#6b7280'
                            }
                        }
                    },
                    yaxis: {
                        labels: {
                            style: {
                                colors: document.documentElement.classList.contains('dark') ? '#9ca3af' : '#6b7280'
                            },
                            formatter: function(value) {
                                return '$' + value.toFixed(0);
                            }
                        }
                    },
                    tooltip: {
                        theme: document.documentElement.classList.contains('dark') ? 'dark' : 'light',
                        y: {
                            formatter: function(value) {
                                return '$' + value.toFixed(2);
                            }
                        }
                    },
                    grid: {
                        borderColor: document.documentElement.classList.contains('dark') ? '#374151' : '#e5e7eb',
                        strokeDashArray: 3
                    },
                    theme: {
                        mode: document.documentElement.classList.contains('dark') ? 'dark' : 'light'
                    }
                };

                const costTrendsChart = new ApexCharts(document.querySelector("#cost-trends-chart"), costTrendsOptions);
                costTrendsChart.render();
            }

            // Ingredient Cost Chart (Bar Chart)
            if (document.getElementById('ingredient-cost-chart') && @json($chartsData['ingredient_cost_chart'])) {
                const ingredientChartData = @json($chartsData['ingredient_cost_chart']);

                const ingredientOptions = {
                    series: [{
                        name: ingredientChartData.datasets[0].label,
                        data: ingredientChartData.datasets[0].data
                    }],
                    chart: {
                        type: 'bar',
                        height: 250,
                        toolbar: {
                            show: false
                        },
                        background: 'transparent'
                    },
                    plotOptions: {
                        bar: {
                            borderRadius: 4,
                            columnWidth: '60%'
                        }
                    },
                    colors: [ingredientChartData.datasets[0].color || '#8b5cf6'],
                    xaxis: {
                        categories: ingredientChartData.labels,
                        labels: {
                            style: {
                                colors: document.documentElement.classList.contains('dark') ? '#9ca3af' : '#6b7280'
                            },
                            rotate: -45
                        }
                    },
                    yaxis: {
                        labels: {
                            style: {
                                colors: document.documentElement.classList.contains('dark') ? '#9ca3af' : '#6b7280'
                            },
                            formatter: function(value) {
                                return '$' + value.toFixed(0);
                            }
                        }
                    },
                    tooltip: {
                        theme: document.documentElement.classList.contains('dark') ? 'dark' : 'light',
                        y: {
                            formatter: function(value) {
                                return '$' + value.toFixed(2);
                            }
                        }
                    },
                    grid: {
                        borderColor: document.documentElement.classList.contains('dark') ? '#374151' : '#e5e7eb',
                        strokeDashArray: 3
                    },
                    theme: {
                        mode: document.documentElement.classList.contains('dark') ? 'dark' : 'light'
                    }
                };

                const ingredientChart = new ApexCharts(document.querySelector("#ingredient-cost-chart"), ingredientOptions);
                ingredientChart.render();
            }
                    ],
                    chart: {
                        type: 'line',
                        height: 250,
                        toolbar: {
                            show: false
                        },
                        background: 'transparent'
                    },
                    stroke: {
                        curve: 'smooth',
                        width: 3
                    },
                    markers: {
                        size: 4,
                        hover: {
                            size: 6
                        }
                    },
                    xaxis: {
                        categories: dailyChartData.labels,
                        labels: {
                            style: {
                                colors: document.documentElement.classList.contains('dark') ? '#9ca3af' : '#6b7280'
                            }
                        }
                    },
                    yaxis: {
                        labels: {
                            style: {
                                colors: document.documentElement.classList.contains('dark') ? '#9ca3af' : '#6b7280'
                            }
                        }
                    },
                    legend: {
                        labels: {
                            colors: document.documentElement.classList.contains('dark') ? '#9ca3af' : '#6b7280'
                        }
                    },
                    tooltip: {
                        theme: document.documentElement.classList.contains('dark') ? 'dark' : 'light'
                    },
                    grid: {
                        borderColor: document.documentElement.classList.contains('dark') ? '#374151' : '#e5e7eb',
                        strokeDashArray: 3
                    },
                    theme: {
                        mode: document.documentElement.classList.contains('dark') ? 'dark' : 'light'
                    }
                };

                const dailyChart = new ApexCharts(document.querySelector("#daily-efficiency-chart"), dailyOptions);
                dailyChart.render();
            }

            // Variance Distribution Chart (Pie Chart)
            if (document.getElementById('variance-distribution-chart') && @json($chartsData['variance_distribution'])) {
                const varianceChartData = @json($chartsData['variance_distribution']);

                const varianceOptions = {
                    series: varianceChartData.data,
                    chart: {
                        type: 'pie',
                        height: 250,
                        toolbar: {
                            show: false
                        },
                        background: 'transparent'
                    },
                    labels: varianceChartData.labels,
                    colors: varianceChartData.colors,
                    legend: {
                        position: 'bottom',
                        labels: {
                            colors: document.documentElement.classList.contains('dark') ? '#9ca3af' : '#6b7280'
                        }
                    },
                    tooltip: {
                        theme: document.documentElement.classList.contains('dark') ? 'dark' : 'light'
                    },
                    theme: {
                        mode: document.documentElement.classList.contains('dark') ? 'dark' : 'light'
                    },
                    responsive: [{
                        breakpoint: 480,
                        options: {
                            chart: {
                                width: 200
                            },
                            legend: {
                                position: 'bottom'
                            }
                        }
                    }]
                };

                const varianceChart = new ApexCharts(document.querySelector("#variance-distribution-chart"), varianceOptions);
                varianceChart.render();
            }

            // Product Efficiency Chart (Bar Chart)
            if (document.getElementById('product-efficiency-chart') && @json($chartsData['product_efficiency_chart'])) {
                const productChartData = @json($chartsData['product_efficiency_chart']);

                const productOptions = {
                    series: [{
                        name: productChartData.datasets[0].label,
                        data: productChartData.datasets[0].data
                    }],
                    chart: {
                        type: 'bar',
                        height: 250,
                        toolbar: {
                            show: false
                        },
                        background: 'transparent'
                    },
                    plotOptions: {
                        bar: {
                            borderRadius: 4,
                            columnWidth: '60%'
                        }
                    },
                    colors: [productChartData.datasets[0].color],
                    xaxis: {
                        categories: productChartData.labels,
                        labels: {
                            style: {
                                colors: document.documentElement.classList.contains('dark') ? '#9ca3af' : '#6b7280'
                            },
                            rotate: -45
                        }
                    },
                    yaxis: {
                        labels: {
                            style: {
                                colors: document.documentElement.classList.contains('dark') ? '#9ca3af' : '#6b7280'
                            }
                        }
                    },
                    tooltip: {
                        theme: document.documentElement.classList.contains('dark') ? 'dark' : 'light'
                    },
                    grid: {
                        borderColor: document.documentElement.classList.contains('dark') ? '#374151' : '#e5e7eb',
                        strokeDashArray: 3
                    },
                    theme: {
                        mode: document.documentElement.classList.contains('dark') ? 'dark' : 'light'
                    }
                };

                const productChart = new ApexCharts(document.querySelector("#product-efficiency-chart"), productOptions);
                productChart.render();
            }
        });

        // Re-render charts when Livewire updates
        document.addEventListener('livewire:updated', function() {
            // Destroy existing charts first
            if (window.dailyChart) window.dailyChart.destroy();
            if (window.varianceChart) window.varianceChart.destroy();
            if (window.productChart) window.productChart.destroy();

            // Charts will be re-initialized on DOMContentLoaded
            setTimeout(() => {
                document.dispatchEvent(new Event('DOMContentLoaded'));
            }, 100);
        });
    </script>
    @endif

</div>
    {{-- Metric Explanation Modal --}}
    @if($showMetricModal && $currentMetric)
        <x-modal wire:model="showMetricModal" title="Metric Explanation" size="md">
            <div class="space-y-4">
                @if($currentMetric === 'material_cost')
                    <div>
                        <h4 class="font-semibold text-lg text-gray-900 dark:text-white mb-2">Material Cost</h4>
                        <p class="text-gray-600 dark:text-gray-400 mb-3">
                            Total cost of all raw materials and ingredients dispatched for production during the selected period.
                        </p>
                        <div class="bg-blue-50 dark:bg-blue-900/20 p-3 rounded-lg">
                            <p class="text-sm text-blue-800 dark:text-blue-200">
                                <strong>Source:</strong> Sum of (dispatched_quantity × unit_cost) for all ingredients
                            </p>
                        </div>
                        <div class="mt-3 text-sm text-gray-600 dark:text-gray-400">
                            <p><strong>What it tells you:</strong> Raw material expenditure and procurement costs</p>
                            <p><strong>Use for:</strong> Cost control, supplier negotiations, and budget planning</p>
                        </div>
                    </div>
                @elseif($currentMetric === 'production_cost')
                    <div>
                        <h4 class="font-semibold text-lg text-gray-900 dark:text-white mb-2">Production Cost</h4>
                        <p class="text-gray-600 dark:text-gray-400 mb-3">
                            Total labor and operational costs incurred during the production process.
                        </p>
                        <div class="bg-green-50 dark:bg-green-900/20 p-3 rounded-lg">
                            <p class="text-sm text-green-800 dark:text-green-200">
                                <strong>Source:</strong> Sum of (produced_quantity × unit_cost) for all production records
                            </p>
                        </div>
                        <div class="mt-3 text-sm text-gray-600 dark:text-gray-400">
                            <p><strong>What it tells you:</strong> Manufacturing overhead and labor efficiency</p>
                            <p><strong>Use for:</strong> Process optimization and workforce planning</p>
                        </div>
                    </div>
                @elseif($currentMetric === 'total_cost')
                    <div>
                        <h4 class="font-semibold text-lg text-gray-900 dark:text-white mb-2">Total Cost</h4>
                        <p class="text-gray-600 dark:text-gray-400 mb-3">
                            Combined cost of materials and production for the selected time period.
                        </p>
                        <div class="bg-red-50 dark:bg-red-900/20 p-3 rounded-lg">
                            <p class="text-sm text-red-800 dark:text-red-200">
                                <strong>Formula:</strong> Material Cost + Production Cost
                            </p>
                        </div>
                        <div class="mt-3 text-sm text-gray-600 dark:text-gray-400">
                            <p><strong>What it tells you:</strong> Overall production expenditure</p>
                            <p><strong>Use for:</strong> Profitability analysis and cost reduction strategies</p>
                        </div>
                    </div>
                @elseif($currentMetric === 'cost_per_unit')
                    <div>
                        <h4 class="font-semibold text-lg text-gray-900 dark:text-white mb-2">Cost per Unit</h4>
                        <p class="text-gray-600 dark:text-gray-400 mb-3">
                            Average cost incurred to produce each unit of finished product.
                        </p>
                        <div class="bg-purple-50 dark:bg-purple-900/20 p-3 rounded-lg">
                            <p class="text-sm text-purple-800 dark:text-purple-200">
                                <strong>Formula:</strong> Total Cost ÷ Total Units Produced
                            </p>
                        </div>
                        <div class="mt-3 text-sm text-gray-600 dark:text-gray-400">
                            <p><strong>What it tells you:</strong> Production efficiency and cost effectiveness</p>
                            <p><strong>Use for:</strong> Pricing decisions and production scale analysis</p>
                        </div>
                    </div>
                @endif

                <div class="flex justify-end pt-4">
                    <x-button wire:click="$set('showMetricModal', false)" color="primary">
                        Got it
                    </x-button>
                </div>
            </div>
        </x-modal>
    @endif

    {{-- Report Modal --}}
    @if($showReportModal && $generatedReport)
        <x-modal wire:model="showReportModal" title="Report Generated Successfully" size="lg">
            <div class="space-y-4">
                <p class="text-sm text-gray-600 dark:text-gray-400">
                    Your production efficiency report has been generated and saved.
                </p>

                <div class="bg-gray-50 dark:bg-gray-900 rounded-lg p-4">
                    <dl class="grid grid-cols-2 gap-4">
                        <div>
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Report ID</dt>
                            <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ substr($generatedReport->id, 0, 8) }}...</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Status</dt>
                            <dd class="mt-1">
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-yellow-100 text-yellow-800">
                                    {{ ucfirst(str_replace('_', ' ', $generatedReport->status)) }}
                                </span>
                            </dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Period</dt>
                            <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ \Carbon\Carbon::parse($generatedReport->period_from)->format('M d, Y') }} - {{ \Carbon\Carbon::parse($generatedReport->period_to)->format('M d, Y') }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Generated</dt>
                            <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ $generatedReport->created_at->format('M d, Y H:i') }}</dd>
                        </div>
                    </dl>
                </div>

                <div class="flex flex-wrap justify-end gap-3 mt-6">
                    <a class="inline-flex items-center justify-center rounded-lg border border-zinc-200 px-3 py-2 text-sm font-semibold text-zinc-700 hover:bg-zinc-50 dark:border-zinc-700 dark:text-zinc-200 dark:hover:bg-zinc-800"
                       href="{{ branch_route('branch-dashboard.production.reporting.show', ['id' => $generatedReport->id, 'b_id' => current_branch_id()]) }}">
                        View Report
                    </a>
                    <a class="inline-flex items-center justify-center rounded-lg border border-zinc-200 px-3 py-2 text-sm font-semibold text-zinc-700 hover:bg-zinc-50 dark:border-zinc-700 dark:text-zinc-200 dark:hover:bg-zinc-800"
                       href="{{ branch_route('branch-dashboard.prints.department-report', ['reportId' => $generatedReport->id, 'b_id' => current_branch_id()]) }}" target="_blank">
                        Print
                    </a>
                    <x-button wire:click="$set('showReportModal', false)" color="secondary">
                        Close
                    </x-button>
                    <x-button wire:click="submitForReview('{{ $generatedReport->id }}')" color="primary">
                        Submit for Review
                    </x-button>
                </div>
            </div>
        </x-modal>
    @endif
</div>
