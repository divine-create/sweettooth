@if($forCsv ?? false)
    <table>
        <thead>
            <tr>
                <th>Order ID</th>
                <th>Order Number</th>
                <th>Product/Item</th>
                <th>Quantity</th>
                <th>Unit</th>
                <th>Department</th>
                <th>Start Date</th>
                <th>Due Date</th>
                <th>Progress %</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            @foreach($data as $order)
                <tr>
                    <td>{{ $order->id }}</td>
                    <td>{{ $order->order_number ?? '-' }}</td>
                    <td>{{ $order->name ?? $order->product?->name ?? '-' }}</td>
                    <td>{{ number_format($order->quantity ?? 0, 2) }}</td>
                    <td>{{ $order->unit ?? '-' }}</td>
                    <td>{{ $order->department?->name ?? '-' }}</td>
                    <td>{{ $order->start_date?->format('Y-m-d') ?? '-' }}</td>
                    <td>{{ $order->due_date?->format('Y-m-d') ?? '-' }}</td>
                    <td>{{ $order->progress ?? 0 }}</td>
                    <td>{{ ucfirst($order->status ?? '-') }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
@endif