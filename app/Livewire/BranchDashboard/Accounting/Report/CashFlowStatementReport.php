<?php

namespace App\Livewire\BranchDashboard\Accounting\Report;

use App\Services\CashFlowStatementService;
use App\Services\CurrencyFormattingService;
use Carbon\Carbon;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.app.branch-dashboard')]
class CashFlowStatementReport extends Component
{
    protected CashFlowStatementService $cfsService;
    protected CurrencyFormattingService $currencyService;

    public ?string $startDate = null;

    public ?string $endDate = null;

    public bool $showBankSummary = false;

    public bool $showCashSummary = false;

    public function boot()
    {
        $this->cfsService = app(CashFlowStatementService::class);
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
        $cfs = $this->cfsService->getCashFlowStatement(
            startDate: $this->startDate ? Carbon::createFromFormat('Y-m-d', $this->startDate) : null,
            endDate: $this->endDate ? Carbon::createFromFormat('Y-m-d', $this->endDate) : null,
            branchId: current_branch_id(),
        );

        $bankPositions = $this->cfsService->getBankPositionsSummary(
            startDate: $this->startDate ? Carbon::createFromFormat('Y-m-d', $this->startDate) : null,
            endDate: $this->endDate ? Carbon::createFromFormat('Y-m-d', $this->endDate) : null,
            branchId: current_branch_id(),
        );

        $cashPositions = $this->cfsService->getCashPositionsSummary(
            startDate: $this->startDate ? Carbon::createFromFormat('Y-m-d', $this->startDate) : null,
            endDate: $this->endDate ? Carbon::createFromFormat('Y-m-d', $this->endDate) : null,
            branchId: current_branch_id(),
        );

        return view('livewire.branch-dashboard.accounting.report.cash-flow-statement-report', [
            'cfs' => $cfs,
            'bankPositions' => $bankPositions,
            'cashPositions' => $cashPositions,
            'showBankSummary' => $this->showBankSummary,
            'showCashSummary' => $this->showCashSummary,
        ]);
    }

    public function toggleBankSummary()
    {
        $this->showBankSummary = ! $this->showBankSummary;
    }

    public function toggleCashSummary()
    {
        $this->showCashSummary = ! $this->showCashSummary;
    }

    public function resetFilters()
    {
        $this->startDate = now()->startOfMonth()->format('Y-m-d');
        $this->endDate = now()->endOfMonth()->format('Y-m-d');
    }

    public function exportToCsv()
    {
        $data = $this->cfsService->exportCashFlowStatement(
            startDate: $this->startDate ? Carbon::createFromFormat('Y-m-d', $this->startDate) : null,
            endDate: $this->endDate ? Carbon::createFromFormat('Y-m-d', $this->endDate) : null,
            branchId: current_branch_id(),
        );

        $filename = 'cash_flow_statement_'.now()->format('Y-m-d_His').'.csv';

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
