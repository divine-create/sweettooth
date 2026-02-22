<div>
    <div class="p-6 bg-gray-50 dark:bg-gray-900 min-h-screen">
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
        <!-- Header Section -->
        <div class="mb-6 flex justify-between items-center">
            <div>
                <h1 class="text-3xl font-bold text-gray-900 dark:text-white">{{ $compiledReport->compilation_title }}</h1>
                <p class="text-gray-600 dark:text-gray-400 mt-1">{{ $compiledReport->compilation_description }}</p>
            </div>
            <div class="flex gap-3">
                <a href="{{ branch_route('branch-dashboard.reporting.send-to-md') }}"
                   class="px-4 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700 flex items-center gap-2">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18" />
                    </svg>
                    Back to Reports
                </a>
            </div>
        </div>

        <!-- Report Metadata Cards -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-6">
            <!-- Status -->
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-6">
                <p class="text-sm text-gray-600 dark:text-gray-400">Status</p>
                <span class="mt-2 inline-flex px-3 py-1 text-sm font-semibold rounded-full
                    {{ $compiledReport->status === 'draft' ? 'bg-gray-100 text-gray-800 dark:bg-gray-900/30 dark:text-gray-300' : '' }}
                    {{ $compiledReport->status === 'pending_approval' ? 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-300' : '' }}
                    {{ $compiledReport->status === 'approved' ? 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300' : '' }}
                    {{ $compiledReport->status === 'sent_to_md' ? 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300' : '' }}">
                    {{ str_replace('_', ' ', ucfirst($compiledReport->status)) }}
                </span>
            </div>

            <!-- Compilation Date -->
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-6">
                <p class="text-sm text-gray-600 dark:text-gray-400">Compilation Date</p>
                <p class="text-lg font-bold text-gray-900 dark:text-white mt-1">
                    {{ \Carbon\Carbon::parse($compiledReport->compilation_date)->format('M d, Y') }}
                </p>
            </div>

            <!-- Period -->
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-6">
                <p class="text-sm text-gray-600 dark:text-gray-400">Reporting Period</p>
                <p class="text-lg font-bold text-gray-900 dark:text-white mt-1">
                    {{ \Carbon\Carbon::parse($compiledReport->period_from)->format('M d') }} -
                    {{ \Carbon\Carbon::parse($compiledReport->period_to)->format('M d, Y') }}
                </p>
            </div>

            <!-- Compiled By -->
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-6">
                <p class="text-sm text-gray-600 dark:text-gray-400">Compiled By</p>
                <p class="text-lg font-bold text-gray-900 dark:text-white mt-1">
                    {{ $compiledReport->compiledBy?->name ?? 'N/A' }}
                </p>
            </div>
        </div>

        <!-- Executive Summary intentionally removed in favor of full report detail blocks -->

        <!-- Compiled Report Annotations -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-6 mb-6">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-xl font-semibold text-gray-900 dark:text-white">Compiled Report Annotations</h2>
                <span class="text-xs text-gray-500 dark:text-gray-400">Read-only</span>
            </div>
            <div class="space-y-3">
                @forelse($compiledReport->annotations as $annotation)
                    <div class="rounded-lg border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900/40 p-4">
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

        <!-- Recommendations -->
        @if($compiledReport->recommendations && count($compiledReport->recommendations) > 0)
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-6 mb-6">
                <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-4">Recommendations</h2>
                <div class="space-y-4">
                    @foreach($compiledReport->recommendations as $recommendation)
                        <div class="border dark:border-gray-700 rounded-lg p-4">
                            <div class="flex items-start justify-between">
                                <div class="flex-1">
                                    <h3 class="text-lg font-medium text-gray-900 dark:text-white">{{ $recommendation['title'] }}</h3>
                                    <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">{{ $recommendation['description'] }}</p>
                                </div>
                                <span class="px-2 py-1 text-xs font-semibold rounded-full
                                    {{ $recommendation['priority'] === 'high' ? 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-300' : '' }}
                                    {{ $recommendation['priority'] === 'medium' ? 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-300' : '' }}
                                    {{ $recommendation['priority'] === 'low' ? 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300' : '' }}">
                                    {{ ucfirst($recommendation['priority']) }}
                                </span>
                            </div>
                            @if(isset($recommendation['action_items']) && count($recommendation['action_items']) > 0)
                                <div class="mt-3">
                                    <p class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Action Items:</p>
                                    <ul class="list-disc list-inside space-y-1">
                                        @foreach($recommendation['action_items'] as $action)
                                            <li class="text-sm text-gray-600 dark:text-gray-400">{{ $action }}</li>
                                        @endforeach
                                    </ul>
                                </div>
                            @endif>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        <!-- Included Department Reports -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-6">
            <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-4">Included Department Reports</h2>
            @php
                $hasIntegrityIssues = $compiledReport->departmentReports->contains(function ($report) {
                    return !$report->systemDataHashIsValid();
                });
            @endphp
            @if($hasIntegrityIssues)
                <div class="mb-4 rounded-lg border border-red-200 dark:border-red-700 bg-red-50 dark:bg-red-900/20 p-3 text-sm text-red-700 dark:text-red-300">
                    Warning: One or more included reports failed the system data integrity check.
                </div>
            @endif

            <!-- Production Reports -->
            @if($productionReports->count() > 0)
                <div class="mb-6">
                    <h3 class="text-lg font-medium text-gray-800 dark:text-gray-200 mb-3">Production Reports</h3>
                    <div class="space-y-2">
                        @foreach($productionReports as $report)
                            @php
                                $integrityOk = $report->systemDataHashIsValid();
                            @endphp
                            <div class="flex items-center justify-between p-3 bg-purple-50 dark:bg-purple-900/20 border border-purple-200 dark:border-purple-800 rounded-lg">
                                <div>
                                    <p class="text-sm font-medium text-gray-900 dark:text-white">{{ $report->report_name }}</p>
                                    <p class="text-xs text-gray-600 dark:text-gray-400 mt-1">
                                        {{ $report->department?->name }} • Generated by {{ $report->generatedBy?->name }}
                                    </p>
                                    @if(!$integrityOk)
                                        <p class="mt-1 text-xs text-red-600 dark:text-red-300">Integrity check failed</p>
                                    @endif
                                </div>
                                <div class="text-xs text-gray-500 dark:text-gray-400 flex items-center gap-2">
                                    <span>{{ \Carbon\Carbon::parse($report->report_date)->format('M d, Y') }}</span>
                                    <a href="{{ branch_route('branch-dashboard.reporting.report.view', ['id' => $report->id, 'b_id' => $b_id]) }}"
                                       class="px-2 py-1 bg-indigo-600 text-white rounded hover:bg-indigo-700">
                                        View Full
                                    </a>
                                </div>
                            </div>
                            @if($report->annotations?->count())
                                <div class="ml-3 mt-2 space-y-2">
                                    @foreach($report->annotations as $annotation)
                                        <div class="rounded-md border border-purple-200 dark:border-purple-800 bg-white dark:bg-gray-800 p-3">
                                            <div class="flex items-center justify-between text-xs text-gray-500 dark:text-gray-400 mb-1">
                                                <span>{{ $annotation->author?->name ?? 'Unknown' }}</span>
                                                <span>{{ $annotation->created_at?->format('Y-m-d H:i') }}</span>
                                            </div>
                                            <p class="text-sm text-gray-700 dark:text-gray-200 whitespace-pre-wrap">{{ $annotation->body }}</p>
                                        </div>
                                    @endforeach
                                </div>
                            @endif

                            @include('livewire.branch-dashboard.reporting-department.view-compiled.report-details', ['report' => $report, 'formatValue' => $formatValue])
                        @endforeach
                    </div>
                </div>
            @endif

            <!-- Sales Reports -->
            @if($salesReports->count() > 0)
                <div class="mb-6">
                    <h3 class="text-lg font-medium text-gray-800 dark:text-gray-200 mb-3">Sales Reports</h3>
                    <div class="space-y-2">
                        @foreach($salesReports as $report)
                            @php
                                $integrityOk = $report->systemDataHashIsValid();
                            @endphp
                            <div class="flex items-center justify-between p-3 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-lg">
                                <div>
                                    <p class="text-sm font-medium text-gray-900 dark:text-white">{{ $report->report_name }}</p>
                                    <p class="text-xs text-gray-600 dark:text-gray-400 mt-1">
                                        {{ $report->department?->name }} • Generated by {{ $report->generatedBy?->name }}
                                    </p>
                                    @if(!$integrityOk)
                                        <p class="mt-1 text-xs text-red-600 dark:text-red-300">Integrity check failed</p>
                                    @endif
                                </div>
                                <div class="text-xs text-gray-500 dark:text-gray-400 flex items-center gap-2">
                                    <span>{{ \Carbon\Carbon::parse($report->report_date)->format('M d, Y') }}</span>
                                    <a href="{{ branch_route('branch-dashboard.reporting.report.view', ['id' => $report->id, 'b_id' => $b_id]) }}"
                                       class="px-2 py-1 bg-indigo-600 text-white rounded hover:bg-indigo-700">
                                        View Full
                                    </a>
                                </div>
                            </div>
                            @if($report->annotations?->count())
                                <div class="ml-3 mt-2 space-y-2">
                                    @foreach($report->annotations as $annotation)
                                        <div class="rounded-md border border-green-200 dark:border-green-800 bg-white dark:bg-gray-800 p-3">
                                            <div class="flex items-center justify-between text-xs text-gray-500 dark:text-gray-400 mb-1">
                                                <span>{{ $annotation->author?->name ?? 'Unknown' }}</span>
                                                <span>{{ $annotation->created_at?->format('Y-m-d H:i') }}</span>
                                            </div>
                                            <p class="text-sm text-gray-700 dark:text-gray-200 whitespace-pre-wrap">{{ $annotation->body }}</p>
                                        </div>
                                    @endforeach
                                </div>
                            @endif

                            @include('livewire.branch-dashboard.reporting-department.view-compiled.report-details', ['report' => $report, 'formatValue' => $formatValue])
                        @endforeach
                    </div>
                </div>
            @endif

            <!-- Inventory Reports -->
            @if($inventoryReports->count() > 0)
                <div>
                    <h3 class="text-lg font-medium text-gray-800 dark:text-gray-200 mb-3">Inventory Reports</h3>
                    <div class="space-y-2">
                        @foreach($inventoryReports as $report)
                            @php
                                $integrityOk = $report->systemDataHashIsValid();
                            @endphp
                            <div class="flex items-center justify-between p-3 bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 rounded-lg">
                                <div>
                                    <p class="text-sm font-medium text-gray-900 dark:text-white">{{ $report->report_name }}</p>
                                    <p class="text-xs text-gray-600 dark:text-gray-400 mt-1">
                                        {{ $report->department?->name }} • Generated by {{ $report->generatedBy?->name }}
                                    </p>
                                    @if(!$integrityOk)
                                        <p class="mt-1 text-xs text-red-600 dark:text-red-300">Integrity check failed</p>
                                    @endif
                                </div>
                                <div class="text-xs text-gray-500 dark:text-gray-400 flex items-center gap-2">
                                    <span>{{ \Carbon\Carbon::parse($report->report_date)->format('M d, Y') }}</span>
                                    <a href="{{ branch_route('branch-dashboard.reporting.report.view', ['id' => $report->id, 'b_id' => $b_id]) }}"
                                       class="px-2 py-1 bg-indigo-600 text-white rounded hover:bg-indigo-700">
                                        View Full
                                    </a>
                                </div>
                            </div>
                            @if($report->annotations?->count())
                                <div class="ml-3 mt-2 space-y-2">
                                    @foreach($report->annotations as $annotation)
                                        <div class="rounded-md border border-amber-200 dark:border-amber-800 bg-white dark:bg-gray-800 p-3">
                                            <div class="flex items-center justify-between text-xs text-gray-500 dark:text-gray-400 mb-1">
                                                <span>{{ $annotation->author?->name ?? 'Unknown' }}</span>
                                                <span>{{ $annotation->created_at?->format('Y-m-d H:i') }}</span>
                                            </div>
                                            <p class="text-sm text-gray-700 dark:text-gray-200 whitespace-pre-wrap">{{ $annotation->body }}</p>
                                        </div>
                                    @endforeach
                                </div>
                            @endif

                            @include('livewire.branch-dashboard.reporting-department.view-compiled.report-details', ['report' => $report, 'formatValue' => $formatValue])
                        @endforeach
                    </div>
                </div>
            @endif

            <!-- Accounting Reports -->
            @if($accountingReports->count() > 0)
                <div class="mt-6">
                    <h3 class="text-lg font-medium text-gray-800 dark:text-gray-200 mb-3">Accounting Reports</h3>
                    <div class="space-y-2">
                        @foreach($accountingReports as $report)
                            @php
                                $integrityOk = $report->systemDataHashIsValid();
                            @endphp
                            <div class="flex items-center justify-between p-3 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg">
                                <div>
                                    <p class="text-sm font-medium text-gray-900 dark:text-white">{{ $report->report_name }}</p>
                                    <p class="text-xs text-gray-600 dark:text-gray-400 mt-1">
                                        {{ $report->department?->name ?? 'N/A' }} • Generated by {{ $report->generatedBy?->name }}
                                    </p>
                                    @if(!$integrityOk)
                                        <p class="mt-1 text-xs text-red-600 dark:text-red-300">Integrity check failed</p>
                                    @endif
                                </div>
                                <div class="text-xs text-gray-500 dark:text-gray-400 flex items-center gap-2">
                                    <span>{{ \Carbon\Carbon::parse($report->report_date)->format('M d, Y') }}</span>
                                    <a href="{{ branch_route('branch-dashboard.reporting.report.view', ['id' => $report->id, 'b_id' => $b_id]) }}"
                                       class="px-2 py-1 bg-indigo-600 text-white rounded hover:bg-indigo-700">
                                        View Full
                                    </a>
                                </div>
                            </div>
                            @if($report->annotations?->count())
                                <div class="ml-3 mt-2 space-y-2">
                                    @foreach($report->annotations as $annotation)
                                        <div class="rounded-md border border-blue-200 dark:border-blue-800 bg-white dark:bg-gray-800 p-3">
                                            <div class="flex items-center justify-between text-xs text-gray-500 dark:text-gray-400 mb-1">
                                                <span>{{ $annotation->author?->name ?? 'Unknown' }}</span>
                                                <span>{{ $annotation->created_at?->format('Y-m-d H:i') }}</span>
                                            </div>
                                            <p class="text-sm text-gray-700 dark:text-gray-200 whitespace-pre-wrap">{{ $annotation->body }}</p>
                                        </div>
                                    @endforeach
                                </div>
                            @endif

                            @include('livewire.branch-dashboard.reporting-department.view-compiled.report-details', ['report' => $report, 'formatValue' => $formatValue])
                        @endforeach
                    </div>
                </div>
            @endif

            <!-- HR Reports -->
            @if($hrReports->count() > 0)
                <div class="mt-6">
                    <h3 class="text-lg font-medium text-gray-800 dark:text-gray-200 mb-3">HR Reports</h3>
                    <div class="space-y-2">
                        @foreach($hrReports as $report)
                            @php
                                $integrityOk = $report->systemDataHashIsValid();
                            @endphp
                            <div class="flex items-center justify-between p-3 bg-emerald-50 dark:bg-emerald-900/20 border border-emerald-200 dark:border-emerald-800 rounded-lg">
                                <div>
                                    <p class="text-sm font-medium text-gray-900 dark:text-white">{{ $report->report_name }}</p>
                                    <p class="text-xs text-gray-600 dark:text-gray-400 mt-1">
                                        {{ $report->department?->name ?? 'N/A' }} • Generated by {{ $report->generatedBy?->name }}
                                    </p>
                                    @if(!$integrityOk)
                                        <p class="mt-1 text-xs text-red-600 dark:text-red-300">Integrity check failed</p>
                                    @endif
                                </div>
                                <div class="text-xs text-gray-500 dark:text-gray-400 flex items-center gap-2">
                                    <span>{{ \Carbon\Carbon::parse($report->report_date)->format('M d, Y') }}</span>
                                    <a href="{{ branch_route('branch-dashboard.reporting.report.view', ['id' => $report->id, 'b_id' => $b_id]) }}"
                                       class="px-2 py-1 bg-indigo-600 text-white rounded hover:bg-indigo-700">
                                        View Full
                                    </a>
                                </div>
                            </div>
                            @if($report->annotations?->count())
                                <div class="ml-3 mt-2 space-y-2">
                                    @foreach($report->annotations as $annotation)
                                        <div class="rounded-md border border-emerald-200 dark:border-emerald-800 bg-white dark:bg-gray-800 p-3">
                                            <div class="flex items-center justify-between text-xs text-gray-500 dark:text-gray-400 mb-1">
                                                <span>{{ $annotation->author?->name ?? 'Unknown' }}</span>
                                                <span>{{ $annotation->created_at?->format('Y-m-d H:i') }}</span>
                                            </div>
                                            <p class="text-sm text-gray-700 dark:text-gray-200 whitespace-pre-wrap">{{ $annotation->body }}</p>
                                        </div>
                                    @endforeach
                                </div>
                            @endif

                            @include('livewire.branch-dashboard.reporting-department.view-compiled.report-details', ['report' => $report, 'formatValue' => $formatValue])
                        @endforeach
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>
