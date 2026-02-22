<div class="w-full">
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="text-2xl font-bold">View Managing Director Report</h2>
            <a href="{{ route('branch-dashboard.md-reports.dashboard') }}" wire:navigate class="text-blue-600 hover:text-blue-800">
                Back to Reports
            </a>
        </div>
    </x-slot>

    <div class="p-6">
        @if($report)
            <div class="bg-white dark:bg-zinc-800 rounded-lg shadow p-6 mb-6">
                <!-- Report Header -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
                    <div>
                        <p class="text-sm text-gray-600 dark:text-gray-400">Branch</p>
                        <p class="text-lg font-semibold">{{ $report->branch?->name }}</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-600 dark:text-gray-400">Compiled By</p>
                        <p class="text-lg font-semibold">{{ $report->compiledBy?->name }}</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-600 dark:text-gray-400">Compilation Date</p>
                        <p class="text-lg font-semibold">{{ $report->compilation_date?->format('M d, Y') }}</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-600 dark:text-gray-400">Status</p>
                        <p class="text-lg font-semibold">
                            <span class="px-3 py-1 rounded-full text-xs font-semibold {{ $report->status === 'reviewed_by_md' ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' : 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200' }}">
                                {{ ucfirst(str_replace('_', ' ', $report->status)) }}
                            </span>
                        </p>
                    </div>
                </div>

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
                @endphp

                <!-- Executive summary removed in favor of full report detail blocks -->

                <!-- Compiled Report Annotations -->
                <div class="mb-6">
                    <div class="flex items-center justify-between mb-2">
                        <h4 class="text-lg font-semibold">Compiled Report Annotations</h4>
                        <span class="text-xs text-gray-500 dark:text-gray-400">Read-only</span>
                    </div>
                    <div class="space-y-3">
                        @forelse($report->annotations as $annotation)
                            <div class="rounded-lg border border-gray-200 dark:border-zinc-700 bg-gray-50 dark:bg-zinc-900/30 p-4">
                                <div class="flex items-center justify-between text-xs text-gray-500 dark:text-gray-400 mb-2">
                                    <span>{{ $annotation->author?->name ?? 'Unknown' }}</span>
                                    <span>{{ $annotation->created_at?->format('Y-m-d H:i') }}</span>
                                </div>
                                <p class="text-sm text-gray-700 dark:text-gray-200 whitespace-pre-wrap">{{ $annotation->body }}</p>
                            </div>
                        @empty
                            <p class="text-sm text-gray-500 dark:text-gray-400">No annotations yet.</p>
                        @endforelse
                    </div>
                </div>

                @php
                    $mdIntegrityIssues = $report->departmentReports->contains(function ($departmentReport) {
                        return !$departmentReport->systemDataHashIsValid();
                    });
                @endphp
                @if($mdIntegrityIssues)
                    <div class="mb-6 rounded-lg border border-red-200 dark:border-red-700 bg-red-50 dark:bg-red-900/20 p-3 text-sm text-red-700 dark:text-red-300">
                        Warning: One or more included reports failed the system data integrity check.
                    </div>
                @endif

                <!-- Department Report Details -->
                @if($report->departmentReports && count($report->departmentReports) > 0)
                    <div class="mt-8 space-y-8">
                        <h3 class="text-lg font-semibold">Department Report Details</h3>
                        @foreach($report->departmentReports as $departmentReport)
                            @php
                                $reportPayload = $departmentReport->report_data ?? [];
                                if (isset($reportPayload['report_data']) && is_array($reportPayload['report_data'])) {
                                    $reportPayload = $reportPayload['report_data'];
                                }
                                $reportPeriodInfo = $reportPayload['period_info'] ?? [
                                    'from' => $departmentReport->period_from,
                                    'to' => $departmentReport->period_to,
                                ];
                            @endphp

                            <div class="border border-gray-200 dark:border-zinc-700 rounded-lg overflow-hidden">
                                <div class="px-4 py-3 bg-gray-50 dark:bg-zinc-800 border-b border-gray-200 dark:border-zinc-700">
                                    <div class="flex flex-col gap-2">
                                        <p class="text-base font-semibold text-gray-900 dark:text-white">
                                            {{ $departmentReport->report_name ?? 'Department Report' }}
                                        </p>
                                        <div class="flex flex-wrap gap-3 text-sm text-gray-600 dark:text-gray-300">
                                            <span>Department: {{ $departmentReport->department?->name ?? 'N/A' }}</span>
                                            <span>Category: {{ ucfirst($departmentReport->report_category ?? 'N/A') }}</span>
                                            <span>Type: {{ str_replace('_', ' ', $departmentReport->report_type ?? 'N/A') }}</span>
                                            <span>Status: {{ str_replace('_', ' ', $departmentReport->status ?? 'N/A') }}</span>
                                            <span>Generated By: {{ $departmentReport->generatedBy?->name ?? 'System' }}</span>
                                        </div>
                                        @if(!$departmentReport->systemDataHashIsValid())
                                            <div class="text-xs text-red-600 dark:text-red-300">Integrity check failed</div>
                                        @endif
                                        <div class="flex flex-wrap gap-3 text-xs text-gray-500 dark:text-gray-400">
                                            <span>Report Date: {{ optional($departmentReport->report_date)->format('M d, Y') ?? 'N/A' }}</span>
                                            <span>Period: {{ $reportPeriodInfo['from'] ?? '-' }} to {{ $reportPeriodInfo['to'] ?? '-' }}</span>
                                            <a href="{{ branch_route('branch-dashboard.reporting.report.view', ['id' => $departmentReport->id, 'b_id' => $report->branch_id]) }}"
                                               class="px-2 py-1 bg-indigo-600 text-white rounded hover:bg-indigo-700">
                                                View Full
                                            </a>
                                        </div>
                                    </div>
                                </div>

                                <div class="p-4 space-y-6">
                                    @include('livewire.branch-dashboard.reporting-department.view-compiled.report-details', ['report' => $departmentReport, 'formatValue' => $formatValue])

                                    @if($departmentReport->annotations?->count())
                                        <div class="border border-gray-200 dark:border-zinc-700 rounded-lg">
                                            <div class="px-3 py-2 border-b border-gray-200 dark:border-zinc-700 bg-gray-50 dark:bg-zinc-800">
                                                <p class="text-sm font-medium text-gray-900 dark:text-gray-100">Annotations</p>
                                            </div>
                                            <div class="p-3 space-y-3">
                                                @foreach($departmentReport->annotations as $annotation)
                                                    <div class="rounded-md border border-gray-200 dark:border-zinc-700 bg-white dark:bg-zinc-900/30 p-3">
                                                        <div class="flex items-center justify-between text-xs text-gray-500 dark:text-gray-400 mb-1">
                                                            <span>{{ $annotation->author?->name ?? 'Unknown' }}</span>
                                                            <span>{{ $annotation->created_at?->format('Y-m-d H:i') }}</span>
                                                        </div>
                                                        <p class="text-sm text-gray-700 dark:text-gray-200 whitespace-pre-wrap">{{ $annotation->body }}</p>
                                                    </div>
                                                @endforeach
                                            </div>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        @else
            <div class="text-center text-gray-500 py-8">
                <p class="text-lg">Report not found</p>
                <p class="mt-2">The requested Managing Director report could not be located.</p>
                <a href="{{ route('branch-dashboard.md-reports.dashboard') }}" wire:navigate class="inline-block mt-4 px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">
                    Return to Reports Dashboard
                </a>
            </div>
        @endif
    </div>
</div>
