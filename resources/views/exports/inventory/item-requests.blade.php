@if($forCsv ?? false)
<table>
    <thead>
        <tr>
            <th>Request #</th>
            <th>Department</th>
            <th>Requester</th>
            <th>Request Date</th>
            <th>Status</th>
            <th>Items</th>
            <th>Total Qty</th>
            <th>Approved</th>
            <th>Dispatched</th>
            <th>Notes</th>
        </tr>
    </thead>
    <tbody>
        @forelse($data as $item)
            <tr>
                <td>{{ $item->request_number ?? $item['request_number'] ?? 'N/A' }}</td>
                <td>{{ $item->department?->name ?? $item['department'] ?? 'N/A' }}</td>
                <td>{{ $item->requester?->name ?? $item['requester'] ?? 'N/A' }}</td>
                <td>{{ isset($item->request_date) ? \Carbon\Carbon::parse($item->request_date)->format('Y-m-d') : ($item['request_date'] ?? 'N/A') }}</td>
                <td>{{ ucfirst(str_replace('_', ' ', $item->status ?? $item['status'] ?? 'pending')) }}</td>
                <td>{{ $item->requestDetails?->count() ?? $item['items_count'] ?? 0 }}</td>
                <td>{{ number_format($item->requestDetails?->sum('quantity_requested') ?? $item['total_quantity'] ?? 0, 2) }}</td>
                <td>{{ number_format($item->requestDetails?->sum('quantity_approved') ?? $item['approved_quantity'] ?? 0, 2) }}</td>
                <td>{{ number_format($item->requestDetails?->sum('quantity_dispatched') ?? $item['dispatched_quantity'] ?? 0, 2) }}</td>
                <td>{{ $item->notes ?? $item['notes'] ?? 'N/A' }}</td>
            </tr>
        @empty
            <tr>
                <td colspan="10">No item requests found</td>
            </tr>
        @endforelse
    </tbody>
</table>
@endif
