<div class="space-y-6">
    {{-- Header Section --}}
    <div class="flex justify-between items-center">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Production Efficiency Report</h1>
            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                Analyze production performance, variance, and efficiency metrics
            </p>
        </div>
        <div class="flex gap-2">
            <x-button wire:click="refresh" wire:loading.attr="disabled" wire:target="refresh" color="secondary">
                <span wire:loading.remove wire:target="refresh" class="flex items-center">
                    <x-icon name="arrow-path" class="w-5 h-5 mr-2" />
                    Refresh
                </span>
                <span wire:loading wire:target="refresh" class="flex items-center">
                    <x-icon name="arrow-path" class="animate-spin w-5 h-5 mr-2" />
                    Refreshing...
                </span>
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

            {{-- Department --}}
            @if(count($availableDepartments ?? []) > 0)
                <div>
                    <x-select.native
                        label="Department"
                        wire:model.live="selectedDepartmentId"
                    >
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
        <div class="space-y-6">
            {{-- Daily Summary Table --}}
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow">
                <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Daily Summary</h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-900">
                            <tr>
                                @foreach(($tablesData['daily_summary']['headers'] ?? []) as $header)
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                        {{ $header }}
                                    </th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                            @forelse(($tablesData['daily_summary']['rows'] ?? []) as $row)
                                <tr>
                                    @foreach($row as $cell)
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700 dark:text-gray-300">
                                            {{ is_numeric($cell) ? number_format($cell, is_float($cell) ? 2 : 0) : $cell }}
                                        </td>
                                    @endforeach
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-6 py-4 text-center text-sm text-gray-500">
                                        No daily summary data available for this period
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- Product Efficiency Table --}}
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow">
                <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Product Efficiency</h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-900">
                            <tr>
                                @foreach(($tablesData['product_efficiency']['headers'] ?? []) as $header)
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                        {{ $header }}
                                    </th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                            @forelse(($tablesData['product_efficiency']['rows'] ?? []) as $row)
                                <tr>
                                    @foreach($row as $cell)
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700 dark:text-gray-300">
                                            {{ is_numeric($cell) ? number_format($cell, is_float($cell) ? 2 : 0) : $cell }}
                                        </td>
                                    @endforeach
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-6 py-4 text-center text-sm text-gray-500">
                                        No product efficiency data available for this period
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- Shift Performance Table --}}
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow">
                <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Shift Performance</h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-900">
                            <tr>
                                @foreach(($tablesData['shift_performance']['headers'] ?? []) as $header)
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                        {{ $header }}
                                    </th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                            @forelse(($tablesData['shift_performance']['rows'] ?? []) as $row)
                                <tr>
                                    @foreach($row as $cell)
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700 dark:text-gray-300">
                                            {{ is_numeric($cell) ? number_format($cell, is_float($cell) ? 2 : 0) : $cell }}
                                        </td>
                                    @endforeach
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="px-6 py-4 text-center text-sm text-gray-500">
                                        No shift performance data available for this period
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
    </div>

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
                       href="{{ branch_route('branch-dashboard.production.reporting.show', ['id' => $generatedReport->id, 'b_id' => $b_id ?? current_branch_id()]) }}">
                        View Report
                    </a>
                    <a class="inline-flex items-center justify-center rounded-lg border border-zinc-200 px-3 py-2 text-sm font-semibold text-zinc-700 hover:bg-zinc-50 dark:border-zinc-700 dark:text-zinc-200 dark:hover:bg-zinc-800"
                       href="{{ branch_route('branch-dashboard.prints.department-report', ['reportId' => $generatedReport->id, 'b_id' => $b_id ?? current_branch_id()]) }}" target="_blank">
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
