<?php

namespace App\Livewire\BranchDashboard\Accounting\Report;

use App\Models\AccountingPeriod;
use App\Services\BalanceSheetService;
use App\Services\CurrencyFormattingService;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.app.branch-dashboard')]
class BalanceSheetReport extends Component
{
    protected BalanceSheetService $bsService;
    protected CurrencyFormattingService $currencyService;

    public ?int $periodId = null;

    public ?int $comparePeriodId = null;

    public bool $isComparative = false;

    public bool $showRatios = false;

    public function boot()
    {
        $this->bsService = app(BalanceSheetService::class);
        $this->currencyService = app(CurrencyFormattingService::class);
    }

    public function render()
    {
        $branchId = current_branch_id();
        if ($this->isComparative && $this->comparePeriodId) {
            $data = $this->bsService->getComparativeBalanceSheet($this->periodId, $this->comparePeriodId, $branchId);
        } else {
            $data = $this->bsService->getBalanceSheet($this->periodId, $branchId);
        }

        $ratios = $this->bsService->getFinancialRatios($this->periodId, $branchId);

        return view('livewire.branch-dashboard.accounting.report.balance-sheet-report', [
            'data' => $data,
            'ratios' => $ratios,
            'periods' => AccountingPeriod::orderBy('year', 'desc')->orderBy('month', 'desc')->get(),
            'isComparative' => $this->isComparative,
            'showRatios' => $this->showRatios,
        ]);
    }

    public function toggleComparative()
    {
        $this->isComparative = ! $this->isComparative;
    }

    public function toggleRatios()
    {
        $this->showRatios = ! $this->showRatios;
    }

    public function exportToCsv()
    {
        $data = $this->bsService->exportBalanceSheet($this->periodId, current_branch_id());
        $filename = 'balance_sheet_'.now()->format('Y-m-d_His').'.csv';

        return response()->streamDownload(function () use ($data) {
            $f = fopen('php://output', 'w');
            foreach ($data as $row) {
                fputcsv($f, is_array($row) ? $row : [$row]);
            }
            fclose($f);
        }, $filename);
    }

    public function formatCurrency($value)
    {
        return $this->currencyService->format($value);
    }
}
