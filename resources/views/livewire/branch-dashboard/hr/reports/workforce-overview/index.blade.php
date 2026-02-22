<div class="p-6 space-y-6">
    <x-breadcrumb title="HR Workforce Overview" :items="[
        ['label' => 'Dashboard', 'url' => branch_route('branch-dashboard.index')],
        ['label' => 'HR'],
        ['label' => 'Reports'],
        ['label' => 'Workforce Overview'],
    ]" :compact="false" :with-icons="true" />

    {{-- Filters --}}
    <div class="bg-white dark:bg-zinc-800 rounded-lg shadow p-6 no-print">
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

                <x-button color="secondary" onclick="window.print()">
                    <x-icon name="printer" class="w-4 h-4 mr-2" />
                    Print
                </x-button>
            </div>
        </div>
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
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                @foreach($summaryMetrics as $label => $value)
                    <div class="bg-white dark:bg-zinc-800 rounded-lg shadow p-5">
                        <p class="text-sm text-zinc-600 dark:text-zinc-400">
                            {{ \Illuminate\Support\Str::of($label)->replace('_', ' ')->title() }}
                        </p>
                        <p class="text-2xl font-semibold text-zinc-900 dark:text-zinc-100 mt-2">
                            {{ is_numeric($value) ? number_format($value, 2) : (is_array($value) ? json_encode($value) : $value) }}
                        </p>
                    </div>
                @endforeach
            </div>
        @endif

        {{-- Detailed Workforce Report --}}
        <div class="bg-white dark:bg-zinc-800 rounded-lg shadow p-6">
            <h3 class="text-lg font-semibold text-zinc-900 dark:text-zinc-100">Detailed Workforce Report</h3>
            <p class="text-sm text-zinc-600 dark:text-zinc-400 mt-1">
                Employee overview, clock-in activity, and appraisal history for the selected period.
            </p>
        </div>

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

        {{-- Dedicated Tables --}}
        @php
            $tableKeys = [
                'employee_overview' => 'Employee Overview',
                'recent_clock_ins' => 'Recent Clock-Ins',
                'appraisal_history' => 'Appraisal History',
                'department_headcount' => 'Department Headcount',
                'leave_requests' => 'Leave Requests',
                'leave_types' => 'Leave Types',
                'recent_hires' => 'Recent Hires',
                'recent_terminations' => 'Recent Terminations',
            ];
        @endphp

        @foreach($tableKeys as $key => $title)
            @if(!empty($tablesData[$key]))
                @php($table = $tablesData[$key])
                <div class="bg-white dark:bg-zinc-800 rounded-lg shadow">
                    <div class="p-4 border-b border-zinc-200 dark:border-zinc-700">
                        <h3 class="text-lg font-semibold text-zinc-900 dark:text-zinc-100">{{ $title }}</h3>
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
                                                    {{ is_numeric($cell) ? number_format($cell, 2) : (is_array($cell) ? json_encode($cell) : $cell) }}
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
            @endif
        @endforeach

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
                                                    {{ is_numeric($cell) ? number_format($cell, 2) : (is_array($cell) ? json_encode($cell) : $cell) }}
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
                Select a period and click "Generate Preview" to view the HR workforce overview.
            </p>
        </div>
    @endif

    {{-- Report Save Modal --}}
    @if($showReportModal && $generatedReport)
        <x-modal wire:model="showReportModal" title="Report Saved Successfully">
            <div class="space-y-4">
                <div class="bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-lg p-4">
                    <p class="text-sm text-green-800 dark:text-green-200">
                        Your HR workforce report has been saved successfully and is ready for review.
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
</div>
