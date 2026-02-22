<div class="p-6 space-y-6">
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

    <x-breadcrumb title="Report Detail" :items="[
        ['label' => 'Dashboard', 'url' => branch_route('branch-dashboard.index')],
        ['label' => 'Reporting'],
        ['label' => 'Report Detail'],
    ]" :compact="false" :with-icons="true" />

    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-6">
        <div class="flex items-start justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">{{ $report->report_name }}</h1>
                <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                    {{ $report->department?->name ?? 'N/A' }} • {{ ucfirst($report->report_category) }} •
                    {{ \Carbon\Carbon::parse($report->report_date)->format('M d, Y') }}
                </p>
            </div>
            <a href="{{ branch_route('branch-dashboard.reporting.review', ['b_id' => $b_id]) }}"
               class="px-4 py-2 bg-gray-200 dark:bg-gray-700 text-gray-800 dark:text-gray-200 rounded-lg hover:bg-gray-300 dark:hover:bg-gray-600">
                Back to Review
            </a>
        </div>

        @include('livewire.branch-dashboard.reporting-department.view-compiled.report-details', ['report' => $report, 'formatValue' => $formatValue])
    </div>
</div>
