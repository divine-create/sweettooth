@if($forCsv ?? false)
    <table>
        <thead>
            <tr>
                <th>Entry ID</th>
                <th>Date</th>
                <th>Account</th>
                <th>Account Code</th>
                <th>Description</th>
                <th>Debit</th>
                <th>Credit</th>
                <th>Reference</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            @foreach($data as $entry)
                <tr>
                    <td>{{ $entry->id }}</td>
                    <td>{{ $entry->entry_date?->format('Y-m-d') ?? '-' }}</td>
                    <td>{{ $entry->glAccount?->account_name ?? '-' }}</td>
                    <td>{{ $entry->glAccount?->account_code ?? '-' }}</td>
                    <td>{{ $entry->description ?? '-' }}</td>
                    <td>{{ number_format($entry->debit ?? 0, 2) }}</td>
                    <td>{{ number_format($entry->credit ?? 0, 2) }}</td>
                    <td>{{ $entry->reference_number ?? '-' }}</td>
                    <td>{{ ucfirst($entry->status ?? '-') }}</td>
                </tr>
            @endforeach
            <tr style="border-top: 2px solid #000; font-weight: bold;">
                <td colspan="5" style="text-align: right;">TOTALS:</td>
                <td>{{ number_format($data->sum('debit'), 2) }}</td>
                <td>{{ number_format($data->sum('credit'), 2) }}</td>
                <td colspan="2"></td>
            </tr>
        </tbody>
    </table>
@endif