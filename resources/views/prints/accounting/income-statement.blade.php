<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Income Statement</title>
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
        .positive { color: #065f46; }
        .negative { color: #991b1b; }
        .footer { margin-top: 24px; font-size: 9px; color: #9ca3af; }
        .section { margin-bottom: 8px; }
    </style>
</head>
<body>
    <h1>Income Statement</h1>
    <div class="meta">
        @if($period)
            Period: {{ $period->name ?? "{$period->month}/{$period->year}" }}
            &nbsp;|&nbsp; {{ $period->period_start->format('M d, Y') }} – {{ $period->period_end->format('M d, Y') }}
        @else
            All periods
        @endif
        &nbsp;|&nbsp; Generated: {{ now()->format('Y-m-d H:i') }}
    </div>

    <div class="section">
        <h2>Revenue</h2>
        <table>
            <thead><tr><th>Account</th><th class="right">Amount</th></tr></thead>
            <tbody>
                @foreach($data['revenues'] as $row)
                    <tr><td>{{ $row['account_name'] }}</td><td class="right">{{ number_format($row['balance'], 2) }}</td></tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr class="subtotal"><td>Total Revenue</td><td class="right">{{ number_format($data['total_revenue'], 2) }}</td></tr>
            </tfoot>
        </table>
    </div>

    <div class="section">
        <h2>Cost of Goods Sold</h2>
        <table>
            <thead><tr><th>Account</th><th class="right">Amount</th></tr></thead>
            <tbody>
                @foreach($data['cogs'] as $row)
                    <tr><td>{{ $row['account_name'] }}</td><td class="right">{{ number_format($row['balance'], 2) }}</td></tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr class="subtotal"><td>Total COGS</td><td class="right">{{ number_format($data['total_cogs'], 2) }}</td></tr>
            </tfoot>
        </table>
    </div>

    <table style="margin-top:4px;">
        <tr class="subtotal">
            <td><strong>Gross Profit</strong></td>
            <td class="right {{ $data['gross_profit'] >= 0 ? 'positive' : 'negative' }}">
                <strong>{{ number_format($data['gross_profit'], 2) }}</strong>
            </td>
        </tr>
    </table>

    @if(!empty($data['opex']))
    <div class="section" style="margin-top:8px;">
        <h2>Operating Expenses</h2>
        <table>
            <tbody>
                @foreach($data['opex'] as $row)
                    <tr><td>{{ $row['account_name'] }}</td><td class="right">{{ number_format($row['balance'], 2) }}</td></tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr class="subtotal"><td>Total OpEx</td><td class="right">{{ number_format($data['total_opex'], 2) }}</td></tr>
            </tfoot>
        </table>
    </div>
    @endif

    <table style="margin-top:8px;">
        <tr class="grand">
            <td>Net Income</td>
            <td class="right {{ $data['net_income'] >= 0 ? 'positive' : 'negative' }}">
                {{ number_format($data['net_income'], 2) }}
            </td>
        </tr>
    </table>

    <div class="footer">SweetTooth &mdash; Confidential &mdash; {{ now()->format('Y') }}</div>
</body>
</html>
