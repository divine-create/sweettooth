<?php

namespace App\Http\Controllers;

use App\Models\AccountingPeriod;
use App\Models\CompiledReport;
use App\Models\DepartmentReport;
use App\Services\BalanceSheetService;
use App\Services\CashFlowStatementService;
use App\Services\IncomeStatementService;
use App\Services\SidebarVisibilityService;
use App\Services\TrialBalanceService;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ReportPrintController extends Controller
{
    public function departmentReport(Request $request, string $reportId)
    {
        $user = Auth::guard('web')->user();
        if (! $user) {
            abort(403, 'You do not have permission to view reports.');
        }

        $branchId = $user->branch_id ?? $request->get('b_id') ?? current_branch_id();
        $report = DepartmentReport::query()
            ->with(['department', 'generatedBy', 'reviewedBy', 'annotations.author'])
            ->where('branch_id', $branchId)
            ->findOrFail($reportId);

        $canView = $this->canViewReports($user)
            || ($report->report_category === 'inventory' && SidebarVisibilityService::canSeeInventory($user));

        if (! $canView) {
            abort(403, 'You do not have permission to view reports.');
        }

        $viewData = [
            'report' => $report,
        ];

        if ($request->get('format') === 'pdf') {
            $pdf = Pdf::loadView('prints.reports.department-report', $viewData)->setPaper('a4', 'portrait');
            $filename = sprintf(
                'report-%s-%s-%s.pdf',
                $report->report_category,
                $report->report_type,
                now()->format('Y-m-d')
            );

            return $pdf->download($filename);
        }

        return view('prints.reports.department-report', $viewData);
    }

    public function compiledReport(Request $request, string $reportId)
    {
        $user = Auth::guard('web')->user();
        if (! $user || ! $this->canViewReports($user)) {
            abort(403, 'You do not have permission to view reports.');
        }

        $branchId = $user->branch_id ?? $request->get('b_id') ?? current_branch_id();
        $compiledReport = CompiledReport::query()
            ->with([
                'compiledBy',
                'approvedBy',
                'annotations.author',
                'departmentReports.department',
                'departmentReports.generatedBy',
                'departmentReports.reviewedBy',
                'departmentReports.annotations.author',
            ])
            ->where('branch_id', $branchId)
            ->findOrFail($reportId);

        $viewData = [
            'compiledReport' => $compiledReport,
        ];

        if ($request->get('format') === 'pdf') {
            $pdf = Pdf::loadView('prints.reports.compiled-report', $viewData)->setPaper('a4', 'portrait');
            $filename = sprintf('compiled-report-%s.pdf', now()->format('Y-m-d'));

            return $pdf->download($filename);
        }

        return view('prints.reports.compiled-report', $viewData);
    }

    public function cashFlowStatement(Request $request)
    {
        $user = Auth::guard('web')->user();
        if (! $user || ! SidebarVisibilityService::canSeeAccounting($user)) {
            abort(403, 'You do not have permission to view reports.');
        }

        $branchId = $user->branch_id ?? $request->get('b_id') ?? current_branch_id();
        $startDate = $request->get('startDate') ? Carbon::createFromFormat('Y-m-d', $request->get('startDate')) : null;
        $endDate = $request->get('endDate') ? Carbon::createFromFormat('Y-m-d', $request->get('endDate')) : null;

        $service = app(CashFlowStatementService::class);
        $cfs = $service->getCashFlowStatement($startDate, $endDate, $branchId);
        $bankPositions = $service->getBankPositionsSummary($startDate, $endDate, $branchId);
        $cashPositions = $service->getCashPositionsSummary($startDate, $endDate, $branchId);

        $viewData = [
            'cfs' => $cfs,
            'bankPositions' => $bankPositions,
            'cashPositions' => $cashPositions,
        ];

        if ($request->get('format') === 'pdf') {
            $pdf = Pdf::loadView('prints.accounting.cash-flow-statement', $viewData)->setPaper('a4', 'portrait');
            $filename = sprintf('cash-flow-statement-%s.pdf', now()->format('Y-m-d'));

            return $pdf->download($filename);
        }

        return view('prints.accounting.cash-flow-statement', $viewData);
    }

    public function trialBalance(Request $request)
    {
        $user = Auth::guard('web')->user();
        if (! $user || ! SidebarVisibilityService::canSeeAccounting($user)) {
            abort(403);
        }

        $branchId = $user->branch_id ?? $request->get('b_id') ?? current_branch_id();
        $periodId = $request->integer('period_id') ?: null;
        $period = $periodId ? AccountingPeriod::find($periodId) : AccountingPeriod::current()->where('branch_id', $branchId)->first();

        $data = app(TrialBalanceService::class)->getTrialBalance($periodId, $branchId);

        $viewData = compact('data', 'period', 'branchId');

        if ($request->get('format') === 'pdf') {
            $pdf = Pdf::loadView('prints.accounting.trial-balance', $viewData)->setPaper('a4', 'portrait');
            return $pdf->download('trial-balance-' . now()->format('Y-m-d') . '.pdf');
        }

        return view('prints.accounting.trial-balance', $viewData);
    }

    public function incomeStatement(Request $request)
    {
        $user = Auth::guard('web')->user();
        if (! $user || ! SidebarVisibilityService::canSeeAccounting($user)) {
            abort(403);
        }

        $branchId = $user->branch_id ?? $request->get('b_id') ?? current_branch_id();
        $periodId = $request->integer('period_id') ?: null;
        $period = $periodId ? AccountingPeriod::find($periodId) : AccountingPeriod::current()->where('branch_id', $branchId)->first();

        $data = app(IncomeStatementService::class)->getIncomeStatement($periodId, $branchId);

        $viewData = compact('data', 'period', 'branchId');

        if ($request->get('format') === 'pdf') {
            $pdf = Pdf::loadView('prints.accounting.income-statement', $viewData)->setPaper('a4', 'portrait');
            return $pdf->download('income-statement-' . now()->format('Y-m-d') . '.pdf');
        }

        return view('prints.accounting.income-statement', $viewData);
    }

    public function balanceSheet(Request $request)
    {
        $user = Auth::guard('web')->user();
        if (! $user || ! SidebarVisibilityService::canSeeAccounting($user)) {
            abort(403);
        }

        $branchId = $user->branch_id ?? $request->get('b_id') ?? current_branch_id();
        $periodId = $request->integer('period_id') ?: null;
        $period = $periodId ? AccountingPeriod::find($periodId) : AccountingPeriod::current()->where('branch_id', $branchId)->first();

        $data = app(BalanceSheetService::class)->getBalanceSheet($periodId, $branchId);

        $viewData = compact('data', 'period', 'branchId');

        if ($request->get('format') === 'pdf') {
            $pdf = Pdf::loadView('prints.accounting.balance-sheet', $viewData)->setPaper('a4', 'portrait');
            return $pdf->download('balance-sheet-' . now()->format('Y-m-d') . '.pdf');
        }

        return view('prints.accounting.balance-sheet', $viewData);
    }

    private function canViewReports($user): bool
    {
        $checks = [
            'view-reports',
            'view_reports',
            'view-analytics',
            'view_analytics',
            'view-hr-reports',
            'view_hr_reports',
        ];

        foreach ($checks as $permission) {
            if ($user->can($permission)) {
                return true;
            }
        }

        return false;
    }
}
