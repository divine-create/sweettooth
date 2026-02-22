@if($forCsv ?? false)
    <table>
        <thead>
            <tr>
                <th>Sale ID</th>
                <th>Sale Number</th>
                <th>Date & Time</th>
                <th>Customer</th>
                <th>Items Count</th>
                <th>Subtotal</th>
                <th>Tax</th>
                <th>Discount</th>
                <th>Total</th>
                <th>Payment Method</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            @foreach($data as $sale)
                <tr>
                    <td>{{ $sale->id }}</td>
                    <td>{{ $sale->sale_number ?? '-' }}</td>
                    <td>{{ $sale->sale_time?->format('Y-m-d H:i') ?? '-' }}</td>
                    <td>{{ $sale->customer_name ?? '-' }}</td>
                    <td>{{ $sale->saleItems?->count() ?? 0 }}</td>
                    <td>{{ number_format($sale->subtotal ?? 0, 2) }}</td>
                    <td>{{ number_format($sale->tax ?? 0, 2) }}</td>
                    <td>{{ number_format($sale->discount ?? 0, 2) }}</td>
                    <td>{{ number_format($sale->total ?? 0, 2) }}</td>
                    <td>{{ $sale->payments?->first()?->payment_method ?? '-' }}</td>
                    <td>{{ ucfirst($sale->status ?? '-') }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
@endif