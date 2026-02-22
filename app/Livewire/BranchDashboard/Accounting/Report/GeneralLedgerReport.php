<?php

namespace App\Livewire\BranchDashboard\Accounting\Report;

use App\Models\AccountingPeriod;
use App\Models\GlAccount;
use App\Services\GeneralLedgerService;
use App\Services\CurrencyFormattingService;
use Carbon\Carbon;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.app.branch-dashboard')]
class GeneralLedgerReport extends Component
{
    use WithPagination;

    protected GeneralLedgerService $glService;
    protected CurrencyFormattingService $currencyService;

    public ?string $startDate = null;

    public ?string $endDate = null;

    public ?int $glAccountId = null;

    public ?int $periodId = null;

    public string $sortBy = 'entry_date';

    public string $sortDirection = 'desc';

    public function boot()
    {
        $this->glService = app(GeneralLedgerService::class);
        $this->currencyService = app(CurrencyFormattingService::class);

        // Default to current month
        if (! $this->startDate) {
            $this->startDate = now()->startOfMonth()->format('Y-m-d');
        }
        if (! $this->endDate) {
            $this->endDate = now()->endOfMonth()->format('Y-m-d');
        }
    }

    public function render()
    {
        $entries = $this->glService->getEntriesPaginated(
            perPage: 50,
            startDate: $this->startDate ? Carbon::createFromFormat('Y-m-d', $this->startDate) : null,
            endDate: $this->endDate ? Carbon::createFromFormat('Y-m-d', $this->endDate) : null,
            glAccountId: $this->glAccountId,
            periodId: $this->periodId,
        );

        // Calculate totals
        $totalDebit = $entries->sum('debit');
        $totalCredit = $entries->sum('credit');

        return view('livewire.branch-dashboard.accounting.report.general-ledger-report', [
            'entries' => $entries,
            'glAccounts' => GlAccount::where('is_active', true)->orderBy('account_number')->get(),
            'periods' => AccountingPeriod::orderBy('year', 'desc')->orderBy('month', 'desc')->get(),
            'totalDebit' => $totalDebit,
            'totalCredit' => $totalCredit,
        ]);
    }

    public function resetFilters()
    {
        $this->startDate = now()->startOfMonth()->format('Y-m-d');
        $this->endDate = now()->endOfMonth()->format('Y-m-d');
        $this->glAccountId = null;
        $this->periodId = null;
        $this->resetPage();
    }

    public function exportToCsv()
    {
        $entries = $this->glService->getEntries(
            startDate: $this->startDate ? Carbon::createFromFormat('Y-m-d', $this->startDate) : null,
            endDate: $this->endDate ? Carbon::createFromFormat('Y-m-d', $this->endDate) : null,
            glAccountId: $this->glAccountId,
        );

        $data = $this->glService->exportEntries(
            startDate: $this->startDate ? Carbon::createFromFormat('Y-m-d', $this->startDate) : null,
            endDate: $this->endDate ? Carbon::createFromFormat('Y-m-d', $this->endDate) : null,
            glAccountId: $this->glAccountId,
        );

        $filename = 'general_ledger_'.now()->format('Y-m-d_His').'.csv';

        return response()->streamDownload(function () use ($data) {
            $f = fopen('php://output', 'w');
            fputcsv($f, array_keys($data[0]));
            foreach ($data as $row) {
                fputcsv($f, $row);
            }
            fclose($f);
        }, $filename);
    }

    public function formatCurrency($value)
    {
        return $this->currencyService->format($value);
    }
}
