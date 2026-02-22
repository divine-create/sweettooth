@if($forCsv ?? false)
<table>
    <thead>
        <tr>
            <th>Item Name</th>
            <th>SKU</th>
            <th>Category</th>
            <th>Available</th>
            <th>Reserved</th>
            <th>Damaged</th>
            <th>Reorder Level</th>
            <th>Health Status</th>
        </tr>
    </thead>
    <tbody>
        @forelse($data as $item)
            <tr>
                <td>{{ $item->item?->name ?? $item['name'] ?? 'N/A' }}</td>
                <td>{{ $item->item?->sku ?? $item['sku'] ?? 'N/A' }}</td>
                <td>{{ str_replace('_', ' ', ucfirst($item->item?->category ?? $item['category'] ?? 'N/A')) }}</td>
                <td>{{ number_format($item->quantity_available ?? $item['available'] ?? 0, 2) }}</td>
                <td>{{ number_format($item->quantity_reserved ?? $item['reserved'] ?? 0, 2) }}</td>
                <td>{{ number_format($item->quantity_damaged ?? $item['damaged'] ?? 0, 2) }}</td>
                <td>{{ $item->item?->reorder_level ? number_format($item->item->reorder_level, 2) : 'Not set' }}</td>
                <td>{{ ucfirst($item->health_status ?? $item['status'] ?? 'Unknown') }}</td>
            </tr>
        @empty
            <tr>
                <td colspan="8">No stock items found</td>
            </tr>
        @endforelse
    </tbody>
</table>
@endif
