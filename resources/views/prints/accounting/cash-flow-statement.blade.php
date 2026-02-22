<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Cash Flow Statement</title>
    <style>
        * { box-sizing: border-box; }
        body { font-family: Arial, sans-serif; font-size: 12px; color: #111; margin: 24px; }
        h1 { font-size: 20px; margin: 0 0 4px; }
        h2 { font-size: 14px; margin: 16px 0 8px; }
        .muted { color: #666; }
        .grid { display: table; width: 100%; border-collapse: collapse; }
        .row { display: table-row; }
        .cell { display: table-cell; padding: 6px 8px; border-bottom: 1px solid #e5e7eb; }
        .cell.head { font-weight: bold; background: #f3f4f6; border-bottom: 1px solid #d1d5db; }
        .section { margin-top: 16px; }
        .totals { font-weight: bold; }
        .summary { display: table; width: 100%; margin-top: 8px; }
        .summary .box { display: table-cell; border: 1px solid #e5e7eb; padding: 10px; text-align: center; }
        .right { text-align: right; }
        .page-break { page-break-before: always; }
    </style>
</head>
<body>
    <div>
        <h1>Cash Flow Statement</h1>
        <div class="muted">
            Generated: {{ now()->format('Y-m-d H:i') }}
        </div>
    </div>

    <div class="section">
        <h2>Operating Activities</h2>
        <div class="grid">
            <div class="row">
                <div class="cell head">Description</div>
                <div class="cell head right">Amount</div>
            </div>
            @foreach($cfs['operating_activities'] ?? [] as $item => $amount)
                <div class="row">
                    <div class="cell">{{ $item }}</div>
                    <div class="cell right">{{ number_format($amount, 2) }}</div>
                </div>
            @endforeach
            <div class="row totals">
                <div class="cell">Net Cash Flow</div>
                <div class="cell right">{{ number_format($cfs['operating']['total'] ?? 0, 2) }}</div>
            </div>
        </div>
    </div>

    <div class="section">
        <h2>Investing Activities</h2>
        <div class="grid">
            <div class="row">
                <div class="cell head">Description</div>
                <div class="cell head right">Amount</div>
            </div>
            @foreach($cfs['investing_activities'] ?? [] as $item => $amount)
                <div class="row">
                    <div class="cell">{{ $item }}</div>
                    <div class="cell right">{{ number_format($amount, 2) }}</div>
                </div>
            @endforeach
            <div class="row totals">
                <div class="cell">Net Cash Flow</div>
                <div class="cell right">{{ number_format($cfs['investing']['total'] ?? 0, 2) }}</div>
            </div>
        </div>
    </div>

    <div class="section">
        <h2>Financing Activities</h2>
        <div class="grid">
            <div class="row">
                <div class="cell head">Description</div>
                <div class="cell head right">Amount</div>
            </div>
            @foreach($cfs['financing_activities'] ?? [] as $item => $amount)
                <div class="row">
                    <div class="cell">{{ $item }}</div>
                    <div class="cell right">{{ number_format($amount, 2) }}</div>
                </div>
            @endforeach
            <div class="row totals">
                <div class="cell">Net Cash Flow</div>
                <div class="cell right">{{ number_format($cfs['financing']['total'] ?? 0, 2) }}</div>
            </div>
        </div>
    </div>

    <div class="section">
        <h2>Summary</h2>
        <div class="summary">
            <div class="box">
                <div class="muted">Opening Cash Balance</div>
                <div class="totals">{{ number_format($cfs['opening_cash'] ?? 0, 2) }}</div>
            </div>
            <div class="box">
                <div class="muted">Net Cash Change</div>
                <div class="totals">{{ number_format($cfs['net_change'] ?? 0, 2) }}</div>
            </div>
            <div class="box">
                <div class="muted">Closing Cash Balance</div>
                <div class="totals">{{ number_format($cfs['closing_cash'] ?? 0, 2) }}</div>
            </div>
        </div>
    </div>

    <div class="section page-break">
        <h2>Bank Positions</h2>
        <div class="grid">
            <div class="row">
                <div class="cell head">Account</div>
                <div class="cell head right">Opening</div>
                <div class="cell head right">Deposits</div>
                <div class="cell head right">Withdrawals</div>
                <div class="cell head right">Closing</div>
            </div>
            @forelse($bankPositions ?? [] as $position)
                @if(is_array($position) && isset($position['opening_balance']))
                    <div class="row">
                        <div class="cell">{{ $position['bank_account'] ?? 'N/A' }}</div>
                        <div class="cell right">{{ number_format($position['opening_balance'] ?? 0, 2) }}</div>
                        <div class="cell right">{{ number_format($position['total_deposits'] ?? 0, 2) }}</div>
                        <div class="cell right">{{ number_format($position['total_withdrawals'] ?? 0, 2) }}</div>
                        <div class="cell right">{{ number_format($position['closing_balance'] ?? 0, 2) }}</div>
                    </div>
                @endif
            @empty
                <div class="row">
                    <div class="cell" colspan="5">No bank positions available.</div>
                </div>
            @endforelse
        </div>
    </div>

    <div class="section">
        <h2>Cash Positions</h2>
        <div class="grid">
            <div class="row">
                <div class="cell head">Location</div>
                <div class="cell head right">Opening</div>
                <div class="cell head right">In</div>
                <div class="cell head right">Out</div>
                <div class="cell head right">Closing</div>
            </div>
            @forelse($cashPositions ?? [] as $position)
                @if(is_array($position) && isset($position['opening_balance']))
                    <div class="row">
                        <div class="cell">{{ $position['location'] ?? 'N/A' }}</div>
                        <div class="cell right">{{ number_format($position['opening_balance'] ?? 0, 2) }}</div>
                        <div class="cell right">{{ number_format($position['cash_in'] ?? 0, 2) }}</div>
                        <div class="cell right">{{ number_format($position['cash_out'] ?? 0, 2) }}</div>
                        <div class="cell right">{{ number_format($position['closing_balance'] ?? 0, 2) }}</div>
                    </div>
                @endif
            @empty
                <div class="row">
                    <div class="cell" colspan="5">No cash positions available.</div>
                </div>
            @endforelse
        </div>
    </div>
</body>
</html>
