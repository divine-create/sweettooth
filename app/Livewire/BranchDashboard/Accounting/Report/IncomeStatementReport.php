<?php

namespace App\Livewire\BranchDashboard\Accounting\Report;

use App\Models\AccountingPeriod;
use App\Services\IncomeStatementService;
use App\Services\CurrencyFormattingService;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.app.branch-dashboard')]
class IncomeStatementReport extends Component
{
    protected IncomeStatementService $isService;
    protected CurrencyFormattingService $currencyService;

    public ?int $periodId = null;

    public ?int $comparePeriodId = null;

    public bool $isComparative = false;

    public function boot()
    {
        $this->isService = app(IncomeStatementService::class);
        $this->currencyService = app(CurrencyFormattingService::class);
    }

    public function render()
    {
        $branchId = current_branch_id();
        if ($this->isComparative && $this->comparePeriodId) {
            $data = $this->isService->getComparativeIncomeStatement($this->periodId, $this->comparePeriodId, $branchId);
        } else {
            $data = $this->isService->getIncomeStatement($this->periodId, $branchId);
        }

        return view('livewire.branch-dashboard.accounting.report.income-statement-report', [
            'data' => $data,
            'periods' => AccountingPeriod::orderBy('year', 'desc')->orderBy('month', 'desc')->get(),
            'isComparative' => $this->isComparative,
        ]);
    }

    public function toggleComparative()
    {
        $this->isComparative = ! $this->isComparative;
    }

    public function exportToCsv()
    {
        $data = $this->isService->exportIncomeStatement($this->periodId, current_branch_id());
        $filename = 'income_statement_'.now()->format('Y-m-d_His').'.csv';

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
