<?php

namespace App\Services\Reports;

use Carbon\Carbon;

class TrialBalanceReportService extends ReportService
{
    protected string $reportCategory = 'accounting';
    protected string $reportType = 'trial_balance';

    protected function getReportName(): string
    {
        return 'Trial Balance Report';
    }

    protected function generateReportData(): array
    {
        $this->validateParameters();

        $service = new TrialBalanceService();

        return $service->generate(
            null,
            $this->periodFrom ? Carbon::parse($this->periodFrom) : null,
            $this->periodTo ? Carbon::parse($this->periodTo) : null,
            $this->branchId
        );
    }

    protected function generateSummaryMetrics(array $reportData): array
    {
        return $reportData['summary'] ?? [];
    }

    protected function generateChartsData(array $reportData): array
    {
        return [];
    }
}
