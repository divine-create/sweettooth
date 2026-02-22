@if($forCsv ?? false)
<table>
    <thead>
        <tr style="background-color: #27ae60; color: white; font-weight: bold;">
            <th colspan="6" style="text-align: center; padding: 15px; font-size: 16pt;">Inventory Analytics Report</th>
        </tr>
        <tr style="background-color: #1e8449; color: white;">
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
            <th>Metric</th><th>Value</th><th>Metric</th><th>Value</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td style="font-weight: bold;">Total Stock Value</td>
            <td>{{ number_format($data['total_stock_value'] ?? 0, 2) }}</td>
            <td style="font-weight: bold;">Total Items</td>
            <td>{{ $data['total_items'] ?? 0 }}</td>
        </tr>
        <tr style="background-color: #f9f9f9;">
            <td style="font-weight: bold;">Total Purchases</td>
            <td>{{ $data['total_purchases'] ?? 0 }}</td>
            <td style="font-weight: bold;">Purchase Value</td>
            <td>{{ number_format($data['purchase_value'] ?? 0, 2) }}</td>
        </tr>
        <tr>
            <td style="font-weight: bold;">Stock In</td>
            <td style="color: green;">{{ number_format($data['movements_in'] ?? 0, 2) }}</td>
            <td style="font-weight: bold;">Stock Out</td>
            <td style="color: red;">{{ number_format($data['movements_out'] ?? 0, 2) }}</td>
        </tr>
        <tr style="background-color: #f9f9f9;">
            <td style="font-weight: bold;">Low Stock Items</td>
            <td style="color: red; font-weight: bold;">{{ $data['low_stock_items'] ?? 0 }}</td>
            <td style="font-weight: bold;">Critical Items</td>
            <td style="color: red; font-weight: bold;">{{ $data['critical_items'] ?? 0 }}</td>
        </tr>
        <tr>
            <td style="font-weight: bold;">Expired Items</td>
            <td>{{ $data['expired_items'] ?? 0 }}</td>
            <td style="font-weight: bold;">Total Movements</td>
            <td>{{ $data['total_movements'] ?? 0 }}</td>
        </tr>
    </tbody>
</table>

<table><tr><td></td></tr></table>

<!-- Stock Health Section -->
@if(!empty($data['stock_health_data']))
<table>
    <thead>
        <tr style="background-color: #e74c3c; color: white; font-weight: bold;">
            <th colspan="6" style="padding: 10px;">STOCK HEALTH STATUS</th>
        </tr>
        <tr style="background-color: #2C3E50; color: white;">
            <th>Item Name</th><th>Stock Level</th><th>Reorder Level</th><th>Health %</th><th>Status</th><th>Last Movement</th>
        </tr>
    </thead>
    <tbody>
        @foreach($data['stock_health_data'] as $index => $stock)
        <tr style="{{ $index % 2 == 0 ? '' : 'background-color: #f9f9f9;' }}">
            <td style="font-weight: bold;">{{ $stock['item_name'] ?? 'N/A' }}</td>
            <td style="text-align: right;">{{ number_format($stock['stock_level'] ?? 0, 2) }} {{ $stock['uom'] ?? '' }}</td>
            <td style="text-align: right;">{{ number_format($stock['reorder_level'] ?? 0, 2) }}</td>
            <td style="text-align: right;">{{ $stock['health_percentage'] ?? 0 }}%</td>
            <td style="{{ ($stock['status'] ?? '') == 'critical' ? 'color: red; font-weight: bold;' : '' }}">{{ ucfirst($stock['status'] ?? 'Good') }}</td>
            <td>{{ $stock['last_movement'] ?? 'N/A' }}</td>
        </tr>
        @endforeach
    </tbody>
</table>
@endif