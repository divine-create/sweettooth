@if($forCsv ?? false)
<table>
    <thead>
        <tr>
            <th>Type</th>
            <th>Category</th>
            <th>Message</th>
            <th>Item</th>
            <th>SKU</th>
            <th>Current Level</th>
            <th>Reorder Level</th>
            <th>Recommended Action</th>
        </tr>
    </thead>
    <tbody>
        @forelse($data as $alert)
            <tr>
                <td>{{ ucfirst($alert['type'] ?? $alert->type ?? 'Info') }}</td>
                <td>{{ ucfirst(str_replace('_', ' ', $alert['category'] ?? $alert->category ?? 'N/A')) }}</td>
                <td>{{ $alert['message'] ?? $alert->message ?? 'N/A' }}</td>
                <td>{{ $alert['item'] ?? $alert->item ?? '' }}</td>
                <td>{{ $alert['sku'] ?? $alert->sku ?? '' }}</td>
                <td>
                    @if(isset($alert['current']))
                        {{ number_format($alert['current'], 2) }}
                    @elseif(isset($alert['damaged_qty']))
                        {{ number_format($alert['damaged_qty'], 2) }}
                    @endif
                </td>
                <td>
                    @if(isset($alert['reorder_level']))
                        {{ number_format($alert['reorder_level'], 2) }}
                    @elseif(isset($alert['days_left']))
                        {{ $alert['days_left'] }} days
                    @endif
                </td>
                <td>{{ $alert['action'] ?? $alert->action ?? 'N/A' }}</td>
            </tr>
        @empty
            <tr>
                <td colspan="8">No alerts found</td>
            </tr>
        @endforelse
    </tbody>
</table>
@endif
