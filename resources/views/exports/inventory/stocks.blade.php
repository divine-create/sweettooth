@if($forCsv ?? false)
<table>
    <thead>
        <tr style="background-color: #3498db; color: white; font-weight: bold;">
            <th colspan="12" style="text-align: center; padding: 15px; font-size: 14pt;">Inventory Stock Report</th>
        </tr>
        <tr style="background-color: #2C3E50; color: white;">
            <th colspan="12" style="padding: 10px;">Generated: {{ now()->format('d/m/Y H:i:s') }} | Total Records: {{ count($data) }}</th>
        </tr>
    </thead>
</table>

<table>
    <thead>
        <tr style="background-color: #2C3E50; color: white; font-weight: bold;">
            <th style="text-align: center;">SKU</th>
            <th>Item Name</th>
            <th>Category</th>
            <th style="text-align: right;">Available</th>
            <th style="text-align: right;">Reserved</th>
            <th style="text-align: right;">Damaged</th>
            <th style="text-align: right;">Total</th>
            <th style="text-align: center;">UOM</th>
            <th style="text-align: right;">Avg Cost</th>
            <th style="text-align: right;">Total Value</th>
            <th style="text-align: center;">Reorder Level</th>
            <th style="text-align: center;">Status</th>
        </tr>
    </thead>
    <tbody>
        @foreach($data as $index => $stock)
        <tr style="{{ $index % 2 == 0 ? '' : 'background-color: #f9f9f9;' }}">
            <td style="text-align: center; font-weight: bold;">{{ $stock['sku'] ?? 'N/A' }}</td>
            <td>{{ $stock['item_name'] ?? 'N/A' }}</td>
            <td>{{ $stock['category'] ?? 'N/A' }}</td>
            <td style="text-align: right;">{{ number_format($stock['quantity_available'] ?? 0, 2) }}</td>
            <td style="text-align: right;">{{ number_format($stock['quantity_reserved'] ?? 0, 2) }}</td>
            <td style="text-align: right;">{{ number_format($stock['quantity_damaged'] ?? 0, 2) }}</td>
            <td style="text-align: right; font-weight: bold;">{{ number_format($stock['quantity_total'] ?? 0, 2) }}</td>
            <td style="text-align: center;">{{ $stock['uom'] ?? 'units' }}</td>
            <td style="text-align: right;">{{ number_format($stock['average_cost'] ?? 0, 2) }}</td>
            <td style="text-align: right; font-weight: bold;">{{ number_format($stock['total_value'] ?? 0, 2) }}</td>
            <td style="text-align: right;">{{ number_format($stock['reorder_level'] ?? 0, 2) }}</td>
            <td style="text-align: center; {{ strtolower($stock['health_status'] ?? 'good') == 'critical' ? 'color: red; font-weight: bold;' : '' }}">{{ $stock['health_status'] ?? 'Good' }}</td>
        </tr>
        @endforeach
    </tbody>
</table>

<table><tr><td></td></tr></table>

<table>
    <tr style="background-color: #3498db; color: white; font-weight: bold;">
        <td colspan="2" style="padding: 10px;">SUMMARY</td>
    </tr>
    <tr style="background-color: #f9f9f9;">
        <td style="font-weight: bold; padding: 8px;">Total Stock Value</td>
        <td style="text-align: right; padding: 8px;">{{ number_format($data->sum(fn($s) => $s['total_value'] ?? 0), 2) }}</td>
    </tr>
    <tr>
        <td style="font-weight: bold; padding: 8px;">Total Items</td>
        <td style="text-align: right; padding: 8px;">{{ count($data) }}</td>
    </tr>
</table>
@endif