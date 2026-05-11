<div class="space-y-6">
    {{-- Header --}}
    <div class="flex justify-between items-center">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Quality Metrics Report</h1>
            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                Track quality, rejection rates, and callback analysis
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
                <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Total Batches</p>
                <p class="mt-2 text-3xl font-bold text-gray-900 dark:text-white">
                    {{ number_format($summaryMetrics['total_batches'] ?? 0) }}
                </p>
            </div>
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Approval Rate</p>
                <p class="mt-2 text-3xl font-bold text-green-600">
                    {{ number_format($summaryMetrics['overall_approval_rate'] ?? 0, 1) }}%
                </p>
            </div>
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Rejection Rate</p>
                <p class="mt-2 text-3xl font-bold text-red-600">
                    {{ number_format($summaryMetrics['overall_rejection_rate'] ?? 0, 1) }}%
                </p>
            </div>
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Total Callbacks</p>
                <p class="mt-2 text-3xl font-bold text-gray-900 dark:text-white">
                    {{ number_format($summaryMetrics['total_callbacks'] ?? 0) }}
                </p>
            </div>
        </div>

        {{-- Produce Finished Good Quality Table --}}
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow">
            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Produce Finished Good Quality Analysis</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-900">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Product</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Batches</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Approved</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Rejected</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Approval Rate</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                        @forelse($reportData['finished_quality'] ?? [] as $product)
                            <tr>
                                <td class="px-6 py-4 text-sm font-medium text-gray-900 dark:text-white">
                                    {{ $product['product_name'] }}
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-500 text-right">
                                    {{ number_format($product['total_batches']) }}
                                </td>
                                <td class="px-6 py-4 text-sm text-green-600 text-right">
                                    {{ number_format($product['total_approved']) }}
                                </td>
                                <td class="px-6 py-4 text-sm text-red-600 text-right">
                                    {{ number_format($product['total_rejected']) }}
                                </td>
                                <td class="px-6 py-4 text-sm text-right">
                                    <span class="px-2 py-1 text-xs font-semibold rounded-full
                                        {{ $product['approval_rate'] >= 95 ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800' }}">
                                        {{ number_format($product['approval_rate'], 1) }}%
                                    </span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-6 py-4 text-center text-sm text-gray-500">
                                    No finished good quality data available
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- WIP Produce Quality Table --}}
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow">
            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">WIP Produce Quality Analysis</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-900">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Product</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Batches</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Approved</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Rejected</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Approval Rate</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                        @forelse($reportData['wip_quality'] ?? [] as $product)
                            <tr>
                                <td class="px-6 py-4 text-sm font-medium text-gray-900 dark:text-white">
                                    {{ $product['product_name'] }}
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-500 text-right">
                                    {{ number_format($product['total_batches']) }}
                                </td>
                                <td class="px-6 py-4 text-sm text-green-600 text-right">
                                    {{ number_format($product['total_approved']) }}
                                </td>
                                <td class="px-6 py-4 text-sm text-red-600 text-right">
                                    {{ number_format($product['total_rejected']) }}
                                </td>
                                <td class="px-6 py-4 text-sm text-right">
                                    <span class="px-2 py-1 text-xs font-semibold rounded-full
                                        {{ $product['approval_rate'] >= 95 ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800' }}">
                                        {{ number_format($product['approval_rate'], 1) }}%
                                    </span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-6 py-4 text-center text-sm text-gray-500">
                                    No WIP produce quality data available
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    @else
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-12">
            <div class="text-center">
                <x-icon name="shield-check" class="mx-auto h-16 w-16 text-gray-400" />
                <h3 class="mt-4 text-lg font-medium text-gray-900 dark:text-white">No Report Generated</h3>
                <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                    Select a date range and click "Generate Preview"
                </p>
            </div>
        </div>
@endif

@include('livewire.partials.department-select-modal')

    @if($showReportModal && $generatedReport)
        <x-modal wire:model="showReportModal" title="Quality Report Generated" size="lg">
            <div class="space-y-4">
                <p class="text-sm text-gray-600">Report has been saved successfully.</p>
                <div class="flex flex-wrap justify-end gap-3 mt-6">
                    <a class="inline-flex items-center justify-center rounded-lg border border-zinc-200 px-3 py-2 text-sm font-semibold text-zinc-700 hover:bg-zinc-50 dark:border-zinc-700 dark:text-zinc-200 dark:hover:bg-zinc-800"
                       href="{{ branch_route('branch-dashboard.production.reporting.show', ['id' => $generatedReport->id, 'b_id' => $b_id ?? current_branch_id()]) }}">
                        View Report
                    </a>
                    <a class="inline-flex items-center justify-center rounded-lg border border-zinc-200 px-3 py-2 text-sm font-semibold text-zinc-700 hover:bg-zinc-50 dark:border-zinc-700 dark:text-zinc-200 dark:hover:bg-zinc-800"
                       href="{{ branch_route('branch-dashboard.prints.department-report', ['reportId' => $generatedReport->id, 'b_id' => $b_id ?? current_branch_id()]) }}" target="_blank">
                        Print
                    </a>
                    <x-button wire:click="$set('showReportModal', false)" color="secondary">Close</x-button>
                    <x-button wire:click="submitForReview('{{ $generatedReport->id }}')" color="primary">
                        Submit for Review
                    </x-button>
                </div>
            </div>
        </x-modal>
    @endif
</div>
