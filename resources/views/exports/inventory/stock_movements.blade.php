@if($forCsv ?? false)
    <table>
        <thead>
            <tr>
                <th>Movement ID</th>
                <th>Date & Time</th>
                <th>Movement Type</th>
                <th>Stock Item</th>
                <th>Quantity</th>
                <th>Department</th>
                <th>Shift</th>
                <th>Moved By</th>
                <th>Notes</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            @foreach($data as $movement)
                <tr>
                    <td>{{ $movement->id ?? '-' }}</td>
                    <td>{{ $movement->movement_date ? \Carbon\Carbon::parse($movement->movement_date)->format('Y-m-d H:i:s') : '-' }}</td>
                    <td>{{ ucfirst($movement->type ?? '-') }}</td>
                    <td>{{ $movement->stock?->item?->name ?? '-' }}</td>
                    <td>{{ abs($movement->quantity ?? 0) }}</td>
                    <td>{{ $movement->department_name ?? '-' }}</td>
                    <td>{{ ucfirst($movement->shift ?? '-') }}</td>
                    <td>{{ $movement->mover?->name ?? 'System' }}</td>
                    <td>{{ $movement->notes ?? '-' }}</td>
                    <td>{{ $movement->reference ? 'Complete' : 'Pending' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
@endif