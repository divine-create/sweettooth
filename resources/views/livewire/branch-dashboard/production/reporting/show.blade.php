<div class="space-y-6">
    <div class="rounded-2xl border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
        <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-zinc-500">Production</p>
                <h1 class="mt-2 text-3xl font-semibold text-zinc-900 dark:text-white">{{ $report?->report_name ?? 'Report' }}</h1>
                <p class="mt-2 text-sm text-zinc-600 dark:text-zinc-400">
                    {{ $report?->report_type ?? '-' }} • {{ optional($report?->report_date)->format('Y-m-d') ?? '-' }}
                </p>
            </div>
            <div class="flex items-center gap-2">
                <a class="rounded-full border border-zinc-200 px-4 py-2 text-xs font-semibold text-zinc-700 hover:bg-zinc-50 dark:border-zinc-800 dark:text-zinc-200 dark:hover:bg-zinc-800"
                   href="{{ branch_route('branch-dashboard.production.reporting.index', ['b_id' => $b_id]) }}">
                    Back
                </a>
                <a class="rounded-full bg-zinc-900 px-4 py-2 text-xs font-semibold text-white hover:bg-zinc-800"
                   href="{{ branch_route('branch-dashboard.prints.department-report', ['reportId' => $report?->id, 'b_id' => $b_id]) }}" target="_blank">
                    Print Report
                </a>
            </div>
        </div>
    </div>

    @php
        $payload = $report?->report_data ?? [];
        $summary = $report?->summary_metrics ?? ($payload['summary_metrics'] ?? []);
        $tables = $payload['tables'] ?? [];
        $narrative = $payload['narrative'] ?? [];
    @endphp

    <div class="rounded-2xl border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
        <h2 class="text-xl font-semibold text-zinc-900 dark:text-white">Summary</h2>
        <div class="mt-4 grid grid-cols-1 gap-4 md:grid-cols-3">
            @forelse($summary as $label => $value)
                <div class="rounded-xl border border-zinc-200 bg-zinc-50 p-4 dark:border-zinc-800 dark:bg-zinc-800/60">
                    <p class="text-xs uppercase tracking-wide text-zinc-500">{{ \Illuminate\Support\Str::of($label)->replace('_', ' ')->title() }}</p>
                    <p class="mt-2 text-lg font-semibold text-zinc-900 dark:text-white">
                        @if(is_numeric($value))
                            {{ number_format($value, 2) }}
                        @elseif(is_array($value))
                            {{ json_encode($value) }}
                        @else
                            {{ $value }}
                        @endif
                    </p>
                </div>
            @empty
                <p class="text-sm text-zinc-500">No summary metrics available.</p>
            @endforelse
        </div>
    </div>

    @if(!empty($narrative))
        <div class="rounded-2xl border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
            <h2 class="text-xl font-semibold text-zinc-900 dark:text-white">Narrative</h2>
            <div class="mt-4 space-y-3 text-sm text-zinc-600 dark:text-zinc-300">
                @if(!empty($narrative['overview']))
                    <div>
                        <p class="font-semibold text-zinc-900 dark:text-white">Overview</p>
                        <p>{{ $narrative['overview'] }}</p>
                    </div>
                @endif
                @foreach(['highlights' => 'Highlights', 'concerns' => 'Concerns', 'recommendations' => 'Recommendations'] as $key => $title)
                    @if(!empty($narrative[$key]))
                        <div>
                            <p class="font-semibold text-zinc-900 dark:text-white">{{ $title }}</p>
                            <p>{{ is_array($narrative[$key]) ? implode('; ', $narrative[$key]) : $narrative[$key] }}</p>
                        </div>
                    @endif
                @endforeach
            </div>
        </div>
    @endif

    @foreach($tables as $tableName => $table)
        <div class="rounded-2xl border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
            <h2 class="text-xl font-semibold text-zinc-900 dark:text-white">{{ \Illuminate\Support\Str::of($tableName)->replace('_', ' ')->title() }}</h2>
            <div class="mt-4 overflow-x-auto">
                <table class="min-w-full divide-y divide-zinc-200 text-sm dark:divide-zinc-800">
                    @if(!empty($table['headers']))
                        <thead class="bg-zinc-50 dark:bg-zinc-800/60">
                            <tr>
                                @foreach($table['headers'] as $header)
                                    <th class="px-3 py-2 text-left text-xs font-semibold uppercase tracking-wide text-zinc-500">{{ $header }}</th>
                                @endforeach
                            </tr>
                        </thead>
                    @endif
                    <tbody class="divide-y divide-zinc-200 dark:divide-zinc-800">
                        @if(!empty($table['rows']))
                            @foreach($table['rows'] as $row)
                                <tr>
                                    @foreach($row as $cell)
                                        <td class="px-3 py-2 text-zinc-700 dark:text-zinc-200">
                                            @if(is_numeric($cell))
                                                {{ number_format($cell, 2) }}
                                            @elseif(is_array($cell))
                                                {{ json_encode($cell) }}
                                            @else
                                                {{ $cell }}
                                            @endif
                                        </td>
                                    @endforeach
                                </tr>
                            @endforeach
                        @else
                            <tr>
                                <td class="px-3 py-4 text-zinc-500" colspan="{{ count($table['headers'] ?? []) }}">No rows available.</td>
                            </tr>
                        @endif
                    </tbody>
                </table>
            </div>
        </div>
    @endforeach
</div>
