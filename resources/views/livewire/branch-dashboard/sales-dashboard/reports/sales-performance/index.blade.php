<div class="p-6 space-y-6">
    <x-breadcrumb title="Sales Performance Report" :items="[
        ['label' => 'Dashboard', 'url' => branch_route('branch-dashboard.index')],
        ['label' => 'Sales'],
        ['label' => 'Reports'],
        ['label' => 'Sales Performance'],
    ]" :compact="false" :with-icons="true" />

    {{-- Filters Section --}}
    <div class="bg-white dark:bg-zinc-800 rounded-lg shadow p-6">
        <div class="flex flex-wrap items-end gap-4">
            <div class="flex-1 min-w-[200px]">
                <x-select.native label="Period Filter" wire:model.live="periodFilter">
                    <option value="today">Today</option>
                    <option value="yesterday">Yesterday</option>
                    <option value="week">This Week</option>
                    <option value="month">This Month</option>
                    <option value="last_month">Last Month</option>
                    <option value="custom">Custom Range</option>
                </x-select.native>
            </div>

            @if($periodFilter === 'custom')
                <div class="flex-1 min-w-[200px]">
                    <x-input label="From Date" type="date" wire:model="customDateFrom" />
                </div>
                <div class="flex-1 min-w-[200px]">
                    <x-input label="To Date" type="date" wire:model="customDateTo" />
                </div>
            @endif

            @if(!$salesDeptSlug && count($availableDepartments ?? []) > 0)
                <div class="flex-1 min-w-[200px]">
                    <x-select.native label="Sales Department" wire:model.live="selectedDepartmentId">
                        <option value="">Select Department</option>
                        @foreach($availableDepartments as $dept)
                            <option value="{{ $dept->id }}">{{ $dept->name }}</option>
                        @endforeach
                    </x-select.native>
                </div>
            @endif

            <div class="flex-1 min-w-[150px]">
                <x-select.native label="Shift Type" wire:model.live="shiftType">
                    <option value="">All Shifts</option>
                    <option value="morning">Morning Shift</option>
                    <option value="afternoon">Afternoon Shift</option>
                    <option value="night">Evening Shift</option>
                </x-select.native>
            </div>

            @include('livewire.partials.department-select-modal')

            <div class="flex gap-2">
                <x-button color="primary" wire:click="generatePreview" :loading="$isLoading">
                    <x-icon name="arrow-path" class="w-4 h-4 mr-2" />
                    Generate Preview
                </x-button>

                @if($reportData)
                    <x-button color="secondary" wire:click="generateReport">
                        <x-icon name="document-check" class="w-4 h-4 mr-2" />
                        Save Report
                    </x-button>
                @endif
            </div>
        </div>
    </div>

    @if($reportData)
        @php
            $scopeNote = data_get($reportData, 'scope_note') ?? data_get($reportData, 'report_data.scope_note');
        @endphp
        @if($scopeNote)
            <div class="flex items-start gap-3 rounded-lg border border-amber-300 bg-amber-50 p-4 dark:border-amber-700/60 dark:bg-amber-900/20">
                <svg class="mt-0.5 h-5 w-5 flex-shrink-0 text-amber-600 dark:text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <p class="text-sm text-amber-800 dark:text-amber-200">{{ $scopeNote }}</p>
            </div>
        @endif

        {{-- Summary Metrics --}}
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4">
            <div class="bg-white dark:bg-zinc-800 rounded-lg shadow p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-zinc-600 dark:text-zinc-400">Total Revenue</p>
                        <p class="text-2xl font-bold text-zinc-900 dark:text-zinc-100 mt-1">
                            {{ \App\Helpers\LocalizationHelper::formatCurrency($summaryMetrics['total_revenue'] ?? 0) }}
                        </p>
                    </div>
                    <div class="p-3 bg-emerald-100 dark:bg-emerald-900 rounded-lg">
                        <x-icon name="currency-dollar" class="w-6 h-6 text-emerald-600 dark:text-emerald-400" />
                    </div>
                </div>
            </div>

            <div class="bg-white dark:bg-zinc-800 rounded-lg shadow p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-zinc-600 dark:text-zinc-400">Total Orders</p>
                        <p class="text-2xl font-bold text-zinc-900 dark:text-zinc-100 mt-1">
                            {{ number_format($summaryMetrics['total_orders'] ?? 0) }}
                        </p>
                    </div>
                    <div class="p-3 bg-blue-100 dark:bg-blue-900 rounded-lg">
                        <x-icon name="shopping-cart" class="w-6 h-6 text-blue-600 dark:text-blue-400" />
                    </div>
                </div>
            </div>

            <div class="bg-white dark:bg-zinc-800 rounded-lg shadow p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-zinc-600 dark:text-zinc-400">Avg Order Value</p>
                        <p class="text-2xl font-bold text-zinc-900 dark:text-zinc-100 mt-1">
                            {{ \App\Helpers\LocalizationHelper::formatCurrency($summaryMetrics['average_order_value'] ?? 0) }}
                        </p>
                    </div>
                    <div class="p-3 bg-purple-100 dark:bg-purple-900 rounded-lg">
                        <x-icon name="chart-bar" class="w-6 h-6 text-purple-600 dark:text-purple-400" />
                    </div>
                </div>
            </div>

            <div class="bg-white dark:bg-zinc-800 rounded-lg shadow p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-zinc-600 dark:text-zinc-400">Total Discounts</p>
                        <p class="text-2xl font-bold text-amber-600 dark:text-amber-400 mt-1">
                            {{ \App\Helpers\LocalizationHelper::formatCurrency($summaryMetrics['total_discount'] ?? 0) }}
                        </p>
                    </div>
                    <div class="p-3 bg-amber-100 dark:bg-amber-900 rounded-lg">
                        <x-icon name="tag" class="w-6 h-6 text-amber-600 dark:text-amber-400" />
                    </div>
                </div>
            </div>

            <div class="bg-white dark:bg-zinc-800 rounded-lg shadow p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-zinc-600 dark:text-zinc-400">Avg Daily Revenue</p>
                        <p class="text-2xl font-bold text-zinc-900 dark:text-zinc-100 mt-1">
                            {{ \App\Helpers\LocalizationHelper::formatCurrency($summaryMetrics['average_daily_revenue'] ?? 0) }}
                        </p>
                    </div>
                    <div class="p-3 bg-sky-100 dark:bg-sky-900 rounded-lg">
                        <x-icon name="calendar-days" class="w-6 h-6 text-sky-600 dark:text-sky-400" />
                    </div>
                </div>
            </div>
        </div>

        {{-- Operational Highlights --}}
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
            <div class="bg-white dark:bg-zinc-800 rounded-lg shadow p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-zinc-600 dark:text-zinc-400">Products Sold</p>
                        <p class="text-2xl font-bold text-zinc-900 dark:text-zinc-100 mt-1">
                            {{ number_format($summaryMetrics['products_sold'] ?? 0) }}
                        </p>
                    </div>
                    <div class="p-3 bg-indigo-100 dark:bg-indigo-900 rounded-lg">
                        <x-icon name="cube" class="w-6 h-6 text-indigo-600 dark:text-indigo-400" />
                    </div>
                </div>
            </div>

            <div class="bg-white dark:bg-zinc-800 rounded-lg shadow p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-zinc-600 dark:text-zinc-400">Most Sold Product</p>
                        <p class="text-lg font-semibold text-zinc-900 dark:text-zinc-100 mt-1">
                            {{ $summaryMetrics['top_selling_product'] ?? 'N/A' }}
                        </p>
                    </div>
                    <div class="p-3 bg-teal-100 dark:bg-teal-900 rounded-lg">
                        <x-icon name="sparkles" class="w-6 h-6 text-teal-600 dark:text-teal-400" />
                    </div>
                </div>
            </div>

            <div class="bg-white dark:bg-zinc-800 rounded-lg shadow p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-zinc-600 dark:text-zinc-400">Active Sales Staff</p>
                        <p class="text-2xl font-bold text-zinc-900 dark:text-zinc-100 mt-1">
                            {{ number_format($summaryMetrics['active_sales_staff'] ?? 0) }}
                        </p>
                    </div>
                    <div class="p-3 bg-rose-100 dark:bg-rose-900 rounded-lg">
                        <x-icon name="users" class="w-6 h-6 text-rose-600 dark:text-rose-400" />
                    </div>
                </div>
            </div>

            <div class="bg-white dark:bg-zinc-800 rounded-lg shadow p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-zinc-600 dark:text-zinc-400">Top Sales Staff</p>
                        <p class="text-lg font-semibold text-zinc-900 dark:text-zinc-100 mt-1">
                            {{ $summaryMetrics['top_sales_staff'] ?? 'N/A' }}
                        </p>
                        <p class="text-sm text-zinc-500 dark:text-zinc-400 mt-1">
                            {{ \App\Helpers\LocalizationHelper::formatCurrency($summaryMetrics['top_sales_staff_revenue'] ?? 0) }}
                        </p>
                    </div>
                    <div class="p-3 bg-amber-100 dark:bg-amber-900 rounded-lg">
                        <x-icon name="trophy" class="w-6 h-6 text-amber-600 dark:text-amber-400" />
                    </div>
                </div>
            </div>
        </div>

        {{-- Variance & Callback Summary Cards --}}
        @php
            $varianceData = data_get($reportData, 'variance_analysis', []);
            $callbackData = data_get($reportData, 'callback_analysis', []);
        @endphp
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            <div class="bg-white dark:bg-zinc-800 rounded-lg shadow p-6 border-l-4 border-orange-400">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-zinc-600 dark:text-zinc-400">Total Variances</p>
                        <p class="text-2xl font-bold text-orange-600 dark:text-orange-400 mt-1">
                            {{ number_format($summaryMetrics['total_variances'] ?? 0) }}
                        </p>
                        <p class="text-xs text-zinc-500 mt-1">
                            {{ number_format($summaryMetrics['variance_rate'] ?? 0, 2) }}% of qty sold
                        </p>
                    </div>
                    <div class="p-3 bg-orange-100 dark:bg-orange-900 rounded-lg">
                        <x-icon name="scale" class="w-6 h-6 text-orange-600 dark:text-orange-400" />
                    </div>
                </div>
            </div>

            <div class="bg-white dark:bg-zinc-800 rounded-lg shadow p-6 border-l-4 border-red-400">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-zinc-600 dark:text-zinc-400">Unresolved Variances</p>
                        <p class="text-2xl font-bold text-red-600 dark:text-red-400 mt-1">
                            {{ number_format($summaryMetrics['unresolved_variances'] ?? 0) }}
                        </p>
                        <p class="text-xs text-zinc-500 mt-1">Pending resolution</p>
                    </div>
                    <div class="p-3 bg-red-100 dark:bg-red-900 rounded-lg">
                        <x-icon name="exclamation-triangle" class="w-6 h-6 text-red-600 dark:text-red-400" />
                    </div>
                </div>
            </div>

            <div class="bg-white dark:bg-zinc-800 rounded-lg shadow p-6 border-l-4 border-yellow-400">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-zinc-600 dark:text-zinc-400">Total Callbacks</p>
                        <p class="text-2xl font-bold text-yellow-600 dark:text-yellow-400 mt-1">
                            {{ number_format($summaryMetrics['total_callbacks'] ?? 0) }}
                        </p>
                        <p class="text-xs text-zinc-500 mt-1">
                            {{ number_format($summaryMetrics['callback_rate'] ?? 0, 2) }}% callback rate
                        </p>
                    </div>
                    <div class="p-3 bg-yellow-100 dark:bg-yellow-900 rounded-lg">
                        <x-icon name="arrow-uturn-left" class="w-6 h-6 text-yellow-600 dark:text-yellow-400" />
                    </div>
                </div>
            </div>
        </div>

        {{-- Variance Detail Table --}}
        @if(!empty($varianceData['by_product']))
        <div class="bg-white dark:bg-zinc-800 rounded-lg shadow">
            <div class="p-4 border-b border-zinc-200 dark:border-zinc-700 flex items-center gap-2">
                <x-icon name="scale" class="w-5 h-5 text-orange-500" />
                <h3 class="text-lg font-semibold text-zinc-900 dark:text-zinc-100">Stock Variance Breakdown</h3>
                <span class="ml-auto text-sm text-zinc-500">Total qty: {{ number_format($varianceData['total_qty'] ?? 0, 2) }}</span>
            </div>
            <div class="p-4 overflow-x-auto">
                <table class="min-w-full divide-y divide-zinc-200 dark:divide-zinc-700">
                    <thead>
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium text-zinc-500 uppercase">Product</th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-zinc-500 uppercase">Variance Qty</th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-zinc-500 uppercase">Occurrences</th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-zinc-500 uppercase">Unresolved</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                        @foreach($varianceData['by_product'] as $row)
                        <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-700/50">
                            <td class="px-4 py-3 text-sm font-medium text-zinc-900 dark:text-zinc-100">{{ $row['product_name'] }}</td>
                            <td class="px-4 py-3 text-sm text-right text-orange-600 dark:text-orange-400">{{ number_format($row['total_variance'], 2) }}</td>
                            <td class="px-4 py-3 text-sm text-right text-zinc-600 dark:text-zinc-400">{{ $row['occurrences'] }}</td>
                            <td class="px-4 py-3 text-sm text-right">
                                @if($row['unresolved'] > 0)
                                    <span class="px-2 py-1 bg-red-100 dark:bg-red-900 text-red-700 dark:text-red-300 rounded text-xs font-medium">{{ $row['unresolved'] }}</span>
                                @else
                                    <span class="text-zinc-400">—</span>
                                @endif
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @if(!empty($varianceData['by_resolution']))
            <div class="px-4 pb-4">
                <p class="text-xs font-medium text-zinc-500 uppercase mb-2">Resolution Breakdown</p>
                <div class="flex flex-wrap gap-2">
                    @foreach($varianceData['by_resolution'] as $res)
                    <span class="px-3 py-1 bg-zinc-100 dark:bg-zinc-700 rounded-full text-xs text-zinc-700 dark:text-zinc-300">
                        {{ $res['type'] }}: {{ $res['count'] }} ({{ number_format($res['quantity'], 2) }} units)
                    </span>
                    @endforeach
                </div>
            </div>
            @endif
        </div>
        @endif

        {{-- Callback Detail Table --}}
        @if(!empty($callbackData['by_product']))
        <div class="bg-white dark:bg-zinc-800 rounded-lg shadow">
            <div class="p-4 border-b border-zinc-200 dark:border-zinc-700 flex items-center gap-2">
                <x-icon name="arrow-uturn-left" class="w-5 h-5 text-yellow-500" />
                <h3 class="text-lg font-semibold text-zinc-900 dark:text-zinc-100">Callback Breakdown</h3>
                <span class="ml-auto text-sm text-zinc-500">Total qty returned: {{ number_format($callbackData['total_qty'] ?? 0, 2) }}</span>
            </div>
            <div class="p-4 overflow-x-auto">
                <table class="min-w-full divide-y divide-zinc-200 dark:divide-zinc-700">
                    <thead>
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium text-zinc-500 uppercase">Product</th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-zinc-500 uppercase">Qty Returned</th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-zinc-500 uppercase">Occurrences</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                        @foreach($callbackData['by_product'] as $row)
                        <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-700/50">
                            <td class="px-4 py-3 text-sm font-medium text-zinc-900 dark:text-zinc-100">{{ $row['product_name'] }}</td>
                            <td class="px-4 py-3 text-sm text-right text-yellow-600 dark:text-yellow-400">{{ number_format($row['total_quantity'], 2) }}</td>
                            <td class="px-4 py-3 text-sm text-right text-zinc-600 dark:text-zinc-400">{{ $row['occurrences'] }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @if(!empty($callbackData['by_reason']))
            <div class="px-4 pb-4">
                <p class="text-xs font-medium text-zinc-500 uppercase mb-2">By Reason</p>
                <div class="flex flex-wrap gap-2">
                    @foreach($callbackData['by_reason'] as $r)
                    <span class="px-3 py-1 bg-yellow-50 dark:bg-yellow-900/30 border border-yellow-200 dark:border-yellow-800 rounded-full text-xs text-yellow-700 dark:text-yellow-300">
                        {{ $r['reason'] }}: {{ $r['count'] }} ({{ number_format($r['quantity'], 2) }} units)
                    </span>
                    @endforeach
                </div>
            </div>
            @endif
        </div>
        @endif

        {{-- Narrative Insights --}}
        @if(!empty($narrative))
            <div class="bg-white dark:bg-zinc-800 rounded-lg shadow p-6">
                <h3 class="text-lg font-semibold text-zinc-900 dark:text-zinc-100 mb-3">Insights</h3>
                @if(!empty($narrative['overview']))
                    <p class="text-sm text-zinc-700 dark:text-zinc-300">{{ $narrative['overview'] }}</p>
                @endif
                @foreach(['highlights' => 'Highlights', 'concerns' => 'Concerns', 'recommendations' => 'Recommendations'] as $key => $title)
                    @if(!empty($narrative[$key]))
                        <div class="mt-3">
                            <p class="text-sm font-medium text-zinc-900 dark:text-zinc-100">{{ $title }}</p>
                            <ul class="list-disc pl-5 text-sm text-zinc-700 dark:text-zinc-300">
                                @foreach($narrative[$key] as $item)
                                    <li>{{ $item }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                @endforeach
            </div>
        @endif

        {{-- Tables --}}
        @if(!empty($tablesData))
            @foreach($tablesData as $tableName => $table)
                <div class="bg-white dark:bg-zinc-800 rounded-lg shadow">
                    <div class="p-4 border-b border-zinc-200 dark:border-zinc-700">
                        <h3 class="text-lg font-semibold text-zinc-900 dark:text-zinc-100">
                            {{ \Illuminate\Support\Str::of($tableName)->replace('_', ' ')->title() }}
                        </h3>
                    </div>
                    <div class="p-4">
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-zinc-200 dark:divide-zinc-700">
                                @if(!empty($table['headers']))
                                    <thead>
                                        <tr>
                                            @foreach($table['headers'] as $header)
                                                <th class="px-4 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">
                                                    {{ $header }}
                                                </th>
                                            @endforeach
                                        </tr>
                                    </thead>
                                @endif
                                <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                                    @forelse(($table['rows'] ?? []) as $row)
                                        <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-700/50">
                                            @foreach($row as $cell)
                                                <td class="px-4 py-3 text-sm text-zinc-700 dark:text-zinc-300">
                                                    {{ is_numeric($cell) ? number_format($cell, 2) : $cell }}
                                                </td>
                                            @endforeach
                                        </tr>
                                    @empty
                                        <tr>
                                            <td class="px-4 py-4 text-sm text-zinc-500" colspan="{{ count($table['headers'] ?? []) }}">
                                                No rows available.
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            @endforeach
        @endif
    @else
        <div class="bg-white dark:bg-zinc-800 rounded-lg shadow p-12 text-center">
            <svg class="mx-auto h-16 w-16 text-zinc-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4" />
            </svg>
            <h3 class="mt-4 text-xl font-semibold text-zinc-900 dark:text-zinc-100">No Report Generated</h3>
            <p class="mt-2 text-sm text-zinc-600 dark:text-zinc-400">
                Select a period and click "Generate Preview" to view the sales performance report.
            </p>
        </div>
    @endif

    {{-- Report Save Modal --}}
    @if($showReportModal && $generatedReport)
        <x-modal wire:model="showReportModal" title="Report Saved Successfully">
            <div class="space-y-4">
                <div class="bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-lg p-4">
                    <p class="text-sm text-green-800 dark:text-green-200">
                        Your sales performance report has been saved successfully and is ready for review.
                    </p>
                </div>

                <div class="space-y-2 text-sm">
                    <p><strong>Report ID:</strong> #{{ $generatedReport->id }}</p>
                    <p><strong>Status:</strong> <span class="px-2 py-1 bg-yellow-100 dark:bg-yellow-900 text-yellow-800 dark:text-yellow-200 rounded text-xs">{{ ucfirst(str_replace('_', ' ', $generatedReport->status)) }}</span></p>
                    <p><strong>Period:</strong> {{ \Carbon\Carbon::parse($generatedReport->period_from)->format('M d, Y') }} - {{ \Carbon\Carbon::parse($generatedReport->period_to)->format('M d, Y') }}</p>
                </div>
            </div>

            <x-slot name="footer">
                <div class="flex justify-end gap-2">
                    <x-button color="secondary" wire:click="$set('showReportModal', false)">
                        Close
                    </x-button>
                    <x-button color="primary" wire:click="submitForReview({{ $generatedReport->id }})">
                        Submit for Review
                    </x-button>
                </div>
            </x-slot>
        </x-modal>
    @endif

    {{-- Saved Reports --}}
    <div class="bg-white dark:bg-zinc-800 rounded-lg shadow">
        <div class="p-4 border-b border-zinc-200 dark:border-zinc-700">
            <h3 class="text-lg font-semibold text-zinc-900 dark:text-zinc-100">Recent Sales Performance Reports</h3>
        </div>
        <div class="p-4">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-zinc-200 dark:divide-zinc-700">
                    <thead>
                        <tr>
                            <th class="px-4 py-2 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase">Date</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase">Department</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase">Status</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                        @forelse($savedReports as $report)
                            <tr>
                                <td class="px-4 py-2 text-sm text-zinc-700 dark:text-zinc-300">
                                    {{ optional($report->report_date)->format('Y-m-d') }}
                                </td>
                                <td class="px-4 py-2 text-sm text-zinc-700 dark:text-zinc-300">
                                    {{ $report->department?->name ?? 'N/A' }}
                                </td>
                                <td class="px-4 py-2 text-sm text-zinc-700 dark:text-zinc-300">
                                    {{ ucfirst(str_replace('_', ' ', $report->status)) }}
                                </td>
                                <td class="px-4 py-2 text-sm text-zinc-700 dark:text-zinc-300">
                                    <div class="flex flex-wrap gap-2">
                                        <x-button size="sm" wire:click="loadSavedReport('{{ $report->id }}')">
                                            View
                                        </x-button>
                                        @if($report->status === 'draft')
                                            <x-button size="sm" color="secondary" wire:click="submitForReview('{{ $report->id }}')">
                                                Submit
                                            </x-button>
                                        @endif
                                        <x-button size="sm" color="secondary" :href="branch_route('branch-dashboard.prints.department-report', ['reportId' => $report->id])">
                                            Export
                                        </x-button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td class="px-4 py-4 text-sm text-zinc-500" colspan="4">
                                    No saved reports yet.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
