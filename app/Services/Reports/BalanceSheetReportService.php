<?php

namespace App\Services\Reports;

use Carbon\Carbon;

class BalanceSheetReportService extends ReportService
{
    protected string $reportCategory = 'accounting';
    protected string $reportType = 'balance_sheet';

    protected function getReportName(): string
    {
        return 'Balance Sheet Report';
    }

    protected function generateReportData(): array
    {
        $this->validateParameters();

        $service = new BalanceSheetService();

        return $service->generate(
            null,
            $this->periodTo ? Carbon::parse($this->periodTo) : null,
            $this->branchId
        );
    }

    protected function generateSummaryMetrics(array $reportData): array
    {
        return $reportData['totals'] ?? [];
    }

    protected function generateChartsData(array $reportData): array
    {
        return [];
    }
}
