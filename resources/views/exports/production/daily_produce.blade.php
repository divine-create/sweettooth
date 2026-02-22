@if($forCsv ?? false)
    <table>
        <thead>
            <tr>
                <th>Production ID</th>
                <th>Date</th>
                <th>Product</th>
                <th>Opening Qty</th>
                <th>Produced</th>
                <th>Approved</th>
                <th>Rejected</th>
                <th>Quality %</th>
                <th>Closing Qty</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            @foreach($data as $record)
                <tr>
                    <td>{{ $record->id }}</td>
                    <td>{{ $record->produce_date?->format('Y-m-d') ?? '-' }}</td>
                    <td>{{ $record->recipe?->product_name ?? '-' }}</td>
                    <td>{{ number_format($record->opening_quantity ?? 0, 2) }}</td>
                    <td>{{ number_format($record->quantity_produced ?? 0, 2) }}</td>
                    <td>{{ number_format($record->quantity_approved ?? 0, 2) }}</td>
                    <td>{{ number_format($record->quantity_rejected ?? 0, 2) }}</td>
                    <td>{{ number_format($record->quality_percentage ?? 0, 2) }}</td>
                    <td>{{ number_format($record->closing_quantity ?? 0, 2) }}</td>
                    <td>{{ ucfirst($record->status ?? '-') }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
@endif