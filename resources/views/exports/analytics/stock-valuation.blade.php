@if($forCsv ?? false)
<table>
    <thead>
        <tr>
            <th>Item Name</th>
            <th>SKU</th>
            <th>Category</th>
            <th>Qty Available</th>
            <th>Qty Reserved</th>
            <th>Avg Cost</th>
            <th>Available Value</th>
            <th>Total Value</th>
        </tr>
    </thead>
    <tbody>
        @forelse($data as $item)
            @php
                $totalValue = ($item->quantity_available + $item->quantity_reserved) * ($item->average_cost ?? 0);
                $availableValue = $item->quantity_available * ($item->average_cost ?? 0);
            @endphp
            <tr>
                <td>{{ $item->item?->name ?? $item['name'] ?? 'N/A' }}</td>
                <td>{{ $item->item?->sku ?? $item['sku'] ?? 'N/A' }}</td>
                <td>{{ str_replace('_', ' ', ucfirst($item->item?->category ?? $item['category'] ?? 'N/A')) }}</td>
                <td>{{ number_format($item->quantity_available ?? $item['available'] ?? 0, 2) }}</td>
                <td>{{ number_format($item->quantity_reserved ?? $item['reserved'] ?? 0, 2) }}</td>
                <td>{{ number_format($item->average_cost ?? 0, 2) }}</td>
                <td>{{ number_format($availableValue, 2) }}</td>
                <td>{{ number_format($totalValue, 2) }}</td>
            </tr>
        @empty
            <tr>
                <td colspan="8">No items found</td>
            </tr>
        @endforelse
    </tbody>
</table>
@endif
