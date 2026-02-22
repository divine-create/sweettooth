<div class="container-fluid p-4">
    <div class="row mb-4">
        <div class="col-12">
            <h1 class="h3">Accounting Reports</h1>
            <p class="text-muted">Select a report to view detailed financial information</p>
        </div>
    </div>

    <div class="row">
        <div class="col-md-6 col-lg-4 mb-3">
            <a href="{{ route('branch-dashboard.accounting.reports.unified') }}" class="card text-decoration-none h-100">
                <div class="card-body">
                    <h5 class="card-title">Unified Reporting</h5>
                    <p class="card-text text-muted">Generate review-ready accounting reports with narratives and tables</p>
                </div>
            </a>
        </div>

        <div class="col-md-6 col-lg-4 mb-3">
            <a href="{{ route('branch-dashboard.accounting.reports.general-ledger') }}" class="card text-decoration-none h-100">
                <div class="card-body">
                    <h5 class="card-title">General Ledger</h5>
                    <p class="card-text text-muted">View all general ledger transactions</p>
                </div>
            </a>
        </div>

        <div class="col-md-6 col-lg-4 mb-3">
            <a href="{{ route('branch-dashboard.accounting.reports.trial-balance') }}" class="card text-decoration-none h-100">
                <div class="card-body">
                    <h5 class="card-title">Trial Balance</h5>
                    <p class="card-text text-muted">Review account balances and verify debits equal credits</p>
                </div>
            </a>
        </div>

        <div class="col-md-6 col-lg-4 mb-3">
            <a href="{{ route('branch-dashboard.accounting.reports.income-statement') }}" class="card text-decoration-none h-100">
                <div class="card-body">
                    <h5 class="card-title">Income Statement</h5>
                    <p class="card-text text-muted">View revenues, expenses, and net income</p>
                </div>
            </a>
        </div>

        <div class="col-md-6 col-lg-4 mb-3">
            <a href="{{ route('branch-dashboard.accounting.reports.balance-sheet') }}" class="card text-decoration-none h-100">
                <div class="card-body">
                    <h5 class="card-title">Balance Sheet</h5>
                    <p class="card-text text-muted">View assets, liabilities, and equity</p>
                </div>
            </a>
        </div>

        <div class="col-md-6 col-lg-4 mb-3">
            <a href="{{ route('branch-dashboard.accounting.reports.cash-flow-statement') }}" class="card text-decoration-none h-100">
                <div class="card-body">
                    <h5 class="card-title">Cash Flow Statement</h5>
                    <p class="card-text text-muted">Analyze cash inflows and outflows</p>
                </div>
            </a>
        </div>
    </div>
</div>

<style>
    .card {
        border: 1px solid #dee2e6;
        transition: all 0.3s ease;
    }

    .card:hover {
        border-color: #0d6efd;
        box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
    }
</style>
