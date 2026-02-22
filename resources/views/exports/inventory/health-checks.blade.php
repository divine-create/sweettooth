@if($forCsv ?? false)
<table>
    <thead>
        <tr>
            <th>Item Name</th>
            <th>SKU</th>
            <th>Check Date</th>
            <th>Condition</th>
            <th>Qty Affected</th>
            <th>UOM</th>
            <th>Observations</th>
            <th>Action Taken</th>
            <th>Checked By</th>
        </tr>
    </thead>
    <tbody>
        @forelse($data as $check)
            <tr>
                <td>{{ $check->stock?->item?->name ?? $check['name'] ?? 'N/A' }}</td>
                <td>{{ $check->stock?->item?->sku ?? $check['sku'] ?? 'N/A' }}</td>
                <td>{{ $check->check_date ? $check->check_date->format('Y-m-d') : ($check['check_date'] ?? 'N/A') }}</td>
                <td>{{ ucfirst($check->condition ?? $check['condition'] ?? 'Unknown') }}</td>
                <td>{{ $check->quantity_affected ? number_format($check->quantity_affected, 2) : ($check['quantity_affected'] ?? '0') }}</td>
                <td>{{ $check->stock?->item?->unitOfMeasure?->symbol ?? ($check['uom'] ?? 'units') }}</td>
                <td>{{ $check->observations ?? $check['observations'] ?? 'N/A' }}</td>
                <td>{{ $check->action_taken ?? $check['action_taken'] ?? 'N/A' }}</td>
                <td>{{ $check->checker?->name ?? $check['checker'] ?? 'N/A' }}</td>
            </tr>
        @empty
            <tr>
                <td colspan="9">No health checks found</td>
            </tr>
        @endforelse
    </tbody>
</table>
@endif
