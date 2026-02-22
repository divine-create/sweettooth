@php
    $payload = $report->report_data ?? [];
    $reportPayload = $payload;
    if (isset($payload['report_data']) && is_array($payload['report_data'])) {
        $reportPayload = $payload;
        $payload = $payload['report_data'];
    }
    $summaryMetrics = $report->summary_metrics ?? ($reportPayload['summary_metrics'] ?? $payload['summary_metrics'] ?? []);
    $tablesData = $reportPayload['tables'] ?? $payload['tables'] ?? [];
    $narrative = $reportPayload['narrative'] ?? $payload['narrative'] ?? [];
@endphp

@if(!empty($tablesData) || !empty($narrative) || !empty($summaryMetrics))
    <div class="mt-3 rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-4 space-y-4">
        @if(empty($tablesData) && empty($narrative) && !empty($summaryMetrics))
            <div>
                <p class="text-sm font-medium text-gray-900 dark:text-gray-100 mb-2">Summary Metrics</p>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-2 text-sm">
                    @foreach($summaryMetrics as $label => $value)
                        <div class="flex items-center justify-between rounded-md bg-gray-50 dark:bg-gray-900/40 px-3 py-2">
                            <span class="text-gray-600 dark:text-gray-400">
                                {{ \Illuminate\Support\Str::of($label)->replace('_', ' ')->title() }}
                            </span>
                            <span class="text-gray-900 dark:text-gray-100 font-medium">
                                {{ $formatValue($value) }}
                            </span>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif
        @if(!empty($narrative))
            <div>
                <p class="text-sm font-medium text-gray-900 dark:text-gray-100 mb-2">Insights</p>
                <div class="text-sm text-gray-700 dark:text-gray-300 space-y-2">
                    @if(!empty($narrative['overview']))
                        <p>{{ $narrative['overview'] }}</p>
                    @endif
                    @foreach(['highlights' => 'Highlights', 'concerns' => 'Concerns', 'recommendations' => 'Recommendations'] as $key => $title)
                        @if(!empty($narrative[$key]))
                            <div>
                                <p class="text-xs font-medium text-gray-600 dark:text-gray-400">{{ $title }}</p>
                                <ul class="list-disc pl-5">
                                    @foreach($narrative[$key] as $item)
                                        <li>{{ $item }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif
                    @endforeach
                </div>
            </div>
        @endif

        @if(!empty($tablesData))
            <div class="space-y-3">
                <p class="text-sm font-medium text-gray-900 dark:text-gray-100">Tables</p>
                @foreach($tablesData as $tableName => $table)
                    <div class="border border-gray-200 dark:border-gray-700 rounded-lg">
                        <div class="px-3 py-2 border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900/40">
                            <p class="text-sm font-medium text-gray-800 dark:text-gray-200">
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
                                                    {{ $formatValue($cell) }}
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
        @endif
    </div>
@else
    <div class="mt-3 rounded-lg border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900/20 p-4 text-sm text-gray-600 dark:text-gray-300">
        No detailed tables or narrative are available for this report yet.
    </div>
@endif
