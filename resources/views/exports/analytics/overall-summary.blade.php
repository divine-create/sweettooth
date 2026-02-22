@if($forCsv ?? false)
<table>
    <thead>
        <tr style="background-color: #2C3E50; color: white; font-weight: bold;">
            <th colspan="6" style="text-align: center; padding: 15px; font-size: 16pt;">Inventory Analytics Report</th>
        </tr>
        <tr style="background-color: #34495e; color: white;">
            <th colspan="3">Period: {{ $data['period']['from'] ?? 'N/A' }} to {{ $data['period']['to'] ?? 'N/A' }}</th>
            <th colspan="3">Generated: {{ now()->format('d/m/Y H:i:s') }}</th>
        </tr>
    </thead>
</table>

<!-- Summary Section -->
<table>
    <thead>
        <tr style="background-color: #3498db; color: white; font-weight: bold;">
            <th colspan="4" style="padding: 10px;">SUMMARY METRICS</th>
        </tr>
        <tr style="background-color: #2C3E50; color: white;">
            <th>Metric</th>
            <th>Value</th>
            <th>Metric</th>
            <th>Value</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td style="font-weight: bold;">Total Stock Value</td>
            <td>{{ number_format($data['summary']['total_stock_value'] ?? 0, 2) }}</td>
            <td style="font-weight: bold;">Total Items</td>
            <td>{{ $data['summary']['total_items'] ?? 0 }}</td>
        </tr>
        <tr style="background-color: #f9f9f9;">
            <td style="font-weight: bold;">Total Purchases</td>
            <td>{{ $data['summary']['total_purchases'] ?? 0 }}</td>
            <td style="font-weight: bold;">Purchase Value</td>
            <td>{{ number_format($data['summary']['total_purchase_value'] ?? 0, 2) }}</td>
        </tr>
        <tr>
            <td style="font-weight: bold;">Stock In</td>
            <td style="color: green;">{{ number_format($data['summary']['stock_in'] ?? 0, 2) }}</td>
            <td style="font-weight: bold;">Stock Out</td>
            <td style="color: red;">{{ number_format($data['summary']['stock_out'] ?? 0, 2) }}</td>
        </tr>
        <tr style="background-color: #f9f9f9;">
            <td style="font-weight: bold;">Total Movements</td>
            <td>{{ $data['summary']['total_movements'] ?? 0 }}</td>
            <td style="font-weight: bold;">Total Requests</td>
            <td>{{ $data['summary']['total_requests'] ?? 0 }}</td>
        </tr>
        <tr>
            <td style="font-weight: bold;">Pending Requests</td>
            <td style="color: orange;">{{ $data['summary']['pending_requests'] ?? 0 }}</td>
            <td style="font-weight: bold;">Completed Requests</td>
            <td style="color: green;">{{ $data['summary']['completed_requests'] ?? 0 }}</td>
        </tr>
        <tr style="background-color: #f9f9f9;">
            <td style="font-weight: bold;">Low Stock Items</td>
            <td style="color: red; font-weight: bold;">{{ $data['summary']['low_stock_items'] ?? 0 }}</td>
            <td style="font-weight: bold;">Critical Items</td>
            <td style="color: red; font-weight: bold;">{{ $data['summary']['critical_items'] ?? 0 }}</td>
        </tr>
        <tr>
            <td style="font-weight: bold;">Expired Items</td>
            <td style="color: gray;">{{ $data['summary']['expired_items'] ?? 0 }}</td>
            <td style="font-weight: bold;">Value Change %</td>
            <td>{{ number_format($data['summary']['stock_value_change_percentage'] ?? 0, 2) }}%</td>
        </tr>
    </tbody>
</table>

<table><tr><td></td></tr></table>

<!-- Stock Health Overview Section -->
@if(!empty($data['health_overview']))
<table>
    <thead>
        <tr style="background-color: #3498db; color: white; font-weight: bold;">
            <th colspan="2" style="padding: 10px;">STOCK HEALTH OVERVIEW</th>
        </tr>
        <tr style="background-color: #2C3E50; color: white;">
            <th style="width: 50%;">Status</th>
            <th style="width: 50%;">Count</th>
        </tr>
    </thead>
    <tbody>
        @foreach($data['health_overview']['labels'] as $index => $label)
        <tr style="{{ $index % 2 == 0 ? '' : 'background-color: #f9f9f9;' }}">
            <td style="font-weight: bold;">{{ $label }}</td>
            <td style="text-align: center;">{{ $data['health_overview']['series'][$index] ?? 0 }}</td>
        </tr>
        @endforeach
    </tbody>
</table>
@endif