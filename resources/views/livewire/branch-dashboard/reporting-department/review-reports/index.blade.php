<div>
    <div class="p-6 bg-gray-50 dark:bg-gray-900 min-h-screen">
        <!-- Header Section -->
        <div class="mb-6">
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white">Review Reports</h1>
            <p class="text-gray-600 dark:text-gray-400 mt-1">Review and approve department reports</p>
        </div>

        <!-- Status Cards -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
            <!-- Pending Review -->
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-600 dark:text-gray-400">Pending Review</p>
                        <p class="text-2xl font-bold text-orange-600 dark:text-orange-400 mt-1">
                            {{ $statusCounts['pending_review'] }}
                        </p>
                    </div>
                    <div class="p-3 bg-orange-100 dark:bg-orange-900/30 rounded-xl">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6 text-orange-600 dark:text-orange-400">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                </div>
            </div>

            <!-- Reviewed -->
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-600 dark:text-gray-400">Reviewed</p>
                        <p class="text-2xl font-bold text-green-600 dark:text-green-400 mt-1">
                            {{ $statusCounts['reviewed'] }}
                        </p>
                    </div>
                    <div class="p-3 bg-green-100 dark:bg-green-900/30 rounded-xl">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6 text-green-600 dark:text-green-400">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                </div>
            </div>

            <!-- Draft -->
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-600 dark:text-gray-400">Draft Reports</p>
                        <p class="text-2xl font-bold text-gray-700 dark:text-gray-200 mt-1">
                            {{ $statusCounts['draft'] }}
                        </p>
                    </div>
                    <div class="p-3 bg-gray-100 dark:bg-gray-700/50 rounded-xl">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6 text-gray-600 dark:text-gray-300">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9.75 9.75l4.5 4.5m0-4.5l-4.5 4.5M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filters Section -->
        <div class="mb-6 bg-white dark:bg-gray-800 rounded-lg shadow p-4">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Category</label>
                    <select wire:model.live="filterCategory"
                            class="w-full px-3 py-2 border dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                        <option value="all">All Categories</option>
                        <option value="production">Production</option>
                        <option value="sales">Sales</option>
                        <option value="inventory">Inventory</option>
                        <option value="accounting">Accounting</option>
                        <option value="hr">HR</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Department</label>
                    <select wire:model.live="filterDepartment"
                            class="w-full px-3 py-2 border dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                        <option value="all">All Departments</option>
                        @foreach($departments as $dept)
                            <option value="{{ $dept->id }}">{{ $dept->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>

        <!-- Reports List -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50 dark:bg-gray-700">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                Report Date
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                Report Name
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                Department
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                Category
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                Generated By
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                Status
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                Actions
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                        @forelse($reports as $report)
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700">
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">
                                    {{ \Carbon\Carbon::parse($report->report_date)->format('M d, Y') }}
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-900 dark:text-gray-100">
                                    {{ $report->report_name }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">
                                    {{ $report->department?->name ?? 'N/A' }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full
                                        {{ $report->report_category === 'production' ? 'bg-purple-100 text-purple-800 dark:bg-purple-900/30 dark:text-purple-300' : '' }}
                                        {{ $report->report_category === 'sales' ? 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300' : '' }}
                                        {{ $report->report_category === 'inventory' ? 'bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-300' : '' }}">
                                        {{ ucfirst($report->report_category) }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">
                                    {{ $report->generatedBy?->name ?? 'System' }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full
                                        {{ $report->status === 'draft' ? 'bg-gray-100 text-gray-800 dark:bg-gray-900/30 dark:text-gray-300' : '' }}
                                        {{ $report->status === 'pending_review' ? 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-300' : '' }}
                                        {{ $report->status === 'reviewed' ? 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300' : '' }}
                                        {{ $report->status === 'rejected' ? 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-300' : '' }}
                                        {{ $report->status === 'compiled' ? 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300' : '' }}
                                        {{ $report->status === 'sent_to_md' ? 'bg-purple-100 text-purple-800 dark:bg-purple-900/30 dark:text-purple-300' : '' }}">
                                        {{ str_replace('_', ' ', ucfirst($report->status)) }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <a href="{{ branch_route('branch-dashboard.reporting.report.view', ['id' => $report->id, 'b_id' => $b_id]) }}"
                                           class="inline-flex items-center px-3 py-1.5 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-200 text-xs rounded hover:bg-gray-200 dark:hover:bg-gray-600">
                                            View
                                        </a>
                                        @if($report->status === 'draft')
                                            <span class="text-gray-600 dark:text-gray-300 text-xs">
                                                Waiting for submission
                                            </span>
                                        @elseif($report->status === 'pending_review')
                                            <button wire:click="openReviewModal('{{ $report->id }}')"
                                                    class="inline-flex items-center px-3 py-1.5 bg-blue-600 text-white text-xs rounded hover:bg-blue-700">
                                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4 mr-1">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z" />
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                </svg>
                                                Review
                                            </button>
                                        @elseif($report->status === 'reviewed')
                                            <span class="text-green-600 dark:text-green-400 text-xs">
                                                ✓ Reviewed by {{ $report->reviewedBy?->name }}
                                            </span>
                                        @elseif($report->status === 'rejected')
                                            <span class="text-red-600 dark:text-red-400 text-xs">
                                                ✗ Rejected
                                            </span>
                                        @elseif($report->status === 'compiled')
                                            <span class="text-blue-600 dark:text-blue-400 text-xs">
                                                Compiled
                                            </span>
                                        @elseif($report->status === 'sent_to_md')
                                            <span class="text-purple-600 dark:text-purple-400 text-xs">
                                                Sent to MD
                                            </span>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-6 py-12 text-center text-gray-500 dark:text-gray-400">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-12 h-12 mx-auto mb-4 text-gray-400">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" />
                                    </svg>
                                    <p class="text-lg font-medium">No reports to review</p>
                                    <p class="text-sm mt-1">Check back later or adjust your filters</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            @if($reports->hasPages())
                <div class="px-6 py-4 border-t dark:border-gray-700">
                    {{ $reports->links() }}
                </div>
            @endif
        </div>
    </div>

    <!-- Review Modal -->
    @if($showReviewModal && $selectedReport)
        <div class="fixed inset-0 z-50 overflow-y-auto">
            <!-- Backdrop -->
            <div class="fixed inset-0 bg-black bg-opacity-50 transition-opacity" wire:click="closeReviewModal"></div>

            <!-- Modal Container -->
            <div class="flex items-center justify-center min-h-screen px-4 py-8">
                <!-- Modal Content -->
                <div class="relative bg-white dark:bg-gray-800 rounded-lg shadow-xl max-w-2xl w-full overflow-hidden">
                    <!-- Modal Header -->
                    <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                        <div class="flex items-center justify-between">
                            <h3 class="text-lg font-medium text-gray-900 dark:text-white">Review Report</h3>
                            <button wire:click="closeReviewModal" type="button" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                </svg>
                            </button>
                        </div>
                    </div>

                    <!-- Modal Body -->
                    <div class="px-6 py-4 max-h-[70vh] overflow-y-auto">
                        <div class="space-y-4">
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

                                $payload = $selectedReport->report_data ?? [];
                                if (isset($payload['report_data']) && is_array($payload['report_data'])) {
                                    $payload = $payload['report_data'];
                                }
                                $summaryMetrics = $selectedReport->summary_metrics ?? ($payload['summary_metrics'] ?? []);
                                $tablesData = $payload['tables'] ?? [];
                                $narrative = $payload['narrative'] ?? [];
                                $periodInfo = $payload['period_info'] ?? [];
                                $reportType = $selectedReport->report_type ?? ($payload['report_type'] ?? null);
                                $reportCategory = $selectedReport->report_category ?? ($payload['report_category'] ?? null);
                                $coverageRows = [];
                                foreach ($tablesData as $tableName => $table) {
                                    $coverageRows[] = [
                                        'table' => \Illuminate\Support\Str::of($tableName)->replace('_', ' ')->title(),
                                        'rows' => count($table['rows'] ?? []),
                                    ];
                                }
                                $dataOverview = [];
                                foreach ($payload as $key => $value) {
                                    if (in_array($key, ['summary_metrics', 'tables', 'narrative', 'period_info'], true)) {
                                        continue;
                                    }
                                    if (is_array($value)) {
                                        $dataOverview[] = [
                                            'section' => \Illuminate\Support\Str::of($key)->replace('_', ' ')->title(),
                                            'items' => count($value),
                                        ];
                                    }
                                }
                                $systemDataValid = $selectedReport->systemDataHashIsValid();
                            @endphp
                            @if(!$systemDataValid)
                                <div class="rounded-lg border border-red-200 dark:border-red-700 bg-red-50 dark:bg-red-900/20 p-3 text-sm text-red-700 dark:text-red-300">
                                    Warning: System data integrity check failed. This report may have been altered.
                                </div>
                            @endif
                            <!-- Report Details -->
                            <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4">
                                <div class="grid grid-cols-2 gap-3 text-sm">
                                    <div>
                                        <span class="text-gray-600 dark:text-gray-400">Report Name:</span>
                                        <p class="font-medium text-gray-900 dark:text-white">{{ $selectedReport->report_name }}</p>
                                    </div>
                                    <div>
                                        <span class="text-gray-600 dark:text-gray-400">Department:</span>
                                        <p class="font-medium text-gray-900 dark:text-white">{{ $selectedReport->department?->name ?? 'N/A' }}</p>
                                    </div>
                                    <div>
                                        <span class="text-gray-600 dark:text-gray-400">Category:</span>
                                        <p class="font-medium text-gray-900 dark:text-white">{{ ucfirst($selectedReport->report_category) }}</p>
                                    </div>
                                    <div>
                                        <span class="text-gray-600 dark:text-gray-400">Generated By:</span>
                                        <p class="font-medium text-gray-900 dark:text-white">{{ $selectedReport->generatedBy?->name ?? 'N/A' }}</p>
                                    </div>
                                    <div>
                                        <span class="text-gray-600 dark:text-gray-400">Report Date:</span>
                                        <p class="font-medium text-gray-900 dark:text-white">{{ \Carbon\Carbon::parse($selectedReport->report_date)->format('M d, Y') }}</p>
                                    </div>
                                    <div>
                                        <span class="text-gray-600 dark:text-gray-400">Period:</span>
                                        <p class="font-medium text-gray-900 dark:text-white">
                                            {{ \Carbon\Carbon::parse($selectedReport->period_from)->format('M d') }} -
                                            {{ \Carbon\Carbon::parse($selectedReport->period_to)->format('M d, Y') }}
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <a href="{{ branch_route('branch-dashboard.reporting.report.view', ['id' => $selectedReport->id, 'b_id' => $b_id]) }}"
                               class="inline-flex items-center px-3 py-2 bg-indigo-600 text-white text-xs rounded hover:bg-indigo-700 w-fit">
                                View Full Report
                            </a>

                            @include('livewire.branch-dashboard.reporting-department.view-compiled.report-details', ['report' => $selectedReport, 'formatValue' => $formatValue])

                            <!-- Tables -->
                            @if(!empty($tablesData))
                                <div class="space-y-4">
                                    @foreach($tablesData as $tableName => $table)
                                        <div class="border border-gray-200 dark:border-gray-600 rounded-lg">
                                            <div class="px-3 py-2 border-b border-gray-200 dark:border-gray-600 bg-gray-50 dark:bg-gray-700">
                                                <p class="text-sm font-medium text-gray-900 dark:text-gray-100">
                                                    {{ \Illuminate\Support\Str::of($tableName)->replace('_', ' ')->title() }}
                                                </p>
                                            </div>
                                            <div class="p-3 overflow-x-auto">
                                                <table class="min-w-full text-sm">
                                                    @if(!empty($table['headers']))
                                                        <thead>
                                                            <tr>
                                                                @foreach($table['headers'] as $header)
                                                                    <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                                                        {{ $header }}
                                                                    </th>
                                                                @endforeach
                                                            </tr>
                                                        </thead>
                                                    @endif
                                                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                                                        @forelse(($table['rows'] ?? []) as $row)
                                                            <tr>
                                                                @foreach($row as $cell)
                                                                    <td class="px-3 py-2 text-gray-700 dark:text-gray-300">
                                                                        {{ is_numeric($cell) ? number_format($cell, 2) : (is_array($cell) ? json_encode($cell) : $cell) }}
                                                                    </td>
                                                                @endforeach
                                                            </tr>
                                                        @empty
                                                            <tr>
                                                                <td class="px-3 py-2 text-gray-500 dark:text-gray-400" colspan="{{ count($table['headers'] ?? []) }}">
                                                                    No rows available.
                                                                </td>
                                                            </tr>
                                                        @endforelse
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @else
                                <div class="text-sm text-gray-500 dark:text-gray-400">
                                    No tables available for this report.
                                </div>
                            @endif

                            <!-- Annotations -->
                            <div class="border border-gray-200 dark:border-gray-600 rounded-lg p-4 bg-gray-50 dark:bg-gray-700/40">
                                <div class="flex items-center justify-between mb-3">
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Annotations (Protected)</label>
                                    <span class="text-xs text-gray-500 dark:text-gray-400">Does not alter system data</span>
                                </div>
                                <div class="space-y-3">
                                    @forelse($annotations as $note)
                                        <div class="rounded-md border border-gray-200 dark:border-gray-600 bg-white dark:bg-gray-800 p-3">
                                            <div class="flex items-center justify-between text-xs text-gray-500 dark:text-gray-400 mb-2">
                                                <span>{{ $note['author'] }}</span>
                                                <span>{{ $note['created_at'] }}</span>
                                            </div>
                                            <p class="text-sm text-gray-700 dark:text-gray-200 whitespace-pre-wrap">{{ $note['body'] }}</p>
                                        </div>
                                    @empty
                                        <p class="text-sm text-gray-500 dark:text-gray-400">No annotations yet.</p>
                                    @endforelse
                                </div>

                                <div class="mt-4">
                                    <textarea wire:model="newAnnotation" rows="3"
                                              placeholder="Add an annotation (visible in review and compile views)..."
                                              class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent"></textarea>
                                    @error('newAnnotation')
                                        <p class="text-xs text-red-600 dark:text-red-400 mt-1">{{ $message }}</p>
                                    @enderror
                                    <div class="mt-2 flex justify-end">
                                        <button type="button" wire:click.prevent="addAnnotation" wire:loading.attr="disabled" wire:target="addAnnotation"
                                                class="px-3 py-2 text-sm bg-blue-600 text-white rounded-lg hover:bg-blue-700 disabled:opacity-60">
                                            Add Annotation
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <!-- Review Notes -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Review Notes</label>
                                <textarea wire:model="reviewNotes" rows="4"
                                          placeholder="Add your review comments or rejection reason..."
                                          class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent"></textarea>
                            </div>
                        </div>
                    </div>

                    <!-- Modal Footer -->
                    <div class="px-6 py-4 bg-gray-50 dark:bg-gray-700 border-t border-gray-200 dark:border-gray-600 flex justify-end gap-3">
                        <button wire:click="closeReviewModal" type="button"
                                class="px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700">
                            Cancel
                        </button>
                        <button type="button" wire:click.prevent="rejectReport" wire:loading.attr="disabled" wire:target="rejectReport"
                                class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 disabled:opacity-60">
                            Reject Report
                        </button>
                        <button type="button" wire:click.prevent="approveReport" wire:loading.attr="disabled" wire:target="approveReport"
                                class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 disabled:opacity-60">
                            Approve Report
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
