<div class="space-y-6">
    <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
        <div>
            <h2 class="text-xl font-semibold text-gray-900 dark:text-white">Production Activities</h2>
            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                Real-time view of production output, requests, dispatches, and production value.
            </p>
        </div>
        <div class="flex flex-wrap gap-2">
            <div class="flex items-center gap-2 text-xs text-gray-500 dark:text-gray-400">
                <span>From: {{ $customDateFrom }}</span>
                <span>To: {{ $customDateTo }}</span>
            </div>
            <x-button wire:click="generateReport" color="primary" icon="document-plus" wire:loading.attr="disabled" wire:target="generateReport">
                Generate & Save Report
            </x-button>
        </div>
    </div>

    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-5">
        <div class="grid grid-cols-1 md:grid-cols-6 gap-4">
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
                <x-input label="From" type="date" wire:model="customDateFrom" :disabled="$periodFilter !== 'custom'" />
            </div>
            <div>
                <x-input label="To" type="date" wire:model="customDateTo" :disabled="$periodFilter !== 'custom'" />
            </div>
            <div>
                <x-select.native label="Shift" wire:model.live="shiftType">
                    <option value="all">All Shifts</option>
                    <option value="morning">Morning</option>
                    <option value="afternoon">Afternoon</option>
                    <option value="night">Night</option>
                </x-select.native>
            </div>
            <div>
                <x-select.native label="Request Status" wire:model.live="requestStatus">
                    <option value="all">All</option>
                    <option value="pending">Pending</option>
                    <option value="in_progress">In Progress</option>
                    <option value="completed">Completed</option>
                    <option value="cancelled">Cancelled</option>
                </x-select.native>
            </div>
            @if(count($availableDepartments ?? []) > 0)
                <div>
                    <x-select.native label="Department" wire:model.live="selectedDepartmentId">
                        <option value="">All Departments</option>
                        @foreach($availableDepartments as $dept)
                            <option value="{{ $dept->id }}">{{ $dept->name }}</option>
                        @endforeach
                    </x-select.native>
                </div>
            @endif
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
            <p class="text-xs uppercase tracking-wide text-gray-500">Produced</p>
            <p class="mt-2 text-2xl font-semibold text-gray-900 dark:text-white">{{ number_format($summary['total_produced'] ?? 0, 2) }}</p>
            <p class="mt-1 text-xs text-gray-500">Requested: {{ number_format($summary['total_requested'] ?? 0, 2) }}</p>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
            <p class="text-xs uppercase tracking-wide text-gray-500">Sent Out</p>
            <p class="mt-2 text-2xl font-semibold text-gray-900 dark:text-white">{{ number_format($summary['total_sent_out'] ?? 0, 2) }}</p>
            <p class="mt-1 text-xs text-gray-500">Unfulfilled: {{ number_format($summary['unfulfilled_quantity'] ?? 0, 2) }}</p>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
            <p class="text-xs uppercase tracking-wide text-gray-500">Production Value</p>
            <p class="mt-2 text-2xl font-semibold text-gray-900 dark:text-white">{{ number_format($summary['production_value'] ?? 0, 2) }}</p>
            <p class="mt-1 text-xs text-gray-500">Variance: {{ number_format($summary['total_variance'] ?? 0, 2) }}</p>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow">
            <div class="px-5 py-4 border-b border-gray-200 dark:border-gray-700">
                <h3 class="text-base font-semibold text-gray-900 dark:text-white">Recent Production Records</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm">
                    <thead class="bg-gray-50 dark:bg-gray-900">
                        <tr>
                            <th class="px-4 py-2 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Time</th>
                            <th class="px-4 py-2 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Product</th>
                            <th class="px-4 py-2 text-right text-xs font-semibold uppercase tracking-wide text-gray-500">Produced</th>
                            <th class="px-4 py-2 text-right text-xs font-semibold uppercase tracking-wide text-gray-500">Sent Out</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                        @forelse($productionRecords as $record)
                            <tr>
                                <td class="px-4 py-2 text-gray-600 dark:text-gray-300">
                                    {{ $record['time'] ?? '-' }}
                                </td>
                                <td class="px-4 py-2 text-gray-900 dark:text-white">
                                    {{ $record['product'] ?? 'Unknown' }}
                                </td>
                                <td class="px-4 py-2 text-right text-gray-600 dark:text-gray-300">
                                    {{ number_format($record['produced'] ?? 0, 2) }}
                                </td>
                                <td class="px-4 py-2 text-right text-gray-600 dark:text-gray-300">
                                    {{ number_format($record['sent_out'] ?? 0, 2) }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td class="px-4 py-6 text-center text-gray-500" colspan="4">No production records for this period.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-lg shadow">
            <div class="px-5 py-4 border-b border-gray-200 dark:border-gray-700">
                <h3 class="text-base font-semibold text-gray-900 dark:text-white">Production Requests</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm">
                    <thead class="bg-gray-50 dark:bg-gray-900">
                        <tr>
                            <th class="px-4 py-2 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Request</th>
                            <th class="px-4 py-2 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Product</th>
                            <th class="px-4 py-2 text-right text-xs font-semibold uppercase tracking-wide text-gray-500">Planned</th>
                            <th class="px-4 py-2 text-right text-xs font-semibold uppercase tracking-wide text-gray-500">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                        @forelse($productionRequests as $request)
                            <tr>
                                <td class="px-4 py-2 text-gray-600 dark:text-gray-300">
                                    {{ $request['id'] ?? '-' }}
                                </td>
                                <td class="px-4 py-2 text-gray-900 dark:text-white">
                                    {{ $request['product'] ?? 'Unknown' }}
                                </td>
                                <td class="px-4 py-2 text-right text-gray-600 dark:text-gray-300">
                                    {{ number_format($request['planned'] ?? 0, 2) }}
                                </td>
                                <td class="px-4 py-2 text-right">
                                    <span class="inline-flex rounded-full bg-zinc-100 px-2 py-1 text-xs font-semibold text-zinc-700 dark:bg-zinc-700 dark:text-zinc-200">
                                        {{ $request['status'] ?? 'unknown' }}
                                    </span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td class="px-4 py-6 text-center text-gray-500" colspan="4">No production requests for this period.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="bg-white dark:bg-gray-800 rounded-lg shadow">
        <div class="px-5 py-4 border-b border-gray-200 dark:border-gray-700">
            <h3 class="text-base font-semibold text-gray-900 dark:text-white">Finished Good Produce Summary</h3>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm">
                <thead class="bg-gray-50 dark:bg-gray-900">
                    <tr>
                        <th class="px-4 py-2 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Date</th>
                        <th class="px-4 py-2 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Product</th>
                        <th class="px-4 py-2 text-right text-xs font-semibold uppercase tracking-wide text-gray-500">Requested</th>
                        <th class="px-4 py-2 text-right text-xs font-semibold uppercase tracking-wide text-gray-500">Produced</th>
                        <th class="px-4 py-2 text-right text-xs font-semibold uppercase tracking-wide text-gray-500">Sent Out</th>
                        <th class="px-4 py-2 text-right text-xs font-semibold uppercase tracking-wide text-gray-500">Variance</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse($finishedGoodsSummary as $row)
                        <tr>
                            <td class="px-4 py-2 text-gray-600 dark:text-gray-300">
                                {{ $row['date'] ?? '-' }}
                            </td>
                            <td class="px-4 py-2 text-gray-900 dark:text-white">
                                {{ $row['product'] ?? 'Unknown' }}
                            </td>
                            <td class="px-4 py-2 text-right text-gray-600 dark:text-gray-300">
                                {{ number_format($row['requested'] ?? 0, 2) }}
                            </td>
                            <td class="px-4 py-2 text-right text-gray-600 dark:text-gray-300">
                                {{ number_format($row['produced'] ?? 0, 2) }}
                            </td>
                            <td class="px-4 py-2 text-right text-gray-600 dark:text-gray-300">
                                {{ number_format($row['sent_out'] ?? 0, 2) }}
                            </td>
                            <td class="px-4 py-2 text-right text-gray-600 dark:text-gray-300">
                                {{ number_format($row['variance'] ?? 0, 2) }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td class="px-4 py-6 text-center text-gray-500" colspan="6">No finished good production data for this period.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="bg-white dark:bg-gray-800 rounded-lg shadow">
        <div class="px-5 py-4 border-b border-gray-200 dark:border-gray-700">
            <h3 class="text-base font-semibold text-gray-900 dark:text-white">WIP Produce Summary</h3>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm">
                <thead class="bg-gray-50 dark:bg-gray-900">
                    <tr>
                        <th class="px-4 py-2 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Date</th>
                        <th class="px-4 py-2 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Product</th>
                        <th class="px-4 py-2 text-right text-xs font-semibold uppercase tracking-wide text-gray-500">Requested</th>
                        <th class="px-4 py-2 text-right text-xs font-semibold uppercase tracking-wide text-gray-500">Produced</th>
                        <th class="px-4 py-2 text-right text-xs font-semibold uppercase tracking-wide text-gray-500">Sent Out</th>
                        <th class="px-4 py-2 text-right text-xs font-semibold uppercase tracking-wide text-gray-500">Variance</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse($wipGoodsSummary as $row)
                        <tr>
                            <td class="px-4 py-2 text-gray-600 dark:text-gray-300">
                                {{ $row['date'] ?? '-' }}
                            </td>
                            <td class="px-4 py-2 text-gray-900 dark:text-white">
                                {{ $row['product'] ?? 'Unknown' }}
                            </td>
                            <td class="px-4 py-2 text-right text-gray-600 dark:text-gray-300">
                                {{ number_format($row['requested'] ?? 0, 2) }}
                            </td>
                            <td class="px-4 py-2 text-right text-gray-600 dark:text-gray-300">
                                {{ number_format($row['produced'] ?? 0, 2) }}
                            </td>
                            <td class="px-4 py-2 text-right text-gray-600 dark:text-gray-300">
                                {{ number_format($row['sent_out'] ?? 0, 2) }}
                            </td>
                            <td class="px-4 py-2 text-right text-gray-600 dark:text-gray-300">
                                {{ number_format($row['variance'] ?? 0, 2) }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td class="px-4 py-6 text-center text-gray-500" colspan="6">No WIP production data for this period.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Reports History --}}
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow mt-6">
        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
            <h3 class="text-lg font-medium text-gray-900 dark:text-white">Reports History</h3>
            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">View previously generated production activities reports</p>
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
                                        <x-button wire:click="viewReport('{{ $report->id }}')" size="sm" color="secondary" icon="eye" wire:loading.attr="disabled" wire:target="viewReport">
                                            View
                                        </x-button>
                                        <x-button wire:click="downloadReport('{{ $report->id }}')" size="sm" color="secondary" icon="document-arrow-down" wire:loading.attr="disabled" wire:target="downloadReport">
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
                    Generate your first production activities report to see it here.
                </p>
            </div>
        @endif
    </div>

    {{-- Report Modal --}}
    @if($showReportModal && $generatedReport)
        <x-modal wire:model="showReportModal" title="Report Generated Successfully" size="lg">
            <div class="space-y-4">
                <p class="text-sm text-gray-600 dark:text-gray-400">
                    Your production activities report has been generated and saved.
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
                </div>
            </div>
        </x-modal>
    @endif

    @include('livewire.partials.department-select-modal')
</div>
