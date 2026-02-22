<div class="container-fluid p-4">
        <div class="row mb-4">
            <div class="col-12">
                <h1 class="h3">Accounting Dashboard</h1>
                <p class="text-muted">Overview of your accounting data</p>
            </div>
        </div>

        <div class="alert alert-info">
            <strong>Accounting Dashboard</strong> - Current Period: {{ $currentPeriod?->name ?? 'No period selected' }}
        </div>

        <div class="row">
            <div class="col-md-3 mb-3">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">Total Entries</h5>
                        <p class="h3">{{ $totalEntries }}</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">Total Debits</h5>
                        <p class="h3">{{ number_format($totalDebits, 2) }}</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">Total Credits</h5>
                        <p class="h3">{{ number_format($totalCredits, 2) }}</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
