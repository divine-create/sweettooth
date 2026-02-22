@if($forCsv ?? false)
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

<table>
    <tr>
        <th colspan="2">Report Overview</th>
    </tr>
    <tr>
        <td>Report Name</td>
        <td>{{ $report->report_name }}</td>
    </tr>
    <tr>
        <td>Category</td>
        <td>{{ $report->report_category }}</td>
    </tr>
    <tr>
        <td>Type</td>
        <td>{{ $report->report_type }}</td>
    </tr>
    <tr>
        <td>Period</td>
        <td>{{ $periodInfo['from'] ?? '-' }} to {{ $periodInfo['to'] ?? '-' }}</td>
    </tr>
    <tr>
        <td>Generated On</td>
        <td>{{ optional($report->report_date)->format('Y-m-d') ?? '-' }}</td>
    </tr>
</table>

<table>
    <tr>
        <th colspan="2">Summary Metrics</th>
    </tr>
    @forelse($summary as $label => $value)
        <tr>
            <td>{{ \Illuminate\Support\Str::of($label)->replace('_', ' ')->title() }}</td>
            <td>{{ is_numeric($value) ? number_format($value, 2) : (is_array($value) ? json_encode($value) : $value) }}</td>
        </tr>
    @empty
        <tr>
            <td colspan="2">No summary metrics available.</td>
        </tr>
    @endforelse
</table>

@if(!empty($narrative))
    <table>
        <tr>
            <th colspan="2">Narrative</th>
        </tr>
        @if(!empty($narrative['overview']))
            <tr>
                <td>Overview</td>
                <td>{{ $narrative['overview'] }}</td>
            </tr>
        @endif
        @foreach(['highlights' => 'Highlights', 'concerns' => 'Concerns', 'recommendations' => 'Recommendations'] as $key => $title)
            @if(!empty($narrative[$key]))
                <tr>
                    <td>{{ $title }}</td>
                    <td>{{ is_array($narrative[$key]) ? implode('; ', $narrative[$key]) : $narrative[$key] }}</td>
                </tr>
            @endif
        @endforeach
    </table>
@endif

@foreach($tables as $tableName => $table)
    <table>
        <tr>
            <th colspan="{{ count($table['headers'] ?? []) }}">{{ \Illuminate\Support\Str::of($tableName)->replace('_', ' ')->title() }}</th>
        </tr>
        @if(!empty($table['headers']))
            <tr>
                @foreach($table['headers'] as $header)
                    <th>{{ $header }}</th>
                @endforeach
            </tr>
        @endif
        @if(!empty($table['rows']))
            @foreach($table['rows'] as $row)
                <tr>
                    @foreach($row as $cell)
                        <td>{{ is_numeric($cell) ? number_format($cell, 2) : (is_array($cell) ? json_encode($cell) : $cell) }}</td>
                    @endforeach
                </tr>
            @endforeach
        @else
            <tr>
                <td colspan="{{ count($table['headers'] ?? []) }}">No rows available.</td>
            </tr>
        @endif
    </table>
@endforeach
@endif
