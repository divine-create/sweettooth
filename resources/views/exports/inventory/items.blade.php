@if($forCsv ?? false)
<table>
    <thead>
        <tr style="background-color: #2C3E50; color: white; font-weight: bold;">
            <th colspan="6" style="text-align: center; padding: 15px; font-size: 14pt;">Inventory Items Report</th>
        </tr>
        <tr style="background-color: #34495e; color: white;">
            <th colspan="6" style="padding: 10px;">Generated: {{ now()->format('d/m/Y H:i:s') }}</th>
        </tr>
    </thead>
</table>

<table>
    <thead>
        <tr style="background-color: #2C3E50; color: white;">
            <th style="width: 15%;">SKU</th>
            <th style="width: 30%;">Name</th>
            <th style="width: 20%;">Category</th>
            <th style="width: 12%;">Reorder Level</th>
            <th style="width: 13%;">Status</th>
            <th style="width: 10%;">UOM</th>
        </tr>
    </thead>
    <tbody>
        @foreach($data as $index => $item)
        <tr style="{{ $index % 2 == 0 ? '' : 'background-color: #f9f9f9;' }}">
            <td>{{ $item['sku'] ?? 'N/A' }}</td>
            <td style="font-weight: bold;">{{ $item['name'] ?? 'N/A' }}</td>
            <td>{{ $item['category'] ?? 'N/A' }}</td>
            <td style="text-align: right;">{{ $item['reorder_level'] ?? 0 }}</td>
            <td>{{ ucfirst($item['status'] ?? 'N/A') }}</td>
            <td>{{ $item['uom'] ?? 'units' }}</td>
        </tr>
        @endforeach
    </tbody>
</table>
@endif