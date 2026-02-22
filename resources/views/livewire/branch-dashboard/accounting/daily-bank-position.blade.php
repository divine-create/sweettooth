<div class="container-fluid p-4">
    <!-- Header -->
    <div class="row mb-4">
        <div class="col-12">
            <h1 class="h3 mb-0">Daily Bank Position</h1>
            <p class="text-muted mb-0">Monitor cash flow by bank account</p>
        </div>
    </div>

    <!-- Filters -->
    <div class="row mb-4">
        <div class="col-md-4">
            <label for="bankAccount" class="form-label">Bank Account</label>
            <select id="bankAccount" wire:model.live="selectedBankAccountId" class="form-select">
                @foreach($bankAccounts as $account)
                    <option value="{{ $account['id'] }}">
                        {{ $account['name'] }} - {{ $account['account_number'] }}
                    </option>
                @endforeach
            </select>
        </div>
        <div class="col-md-4">
            <label for="positionDate" class="form-label">Date</label>
            <input type="date" id="positionDate" wire:model.live="selectedDate" class="form-control">
        </div>
    </div>

    <!-- Summary Cards -->
    @if(!empty($summary))
    <div class="row mb-4">
        <div class="col-md-3 mb-3">
            <div class="card border-left-success">
                <div class="card-body">
                    <h6 class="text-muted">Total Inflows</h6>
                    <h4 class="text-success">{{ $this->formatCurrency($summary['total_inflows']) }}</h4>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="card border-left-danger">
                <div class="card-body">
                    <h6 class="text-muted">Total Outflows</h6>
                    <h4 class="text-danger">{{ $this->formatCurrency($summary['total_outflows']) }}</h4>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="card border-left-info">
                <div class="card-body">
                    <h6 class="text-muted">Net Movement</h6>
                    <h4 class="@if($summary['net_movement'] >= 0) text-success @else text-danger @endif">
                        {{ $this->formatCurrency($summary['net_movement']) }}
                    </h4>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="card">
                <div class="card-body">
                    <h6 class="text-muted">Transactions</h6>
                    <h4>{{ $summary['total_transactions'] }}</h4>
                    <small class="text-success">{{ $summary['reconciled_count'] }} reconciled</small>
                </div>
            </div>
        </div>
    </div>
    @endif

    <!-- Bank Position Details -->
    @if(!empty($positions))
    <div class="row mb-4">
        @foreach($positions as $position)
        <div class="col-12">
            <div class="card">
                <div class="card-header bg-light">
                    <h5 class="mb-0">Bank Position - {{ $position['date'] }}</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <h6 class="text-muted">Opening Balance</h6>
                            <h4>{{ $this->formatCurrency($position['opening_balance']) }}</h4>
                        </div>
                        <div class="col-md-6">
                            <h6 class="text-muted">Closing Balance</h6>
                            <h4>{{ $this->formatCurrency($position['closing_balance']) }}</h4>
                        </div>
                    </div>
                    <hr>
                    <div class="row">
                        <div class="col-md-6">
                            <h6 class="text-muted">Total Inflow</h6>
                            <h5 class="text-success">{{ $this->formatCurrency($position['total_inflow']) }}</h5>
                        </div>
                        <div class="col-md-6">
                            <h6 class="text-muted">Total Outflow</h6>
                            <h5 class="text-danger">{{ $this->formatCurrency($position['total_outflow']) }}</h5>
                        </div>
                    </div>
                    @if($position['reconciled'])
                        <div class="alert alert-success mt-3 mb-0">
                            ✓ Position reconciled
                        </div>
                    @endif
                </div>
            </div>
        </div>
        @endforeach
    </div>
    @endif

    <!-- Daily Transactions -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header bg-light">
                    <h5 class="mb-0">Daily Transactions ({{ count($transactions) }})</h5>
                </div>
                <div class="card-body p-0">
                    @if(count($transactions) > 0)
                        <div class="table-responsive">
                            <table class="table table-striped mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Date/Time</th>
                                        <th>Type</th>
                                        <th>Subtype</th>
                                        <th class="text-end">Amount</th>
                                        <th>Description</th>
                                        <th>Reference</th>
                                        <th>Status</th>
                                        <th>Reconciled</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($transactions as $transaction)
                                    <tr>
                                        <td class="small">{{ $transaction['date'] }}</td>
                                        <td>
                                            <span class="badge @if($transaction['type'] === 'inflow') bg-success @else bg-danger @endif">
                                                {{ ucfirst($transaction['type']) }}
                                            </span>
                                        </td>
                                        <td class="small">{{ ucfirst(str_replace('_', ' ', $transaction['subtype'])) }}</td>
                                        <td class="text-end">
                                             <strong class="@if($transaction['type'] === 'inflow') text-success @else text-danger @endif">
                                                 {{ $this->formatCurrency($transaction['amount']) }}
                                             </strong>
                                         </td>
                                        <td class="small">{{ $transaction['description'] }}</td>
                                        <td class="small text-muted">{{ $transaction['reference'] }}</td>
                                        <td>
                                            <span class="badge 
                                                @if($transaction['status'] === 'cleared') bg-success
                                                @elseif($transaction['status'] === 'pending') bg-warning
                                                @else bg-secondary @endif">
                                                {{ ucfirst($transaction['status']) }}
                                            </span>
                                        </td>
                                        <td>
                                            @if($transaction['reconciled'])
                                                <span class="badge bg-success">✓ Reconciled</span>
                                            @else
                                                <span class="badge bg-secondary">Pending</span>
                                            @endif
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <p class="text-muted p-4 mb-0">No transactions found for this date</p>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Reconciliation Summary -->
    @if(!empty($summary) && $summary['unreconciled_count'] > 0)
    <div class="row mt-4">
        <div class="col-12">
            <div class="alert alert-warning">
                <h6 class="alert-heading">⚠️ Pending Reconciliation</h6>
                <p class="mb-1">
                    <strong>{{ $summary['unreconciled_count'] }}</strong> transaction(s) pending reconciliation 
                    worth <strong>{{ $this->formatCurrency($summary['unreconciled_amount']) }}</strong>
                </p>
                <a href="{{ route('branch-dashboard.accounting.bank-reconciliation', ['b_id' => request('b_id')]) }}" 
                   class="btn btn-sm btn-warning">
                    Go to Bank Reconciliation
                </a>
            </div>
        </div>
    </div>
    @endif
</div>

<style>
    .border-left-success {
        border-left: 4px solid #198754;
    }
    .border-left-danger {
        border-left: 4px solid #dc3545;
    }
    .border-left-info {
        border-left: 4px solid #0dcaf0;
    }
</style>
