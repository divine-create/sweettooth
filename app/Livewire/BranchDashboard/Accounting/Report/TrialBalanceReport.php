<?php

namespace App\Livewire\BranchDashboard\Accounting\Report;

use App\Models\AccountingPeriod;
use App\Services\TrialBalanceService;
use App\Services\CurrencyFormattingService;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.app.branch-dashboard')]
class TrialBalanceReport extends Component
{
    protected TrialBalanceService $tbService;
    protected CurrencyFormattingService $currencyService;

    public ?int $periodId = null;

    public ?int $comparePeriodId = null;

    public bool $isComparative = false;

    public function boot()
    {
        $this->tbService = app(TrialBalanceService::class);
        $this->currencyService = app(CurrencyFormattingService::class);
    }

    public function render()
    {
        $branchId = current_branch_id();

        if ($this->isComparative && $this->comparePeriodId) {
            $data = $this->tbService->getComparativeTrialBalance($this->periodId, $this->comparePeriodId, $branchId);
        } else {
            $data = $this->tbService->getTrialBalance($this->periodId, $branchId);
        }

        $balancingReport = $this->tbService->getBalancingReport($this->periodId, $branchId);

        return view('livewire.branch-dashboard.accounting.report.trial-balance-report', [
            'data' => $data,
            'balancingReport' => $balancingReport,
            'periods' => AccountingPeriod::where('branch_id', $branchId)->orderBy('year', 'desc')->orderBy('month', 'desc')->get(),
            'isComparative' => $this->isComparative,
        ]);
    }

    public function toggleComparative()
    {
        $this->isComparative = ! $this->isComparative;
    }

    public function exportToCsv()
    {
        $data = $this->tbService->exportTrialBalance($this->periodId, current_branch_id());
        $filename = 'trial_balance_'.now()->format('Y-m-d_His').'.csv';

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
