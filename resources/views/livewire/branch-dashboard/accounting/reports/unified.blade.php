<div class="p-6 space-y-6">
    <x-breadcrumb title="Accounting Reports" :items="[
        ['label' => 'Dashboard', 'url' => branch_route('branch-dashboard.index')],
        ['label' => 'Accounting'],
        ['label' => 'Reports'],
    ]" :compact="false" :with-icons="true" />

    {{-- Filters --}}
    <div class="bg-white dark:bg-zinc-800 rounded-lg shadow p-6">
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
            <div>
                <x-select.native label="Report Type" wire:model.live="reportKey">
                    @forelse($availableReports as $report)
                        <option value="{{ $report['key'] }}">{{ $report['meta']['name'] ?? $report['key'] }}</option>
                    @empty
                        <option value="">No accounting reports available</option>
                    @endforelse
                </x-select.native>
            </div>

            <div>
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
                <div>
                    <x-input label="From Date" type="date" wire:model="customDateFrom" />
                </div>
                <div>
                    <x-input label="To Date" type="date" wire:model="customDateTo" />
                </div>
            @endif
        </div>

        <div class="flex flex-wrap gap-2 mt-6">
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

    @if($reportData)
        @if(!$hasPostedEntries)
            <div class="bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 rounded-lg p-4 text-amber-900 dark:text-amber-100 text-sm">
                No posted GL entries were found for this branch in the selected date range. Reports will show zeros until entries are posted.
            </div>
        @elseif(!$hasReportRows)
            <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-4 text-blue-900 dark:text-blue-100 text-sm">
                Report generated, but no rows matched the current filters. Try a wider date range or verify account activity.
            </div>
        @endif

        {{-- Summary Metrics --}}
        @if(!empty($summaryMetrics))
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                @foreach($summaryMetrics as $label => $value)
                    <div class="bg-white dark:bg-zinc-800 rounded-lg shadow p-5">
                        <p class="text-sm text-zinc-600 dark:text-zinc-400">
                            {{ \Illuminate\Support\Str::of($label)->replace('_', ' ')->title() }}
                        </p>
                        <p class="text-2xl font-semibold text-zinc-900 dark:text-zinc-100 mt-2">
                            {{ is_numeric($value) ? number_format($value, 2) : $value }}
                        </p>
                    </div>
                @endforeach
            </div>
        @endif

        {{-- Narrative --}}
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
                Select a period and click "Generate Preview" to view the accounting report.
            </p>
        </div>
    @endif

    {{-- Saved Reports --}}
    <div class="bg-white dark:bg-zinc-800 rounded-lg shadow">
        <div class="p-4 border-b border-zinc-200 dark:border-zinc-700">
            <h3 class="text-lg font-semibold text-zinc-900 dark:text-zinc-100">Recent Reports</h3>
        </div>
        <div class="p-4">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-zinc-200 dark:divide-zinc-700">
                    <thead>
                        <tr>
                            <th class="px-4 py-2 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase">Date</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase">Name</th>
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
                                    {{ $report->report_name }}
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
                                        <x-button size="sm" color="secondary" :href="branch_route('branch-dashboard.exports.department-report', ['reportId' => $report->id])">
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
