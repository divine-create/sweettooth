<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Trial Balance</title>
    <style>
        * { box-sizing: border-box; }
        body { font-family: Arial, sans-serif; font-size: 11px; color: #111; margin: 24px; }
        h1 { font-size: 18px; margin: 0 0 4px; }
        .meta { color: #666; font-size: 10px; margin-bottom: 16px; }
        table { width: 100%; border-collapse: collapse; margin-top: 8px; }
        th { background: #f3f4f6; text-align: left; padding: 6px 8px; border-bottom: 2px solid #d1d5db; font-size: 10px; text-transform: uppercase; }
        th.right, td.right { text-align: right; }
        td { padding: 5px 8px; border-bottom: 1px solid #e5e7eb; }
        tr:nth-child(even) td { background: #fafafa; }
        .totals td { font-weight: bold; border-top: 2px solid #374151; background: #f3f4f6; }
        .badge { display: inline-block; padding: 2px 6px; border-radius: 4px; font-size: 9px; font-weight: bold; }
        .badge-ok { background: #d1fae5; color: #065f46; }
        .badge-err { background: #fee2e2; color: #991b1b; }
        .footer { margin-top: 24px; font-size: 9px; color: #9ca3af; }
    </style>
</head>
<body>
    <h1>Trial Balance</h1>
    <div class="meta">
        @if($period)
            Period: {{ $period->name ?? "{$period->month}/{$period->year}" }}
            &nbsp;|&nbsp; {{ $period->period_start->format('M d, Y') }} – {{ $period->period_end->format('M d, Y') }}
            &nbsp;|&nbsp; Status: {{ ucfirst($period->status) }}
        @else
            All periods (no filter)
        @endif
        &nbsp;|&nbsp; Generated: {{ now()->format('Y-m-d H:i') }}
        &nbsp;|&nbsp;
        @if($data['balanced'])
            <span class="badge badge-ok">BALANCED</span>
        @else
            <span class="badge badge-err">UNBALANCED (Diff: {{ number_format(abs($data['difference']), 2) }})</span>
        @endif
    </div>

    <table>
        <thead>
            <tr>
                <th>Account</th>
                <th>Type</th>
                <th class="right">Debit</th>
                <th class="right">Credit</th>
            </tr>
        </thead>
        <tbody>
            @forelse($data['accounts'] as $account)
                <tr>
                    <td>{{ $account['account_number'] ?? '' }} {{ $account['account_name'] }}</td>
                    <td>{{ ucfirst($account['account_type'] ?? '') }}</td>
                    <td class="right">{{ $account['debit'] > 0 ? number_format($account['debit'], 2) : '—' }}</td>
                    <td class="right">{{ $account['credit'] > 0 ? number_format($account['credit'], 2) : '—' }}</td>
                </tr>
            @empty
                <tr><td colspan="4" style="text-align:center;color:#9ca3af;padding:16px;">No entries found.</td></tr>
            @endforelse
        </tbody>
        <tfoot>
            <tr class="totals">
                <td colspan="2">Total</td>
                <td class="right">{{ number_format($data['total_debits'], 2) }}</td>
                <td class="right">{{ number_format($data['total_credits'], 2) }}</td>
            </tr>
        </tfoot>
    </table>

    <div class="footer">SweetTooth &mdash; Confidential &mdash; {{ now()->format('Y') }}</div>
</body>
</html>
