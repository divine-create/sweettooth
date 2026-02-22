<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Department Report</title>
    <style>
        * { box-sizing: border-box; }
        body { font-family: Arial, sans-serif; font-size: 12px; color: #111; margin: 24px; }
        h1 { font-size: 20px; margin: 0 0 4px; }
        h2 { font-size: 14px; margin: 16px 0 8px; }
        .muted { color: #666; }
        .grid { display: table; width: 100%; border-collapse: collapse; }
        .row { display: table-row; }
        .cell { display: table-cell; padding: 6px 8px; border-bottom: 1px solid #e5e7eb; vertical-align: top; }
        .cell.head { font-weight: bold; background: #f3f4f6; border-bottom: 1px solid #d1d5db; }
        .right { text-align: right; }
        .section { margin-top: 16px; }
        .summary { display: table; width: 100%; }
        .summary .box { display: table-cell; border: 1px solid #e5e7eb; padding: 10px; }
        .page-break { page-break-before: always; }
    </style>
</head>
<body>
    @php
        $payload = $report->report_data ?? [];
        $summary = $report->summary_metrics ?? ($payload['summary_metrics'] ?? []);
        $tables = $payload['tables'] ?? [];
        $narrative = $payload['narrative'] ?? [];
        $periodInfo = $payload['period_info'] ?? [
            'from' => $report->period_from,
            'to' => $report->period_to,
        ];
    @endphp

    <div>
        <h1>{{ $report->report_name ?? 'Department Report' }}</h1>
        <div class="muted">
            Category: {{ $report->report_category ?? '-' }} |
            Type: {{ $report->report_type ?? '-' }} |
            Period: {{ $periodInfo['from'] ?? '-' }} to {{ $periodInfo['to'] ?? '-' }} |
            Generated: {{ optional($report->report_date)->format('Y-m-d') ?? '-' }}
        </div>
    </div>

    <div class="section">
        <h2>Summary Metrics</h2>
        <div class="grid">
            <div class="row">
                <div class="cell head">Metric</div>
                <div class="cell head right">Value</div>
            </div>
            @forelse($summary as $label => $value)
                <div class="row">
                    <div class="cell">{{ \Illuminate\Support\Str::of($label)->replace('_', ' ')->title() }}</div>
                    <div class="cell right">
                        @if(is_numeric($value))
                            {{ number_format($value, 2) }}
                        @elseif(is_array($value))
                            {{ json_encode($value) }}
                        @else
                            {{ $value }}
                        @endif
                    </div>
                </div>
            @empty
                <div class="row">
                    <div class="cell" colspan="2">No summary metrics available.</div>
                </div>
            @endforelse
        </div>
    </div>

    @if(!empty($narrative))
        <div class="section">
            <h2>Narrative</h2>
            <div class="grid">
                <div class="row">
                    <div class="cell head">Section</div>
                    <div class="cell head">Details</div>
                </div>
                @if(!empty($narrative['overview']))
                    <div class="row">
                        <div class="cell">Overview</div>
                        <div class="cell">{{ $narrative['overview'] }}</div>
                    </div>
                @endif
                @foreach(['highlights' => 'Highlights', 'concerns' => 'Concerns', 'recommendations' => 'Recommendations'] as $key => $title)
                    @if(!empty($narrative[$key]))
                        <div class="row">
                            <div class="cell">{{ $title }}</div>
                            <div class="cell">
                                @if(is_array($narrative[$key]))
                                    {{ implode('; ', $narrative[$key]) }}
                                @else
                                    {{ $narrative[$key] }}
                                @endif
                            </div>
                        </div>
                    @endif
                @endforeach
            </div>
        </div>
    @endif

    @foreach($tables as $tableName => $table)
        <div class="section page-break">
            <h2>{{ \Illuminate\Support\Str::of($tableName)->replace('_', ' ')->title() }}</h2>
            <div class="grid">
                @if(!empty($table['headers']))
                    <div class="row">
                        @foreach($table['headers'] as $header)
                            <div class="cell head">{{ $header }}</div>
                        @endforeach
                    </div>
                @endif
                @if(!empty($table['rows']))
                    @foreach($table['rows'] as $row)
                        <div class="row">
                            @foreach($row as $cell)
                                <div class="cell">
                                    @if(is_numeric($cell))
                                        {{ number_format($cell, 2) }}
                                    @elseif(is_array($cell))
                                        {{ json_encode($cell) }}
                                    @else
                                        {{ $cell }}
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    @endforeach
                @else
                    <div class="row">
                        <div class="cell" colspan="{{ count($table['headers'] ?? []) }}">No rows available.</div>
                    </div>
                @endif
            </div>
        </div>
    @endforeach
</body>
</html>
