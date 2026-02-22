@if($forCsv ?? false)
    <table>
        <thead>
            <tr>
                <th>Stock Take ID</th>
                <th>Date</th>
                <th>Type</th>
                <th>Status</th>
                <th>Conductor</th>
                <th>Verifier</th>
                <th>Items Counted</th>
                <th>Matched</th>
                <th>Surplus</th>
                <th>Shortage</th>
                <th>Notes</th>
                <th>Created Date</th>
            </tr>
        </thead>
        <tbody>
            @foreach($data as $take)
                <tr>
                    <td>{{ $take->stock_take_number ?? '-' }}</td>
                    <td>{{ $take->stock_take_date ? \Carbon\Carbon::parse($take->stock_take_date)->format('Y-m-d') : '-' }}</td>
                    <td>{{ ucfirst($take->type ?? 'full') }}</td>
                    <td>{{ ucfirst($take->status ?? 'pending') }}</td>
                    <td>{{ $take->conductor?->name ?? '-' }}</td>
                    <td>{{ $take->verifier?->name ?? '-' }}</td>
                    <td>{{ $take->stockTakeDetails?->count() ?? 0 }}</td>
                    <td>{{ $take->stockTakeDetails?->where('variance_type', 'match')->count() ?? 0 }}</td>
                    <td>{{ $take->stockTakeDetails?->where('variance_type', 'surplus')->count() ?? 0 }}</td>
                    <td>{{ $take->stockTakeDetails?->where('variance_type', 'shortage')->count() ?? 0 }}</td>
                    <td>{{ $take->notes ?? '-' }}</td>
                    <td>{{ $take->created_at?->format('Y-m-d H:i') ?? '-' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
@endif