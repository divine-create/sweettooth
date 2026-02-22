@if($forCsv ?? false)
    <table>
        <thead>
            <tr>
                <th>Callback ID</th>
                <th>Product Name</th>
                <th>SKU</th>
                <th>Quantity</th>
                <th>UOM</th>
                <th>Reason</th>
                <th>Status</th>
                <th>Callback Date & Time</th>
                <th>Recorded By</th>
                <th>Approved By</th>
                <th>Received By</th>
                <th>Notes</th>
            </tr>
        </thead>
        <tbody>
            @foreach($data as $callback)
                <tr>
                    <td>{{ $callback->id ?? '-' }}</td>
                    <td>{{ $callback->product?->name ?? '-' }}</td>
                    <td>{{ $callback->product?->sku ?? '-' }}</td>
                    <td>{{ $callback->quantity ?? 0 }}</td>
                    <td>{{ $callback->uom ?? 'kg' }}</td>
                    <td>{{ ucfirst(str_replace('_', ' ', $callback->reason ?? '-')) }}</td>
                    <td>{{ ucfirst(str_replace('_', ' ', $callback->status ?? 'pending')) }}</td>
                    <td>{{ $callback->callback_time ? \Carbon\Carbon::parse($callback->callback_time)->format('Y-m-d H:i:s') : '-' }}</td>
                    <td>{{ $callback->recordedBy?->name ?? '-' }}</td>
                    <td>{{ $callback->approvedBy?->name ?? '-' }}</td>
                    <td>{{ $callback->receivedBy?->name ?? '-' }}</td>
                    <td>{{ $callback->notes ?? '-' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
@endif