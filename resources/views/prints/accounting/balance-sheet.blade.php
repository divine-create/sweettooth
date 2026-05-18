<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Balance Sheet</title>
    <style>
        * { box-sizing: border-box; }
        body { font-family: Arial, sans-serif; font-size: 11px; color: #111; margin: 24px; }
        h1 { font-size: 18px; margin: 0 0 4px; }
        h2 { font-size: 12px; margin: 16px 0 4px; color: #374151; text-transform: uppercase; letter-spacing: 0.05em; }
        .meta { color: #666; font-size: 10px; margin-bottom: 16px; }
        table { width: 100%; border-collapse: collapse; }
        th { background: #f3f4f6; text-align: left; padding: 5px 8px; border-bottom: 2px solid #d1d5db; font-size: 10px; text-transform: uppercase; }
        th.right, td.right { text-align: right; }
        td { padding: 4px 8px; border-bottom: 1px solid #f3f4f6; }
        .subtotal td { font-weight: 600; border-top: 1px solid #d1d5db; background: #f9fafb; }
        .grand td { font-weight: bold; border-top: 2px solid #374151; background: #f3f4f6; }
        .badge { display: inline-block; padding: 2px 6px; border-radius: 4px; font-size: 9px; font-weight: bold; }
        .badge-ok { background: #d1fae5; color: #065f46; }
        .badge-err { background: #fee2e2; color: #991b1b; }
        .footer { margin-top: 24px; font-size: 9px; color: #9ca3af; }
        .section { margin-bottom: 8px; }
    </style>
</head>
<body>
    <h1>Balance Sheet</h1>
    <div class="meta">
        @if($period)
            As at: {{ $period->period_end->format('M d, Y') }}
            &nbsp;|&nbsp; Period: {{ $period->name ?? "{$period->month}/{$period->year}" }}
        @else
            All periods
        @endif
        &nbsp;|&nbsp; Generated: {{ now()->format('Y-m-d H:i') }}
        &nbsp;|&nbsp;
        @if($data['is_balanced'])
            <span class="badge badge-ok">BALANCED</span>
        @else
            <span class="badge badge-err">UNBALANCED (Diff: {{ number_format(abs($data['difference']), 2) }})</span>
        @endif
    </div>

    <div class="section">
        <h2>Assets</h2>
        <table>
            <thead><tr><th>Account</th><th class="right">Amount</th></tr></thead>
            <tbody>
                @foreach($data['assets'] as $row)
                    <tr><td>{{ $row['account_name'] }}</td><td class="right">{{ number_format($row['balance'], 2) }}</td></tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr class="subtotal"><td>Total Assets</td><td class="right">{{ number_format($data['total_assets'], 2) }}</td></tr>
            </tfoot>
        </table>
    </div>

    <div class="section">
        <h2>Liabilities</h2>
        <table>
            <thead><tr><th>Account</th><th class="right">Amount</th></tr></thead>
            <tbody>
                @foreach($data['liabilities'] as $row)
                    <tr><td>{{ $row['account_name'] }}</td><td class="right">{{ number_format($row['balance'], 2) }}</td></tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr class="subtotal"><td>Total Liabilities</td><td class="right">{{ number_format($data['total_liabilities'], 2) }}</td></tr>
            </tfoot>
        </table>
    </div>

    <div class="section">
        <h2>Equity</h2>
        <table>
            <thead><tr><th>Account</th><th class="right">Amount</th></tr></thead>
            <tbody>
                @foreach($data['equity'] as $row)
                    <tr><td>{{ $row['account_name'] }}</td><td class="right">{{ number_format($row['balance'], 2) }}</td></tr>
                @endforeach
                <tr><td>Retained Earnings</td><td class="right">{{ number_format($data['retained_earnings'], 2) }}</td></tr>
            </tbody>
            <tfoot>
                <tr class="subtotal"><td>Total Equity</td><td class="right">{{ number_format($data['total_equity_with_re'], 2) }}</td></tr>
            </tfoot>
        </table>
    </div>

    <table style="margin-top:4px;">
        <tr class="grand">
            <td>Total Liabilities + Equity</td>
            <td class="right">{{ number_format($data['total_liabilities_equity'], 2) }}</td>
        </tr>
    </table>

    <div class="footer">SweetTooth &mdash; Confidential &mdash; {{ now()->format('Y') }}</div>
</body>
</html>
