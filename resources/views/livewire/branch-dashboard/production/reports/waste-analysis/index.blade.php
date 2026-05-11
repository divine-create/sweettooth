<div class="space-y-6">
    {{-- Header --}}
    <div class="flex justify-between items-center">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Waste Analysis Report</h1>
            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                Track production waste, callbacks, and identify waste reduction opportunities
            </p>
        </div>
            <x-button wire:click="generateReport" color="primary" icon="document-plus" wire:loading.attr="disabled" wire:target="generateReport">
                Generate & Save Report
            </x-button>
    </div>

    {{-- Filters --}}
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
        <div class="grid grid-cols-1 md:grid-cols-5 gap-4">
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
            <div>
                <x-input label="From Date" type="date" wire:model="customDateFrom" :disabled="$periodFilter !== 'custom'" />
            </div>
            <div>
                <x-input label="To Date" type="date" wire:model="customDateTo" :disabled="$periodFilter !== 'custom'" />
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
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Total Waste</p>
                <p class="mt-2 text-3xl font-bold text-red-600">
                    {{ number_format($summaryMetrics['total_waste'] ?? 0, 2) }}
                </p>
                <p class="mt-1 text-xs text-gray-500">{{ $summaryMetrics['total_incidents'] ?? 0 }} incidents</p>
            </div>
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Production Waste</p>
                <p class="mt-2 text-3xl font-bold text-orange-600">
                    {{ number_format($summaryMetrics['production_waste'] ?? 0, 2) }}
                </p>
                <p class="mt-1 text-xs text-gray-500">During production</p>
            </div>
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Callback Waste</p>
                <p class="mt-2 text-3xl font-bold text-yellow-600">
                    {{ number_format($summaryMetrics['callback_waste'] ?? 0, 2) }}
                </p>
                <p class="mt-1 text-xs text-gray-500">Returns & callbacks</p>
            </div>
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Avg. Daily Waste</p>
                <p class="mt-2 text-3xl font-bold text-gray-900 dark:text-white">
                    {{ number_format($summaryMetrics['average_daily_waste'] ?? 0, 2) }}
                </p>
                <p class="mt-1 text-xs text-gray-500">Per day</p>
            </div>
        </div>

        {{-- Key Insights --}}
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div class="bg-gradient-to-br from-red-50 to-orange-50 dark:from-red-900/20 dark:to-orange-900/20 rounded-lg shadow p-6">
                <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3">Top Waste Reason</h3>
                <p class="text-2xl font-bold text-red-700 dark:text-red-400">
                    {{ $summaryMetrics['top_waste_reason'] ?? 'N/A' }}
                </p>
            </div>
            <div class="bg-gradient-to-br from-yellow-50 to-amber-50 dark:from-yellow-900/20 dark:to-amber-900/20 rounded-lg shadow p-6">
                <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3">Top Waste Product</h3>
                <p class="text-2xl font-bold text-yellow-700 dark:text-yellow-400">
                    {{ $summaryMetrics['top_waste_product'] ?? 'N/A' }}
                </p>
            </div>
        </div>

        {{-- Waste by Reason --}}
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow">
            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Waste by Reason</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-900">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Reason</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Incidents</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Quantity</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                        @forelse($reportData['waste_by_reason'] ?? [] as $reason)
                            <tr>
                                <td class="px-6 py-4 text-sm font-medium text-gray-900 dark:text-white">
                                    {{ $reason['reason'] }}
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-500 text-right">
                                    {{ number_format($reason['count']) }}
                                </td>
                                <td class="px-6 py-4 text-sm text-red-600 text-right">
                                    {{ number_format($reason['quantity'], 2) }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="px-6 py-4 text-center text-sm text-gray-500">
                                    No waste data available for this period
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Produce Finished Good Waste --}}
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow">
            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Produce Finished Good Waste</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-900">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Product</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Production Waste</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Callback Waste</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Total Waste</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Incidents</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                        @forelse($reportData['finished_goods_waste'] ?? [] as $product)
                            <tr>
                                <td class="px-6 py-4 text-sm font-medium text-gray-900 dark:text-white">
                                    {{ $product['product'] }}
                                </td>
                                <td class="px-6 py-4 text-sm text-orange-600 text-right">
                                    {{ number_format($product['production_waste'], 2) }}
                                </td>
                                <td class="px-6 py-4 text-sm text-yellow-600 text-right">
                                    {{ number_format($product['callback_waste'], 2) }}
                                </td>
                                <td class="px-6 py-4 text-sm text-red-600 font-semibold text-right">
                                    {{ number_format($product['total_waste'], 2) }}
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-500 text-right">
                                    {{ number_format($product['incidents']) }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-6 py-4 text-center text-sm text-gray-500">
                                    No finished good waste data available
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- WIP Produce Waste --}}
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow">
            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">WIP Produce Waste</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-900">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Product</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Production Waste</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Callback Waste</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Total Waste</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Incidents</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                        @forelse($reportData['wip_goods_waste'] ?? [] as $product)
                            <tr>
                                <td class="px-6 py-4 text-sm font-medium text-gray-900 dark:text-white">
                                    {{ $product['product'] }}
                                </td>
                                <td class="px-6 py-4 text-sm text-orange-600 text-right">
                                    {{ number_format($product['production_waste'], 2) }}
                                </td>
                                <td class="px-6 py-4 text-sm text-yellow-600 text-right">
                                    {{ number_format($product['callback_waste'], 2) }}
                                </td>
                                <td class="px-6 py-4 text-sm text-red-600 font-semibold text-right">
                                    {{ number_format($product['total_waste'], 2) }}
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-500 text-right">
                                    {{ number_format($product['incidents']) }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-6 py-4 text-center text-sm text-gray-500">
                                    No WIP produce waste data available
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Daily Waste Trends --}}
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow">
            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Daily Waste Trends</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-900">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Production Waste</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Callback Waste</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Total Waste</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Incidents</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                        @forelse($reportData['daily_waste_trends'] ?? [] as $day)
                            <tr>
                                <td class="px-6 py-4 text-sm font-medium text-gray-900 dark:text-white">
                                    {{ \Carbon\Carbon::parse($day['date'])->format('M d, Y') }}
                                </td>
                                <td class="px-6 py-4 text-sm text-orange-600 text-right">
                                    {{ number_format($day['production_waste'], 2) }}
                                </td>
                                <td class="px-6 py-4 text-sm text-yellow-600 text-right">
                                    {{ number_format($day['callback_waste'], 2) }}
                                </td>
                                <td class="px-6 py-4 text-sm text-red-600 font-semibold text-right">
                                    {{ number_format($day['total_waste'], 2) }}
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-500 text-right">
                                    {{ number_format($day['incidents']) }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-6 py-4 text-center text-sm text-gray-500">
                                    No daily trends available
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Employee Waste Analysis --}}
        @if(!empty($reportData['employee_waste_analysis']))
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow">
                <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Employee Waste Analysis</h3>
                    <p class="mt-1 text-sm text-gray-500">Production rejection rates by employee</p>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-900">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Employee</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Total Produced</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Total Rejected</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Rejection Rate</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Incidents</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                            @foreach($reportData['employee_waste_analysis'] as $employee)
                                <tr>
                                    <td class="px-6 py-4 text-sm font-medium text-gray-900 dark:text-white">
                                        {{ $employee['employee_name'] }}
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-500 text-right">
                                        {{ number_format($employee['total_produced']) }}
                                    </td>
                                    <td class="px-6 py-4 text-sm text-red-600 text-right">
                                        {{ number_format($employee['total_rejected']) }}
                                    </td>
                                    <td class="px-6 py-4 text-sm text-right">
                                        <span class="px-2 py-1 text-xs font-semibold rounded-full
                                            {{ $employee['rejection_rate'] <= 5 ? 'bg-green-100 text-green-800' : ($employee['rejection_rate'] <= 10 ? 'bg-yellow-100 text-yellow-800' : 'bg-red-100 text-red-800') }}">
                                            {{ number_format($employee['rejection_rate'], 1) }}%
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-500 text-right">
                                        {{ number_format($employee['incidents']) }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
@endif

@include('livewire.partials.department-select-modal')
    @else
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-12">
            <div class="text-center">
                <x-icon name="trash" class="mx-auto h-16 w-16 text-gray-400" />
                <h3 class="mt-4 text-lg font-medium text-gray-900 dark:text-white">No Report Generated</h3>
                <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                    Select a date range and click "Generate Preview" to view waste analysis
                </p>
            </div>
        </div>
    @endif

    @if($showReportModal && $generatedReport)
        <x-modal wire:model="showReportModal" title="Report Generated Successfully">
            <div class="space-y-4">
                <div class="bg-green-50 dark:bg-green-900/20 rounded-lg p-4">
                    <p class="text-sm text-green-800 dark:text-green-200">
                        Your waste analysis report has been generated and saved successfully.
                    </p>
                </div>

                <div class="space-y-2">
                    <p class="text-sm text-gray-600 dark:text-gray-400">
                        <strong>Report ID:</strong> {{ $generatedReport->id }}
                    </p>
                    <p class="text-sm text-gray-600 dark:text-gray-400">
                        <strong>Period:</strong> {{ \Carbon\Carbon::parse($generatedReport->period_from)->format('M d, Y') }} -
                        {{ \Carbon\Carbon::parse($generatedReport->period_to)->format('M d, Y') }}
                    </p>
                    <p class="text-sm text-gray-600 dark:text-gray-400">
                        <strong>Status:</strong> {{ ucfirst($generatedReport->status) }}
                    </p>
                </div>

                <div class="flex flex-wrap justify-end gap-3">
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
