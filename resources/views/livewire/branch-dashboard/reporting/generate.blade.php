<div class="p-6 space-y-6">
    <x-breadcrumb title="Report Generator" :items="[
        ['label' => 'Dashboard', 'url' => branch_route('branch-dashboard.index')],
        ['label' => 'Reporting'],
        ['label' => 'Generate'],
    ]" :compact="false" :with-icons="true" />

    @php
        $formatValue = function ($value) {
            if (is_null($value) || $value === '') {
                return '—';
            }
            if (is_numeric($value)) {
                return number_format($value, 2);
            }
            if (is_array($value)) {
                if (empty($value)) {
                    return '—';
                }
                $isAssoc = \Illuminate\Support\Arr::isAssoc($value);
                if (!$isAssoc && collect($value)->every(fn($item) => is_scalar($item))) {
                    return implode(', ', $value);
                }
                return 'Items: '.count($value);
            }
            if (is_object($value)) {
                return method_exists($value, '__toString') ? (string) $value : '—';
            }
            return (string) $value;
        };

        $selectedReport = null;
        foreach ($reportsByCategory as $category => $reports) {
            foreach ($reports as $report) {
                if (($report['key'] ?? null) === $reportKey) {
                    $selectedReport = $report;
                    break 2;
                }
            }
        }
        $requiresDepartment = $selectedReport['meta']['requires_department'] ?? false;
        $departmentLabel = match ($reportCategory ?? '') {
            'sales' => 'Sales Department',
            'production' => 'Production Department',
            'inventory' => 'Inventory Department',
            'accounting' => 'Accounting Department',
            'hr' => 'HR Department',
            default => 'Department',
        };
    @endphp

    {{-- Filters --}}
    <div class="bg-white dark:bg-zinc-800 rounded-lg shadow p-6">
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
            <div>
                <x-select.native label="Report Category" wire:model.live="reportCategory">
                    @forelse($reportsByCategory as $category => $reports)
                        <option value="{{ $category }}">{{ ucfirst($category) }}</option>
                    @empty
                        <option value="">No reports available</option>
                    @endforelse
                </x-select.native>
            </div>

            <div>
                <x-select.native label="Report Type" wire:model.live="reportKey">
                    @if(!empty($reportsByCategory[$reportCategory] ?? []))
                        @foreach($reportsByCategory[$reportCategory] as $report)
                            <option value="{{ $report['key'] }}">{{ $report['meta']['name'] ?? $report['key'] }}</option>
                        @endforeach
                    @else
                        <option value="">Select a category first</option>
                    @endif
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

            @if($requiresDepartment || count($availableDepartments ?? []) > 0)
                <div>
                    <x-select.native :label="$departmentLabel" wire:model.live="departmentId">
                        <option value="">Select Department</option>
                        @foreach($availableDepartments as $dept)
                            <option value="{{ $dept->id }}">{{ $dept->name }}</option>
                        @endforeach
                    </x-select.native>
                </div>
            @endif

            @if($reportCategory === 'sales')
                <div>
                    <x-select.native label="Shift Type" wire:model.live="shiftType">
                        <option value="">All Shifts</option>
                        <option value="morning">Morning Shift</option>
                        <option value="afternoon">Afternoon Shift</option>
                        <option value="evening">Evening Shift</option>
                    </x-select.native>
                </div>
            @endif

            <div class="col-span-full flex gap-2 pt-2">
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
        {{-- Summary Metrics --}}
        @if(!empty($summaryMetrics))
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                @foreach($summaryMetrics as $label => $value)
                    <div class="bg-white dark:bg-zinc-800 rounded-lg shadow p-5">
                        <p class="text-sm text-zinc-600 dark:text-zinc-400">
                            {{ \Illuminate\Support\Str::of($label)->replace('_', ' ')->title() }}
                        </p>
                        <p class="text-2xl font-semibold text-zinc-900 dark:text-zinc-100 mt-2">
                            {{ $formatValue($value) }}
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
                                                    {{ $formatValue($cell) }}
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
