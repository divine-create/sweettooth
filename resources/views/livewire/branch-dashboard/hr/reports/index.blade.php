<div class="p-6 space-y-6">
    <x-breadcrumb title="HR Reports" :items="[
        ['label' => 'Dashboard', 'url' => branch_route('branch-dashboard.index')],
        ['label' => 'HR'],
        ['label' => 'Reports'],
    ]" :compact="false" :with-icons="true" />

    {{-- Filters --}}
    <div class="bg-white dark:bg-zinc-800 rounded-lg shadow p-6 no-print">
        <div class="flex flex-wrap items-end gap-4">
            <div class="flex-1 min-w-[200px]">
                <x-select.native label="Report Type" wire:model.live="reportType">
                    @foreach($reportTypes as $key => $type)
                        <option value="{{ $key }}">{{ $type['name'] }}</option>
                    @endforeach
                </x-select.native>
            </div>

            <div class="flex-1 min-w-[180px]">
                <x-select.native label="Period" wire:model.live="periodFilter">
                    <option value="today">Today</option>
                    <option value="yesterday">Yesterday</option>
                    <option value="week">This Week</option>
                    <option value="month">This Month</option>
                    <option value="last_month">Last Month</option>
                    <option value="custom">Custom Range</option>
                </x-select.native>
            </div>

            @if($periodFilter === 'custom')
                <div class="flex-1 min-w-[160px]">
                    <x-input label="From" type="date" wire:model="dateFrom" />
                </div>
                <div class="flex-1 min-w-[160px]">
                    <x-input label="To" type="date" wire:model="dateTo" />
                </div>
            @endif

            <div class="flex gap-2">
                <x-button color="primary" wire:click="generatePreview" :loading="$isLoading">
                    <x-icon name="arrow-path" class="w-4 h-4 mr-1" />
                    Generate Preview
                </x-button>

                @if($reportData)
                    <x-button color="secondary" wire:click="saveReport">
                        <x-icon name="document-check" class="w-4 h-4 mr-1" />
                        Save Report
                    </x-button>
                @endif

                <x-button color="secondary" onclick="window.print()">
                    <x-icon name="printer" class="w-4 h-4 mr-1" />
                    Print
                </x-button>
            </div>
        </div>

        @if($dateFrom && $dateTo)
            <p class="mt-3 text-xs text-zinc-500 dark:text-zinc-400">
                Period: {{ \Carbon\Carbon::parse($dateFrom)->format('M d, Y') }} — {{ \Carbon\Carbon::parse($dateTo)->format('M d, Y') }}
            </p>
        @endif
    </div>

    <style>
        @media print {
            .no-print { display: none !important; }
            body { background: #fff; }
        }
    </style>

    @if($reportData)
        {{-- Summary Metrics --}}
        @if(!empty($summaryMetrics))
            <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
                @foreach($summaryMetrics as $label => $value)
                    <div class="bg-white dark:bg-zinc-800 rounded-lg shadow p-4">
                        <p class="text-xs text-zinc-500 dark:text-zinc-400 uppercase tracking-wide">
                            {{ \Illuminate\Support\Str::of($label)->replace('_', ' ')->title() }}
                        </p>
                        <p class="text-xl font-semibold text-zinc-900 dark:text-zinc-100 mt-1">
                            {{ is_numeric($value) ? number_format($value, is_float($value + 0) ? 2 : 0) : (is_array($value) ? implode(', ', $value) : $value) }}
                        </p>
                    </div>
                @endforeach
            </div>
        @endif

        {{-- Narrative --}}
        @if(!empty($narrative))
            <div class="bg-white dark:bg-zinc-800 rounded-lg shadow p-6">
                <h3 class="text-base font-semibold text-zinc-900 dark:text-zinc-100 mb-3">Insights</h3>
                @if(!empty($narrative['overview']))
                    <p class="text-sm text-zinc-700 dark:text-zinc-300">{{ $narrative['overview'] }}</p>
                @endif
                @foreach(['highlights' => 'Highlights', 'concerns' => 'Concerns', 'recommendations' => 'Recommendations'] as $key => $title)
                    @if(!empty($narrative[$key]))
                        <div class="mt-3">
                            <p class="text-sm font-medium text-zinc-900 dark:text-zinc-100">{{ $title }}</p>
                            <ul class="list-disc pl-5 mt-1 space-y-0.5 text-sm text-zinc-700 dark:text-zinc-300">
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
        @foreach($tablesData as $tableKey => $table)
            <div class="bg-white dark:bg-zinc-800 rounded-lg shadow">
                <div class="px-5 py-4 border-b border-zinc-200 dark:border-zinc-700">
                    <h3 class="text-base font-semibold text-zinc-900 dark:text-zinc-100">
                        {{ \Illuminate\Support\Str::of($tableKey)->replace('_', ' ')->title() }}
                    </h3>
                </div>
                <div class="p-4 overflow-x-auto">
                    <table class="min-w-full divide-y divide-zinc-200 dark:divide-zinc-700 text-sm">
                        @if(!empty($table['headers']))
                            <thead>
                                <tr>
                                    @foreach($table['headers'] as $header)
                                        <th class="px-4 py-2 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wider whitespace-nowrap">
                                            {{ $header }}
                                        </th>
                                    @endforeach
                                </tr>
                            </thead>
                        @endif
                        <tbody class="divide-y divide-zinc-100 dark:divide-zinc-700/50">
                            @forelse($table['rows'] ?? [] as $row)
                                <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-700/30">
                                    @foreach($row as $cell)
                                        <td class="px-4 py-2 text-zinc-700 dark:text-zinc-300 whitespace-nowrap">
                                            {{ is_numeric($cell) ? number_format($cell, 2) : (is_array($cell) ? implode(', ', $cell) : $cell) }}
                                        </td>
                                    @endforeach
                                </tr>
                            @empty
                                <tr>
                                    <td class="px-4 py-4 text-zinc-400 text-center" colspan="{{ count($table['headers'] ?? [1]) }}">
                                        No data for this period.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        @endforeach
    @else
        <div class="bg-white dark:bg-zinc-800 rounded-lg shadow p-12 text-center no-print">
            <x-icon name="chart-bar" class="mx-auto h-14 w-14 text-zinc-300" />
            <h3 class="mt-4 text-lg font-semibold text-zinc-900 dark:text-zinc-100">No Report Generated</h3>
            <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">
                Select a report type and period, then click "Generate Preview".
            </p>
        </div>
    @endif

    {{-- Saved Reports --}}
    <div class="bg-white dark:bg-zinc-800 rounded-lg shadow no-print">
        <div class="px-5 py-4 border-b border-zinc-200 dark:border-zinc-700">
            <h3 class="text-base font-semibold text-zinc-900 dark:text-zinc-100">Saved Reports</h3>
            <p class="text-xs text-zinc-500 dark:text-zinc-400 mt-0.5">Showing saved records for the selected report type.</p>
        </div>

        @if($savedReports->isEmpty())
            <div class="p-8 text-center text-sm text-zinc-400">No saved reports yet for this type.</div>
        @else
            <div class="divide-y divide-zinc-100 dark:divide-zinc-700/50">
                @foreach($savedReports as $saved)
                    <div class="flex items-center justify-between px-5 py-3">
                        <div>
                            <p class="text-sm font-medium text-zinc-900 dark:text-zinc-100">{{ $saved->report_name }}</p>
                            <p class="text-xs text-zinc-500 dark:text-zinc-400">
                                {{ \Carbon\Carbon::parse($saved->period_from)->format('M d, Y') }}
                                —
                                {{ \Carbon\Carbon::parse($saved->period_to)->format('M d, Y') }}
                                &middot; Generated {{ $saved->created_at->diffForHumans() }}
                            </p>
                        </div>
                        <div class="flex items-center gap-3">
                            @php
                                $statusColors = [
                                    'draft'          => 'bg-zinc-100 text-zinc-600 dark:bg-zinc-700 dark:text-zinc-300',
                                    'pending_review' => 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/40 dark:text-yellow-300',
                                    'reviewed'       => 'bg-green-100 text-green-700 dark:bg-green-900/40 dark:text-green-300',
                                    'rejected'       => 'bg-red-100 text-red-700 dark:bg-red-900/40 dark:text-red-300',
                                    'compiled'       => 'bg-blue-100 text-blue-700 dark:bg-blue-900/40 dark:text-blue-300',
                                    'sent_to_md'     => 'bg-indigo-100 text-indigo-700 dark:bg-indigo-900/40 dark:text-indigo-300',
                                ];
                                $color = $statusColors[$saved->status] ?? 'bg-zinc-100 text-zinc-600';
                            @endphp
                            <span class="px-2 py-0.5 rounded text-xs font-medium {{ $color }}">
                                {{ ucfirst(str_replace('_', ' ', $saved->status)) }}
                            </span>
                            <a href="{{ branch_route('branch-dashboard.reporting.report.view', ['id' => $saved->id]) }}"
                               class="text-xs text-primary-600 dark:text-primary-400 hover:underline" wire:navigate>
                                View
                            </a>
                        </div>
                    </div>
                @endforeach
            </div>
            <div class="px-5 py-3 border-t border-zinc-100 dark:border-zinc-700">
                {{ $savedReports->links() }}
            </div>
        @endif
    </div>

    {{-- Save Success Modal --}}
    @if($showSaveModal && $savedReportId)
        <x-modal wire:model="showSaveModal" title="Report Saved">
            <div class="space-y-3">
                <div class="bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-lg p-4">
                    <p class="text-sm text-green-800 dark:text-green-200">
                        The report has been saved. You can submit it for review now or do it later from the saved reports list.
                    </p>
                </div>
                <p class="text-sm text-zinc-600 dark:text-zinc-400"><strong>Report ID:</strong> #{{ $savedReportId }}</p>
            </div>
            <x-slot name="footer">
                <div class="flex justify-end gap-2">
                    <x-button color="secondary" wire:click="$set('showSaveModal', false)">Close</x-button>
                    <x-button color="primary" wire:click="submitForReview">Submit for Review</x-button>
                </div>
            </x-slot>
        </x-modal>
    @endif
</div>
